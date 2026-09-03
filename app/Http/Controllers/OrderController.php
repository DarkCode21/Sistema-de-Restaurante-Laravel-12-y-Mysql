<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderCorrection;
use App\Models\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        if (!Auth::user()->hasRole('admin') && !Auth::user()->preparationStations()->exists()) {
            abort(403, 'No tienes permiso para acceder a la cocina.');
        }

        return view('orders.chef');
    }

    public function cashier()
    {
        abort_unless(Auth::user()?->can('ordenes.cobrar'), 403, 'No tienes permiso para acceder a la caja.');

        return view('orders.cashier');
    }

    public function create($tableId)
    {
        try {
            $decryptedId = decrypt($tableId);
            $table = Table::findOrFail($decryptedId);
            return view('orders.create', ['table' => $table, 'orderType' => 'dine_in']);
        } catch (DecryptException $e) {
            abort(404, 'El identificador de la mesa no es válido.');
        }
    }

    public function createDirect(string $type)
    {
        abort_unless(in_array($type, ['pickup', 'delivery'], true), 404);

        return view('orders.create', ['table' => null, 'orderType' => $type]);
    }

    public function manage(Order $order)
    {
        abort_unless($order->status === 'abierto', 404);

        return view('orders.create', [
            'table' => $order->table,
            'order' => $order,
            'orderType' => $order->order_type,
        ]);
    }

    public function print(Request $request, $id)
    {
        $detailIds = collect($request->input('detail_ids', []))
            ->filter(fn ($detailId) => is_numeric($detailId) && (int) $detailId > 0)
            ->map(fn ($detailId) => (int) $detailId)
            ->unique()
            ->values();
        $correctionIds = collect($request->input('correction_ids', []))
            ->filter(fn ($correctionId) => is_numeric($correctionId) && (int) $correctionId > 0)
            ->map(fn ($correctionId) => (int) $correctionId)
            ->unique()
            ->values();
        $isCorrection = $request->boolean('correction');

        [$order, $details, $corrections] = DB::transaction(function () use ($request, $id, $detailIds, $correctionIds, $isCorrection) {
            $order = Order::query()
                ->with('table')
                ->whereKey($id)
                ->when(!$isCorrection, fn ($query) => $query->where('status', 'abierto'))
                ->lockForUpdate()
                ->firstOrFail();

            if ($isCorrection) {
                $corrections = OrderCorrection::query()
                    ->where('order_id', $order->id)
                    ->whereIn('id', $correctionIds)
                    ->when($request->has('requires_kitchen'), fn ($query) => $query->where('requires_kitchen', $request->boolean('requires_kitchen')))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($corrections as $correction) {
                    if (!$correction->printed_at) {
                        $correction->update(['printed_at' => now()]);
                    }
                }

                $order->setRelation('details', collect());

                return [$order, collect(), $corrections];
            }

            $query = $order->details()->with('product');

            if ($request->has('requires_kitchen')) {
                $query->where('requires_kitchen', $request->boolean('requires_kitchen'));
            }

            if ($detailIds->isNotEmpty()) {
                $query->whereIn('id', $detailIds)
                    ->where('cooking_status', 'pending')
                    ->where('is_printed', false);
            } else {
                // A kitchen user may deliberately reprint the active ticket.
                $query->whereIn('cooking_status', ['pending', 'in_progress']);
            }

            $details = $query->lockForUpdate()->get();

            foreach ($details as $detail) {
                $detail->update([
                    'cooking_status' => 'in_progress',
                    'is_printed' => true,
                ]);
            }

            $order->setRelation('details', $details);

            return [$order, $details, collect()];
        });

        if (($isCorrection ? $corrections : $details)->isEmpty()) {

            return response()->json([
                'message' => 'No hay productos pendientes para este destino.'
            ], 404);
        }

        $width_mm = env('IMPRESION_SIZE') - 10;
        $height_mm = 297;

        $width_pt = $this->mmToPoints($width_mm);
        $height_pt = $this->mmToPoints($height_mm);

        $pdf = Pdf::loadView('orders.receipt', compact('order', 'isCorrection', 'corrections'))
            ->setPaper([0, 0, $width_pt, $height_pt], 'portrait');

        return $pdf->stream("ticket_{$id}.pdf");
    }

    public function ticket($id)
    {
        $isCorrection = false;
        $corrections = collect();

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

        $pdf = Pdf::loadView('orders.receipt', compact('order', 'isCorrection', 'corrections'))
            ->setPaper([0, 0, $width_pt, $height_pt], 'portrait');

        return $pdf->stream("ticket_{$id}.pdf");
    }

    private function mmToPoints($mm)
    {
        return $mm * 2.83464567;
    }
}
