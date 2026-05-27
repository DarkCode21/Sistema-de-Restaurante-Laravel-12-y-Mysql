<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class OrdersIndexComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';

    public function markDetailAsReady($detailId)
    {
        $detail = OrderDetail::find($detailId);

        if ($detail) {
            $detail->update([
                'cooking_status' => 'served'
            ]);

            $this->dispatch('swal', [
                'title' => '¡Listo!',
                'text' => 'El producto ha sido marcado como servido.',
                'icon' => 'success'
            ]);
        }
    }

    public function cancelarDetalle($detailId)
    {
        $detail = OrderDetail::with('order', 'product')->find($detailId);

        if (!$detail) return;

        if ($detail->cooking_status === 'cancelled') return;

        DB::transaction(function () use ($detail) {

            // 1. marcar cancelado
            $detail->update([
                'cooking_status' => 'cancelled'
            ]);

            // 2. devolver stock
            if ($detail->product) {
                $detail->product->increment('stock', $detail->quantity);
            }

            // 3. actualizar total de la orden
            if ($detail->order) {
                $nuevoTotal = max(0, $detail->order->total - $detail->subtotal);

                $detail->order->update([
                    'total' => $nuevoTotal,
                    'amount_pending' => $nuevoTotal
                ]);
            }
        });

        $this->dispatch('swal', [
            'title' => '¡Cancelado!',
            'text' => 'El producto fue cancelado y el stock fue devuelto.',
            'icon' => 'error'
        ]);
    }

    public function render()
    {
        $orders = Order::with([
            'table',
            'details' => function ($q) {
                $q->where('cooking_status', '!=', 'cancelled')
                    ->with('product');
            },
            'user'
        ])
            ->where('status', 'abierto')
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->whereHas('table', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->whereHas('details', function ($q) {
                $q->where('cooking_status', '!=', 'cancelled');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('livewire.orders-index-component', [
            'orders' => $orders
        ]);
    }
}
