<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\PaymentEdit;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\TipAdjustment;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Carbon\Carbon;

class SalesIndexComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $fromDate;
    public $toDate;
    public string $waiter = '';
    public string $customer = '';

    public $totalSales = 0;
    public $totalTips = 0;
    public $selectedSaleId = null;
    public bool $tipsEnabled = true;
    public bool $showTipAdjustment = false;
    public string $tipAdjustmentSaleId = '';
    public string $tipAdjustmentAmount = '';
    public string $tipAdjustmentReason = '';
    public string $tipAdjustmentReference = '';
    public string $tipAdjustmentPaymentMethodId = '';
    public string $tipAdjustmentCashRegisterId = '';
    public bool $showPaymentEditor = false;
    public string $paymentEditSaleId = '';
    public array $paymentEdits = [];
    public string $paymentEditReason = '';

    public function mount()
    {
        $this->tipsEnabled = (bool) (Setting::first()?->tips_enabled ?? true);
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }
    public function updatingFromDate() { $this->resetPage(); }
    public function updatingToDate() { $this->resetPage(); }
    public function updatingWaiter() { $this->resetPage(); }
    public function updatingCustomer() { $this->resetPage(); }

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
        $this->closeTipAdjustment();
        $this->closePaymentEditor();
    }

    public function openPaymentEditor(int $saleId): void
    {
        abort_unless(auth()->user()?->can('ordenes.cobrar'), 403);

        $sale = Sale::query()->with(['payments.method', 'cashRegister'])->find($saleId);

        if (!$sale || !$sale->cashRegister || $sale->cashRegister->status !== 'open'
            || ($sale->cashRegister->opened_by !== auth()->id() && !auth()->user()?->hasRole('admin'))
            || $sale->payments->contains(fn (Payment $payment) => (float) $payment->returned_amount > 0)) {
            $this->dispatch('swal', [
                'title' => 'Pago no editable',
                'text' => 'Solo el cajero de un turno abierto puede corregir pagos sin vuelto.',
                'icon' => 'warning',
            ]);
            return;
        }

        $this->resetValidation();
        $this->paymentEditSaleId = (string) $sale->id;
        $this->paymentEdits = $sale->payments->map(fn (Payment $payment) => [
            'id' => $payment->id,
            'payment_method_id' => (string) $payment->payment_method_id,
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'reference' => $payment->reference ?? '',
        ])->all();
        $this->paymentEditReason = '';
        $this->showPaymentEditor = true;
    }

    public function closePaymentEditor(): void
    {
        $this->showPaymentEditor = false;
        $this->reset(['paymentEditSaleId', 'paymentEdits', 'paymentEditReason']);
        $this->resetValidation();
    }

    public function savePaymentEdits(): void
    {
        abort_unless(auth()->user()?->can('ordenes.cobrar'), 403);

        $this->validate([
            'paymentEditSaleId' => ['required', 'integer', 'exists:sales,id'],
            'paymentEdits' => ['required', 'array', 'min:1'],
            'paymentEdits.*.id' => ['required', 'integer', 'distinct'],
            'paymentEdits.*.payment_method_id' => ['required', 'integer', 'distinct', 'exists:payment_methods,id'],
            'paymentEdits.*.amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/', 'gt:0'],
            'paymentEdits.*.reference' => ['nullable', 'string', 'max:255'],
            'paymentEditReason' => ['required', 'string', 'max:255'],
        ]);

        $rows = collect($this->paymentEdits)->map(fn ($payment) => [
            'id' => (int) $payment['id'],
            'payment_method_id' => (int) $payment['payment_method_id'],
            'amount' => round((float) $payment['amount'], 2),
            'reference' => trim((string) ($payment['reference'] ?? '')) ?: null,
        ])->values();

        try {
            $changed = DB::transaction(function () use ($rows): bool {
                $sale = Sale::query()
                    ->with(['payments.method', 'cashRegister'])
                    ->whereKey($this->paymentEditSaleId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $cashRegister = CashRegister::query()
                    ->whereKey($sale->cash_register_id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$cashRegister || ($cashRegister->opened_by !== auth()->id() && !auth()->user()?->hasRole('admin'))) {
                    throw new \RuntimeException('La caja de esta venta ya no está disponible para correcciones.');
                }

                $payments = Payment::query()->with('method')->where('sale_id', $sale->id)->lockForUpdate()->get()->keyBy('id');
                if ($payments->count() !== $rows->count() || $rows->pluck('id')->diff($payments->keys())->isNotEmpty()) {
                    throw new \RuntimeException('Los pagos de esta venta cambiaron. Vuelve a abrir el detalle.');
                }

                if ($payments->contains(fn (Payment $payment) => !$payment->method || (float) $payment->returned_amount > 0)) {
                    throw new \RuntimeException('No se pueden corregir pagos históricos ni pagos con vuelto.');
                }

                if (round((float) $rows->sum('amount') - (float) $sale->total, 2) !== 0.0) {
                    throw new \RuntimeException('La suma de pagos debe mantenerse en ' . number_format($sale->total, 2) . '.');
                }

                $methods = PaymentMethod::query()->whereIn('id', $rows->pluck('payment_method_id'))->get()->keyBy('id');
                if ($methods->count() !== $rows->pluck('payment_method_id')->unique()->count()) {
                    throw new \RuntimeException('Uno de los métodos de pago ya no está disponible.');
                }

                $oldCash = $payments->filter(fn (Payment $payment) => $payment->method->is_efectivo)->sum('amount');
                $newCash = $rows->filter(fn ($row) => $methods[$row['payment_method_id']]->is_efectivo)->sum('amount');
                $newCashBalance = round((float) $cashRegister->current_amount + (float) $newCash - (float) $oldCash, 2);

                if ($newCashBalance < 0) {
                    throw new \RuntimeException('La corrección supera el efectivo disponible en caja.');
                }

                $changed = false;
                foreach ($rows as $row) {
                    $payment = $payments[$row['id']];
                    if ((int) $payment->payment_method_id === $row['payment_method_id']
                        && (float) $payment->amount === $row['amount']
                        && $payment->reference === $row['reference']) {
                        continue;
                    }

                    $method = $methods[$row['payment_method_id']];
                    $edit = new PaymentEdit([
                        'sale_id' => $sale->id,
                        'payment_id' => $payment->id,
                        'edited_by' => auth()->id(),
                        'previous_payment_method_id' => $payment->payment_method_id,
                        'new_payment_method_id' => $method->id,
                        'previous_amount' => $payment->amount,
                        'new_amount' => $row['amount'],
                        'previous_reference' => $payment->reference,
                        'new_reference' => $row['reference'],
                        'reason' => trim($this->paymentEditReason),
                    ]);
                    $edit->branch_id = $sale->branch_id;
                    $edit->save();
                    $payment->update([
                        'payment_method_id' => $method->id,
                        'amount' => $row['amount'],
                        'received_amount' => $method->is_efectivo ? $row['amount'] : null,
                        'returned_amount' => $method->is_efectivo ? 0 : null,
                        'reference' => $row['reference'],
                    ]);
                    $changed = true;
                }

                if ($changed) {
                    $cashRegister->update(['current_amount' => $newCashBalance]);
                }

                return $changed;
            });
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', ['title' => 'No se pudo corregir el pago', 'text' => $e->getMessage(), 'icon' => 'error']);
            return;
        }

        if (!$changed) {
            $this->dispatch('swal', ['title' => 'Sin cambios', 'text' => 'No se modificó ningún pago.', 'icon' => 'info']);
            return;
        }

        $this->closePaymentEditor();
        $this->dispatch('swal', ['title' => 'Pago corregido', 'text' => 'La corrección quedó registrada en auditoría.', 'icon' => 'success']);
    }

    public function openTipAdjustment(int $saleId): void
    {
        abort_unless(auth()->user()?->can('empresa.editar'), 403);

        if (!$this->tipsEnabled || !Sale::query()->whereKey($saleId)->exists()) {
            return;
        }

        $this->resetValidation();
        $this->tipAdjustmentSaleId = (string) $saleId;
        $this->tipAdjustmentAmount = '';
        $this->tipAdjustmentReason = '';
        $this->tipAdjustmentReference = '';
        $this->tipAdjustmentPaymentMethodId = (string) PaymentMethod::query()->orderBy('name')->value('id');
        $this->tipAdjustmentCashRegisterId = (string) CashRegister::query()
            ->where('status', 'open')
            ->when(!auth()->user()?->hasRole('admin'), fn ($query) => $query->where('opened_by', auth()->id()))
            ->orderBy('name')
            ->value('id');
        $this->showTipAdjustment = true;
    }

    public function closeTipAdjustment(): void
    {
        $this->showTipAdjustment = false;
        $this->resetValidation();
    }

    public function saveTipAdjustment(): void
    {
        abort_unless(auth()->user()?->can('empresa.editar'), 403);
        abort_unless(Setting::first()?->tips_enabled ?? true, 404);

        $this->validate([
            'tipAdjustmentSaleId' => ['required', 'integer', 'exists:sales,id'],
            'tipAdjustmentAmount' => ['required', 'regex:/^-?\d+(?:\.\d{1,2})?$/'],
            'tipAdjustmentReason' => ['required', 'string', 'max:255'],
            'tipAdjustmentReference' => ['nullable', 'string', 'max:255'],
            'tipAdjustmentPaymentMethodId' => ['required', 'integer', 'exists:payment_methods,id'],
            'tipAdjustmentCashRegisterId' => ['required', 'integer', 'exists:cash_registers,id'],
        ]);

        $amount = round((float) $this->tipAdjustmentAmount, 2);
        if ($amount === 0.0) {
            $this->addError('tipAdjustmentAmount', 'El ajuste no puede ser cero.');
            return;
        }

        try {
            DB::transaction(function () use ($amount): void {
                $sale = Sale::query()
                    ->whereKey($this->tipAdjustmentSaleId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $method = PaymentMethod::query()->findOrFail($this->tipAdjustmentPaymentMethodId);
                $cashRegister = CashRegister::query()
                    ->whereKey($this->tipAdjustmentCashRegisterId)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$cashRegister || (!auth()->user()->hasRole('admin') && $cashRegister->opened_by !== auth()->id())) {
                    throw new \RuntimeException('La caja seleccionada no está disponible para este usuario.');
                }

                if (!$cashRegister->branch_id) {
                    throw new \RuntimeException('La caja seleccionada no tiene una sede asignada.');
                }

                $currentTip = (float) $sale->tip + (float) TipAdjustment::query()
                    ->where('sale_id', $sale->id)
                    ->lockForUpdate()
                    ->sum('amount');

                if (round($currentTip + $amount, 2) < 0) {
                    throw new \RuntimeException('La propina no puede quedar en negativo.');
                }

                if ($method->is_efectivo) {
                    $newCashAmount = round((float) $cashRegister->current_amount + $amount, 2);
                    if ($newCashAmount < 0) {
                        throw new \RuntimeException('La devolución supera el efectivo disponible en caja.');
                    }

                    $cashRegister->update(['current_amount' => $newCashAmount]);
                }

                $adjustment = new TipAdjustment([
                    'sale_id' => $sale->id,
                    'cash_register_id' => $cashRegister->id,
                    'payment_method_id' => $method->id,
                    'adjusted_by' => auth()->id(),
                    'amount' => $amount,
                    'reason' => trim($this->tipAdjustmentReason),
                    'reference' => trim($this->tipAdjustmentReference) ?: null,
                    'adjusted_at' => now(),
                ]);
                $adjustment->branch_id = $cashRegister->branch_id;
                $adjustment->save();
            });
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', ['title' => 'No se pudo ajustar', 'text' => $e->getMessage(), 'icon' => 'error']);
            return;
        }

        $this->closeTipAdjustment();
        $this->dispatch('swal', ['title' => 'Propina ajustada', 'text' => 'La venta original permanece intacta.', 'icon' => 'success']);
    }

    public function render()
    {
        $relations = ['order.table', 'details.product', 'order.user', 'payments.method', 'cashRegister'];
        if ($this->tipsEnabled) {
            $relations[] = 'tipAdjustments';
        }

        $baseQuery = Sale::with($relations)
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
            })
            ->when($this->waiter, fn ($query) => $query->whereHas('order.user', fn ($user) => $user->where('name', 'like', '%' . $this->waiter . '%')))
            ->when($this->customer, function ($query) {
                $query->where(function ($customer) {
                    $customer->where('customer_name', 'like', '%' . $this->customer . '%')
                        ->orWhereHas('order', fn ($order) => $order->where('customer_name', 'like', '%' . $this->customer . '%'));
                });
            });

        $adjustments = $this->tipsEnabled
            ? TipAdjustment::query()->whereIn('sale_id', (clone $baseQuery)->select('sales.id'))
            : null;
        $this->totalSales = (float) (clone $baseQuery)->sum('total') + ($adjustments ? (float) (clone $adjustments)->sum('amount') : 0);
        $this->totalTips = $this->tipsEnabled
            ? (float) (clone $baseQuery)->sum('tip') + (float) (clone $adjustments)->sum('amount')
            : 0;

        $paymentRows = (clone $baseQuery)
            ->join('payments', 'payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->selectRaw('payment_methods.name, payment_methods.is_efectivo, SUM(payments.amount) as total')
            ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.is_efectivo')
            ->get();
        $adjustmentRows = $adjustments
            ? (clone $adjustments)
                ->join('payment_methods', 'payment_methods.id', '=', 'tip_adjustments.payment_method_id')
                ->selectRaw('payment_methods.name, payment_methods.is_efectivo, SUM(tip_adjustments.amount) as total')
                ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.is_efectivo')
                ->get()
            : collect();
        $paymentTotals = ['cash' => 0.0, 'yape' => 0.0, 'card' => 0.0];

        foreach ($paymentRows->concat($adjustmentRows) as $payment) {
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

        $waiters = Sale::query()
            ->join('orders', 'orders.id', '=', 'sales.order_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->orderBy('users.name')
            ->distinct()
            ->pluck('users.name');
        $customers = Sale::query()
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->orderBy('customer_name')
            ->distinct()
            ->pluck('customer_name');

        $selectedRelations = ['order.table', 'order.user', 'details.product', 'payments.method', 'cashRegister'];
        if ($this->tipsEnabled) {
            $selectedRelations[] = 'tipAdjustments.paymentMethod';
            $selectedRelations[] = 'tipAdjustments.cashRegister';
            $selectedRelations[] = 'tipAdjustments.adjuster';
        }
        $selectedSale = $this->selectedSaleId ? Sale::with($selectedRelations)->find($this->selectedSaleId) : null;
        $canEditPayments = $selectedSale?->cashRegister?->status === 'open'
            && ($selectedSale->cashRegister->opened_by === auth()->id() || auth()->user()?->hasRole('admin'))
            && $selectedSale->payments->every(fn (Payment $payment) => (float) $payment->returned_amount === 0.0);
        $paymentMethods = PaymentMethod::query()->orderBy('name')->get();
        $cashRegisters = CashRegister::query()
            ->where('status', 'open')
            ->when(!auth()->user()?->hasRole('admin'), fn ($query) => $query->where('opened_by', auth()->id()))
            ->orderBy('name')
            ->get(['id', 'name', 'current_amount']);

        return view('livewire.sales-index-component', compact('sales', 'selectedSale', 'paymentTotals', 'paymentMethods', 'cashRegisters', 'waiters', 'customers', 'canEditPayments'));
    }
}
