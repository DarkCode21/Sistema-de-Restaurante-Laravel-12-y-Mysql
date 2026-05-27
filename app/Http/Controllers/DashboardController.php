<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Table;
use App\Models\SaleDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
{
    $hoy = Carbon::today();
    $ayer = Carbon::yesterday();

    // VENTAS
    $ventasHoy = Sale::whereDate('paid_at', $hoy)->sum('total');
    $ventasAyer = Sale::whereDate('paid_at', $ayer)->sum('total');
    $variacionVentas = $ventasAyer > 0 ? (($ventasHoy - $ventasAyer) / $ventasAyer) * 100 : 100;

    // PROPINAS (Reemplaza al ticket promedio)
    $propinasHoy = Sale::whereDate('paid_at', $hoy)->sum('tip');
    $propinasAyer = Sale::whereDate('paid_at', $ayer)->sum('tip');
    $variacionPropinas = $propinasAyer > 0 ? (($propinasHoy - $propinasAyer) / $propinasAyer) * 100 : 100;

    // MESAS
    $mesasTotales = Table::count();
    $mesasOcupadas = Table::where('status', 'ocupada')->count();
    $porcentajeOcupacion = $mesasTotales > 0 ? ($mesasOcupadas / $mesasTotales) * 100 : 0;

    // COCINA
    $comandasEnCocina = OrderDetail::where('requires_kitchen', true)
        ->whereIn('cooking_status', ['pending', 'in_progress'])
        ->count();

    // ÚLTIMAS COMANDAS
    $ultimasComandas = Order::with(['table', 'details'])
        ->whereDate('created_at', $hoy)
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

    // RANKING PRODUCTOS
    $rankingProductos = SaleDetail::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_money'))
        ->whereHas('sale', function ($q) use ($hoy) {
            $q->whereDate('paid_at', $hoy);
        })
        ->with('product')
        ->groupBy('product_id')
        ->orderByDesc('total_qty')
        ->take(5)
        ->get();

    // GRÁFICO VENTAS MENSUALES (ÚLTIMOS 6 MESES)
    $seisMesesAtras = Carbon::now()->subMonths(5)->startOfMonth();

    $ventasMensualesRaw = Sale::select(
        DB::raw("YEAR(paid_at) as anio"),
        DB::raw("MONTH(paid_at) as mes"),
        DB::raw("SUM(total) as total")
    )
        ->where('paid_at', '>=', $seisMesesAtras)
        ->groupBy('anio', 'mes')
        ->orderBy('anio', 'asc')
        ->orderBy('mes', 'asc')
        ->get();

    $chartVentasMesLabels = [];
    $chartVentasMesData = [];
    $mesesEspaniol = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    for ($i = 5; $i >= 0; $i--) {
        $mesObjeto = Carbon::now()->subMonths($i);
        $anioActual = $mesObjeto->year;
        $mesActual = $mesObjeto->month;

        $ventaDelMes = $ventasMensualesRaw->first(function ($item) use ($anioActual, $mesActual) {
            return (int)$item->anio === $anioActual && (int)$item->mes === $mesActual;
        });

        $chartVentasMesData[] = $ventaDelMes ? (float)$ventaDelMes->total : 0.0;
        $chartVentasMesLabels[] = $mesesEspaniol[$mesActual - 1] . ' ' . $mesObjeto->format('y');
    }

    // MÉTODOS DE PAGO
    $metodosPagoData = Payment::select('payment_methods.name', DB::raw('SUM(payments.amount) as total'))
        ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
        ->whereDate('payments.created_at', $hoy)
        ->groupBy('payment_methods.name')
        ->get();

    $chartMetodosLabels = $metodosPagoData->pluck('name')->toArray();
    $chartMetodosData = $metodosPagoData->pluck('total')->map(fn($val) => (float)$val)->toArray();

    // RETORNO A LA VISTA
    return view('dashboard', compact(
        'ventasHoy',
        'variacionVentas',
        'propinasHoy',
        'variacionPropinas',
        'mesasOcupadas',
        'mesasTotales',
        'porcentajeOcupacion',
        'comandasEnCocina',
        'ultimasComandas',
        'rankingProductos',
        'chartVentasMesLabels',
        'chartVentasMesData',
        'chartMetodosLabels',
        'chartMetodosData'
    ));
}
}
