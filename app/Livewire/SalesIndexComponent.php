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

    public function render()
    {
        $baseQuery = Sale::with(['order.table', 'details.product', 'order.user'])
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->fromDate, fn($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->whereHas('order.table', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });

        $this->totalSales = (clone $baseQuery)->sum('total');
        $this->totalTips  = (clone $baseQuery)->sum('tip');

        $sales = (clone $baseQuery)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('livewire.sales-index-component', [
            'sales' => $sales
        ]);
    }
}