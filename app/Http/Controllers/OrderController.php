<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('cocinero')) {
            return redirect()->route('orders.chef');
        }

        if ($user->hasRole('cajero') && !$user->hasRole('admin')) {
            return redirect()->route('orders.cashier');
        }

        return view('orders.index');
    }

    public function chef()
    {
        if (!Auth::user()->hasAnyRole(['admin', 'cocinero'])) {
            abort(403, 'No tienes permiso para acceder a la cocina.');
        }

        return view('orders.chef');
    }

    public function cashier()
    {
        if (!Auth::user()->hasAnyRole(['admin', 'cajero'])) {
            abort(403, 'No tienes permiso para acceder a la caja.');
        }

        return view('orders.cashier');
    }

    public function create($tableId)
    {
        try {
            $decryptedId = decrypt($tableId);
            $table = Table::findOrFail($decryptedId);
            return view('orders.create', compact('table'));
        } catch (DecryptException $e) {
            abort(404, 'El identificador de la mesa no es válido.');
        }
    }

    public function print(Request $request, $id)
    {
        $requiresKitchen = $request->boolean('requires_kitchen');

        $order = Order::with([
            'table',
            'details' => function ($q) use ($requiresKitchen) {

                $q->where('requires_kitchen', $requiresKitchen)
                    ->whereIn('cooking_status', ['pending', 'in_progress'])
                    ->with('product');
            },
        ])->where('status', 'abierto')->findOrFail($id);

        if ($order->details->isEmpty()) {
            return response()->json([
                'message' => 'No hay productos pendientes para este destino.'
            ], 404);
        }

        // ACTUALIZAR ESTADO
        OrderDetail::whereIn(
            'id',
            $order->details->pluck('id')
        )->update([
            'cooking_status' => 'in_progress'
        ]);

        $width_mm = env('IMPRESION_SIZE') - 10;
        $height_mm = 297;

        $width_pt = $this->mmToPoints($width_mm);
        $height_pt = $this->mmToPoints($height_mm);

        $pdf = Pdf::loadView('orders.receipt', compact('order'))
            ->setPaper([0, 0, $width_pt, $height_pt], 'portrait');

        return $pdf->stream("ticket_{$id}.pdf");
    }

    public function ticket(Request $request, $id)
    {
        $requiresKitchen = $request->boolean('requires_kitchen');

        $order = Order::with([
            'table',
            'details' => function ($q) {
                $q->where('cooking_status', '!=', 'cancelled')
                    ->with('product');
            },
        ])->findOrFail($id);

        if ($order->details->isEmpty()) {
            return response()->json(['message' => 'No hay productos pendientes para este destino.'], 404);
        }

        $width_mm = env('IMPRESION_SIZE') - 10;

        $height_mm = 297;

        $width_pt = $this->mmToPoints($width_mm);

        $height_pt = $this->mmToPoints($height_mm);

        $pdf = Pdf::loadView('orders.receipt', compact('order'))
            ->setPaper([0, 0, $width_pt, $height_pt], 'portrait');

        return $pdf->stream("ticket_{$id}.pdf");
    }

    private function mmToPoints($mm)
    {
        return $mm * 2.83464567;
    }
}
