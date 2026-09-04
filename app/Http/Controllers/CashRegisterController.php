<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index()
    {
        return view('cashRegister.index');
    }

    public function movements($id, Request $request)
    {
        $realId = Crypt::decrypt($id);
        $caja = CashRegister::with(['terminal', 'opener', 'sales.payments.method', 'expenses.paymentMethod', 'paymentClosures'])->findOrFail($realId);

        $cashSales = $caja->sales;
        $gastos = $caja->expenses->filter(fn ($expense) => $expense->paymentMethod?->is_efectivo);

        $pagosPorMetodo = $cashSales->flatMap->payments
            ->groupBy(fn($p) => $p->method->name)
            ->map(fn($g) => $g->sum('amount'));

        foreach ($gastos as $gasto) {
            $metodoNombre = $gasto->paymentMethod->name;
            if (isset($pagosPorMetodo[$metodoNombre])) {
                $pagosPorMetodo[$metodoNombre] -= $gasto->amount;
            } else {
                $pagosPorMetodo[$metodoNombre] = -1 * $gasto->amount;
            }
        }

        $settlementRows = $this->paymentSettlementRows($caja);

        if ($request->action == 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf_cash_movements', compact('caja', 'cashSales', 'pagosPorMetodo', 'gastos'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream("Movimientos_Caja_{$caja->id}.pdf");
        }

        return view('cashRegister.movements', compact('caja', 'cashSales', 'pagosPorMetodo', 'settlementRows', 'gastos', 'id'));
    }

    public function close($id, Request $request)
    {
        $request->validate([
            'counted_amount' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
            'payment_closures' => ['nullable', 'array'],
            'payment_closures.*.payment_method_id' => ['required', 'integer', 'distinct', 'exists:payment_methods,id'],
            'payment_closures.*.counted_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $caja = DB::transaction(function () use ($id, $request) {
            $caja = CashRegister::query()
                ->with(['sales.payments.method', 'paymentClosures'])
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($caja->status === 'open', 422, 'La caja ya está cerrada.');
            abort_unless(
                $caja->opened_by === auth()->id() || auth()->user()?->hasRole('admin'),
                403,
                'Solo el cajero que abrió el turno puede cerrarlo.'
            );

            $submittedClosures = collect($request->input('payment_closures', []))->keyBy('payment_method_id');
            $settlementRows = $this->paymentSettlementRows($caja);

            foreach ($settlementRows as $row) {
                $countedAmount = $row['is_cash']
                    ? (float) $request->counted_amount
                    : (float) ($submittedClosures->get($row['payment_method_id'])['counted_amount'] ?? $row['expected_amount']);

                $caja->paymentClosures()->create([
                    'payment_method_id' => $row['payment_method_id'],
                    'label' => $row['label'],
                    'expected_amount' => $row['expected_amount'],
                    'counted_amount' => $countedAmount,
                    'difference' => round($countedAmount - $row['expected_amount'], 2),
                ]);
            }

            $caja->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => auth()->id(),
                'closing_amount' => $request->counted_amount,
                'difference' => (float) $request->counted_amount - (float) $caja->current_amount,
                'closing_notes' => trim((string) $request->closing_notes) ?: null,
            ]);

            return $caja;
        });

        return response()->json([
            'success' => true,
            'message' => 'Caja cerrada con éxito',
            'cash_register_id' => $caja->id,
            'difference' => $caja->difference,
        ]);
    }

    private function paymentSettlementRows(CashRegister $caja)
    {
        $closures = $caja->paymentClosures->keyBy(fn ($closure) => $closure->payment_method_id ?? 'cash');
        $digitalRows = $caja->sales
            ->flatMap->payments
            ->filter(fn ($payment) => !$payment->method?->is_efectivo)
            ->groupBy('payment_method_id')
            ->map(function ($payments, $methodId) use ($closures) {
                $closure = $closures->get($methodId);
                $expectedAmount = round((float) $payments->sum('amount'), 2);

                return [
                    'payment_method_id' => (int) $methodId,
                    'label' => $payments->first()->method->name,
                    'is_cash' => false,
                    'expected_amount' => $expectedAmount,
                    'counted_amount' => $closure?->counted_amount,
                    'difference' => $closure?->difference,
                ];
            });
        $cashClosure = $closures->get('cash');

        return collect([[
            'payment_method_id' => null,
            'label' => 'Efectivo físico',
            'is_cash' => true,
            'expected_amount' => round((float) $caja->current_amount, 2),
            'counted_amount' => $cashClosure?->counted_amount ?? $caja->closing_amount,
            'difference' => $cashClosure?->difference ?? $caja->difference,
        ]])->concat($digitalRows)->values();
    }
}
