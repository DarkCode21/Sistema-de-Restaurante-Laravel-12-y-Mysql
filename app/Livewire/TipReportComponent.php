<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\TipPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class TipReportComponent extends Component
{
    use WithPagination;

    public string $fromDate;
    public string $toDate;
    public string $waiterSearch = '';
    public string $waiterId = '';
    public bool $showPayoutModal = false;
    public string $payoutWaiterId = '';
    public string $payoutPaymentMethodId = '';
    public string $payoutCashRegisterId = '';
    public string $payoutAmount = '';
    public string $payoutReference = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('ventas.reportes'), 403);
        abort_unless(Setting::first()?->tips_enabled ?? true, 404);
        $this->fromDate = Carbon::now()->startOfMonth()->toDateString();
        $this->toDate = Carbon::now()->toDateString();
    }

    public function updating($name): void
    {
        if (in_array($name, ['fromDate', 'toDate', 'waiterSearch', 'waiterId'], true)) {
            $this->resetPage();
        }
    }

    public function openPayout(int $waiterId): void
    {
        $balance = $this->tipBalance($waiterId);

        if ($balance <= 0) {
            $this->dispatch('swal', ['title' => 'Sin saldo', 'text' => 'Este mozo no tiene propinas pendientes.', 'icon' => 'warning']);
            return;
        }

        $this->resetValidation();
        $this->payoutWaiterId = (string) $waiterId;
        $this->payoutAmount = number_format($balance, 2, '.', '');
        $this->payoutPaymentMethodId = (string) PaymentMethod::query()->value('id');
        $this->payoutCashRegisterId = '';
        $this->payoutReference = '';
        $this->showPayoutModal = true;
    }

    public function closePayout(): void
    {
        $this->showPayoutModal = false;
        $this->resetValidation();
    }

    public function savePayout(): void
    {
        abort_unless(auth()->user()?->can('ventas.reportes'), 403);
        abort_unless(Setting::first()?->tips_enabled ?? true, 404);

        $this->validate([
            'payoutWaiterId' => ['required', 'integer', 'exists:users,id'],
            'payoutPaymentMethodId' => ['required', 'integer', 'exists:payment_methods,id'],
            'payoutCashRegisterId' => ['nullable', 'integer', 'exists:cash_registers,id'],
            'payoutAmount' => ['required', 'numeric', 'min:0.01'],
            'payoutReference' => ['nullable', 'string', 'max:255'],
        ]);
        $branchId = (int) session('branch_id');

        if (!$branchId) {
            $this->dispatch('swal', ['title' => 'Sede requerida', 'text' => 'Selecciona una sede antes de desembolsar propinas.', 'icon' => 'error']);
            return;
        }

        try {
            DB::transaction(function () use ($branchId): void {
                $waiter = User::query()->whereKey($this->payoutWaiterId)->lockForUpdate()->firstOrFail();
                $method = PaymentMethod::query()->find($this->payoutPaymentMethodId);

                if (!$method) {
                    throw new \RuntimeException('El método de pago ya no está disponible.');
                }

                $credited = $this->creditedTips($waiter->id);
                $paid = (float) TipPayout::query()
                    ->where('waiter_id', $waiter->id)
                    ->lockForUpdate()
                    ->sum('amount');
                $amount = round((float) $this->payoutAmount, 2);
                $available = round($credited - $paid, 2);

                if ($amount > $available) {
                    throw new \RuntimeException('El desembolso supera las propinas pendientes del mozo.');
                }

                $cashRegisterId = null;
                if ($method->is_efectivo) {
                    if (!$this->payoutCashRegisterId) {
                        throw new \RuntimeException('Selecciona una caja abierta para pagar propinas en efectivo.');
                    }

                    $cashRegister = CashRegister::query()
                        ->whereKey($this->payoutCashRegisterId)
                        ->where('status', 'open')
                        ->lockForUpdate()
                        ->first();

                    if (!$cashRegister || (!auth()->user()->hasRole('admin') && $cashRegister->opened_by !== auth()->id())) {
                        throw new \RuntimeException('La caja seleccionada no está disponible para este usuario.');
                    }

                    if ($amount > (float) $cashRegister->current_amount) {
                        throw new \RuntimeException('El desembolso supera el efectivo disponible en caja.');
                    }

                    $cashRegister->decrement('current_amount', $amount);
                    $cashRegisterId = $cashRegister->id;
                }

                $payout = new TipPayout([
                    'waiter_id' => $waiter->id,
                    'waiter_name' => $waiter->name,
                    'payment_method_id' => $method->id,
                    'cash_register_id' => $cashRegisterId,
                    'paid_by' => auth()->id(),
                    'amount' => $amount,
                    'reference' => trim($this->payoutReference) ?: null,
                    'paid_at' => now(),
                ]);
                $payout->branch_id = $branchId;
                $payout->save();
            });
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', ['title' => 'No se pudo desembolsar', 'text' => $e->getMessage(), 'icon' => 'error']);
            return;
        }

        $this->closePayout();
        $this->dispatch('swal', ['title' => 'Propina desembolsada', 'text' => 'El pago quedó registrado.', 'icon' => 'success']);
    }

    public function render()
    {
        $allTimeTipSales = $this->tipSales()->get()
            ->filter(fn (Sale $sale) => $sale->order?->user_id && $sale->adjusted_tip > 0);
        $allTimeTipsByWaiter = $allTimeTipSales
            ->groupBy(fn (Sale $sale) => $sale->order->user_id)
            ->map(function ($sales, $waiterId) {
                $firstSale = $sales->first();

                return (object) [
                    'waiter_id' => $waiterId,
                    'name' => $firstSale->order->user?->name ?? 'Mesero histórico',
                    'total_tips' => (float) $sales->sum('adjusted_tip'),
                ];
            });
        $payoutsByWaiter = TipPayout::query()
            ->select('waiter_id', DB::raw('SUM(amount) as total_payouts'))
            ->groupBy('waiter_id')
            ->get()
            ->keyBy('waiter_id');
        $waiters = User::query()
            ->whereIn('id', $allTimeTipsByWaiter->keys())
            ->when($this->waiterSearch, fn ($query) => $query->where('name', 'like', '%' . $this->waiterSearch . '%'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $tips = $this->tipSales($this->fromDate, $this->toDate, $this->waiterId);
        $periodTipSales = (clone $tips)->get()
            ->filter(fn (Sale $sale) => $sale->adjusted_tip > 0 && $sale->order?->user_id);
        $totalTips = (float) $periodTipSales->sum('adjusted_tip');
        $salesCount = $periodTipSales->count();
        $byWaiter = $periodTipSales
            ->groupBy(fn (Sale $sale) => $sale->order->user_id)
            ->map(function ($sales, $waiterId) use ($allTimeTipsByWaiter, $payoutsByWaiter) {
                $firstSale = $sales->first();
                $totalPayouts = (float) ($payoutsByWaiter->get($waiterId)?->total_payouts ?? 0);

                return (object) [
                    'waiter_id' => $waiterId,
                    'name' => $firstSale->order->user?->name ?? 'Mesero histórico',
                    'total_tips' => (float) $sales->sum('adjusted_tip'),
                    'sales_count' => $sales->count(),
                    'total_payouts' => $totalPayouts,
                    'pending_tips' => max(0, (float) ($allTimeTipsByWaiter->get($waiterId)?->total_tips ?? 0) - $totalPayouts),
                ];
            })
            ->sortByDesc('total_tips')
            ->values();
        $sales = $tips->with(['order.user', 'cashRegister'])->orderByDesc('paid_at')->paginate(12);
        $payouts = TipPayout::query()
            ->with(['paymentMethod', 'cashRegister', 'paidBy'])
            ->when($this->fromDate, fn ($query) => $query->whereDate('paid_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($query) => $query->whereDate('paid_at', '<=', $this->toDate))
            ->when($this->waiterId, fn ($query) => $query->where('waiter_id', $this->waiterId))
            ->orderByDesc('paid_at')
            ->paginate(8, ['*'], 'payoutPage');
        $paymentMethods = PaymentMethod::query()->orderBy('name')->get();
        $cashRegisters = CashRegister::query()
            ->where('status', 'open')
            ->when(!auth()->user()?->hasRole('admin'), fn ($query) => $query->where('opened_by', auth()->id()))
            ->orderBy('name')
            ->get(['id', 'name', 'current_amount']);
        $totalPending = $allTimeTipsByWaiter->sum(fn ($tip) => max(0, (float) $tip->total_tips - (float) ($payoutsByWaiter->get($tip->waiter_id)?->total_payouts ?? 0)));
        $totalPayouts = (clone TipPayout::query())
            ->when($this->fromDate, fn ($query) => $query->whereDate('paid_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($query) => $query->whereDate('paid_at', '<=', $this->toDate))
            ->when($this->waiterId, fn ($query) => $query->where('waiter_id', $this->waiterId))
            ->sum('amount');
        $selectedPayoutMethod = $paymentMethods->firstWhere('id', (int) $this->payoutPaymentMethodId);

        return view('livewire.tip-report-component', compact('waiters', 'totalTips', 'salesCount', 'byWaiter', 'sales', 'payouts', 'paymentMethods', 'cashRegisters', 'totalPending', 'totalPayouts', 'selectedPayoutMethod'));
    }

    private function tipBalance(int $waiterId): float
    {
        $credited = $this->creditedTips($waiterId);

        return max(0, round($credited - (float) TipPayout::query()->where('waiter_id', $waiterId)->sum('amount'), 2));
    }

    private function tipSales(?string $fromDate = null, ?string $toDate = null, ?string $waiterId = null)
    {
        return Sale::query()
            ->with(['order.user', 'cashRegister', 'tipAdjustments'])
            ->where(fn ($query) => $query->where('tip', '>', 0)->orWhereHas('tipAdjustments'))
            ->when($fromDate, fn ($query) => $query->whereDate('paid_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('paid_at', '<=', $toDate))
            ->when($waiterId, fn ($query) => $query->whereHas('order', fn ($order) => $order->where('user_id', $waiterId)));
    }

    private function creditedTips(int $waiterId): float
    {
        return (float) $this->tipSales(null, null, (string) $waiterId)
            ->get()
            ->sum('adjusted_tip');
    }
}
