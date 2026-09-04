<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Promotion;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Sale;
use App\Models\SaleDetail;
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

    public function promotions(Request $request)
    {
        $start_date = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $end_date = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');

        $rows = SaleDetail::query()
            ->selectRaw('promotion_id, COUNT(*) as times_applied, SUM(quantity) as qty, SUM(discount) as total_discount, SUM(subtotal) as net_revenue, SUM(subtotal + tax) as gross_revenue')
            ->whereNotNull('promotion_id')
            ->whereHas('sale', function ($q) use ($start_date, $end_date) {
                $q->whereDate('paid_at', '>=', $start_date)
                    ->whereDate('paid_at', '<=', $end_date);
            })
            ->groupBy('promotion_id')
            ->get()
            ->keyBy('promotion_id');

        $promotions = Promotion::with('product')
            ->whereIn('id', $rows->keys())
            ->orderBy('name')
            ->get()
            ->map(function (Promotion $promotion) use ($rows) {
                $row = $rows->get($promotion->id);
                return [
                    'promotion' => $promotion,
                    'times_applied' => (int) ($row->times_applied ?? 0),
                    'qty' => (int) ($row->qty ?? 0),
                    'total_discount' => (float) ($row->total_discount ?? 0),
                    'net_revenue' => (float) ($row->net_revenue ?? 0),
                    'gross_revenue' => (float) ($row->gross_revenue ?? 0),
                ];
            })
            ->sortByDesc('total_discount')
            ->values();

        $totals = [
            'times_applied' => $promotions->sum('times_applied'),
            'qty' => $promotions->sum('qty'),
            'total_discount' => $promotions->sum('total_discount'),
            'net_revenue' => $promotions->sum('net_revenue'),
            'gross_revenue' => $promotions->sum('gross_revenue'),
        ];

        if ($request->action === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf_promotions', compact('promotions', 'totals', 'start_date', 'end_date'))
                ->setPaper('a4', 'portrait');
            return $pdf->download("Reporte_Promociones_{$start_date}_a_{$end_date}.pdf");
        }

        return view('reports.promotions', compact('promotions', 'totals', 'start_date', 'end_date'));
    }

    public function profit(Request $request)
    {
        $start_date = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $end_date = $request->end_date ?? now()->format('Y-m-d');

        $details = SaleDetail::query()->whereHas('sale', function ($query) use ($start_date, $end_date) {
            $query->whereDate('paid_at', '>=', $start_date)
                ->whereDate('paid_at', '<=', $end_date);
        });
        $costedDetails = (clone $details)->whereNotNull('cost_total');
        $costSummary = (clone $costedDetails)->selectRaw('COUNT(*) as line_count, COALESCE(SUM(cost_total), 0) as cost, COALESCE(SUM(gross_profit), 0) as gross_profit')->first();
        $sales = Sale::query()
            ->whereDate('paid_at', '>=', $start_date)
            ->whereDate('paid_at', '<=', $end_date)
            ->sum('subtotal');
        $expenses = Expense::query()
            ->whereDate('expense_date', '>=', $start_date)
            ->whereDate('expense_date', '<=', $end_date)
            ->sum('amount');
        $products = (clone $costedDetails)
            ->selectRaw('product_id, product_name, SUM(quantity) as quantity, SUM(cost_total) as cost, SUM(gross_profit) as gross_profit')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('gross_profit')
            ->get();
        $lowStockIngredients = Ingredient::query()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('name')
            ->get();

        $totals = [
            'sales' => (float) $sales,
            'cost' => (float) $costSummary->cost,
            'gross_profit' => (float) $costSummary->gross_profit,
            'expenses' => (float) $expenses,
            'net_profit' => (float) $costSummary->gross_profit - (float) $expenses,
            'costed_lines' => (int) $costSummary->line_count,
            'missing_cost_lines' => (clone $details)->whereNull('cost_total')->count(),
        ];

        return view('reports.profit', compact('start_date', 'end_date', 'products', 'lowStockIngredients', 'totals'));
    }
}
