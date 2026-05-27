<?php

namespace App\Http\Controllers;

use App\Exports\ExpensesExport;
use App\Models\Expense;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends Controller
{
    public function index(){
        return view('expenses.index');
    }

    private function getQuery(Request $request)
    {
        return Expense::with(['cashRegister', 'paymentMethod', 'user'])
            ->where(function($query) use ($request) {
                if ($request->filled('search')) {
                    $query->where('concept', 'like', '%' . $request->search . '%')
                          ->orWhere('description', 'like', '%' . $request->search . '%');
                }
            })
            ->when($request->start_date, function($query) use ($request) {
                $query->whereDate('expense_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function($query) use ($request) {
                $query->whereDate('expense_date', '<=', $request->end_date);
            })
            ->latest();
    }

    public function pdf(Request $request)
    {
        $expenses = $this->getQuery($request)->get();

        $search = $request->search;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $pdf = Pdf::loadView('expenses.pdf', compact('expenses', 'search', 'start_date', 'end_date'));
        
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('reporte-egresos-' . now()->format('Ymd') . '.pdf');
    }

    public function excel(Request $request)
    {
        $query = $this->getQuery($request);

        return Excel::download(
            new ExpensesExport($query), 
            'reporte-egresos-' . now()->format('Ymd') . '.xlsx'
        );
    }
}
