<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function transactions(Request $request)
{
    $categories = Category::all();
    $paymentMethods = PaymentMethod::all();

    $query = Transaction::with(['category', 'user', 'payments.paymentMethod']);

    $query->when($request->start_date, function ($q) use ($request) {
        return $q->whereDate('transaction_date', '>=', $request->start_date);
    });

    $query->when($request->end_date, function ($q) use ($request) {
        return $q->whereDate('transaction_date', '<=', $request->end_date);
    });

    $query->when($request->type, function ($q) use ($request) {
        return $q->where('type', $request->type);
    });

    $query->when($request->category_id, function ($q) use ($request) {
        return $q->where('category_id', $request->category_id);
    });

    $query->when($request->payment_method_id, function ($q) use ($request) {
        return $q->whereHas('payments', function ($subQuery) use ($request) {
            $subQuery->where('payment_method_id', $request->payment_method_id);
        });
    });

    $totalIngresos = (clone $query)->where('type', 'ingreso')->sum('amount');
    $totalEgresos = (clone $query)->where('type', 'egreso')->sum('amount');

    if ($request->action == 'pdf') {
        $transactions = $query->orderBy('transaction_date', 'desc')->get();
        $pdf = Pdf::loadView('reports.pdf_transactions', compact('transactions', 'totalIngresos', 'totalEgresos'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('Reporte_Transacciones.pdf');
    }

    if ($request->action == 'excel') {
        $transactions = $query->orderBy('transaction_date', 'desc')->get();
        $empresa = \App\Models\Setting::first();
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TransactionsExport($transactions, $empresa),
            'Reporte_Transacciones.xlsx'
        );
    }

    $transactions = $query->orderBy('transaction_date', 'desc')
        ->paginate(20)
        ->appends($request->all());

    return view('reports.transactions', compact(
        'transactions', 
        'categories', 
        'paymentMethods', 
        'totalIngresos', 
        'totalEgresos'
    ));
}

    public function balance(Request $request)
    {
        // 1. Filtros de fecha (por defecto mes actual)
        $start_date = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $end_date = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');

        // 2. Obtener sumatorias agrupadas por categoría
        $data = Transaction::with('category')
            ->whereBetween('transaction_date', [$start_date, $end_date])
            ->selectRaw('category_id, type, SUM(amount) as total')
            ->groupBy('category_id', 'type')
            ->get();

        // 3. Procesar datos para la vista
        $ingresosPorCategoria = $data->where('type', 'ingreso');
        $egresosPorCategoria = $data->where('type', 'egreso');

        $totalIngresos = $ingresosPorCategoria->sum('total');
        $totalEgresos = $egresosPorCategoria->sum('total');
        $balanceNeto = $totalIngresos - $totalEgresos;

        // 4. Acciones de Exportación
        if ($request->action == 'pdf') {
            $pdf = Pdf::loadView('reports.pdf_balance', compact('ingresosPorCategoria', 'egresosPorCategoria', 'totalIngresos', 'totalEgresos', 'balanceNeto', 'start_date', 'end_date'))
                ->setPaper('a4', 'portrait');
            return $pdf->download("Balance_{$start_date}_a_{$end_date}.pdf");
        }

        return view('reports.balance', compact(
            'ingresosPorCategoria',
            'egresosPorCategoria',
            'totalIngresos',
            'totalEgresos',
            'balanceNeto',
            'start_date',
            'end_date'
        ));
    }
}
