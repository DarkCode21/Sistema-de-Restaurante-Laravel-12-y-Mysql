<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersCashierComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $detailsToPay = [];
    public $paymentMethods = [];
    public $selectedMethod = null;
    public array $selectedDetails = [];
    public $showPaymentModal = false;
    public $paymentAmount = 0;
    public $payments = [];
    public $boxes = [];
    public $boxId;
    public $order;
    public $printer_name;
    public $direct_printing;
    public bool $quickCheckout = false;

    public $subtotal = 0;
    public $tax = 0;
    public $tip = 0;

    public function mount()
    {
        $setting = Setting::first();
        $this->printer_name = $setting->printer_name;
        $this->direct_printing = $setting->direct_printing;
        $this->paymentMethods = PaymentMethod::all();
        $this->boxes = CashRegister::where('status', 'open')->get();
        $this->quickCheckout = request()->boolean('quick_checkout');

        $orderId = request()->integer('order');
        if (!$this->quickCheckout || !$orderId) {
            return;
        }

        $order = Order::with('details')->find($orderId);
        if ($order?->is_ready_for_checkout) {
            $this->openFullPayment($order->id);
        }
    }

    public function getPaidProperty()
    {
        return collect($this->payments)->sum(fn($p) => (float) ($p['amount'] ?? 0));
    }

    public function getChangeProperty()
    {
        $totalPagadoGeneral = (float)$this->paid;
        $montoADeber = (float)$this->paymentAmount;

        if ($totalPagadoGeneral > $montoADeber) {
            return $totalPagadoGeneral - $montoADeber;
        }

        return 0;
    }

    public function getTotalProperty()
    {
        return (float)$this->subtotal + (float)$this->tax + (float)$this->tip;
    }

    private function calculateTotals($details)
    {
        $this->subtotal = $details->sum('subtotal');
        $this->tax = $details->sum('tax');
        $this->paymentAmount = $this->subtotal + $this->tax + $this->tip;
    }

    public function updatedTip()
    {
        $this->paymentAmount = $this->total;
    }

    public function addPaymentFromSelect()
    {
        if (!$this->selectedMethod) return;

        $exists = collect($this->payments)->pluck('method_id')->contains($this->selectedMethod);
        if ($exists) return;

        $this->payments[] = [
            'method_id' => (int) $this->selectedMethod,
            'amount' => 0,
            'reference' => ''
        ];

        $this->selectedMethod = null;
    }

    public function removePaymentRow($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
    }

    public function openSplitPayment($orderId): void
    {
        $selectedDetailIds = collect($this->selectedDetails[$orderId] ?? [])
            ->filter()
            ->keys()
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $order = Order::query()
            ->with('table')
            ->where('status', 'abierto')
            ->find($orderId);

        if (!$order || $selectedDetailIds === []) {
            return;
        }

        $details = $order->details()
            ->whereIn('id', $selectedDetailIds)
            ->where('cooking_status', '!=', 'cancelled')
            ->get();

        if ($details->count() !== count($selectedDetailIds)) {
            $this->dispatch('swal', [
                'title' => 'Selección no válida',
                'text' => 'Solo se pueden cobrar productos de la misma mesa.',
                'icon' => 'warning',
            ]);
            return;
        }

        $this->order = $order;
        $this->detailsToPay = $details->pluck('id')->all();
        $this->calculateTotals($details);

        $this->resetPaymentFields();
    }

    public function openFullPayment($order_id)
    {
        $order = Order::with('table')
            ->where('status', 'abierto')
            ->find($order_id);

        if (!$order) {
            return;
        }

        $details = $order->details()
            ->where('cooking_status', '!=', 'cancelled')
            ->get();

        if ($details->isEmpty()) {
            $this->dispatch('swal', [
                'title' => 'Sin productos',
                'text' => 'La orden no tiene productos pendientes por cobrar.',
                'icon' => 'warning',
            ]);
            return;
        }

        $this->order = $order;
        $this->detailsToPay = $details->pluck('id')->all();
        $this->calculateTotals($details);

        $this->resetPaymentFields();
    }

    private function resetPaymentFields()
    {
        $this->payments = [];
        $this->selectedMethod = null;
        $this->tip = 0;
        $this->paymentAmount = $this->subtotal + $this->tax;
        $this->showPaymentModal = true;
    }

    public function processPayment(): void
    {
        if (!$this->boxId) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'Debe seleccionar una caja para procesar el pago',
                'icon'  => 'error'
            ]);
            return;
        }

        if (!$this->order) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'Debe seleccionar una orden para cobrar',
                'icon'  => 'error'
            ]);
            return;
        }

        $paymentRows = collect($this->payments)
            ->filter(fn ($payment) => is_array($payment)
                && is_numeric($payment['method_id'] ?? null)
                && is_numeric($payment['amount'] ?? null)
                && (float) $payment['amount'] > 0
                && (!isset($payment['reference']) || (is_string($payment['reference']) && mb_strlen($payment['reference']) <= 255)))
            ->map(fn ($payment) => [
                'method_id' => (int) $payment['method_id'],
                'amount' => (float) $payment['amount'],
                'reference' => trim($payment['reference'] ?? '') ?: null,
            ])
            ->values();

        if ($paymentRows->isEmpty() || $paymentRows->count() !== count($this->payments)) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'Cada método de pago debe tener un monto válido.',
                'icon'  => 'error'
            ]);
            return;
        }

        if ($paymentRows->pluck('method_id')->unique()->count() !== $paymentRows->count()) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'No se puede repetir un método de pago.',
                'icon'  => 'error'
            ]);
            return;
        }

        $detailIds = collect($this->detailsToPay)
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($detailIds === []) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'No hay productos para cobrar',
                'icon'  => 'error'
            ]);
            return;
        }

        if (!is_numeric($this->tip) || (float) $this->tip < 0) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'La propina debe ser un monto válido.',
                'icon'  => 'error'
            ]);
            return;
        }

        $orderId = $this->order->id;

        try {
            $sale = DB::transaction(function () use ($detailIds, $orderId, $paymentRows) {
                $order = Order::query()
                    ->with('table')
                    ->whereKey($orderId)
                    ->where('status', 'abierto')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \RuntimeException('La orden ya no está disponible.');
                }

                if ($this->quickCheckout && !$order->isReadyForCheckout()) {
                    throw new \RuntimeException('La orden aún no está lista para cobro rápido.');
                }

                $cashRegister = CashRegister::query()
                    ->whereKey($this->boxId)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$cashRegister) {
                    throw new \RuntimeException('La caja ya no está disponible.');
                }

                $details = OrderDetail::query()
                    ->where('order_id', $order->id)
                    ->whereIn('id', $detailIds)
                    ->where('cooking_status', '!=', 'cancelled')
                    ->lockForUpdate()
                    ->get();

                if ($details->count() !== count($detailIds)) {
                    throw new \RuntimeException('Los productos seleccionados ya no pertenecen a esta orden.');
                }

                $methodIds = $paymentRows->pluck('method_id')->unique();
                $methods = PaymentMethod::query()
                    ->whereIn('id', $methodIds)
                    ->get()
                    ->keyBy('id');

                if ($methods->count() !== $methodIds->count()) {
                    throw new \RuntimeException('Uno de los métodos de pago no está disponible.');
                }

                $subtotal = (float) $details->sum('subtotal');
                $tax = (float) $details->sum('tax');
                $tip = (float) $this->tip;
                $total = $subtotal + $tax + $tip;
                $paid = (float) $paymentRows->sum('amount');

                if ($paid < $total) {
                    throw new \RuntimeException('El monto pagado es insuficiente.');
                }

                $change = $paid - $total;
                $remainingChange = $change;
                $cashAmount = 0;

                $sale = Sale::create([
                    'order_id' => $order->id,
                    'cash_register_id' => $cashRegister->id,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'tip' => $tip,
                    'total' => $total,
                    'paid_amount' => $paid,
                    'change' => $change,
                    'paid_at' => now(),
                ]);

                foreach ($details as $detail) {
                    $sale->details()->create([
                        'product_id' => $detail->product_id,
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'subtotal' => $detail->subtotal,
                        'tax' => $detail->tax,
                        'notes' => $detail->notes,
                    ]);
                }

                foreach ($paymentRows as $payment) {
                    $method = $methods->get($payment['method_id']);
                    $returnedAmount = 0;
                    $realAmount = $payment['amount'];

                    if ($method->is_efectivo && $remainingChange > 0) {
                        $returnedAmount = min($payment['amount'], $remainingChange);
                        $realAmount -= $returnedAmount;
                        $remainingChange -= $returnedAmount;
                    }

                    if ($method->is_efectivo) {
                        $cashAmount += $realAmount;
                    }

                    $sale->payments()->create([
                        'payment_method_id' => $method->id,
                        'amount' => $realAmount,
                        'received_amount' => $payment['amount'],
                        'returned_amount' => $returnedAmount,
                        'reference' => $payment['reference'],
                    ]);
                }

                if ($remainingChange > 0) {
                    throw new \RuntimeException('El vuelto debe descontarse de un pago en efectivo.');
                }

                OrderDetail::whereKey($details->pluck('id'))->delete();

                $remainingDetails = $order->details()
                    ->where('cooking_status', '!=', 'cancelled')
                    ->get();

                if ($remainingDetails->isEmpty()) {
                    $order->update([
                        'status' => 'cerrado',
                        'amount_pending' => 0,
                    ]);
                    $order->table?->update(['status' => 'libre']);
                } else {
                    $order->update([
                        'amount_pending' => $remainingDetails->sum('subtotal') + $remainingDetails->sum('tax'),
                    ]);
                }

                if ($cashAmount > 0) {
                    $cashRegister->increment('current_amount', $cashAmount);
                }

                return $sale;
            });

            $this->showPaymentModal = false;
            $this->selectedDetails = [];
            $this->order = null;

            $message = 'Pago procesado exitosamente.';
            if ($sale->change > 0) {
                $message = 'Entregar vuelto: ' . number_format($sale->change, 2);
            }

            $this->dispatch('swal', [
                'title' => 'Venta Exitosa',
                'text'  => $message,
                'icon'  => 'success'
            ]);

            $url = route('sales.receipt', ['id' => $sale->id]);

            if ($this->direct_printing) {
                $url = URL::temporarySignedRoute(
                    'sales.print-local',
                    now()->addMinutes(5),
                    ['id' => $sale->id],
                );
            }

            $this->dispatch('print-receipt', [
                'url' => $url,
                'printer_name' => $this->printer_name
            ]);
            $this->reset([
                'boxId',
                'tip',
                'payments',
                'selectedMethod',
                'detailsToPay',
                'paymentAmount',
                'subtotal',
                'tax',
            ]);

        } catch (\RuntimeException $e) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => $e->getMessage(),
                'icon'  => 'error'
            ]);
        } catch (\Throwable $e) {

            Log::error('PAYMENT ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'No se pudo procesar el pago',
                'icon'  => 'error'
            ]);
        }
    }

    public function render()
    {
        $orders = Order::with([
            'table',
            'details' => function ($q) {
                $q->where('cooking_status', '!=', 'cancelled')
                    ->with('product');
            },
            'user'
        ])
        ->where('status', 'abierto')
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        ->whereHas('table', function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(12);

        return view('livewire.orders-cashier-component', [
            'orders' => $orders
        ]);
    }
}
