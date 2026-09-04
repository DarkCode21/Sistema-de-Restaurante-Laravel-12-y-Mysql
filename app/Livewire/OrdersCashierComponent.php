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
    public array $knownReadyOrderIds = [];

    public $subtotal = 0;
    public $tax = 0;
    public $tip = 0;
    public $manual_discount = 0;
    public $manual_discount_reason = '';

    public function mount()
    {
        $setting = Setting::first();
        $this->printer_name = $setting->printer_name;
        $this->direct_printing = $setting->direct_printing;
        $this->paymentMethods = PaymentMethod::all();
        $this->boxes = CashRegister::query()
            ->with(['terminal', 'opener'])
            ->where('status', 'open')
            ->where('opened_by', auth()->id())
            ->get();
        $this->boxId = $this->boxes->count() === 1 ? $this->boxes->first()->id : null;
        $this->quickCheckout = request()->boolean('quick_checkout');
        $this->knownReadyOrderIds = $this->readyOrders()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $orderId = request()->integer('order');
        if (!$this->quickCheckout || !$orderId) {
            return;
        }

        $order = Order::with(['details', 'sale'])->find($orderId);
        if ($order?->is_ready_for_checkout && !$order->sale) {
            $this->openFullPayment($order->id);
        }
    }

    public function refreshReadyOrderAlerts(): void
    {
        $readyOrders = $this->readyOrders();
        $newReadyOrders = $readyOrders->filter(
            fn (Order $order) => !in_array($order->id, $this->knownReadyOrderIds, true)
        );

        if ($newReadyOrders->isNotEmpty()) {
            $this->dispatch(
                'order-ready-for-checkout',
                orderId: $newReadyOrders->pluck('id')->all(),
                tableName: $newReadyOrders->pluck('service_label')->join(', '),
            );
        }

        $this->knownReadyOrderIds = $readyOrders->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function readyOrders()
    {
        return Order::query()
            ->with('table')
            ->where('status', 'abierto')
            ->doesntHave('sale')
            ->whereHas('details', fn ($query) => $query->where('cooking_status', '!=', 'cancelled'))
            ->whereDoesntHave('details', fn ($query) => $query
                ->where('requires_kitchen', true)
                ->where('cooking_status', '!=', 'served')
                ->where('cooking_status', '!=', 'cancelled'))
            ->get();
    }

    public function getPaidProperty()
    {
        return collect($this->payments)->sum(fn($p) => (float) ($p['amount'] ?? 0));
    }

    public function getChangeProperty()
    {
        $totalPagadoGeneral = (float)$this->paid;
        $montoADeber = (float)$this->paymentAmount;

        $cashMethodIds = collect($this->paymentMethods)
            ->where('is_efectivo', true)
            ->pluck('id');
        $hasCashPayment = collect($this->payments)
            ->contains(fn ($payment) => $cashMethodIds->contains((int) ($payment['method_id'] ?? 0)));

        if ($hasCashPayment && $totalPagadoGeneral > $montoADeber) {
            return $totalPagadoGeneral - $montoADeber;
        }

        return 0;
    }

    public function getTotalProperty()
    {
        return (float) $this->subtotal - $this->manualDiscount + (float) $this->tax - $this->manualDiscountTax + (float) $this->tip;
    }

    public function getManualDiscountProperty(): float
    {
        return min(max((float) $this->manual_discount, 0), (float) $this->subtotal);
    }

    public function getManualDiscountTaxProperty(): float
    {
        if ((float) $this->subtotal <= 0) {
            return 0;
        }

        return round((float) $this->tax * ($this->manualDiscount / (float) $this->subtotal), 2);
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

    public function updatedManualDiscount()
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
            ->doesntHave('sale')
            ->find($orderId);

        if (!$order || $selectedDetailIds === []) {
            return;
        }

        if (!$order->isReadyForCheckout()) {
            $this->dispatch('swal', [
                'title' => 'Pedido en cocina',
                'text' => 'Solo se puede cobrar después de entregar los platos de cocina.',
                'icon' => 'warning',
            ]);
            return;
        }

        $details = $order->details()
            ->whereNull('parent_detail_id')
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
        $order = Order::with(['table', 'sale'])
            ->where('status', 'abierto')
            ->find($order_id);

        if (!$order) {
            return;
        }

        if ($order->sale) {
            $this->dispatch('swal', [
                'title' => 'Pedido pagado',
                'text' => 'Este pedido ya fue cobrado y solo queda entregarlo.',
                'icon' => 'warning',
            ]);
            return;
        }

        $details = $order->details()
            ->whereNull('parent_detail_id')
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
        $this->manual_discount = 0;
        $this->manual_discount_reason = '';
        $this->paymentAmount = $this->total;
        $this->showPaymentModal = true;
    }

    private function detailCost(OrderDetail $detail): ?float
    {
        $cost = 0.0;
        $items = $detail->components->isNotEmpty() ? $detail->components : collect([$detail]);

        foreach ($items as $item) {
            if ($item->ingredientUsages->isNotEmpty()) {
                foreach ($item->ingredientUsages as $usage) {
                    $unitCost = $usage->unit_cost ?? $usage->ingredient?->unit_cost;
                    if ($unitCost === null) {
                        return null;
                    }

                    $cost += (float) $usage->quantity * (float) $unitCost;
                }

                continue;
            }

            if ($item->product?->recipeIngredients->isNotEmpty() || $item->product?->cost === null) {
                return null;
            }

            $cost += (float) $item->quantity * (float) $item->product->cost;
        }

        return round($cost, 2);
    }

    public function processPayment(): void
    {
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

        if (!is_numeric($this->manual_discount) || (float) $this->manual_discount < 0 || (float) $this->manual_discount > (float) $this->subtotal) {
            $this->dispatch('swal', [
                'title' => 'Descuento no válido',
                'text' => 'El descuento manual no puede superar el subtotal.',
                'icon' => 'error',
            ]);
            return;
        }

        $manualDiscount = round((float) $this->manual_discount, 2);
        $manualDiscountReason = trim((string) $this->manual_discount_reason);

        if ($manualDiscount > 0 && $manualDiscountReason === '') {
            $this->dispatch('swal', [
                'title' => 'Falta el motivo',
                'text' => 'Indica el motivo del descuento manual.',
                'icon' => 'warning',
            ]);
            return;
        }

        if (mb_strlen($manualDiscountReason) > 255) {
            $this->dispatch('swal', [
                'title' => 'Motivo muy largo',
                'text' => 'El motivo del descuento no puede superar 255 caracteres.',
                'icon' => 'warning',
            ]);
            return;
        }

        $orderId = $this->order->id;

        try {
            $sale = DB::transaction(function () use ($detailIds, $orderId, $paymentRows, $manualDiscount, $manualDiscountReason) {
                $order = Order::query()
                    ->with('table')
                    ->whereKey($orderId)
                    ->where('status', 'abierto')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \RuntimeException('La orden ya no está disponible.');
                }

                if ($order->sale()->exists()) {
                    throw new \RuntimeException('La orden ya fue cobrada.');
                }

                $payingInAdvance = !$order->isReadyForCheckout();

                $details = OrderDetail::query()
                    ->with([
                        'product.recipeIngredients',
                        'ingredientUsages.ingredient',
                        'components.product.recipeIngredients',
                        'components.ingredientUsages.ingredient',
                    ])
                    ->where('order_id', $order->id)
                    ->whereNull('parent_detail_id')
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

                if (!$this->boxId) {
                    throw new \RuntimeException('Abre o selecciona tu turno de caja antes de cobrar.');
                }

                $cashRegister = CashRegister::query()
                    ->whereKey($this->boxId)
                    ->where('status', 'open')
                    ->where('opened_by', auth()->id())
                    ->lockForUpdate()
                    ->first();

                if (!$cashRegister) {
                    throw new \RuntimeException('El turno seleccionado no está disponible para este usuario.');
                }

                $rawSubtotal = (float) $details->sum('subtotal');
                $rawTax = (float) $details->sum('tax');

                if ($manualDiscount > $rawSubtotal) {
                    throw new \RuntimeException('El descuento manual supera el subtotal disponible.');
                }

                $manualDiscountTax = $rawSubtotal > 0
                    ? round($rawTax * ($manualDiscount / $rawSubtotal), 2)
                    : 0;
                $subtotal = round($rawSubtotal - $manualDiscount, 2);
                $tax = round($rawTax - $manualDiscountTax, 2);
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
                    'customer_name' => $order->customer_name ?: 'Consumidor Final',
                    'cash_register_id' => $cashRegister->id,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'manual_discount' => $manualDiscount,
                    'manual_discount_reason' => $manualDiscount > 0 ? $manualDiscountReason : null,
                    'manual_discount_by' => $manualDiscount > 0 ? auth()->id() : null,
                    'tip' => $tip,
                    'total' => $total,
                    'paid_amount' => $paid,
                    'change' => $change,
                    'paid_at' => now(),
                ]);

                foreach ($details as $detail) {
                    $costTotal = $this->detailCost($detail);
                    $netRevenue = $rawSubtotal > 0
                        ? round((float) $detail->subtotal * ($subtotal / $rawSubtotal), 2)
                        : 0;

                    $sale->details()->create([
                        'product_id' => $detail->product_id,
                        'product_name' => $detail->product?->name,
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'unit_cost' => $costTotal === null || (float) $detail->quantity === 0
                            ? null
                            : round($costTotal / (float) $detail->quantity, 4),
                        'cost_total' => $costTotal,
                        'gross_profit' => $costTotal === null ? null : round($netRevenue - $costTotal, 2),
                        'discount' => (float) $detail->discount,
                        'tax_rate' => (float) $detail->tax_rate,
                        'tax' => $detail->tax,
                        'promotion_id' => $detail->promotion_id,
                        'subtotal' => $detail->subtotal,
                        'notes' => $detail->notes,
                        'selected_options' => $detail->selected_options,
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
                        'received_amount' => $method->is_efectivo ? $payment['amount'] : null,
                        'returned_amount' => $method->is_efectivo ? $returnedAmount : null,
                        'reference' => $payment['reference'],
                    ]);
                }

                if ($remainingChange > 0) {
                    throw new \RuntimeException('El vuelto debe descontarse de un pago en efectivo.');
                }

                if ($payingInAdvance) {
                    $order->update(['amount_pending' => 0]);
                } else {
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
                }

                if ($cashAmount > 0 && $cashRegister) {
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
                'manual_discount',
                'manual_discount_reason',
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
            'sale',
            'details' => function ($q) {
                $q->whereNull('parent_detail_id')
                    ->where('cooking_status', '!=', 'cancelled')
                    ->with(['product', 'components']);
            },
            'user'
        ])
        ->where('status', 'abierto')
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        ->when($this->search, function ($query) {
            $query->where(function ($query) {
                $query->where('customer_name', 'like', '%' . $this->search . '%')
                    ->orWhere('delivery_address', 'like', '%' . $this->search . '%')
                    ->orWhereHas('table', fn ($table) => $table->where('name', 'like', '%' . $this->search . '%'));
            });
        })
        ->orderBy('created_at', 'desc')
        ->paginate(12);

        return view('livewire.orders-cashier-component', [
            'orders' => $orders
        ]);
    }
}
