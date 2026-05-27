<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

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

    public function close(Request $request, $id)
    {
        try {
            $caja = CashRegister::findOrFail($id);

            // Lógica de cierre (cambiar estado, guardar fecha, etc.)
            $caja->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closing_amount' => $caja->current_amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada con éxito'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
