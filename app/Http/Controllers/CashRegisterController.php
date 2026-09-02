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
        $caja = CashRegister::with(['opener', 'sales.payments.method', 'expenses.paymentMethod'])->findOrFail($realId);

        $gastos = $caja->expenses;

        $pagosPorMetodo = $caja->sales->flatMap->payments
            ->groupBy(fn($p) => $p->method->name)
            ->map(fn($g) => $g->sum('amount'));

        foreach ($gastos as $gasto) {
            $metodoNombre = $gasto->paymentMethod->name ?? 'N/A';
            if (isset($pagosPorMetodo[$metodoNombre])) {
                $pagosPorMetodo[$metodoNombre] -= $gasto->amount;
            } else {
                $pagosPorMetodo[$metodoNombre] = -1 * $gasto->amount;
            }
        }

        if ($request->action == 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf_cash_movements', compact('caja', 'pagosPorMetodo', 'gastos'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream("Movimientos_Caja_{$caja->id}.pdf");
        }

        return view('cashRegister.movements', compact('caja', 'pagosPorMetodo', 'gastos', 'id'));
    }

    public function close($id)
    {
        $caja = DB::transaction(function () use ($id) {
            $caja = CashRegister::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($caja->status === 'open', 422, 'La caja ya está cerrada.');

            $caja->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => auth()->id(),
            ]);

            return $caja;
        });

        return response()->json([
            'success' => true,
            'message' => 'Caja cerrada con éxito',
            'cash_register_id' => $caja->id,
        ]);
    }
}
