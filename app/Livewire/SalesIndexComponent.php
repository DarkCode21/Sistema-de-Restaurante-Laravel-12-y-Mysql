<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use Livewire\WithPagination;
use Carbon\Carbon;

class SalesIndexComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $fromDate;
    public $toDate;

    public $totalSales = 0;
    public $totalTips = 0;
    public $selectedSaleId = null;

    public function mount()
    {
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }
    public function updatingFromDate() { $this->resetPage(); }
    public function updatingToDate() { $this->resetPage(); }

    public function printTicket($saleId)
    {
        $this->dispatch('print-ticket', saleId: $saleId);
    }

    public function viewSale(int $saleId): void
    {
        $this->selectedSaleId = $saleId;
    }

    public function closeSaleDetails(): void
    {
        $this->selectedSaleId = null;
    }

    public function render()
    {
        $baseQuery = Sale::with(['order.table', 'details.product', 'order.user', 'payments.method', 'cashRegister'])
            ->when($this->fromDate, fn($q) => $q->whereDate('paid_at', '>=', $this->fromDate))
            ->when($this->toDate, fn($q) => $q->whereDate('paid_at', '<=', $this->toDate))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('customer_name', 'like', '%' . $this->search . '%')
                        ->orWhere('sales.id', $this->search)
                        ->orWhereHas('order', fn ($order) => $order
                            ->where('customer_name', 'like', '%' . $this->search . '%')
                            ->orWhereHas('table', fn ($table) => $table->where('name', 'like', '%' . $this->search . '%')));
                });
            });

        $this->totalSales = (clone $baseQuery)->sum('total');
        $this->totalTips  = (clone $baseQuery)->sum('tip');

        $paymentRows = (clone $baseQuery)
            ->join('payments', 'payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->selectRaw('payment_methods.name, payment_methods.is_efectivo, SUM(payments.amount) as total')
            ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.is_efectivo')
            ->get();
        $paymentTotals = ['cash' => 0.0, 'yape' => 0.0, 'card' => 0.0];

        foreach ($paymentRows as $payment) {
            if ($payment->is_efectivo) {
                $paymentTotals['cash'] += (float) $payment->total;
            } elseif (str_contains(strtolower($payment->name), 'yape')) {
                $paymentTotals['yape'] += (float) $payment->total;
            } elseif (str_contains(strtolower($payment->name), 'tarjeta')) {
                $paymentTotals['card'] += (float) $payment->total;
            }
        }

        $sales = (clone $baseQuery)
            ->orderByDesc('paid_at')
            ->paginate(12);

        $selectedSale = $this->selectedSaleId
            ? Sale::with(['order.table', 'order.user', 'details.product', 'payments.method', 'cashRegister'])->find($this->selectedSaleId)
            : null;

        return view('livewire.sales-index-component', compact('sales', 'selectedSale', 'paymentTotals'));
    }
}
