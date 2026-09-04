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
        $caja = CashRegister::with(['terminal', 'opener', 'sales.payments.method', 'expenses.paymentMethod'])->findOrFail($realId);

        $cashSales = $caja->sales->filter(fn ($sale) => $sale->payments
            ->contains(fn ($payment) => $payment->method?->is_efectivo));
        $gastos = $caja->expenses->filter(fn ($expense) => $expense->paymentMethod?->is_efectivo);

        $pagosPorMetodo = $cashSales->flatMap->payments
            ->filter(fn ($payment) => $payment->method?->is_efectivo)
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

        if ($request->action == 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf_cash_movements', compact('caja', 'cashSales', 'pagosPorMetodo', 'gastos'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream("Movimientos_Caja_{$caja->id}.pdf");
        }

        return view('cashRegister.movements', compact('caja', 'cashSales', 'pagosPorMetodo', 'gastos', 'id'));
    }

    public function close($id, Request $request)
    {
        $request->validate([
            'counted_amount' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $caja = DB::transaction(function () use ($id, $request) {
            $caja = CashRegister::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($caja->status === 'open', 422, 'La caja ya está cerrada.');

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
}
