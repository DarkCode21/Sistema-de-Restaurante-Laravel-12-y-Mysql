<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class OrdersIndexComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';

    public function markDetailAsServed($detailId): void
    {
        $detail = OrderDetail::find($detailId);

        if (!$detail || $detail->cooking_status !== 'ready') {
            $this->dispatch('swal', [
                'title' => 'Aún no disponible',
                'text' => 'Solo se pueden retirar platos marcados como listos por Cocina.',
                'icon' => 'warning',
                'timer' => 1500,
            ]);
            return;
        }

        $detail->update([
            'cooking_status' => 'served'
        ]);

        $this->dispatch('swal', [
            'title' => '¡Entregado!',
            'text' => 'El plato fue retirado y marcado como entregado.',
            'icon' => 'success',
            'timer' => 1000,
        ]);
    }

    public function cancelarDetalle($detailId): void
    {
        $wasCancelled = DB::transaction(function () use ($detailId) {
            $detail = OrderDetail::query()
                ->whereKey($detailId)
                ->lockForUpdate()
                ->first();

            if (!$detail || in_array($detail->cooking_status, ['cancelled', 'served'], true)) {
                return false;
            }

            $order = Order::query()
                ->whereKey($detail->order_id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return false;
            }

            $detail->update([
                'cooking_status' => 'cancelled'
            ]);

            $product = Product::query()
                ->whereKey($detail->product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $product->increment('stock', $detail->quantity);
            }

            $remainingDetails = $order->details()
                ->where('cooking_status', '!=', 'cancelled')
                ->get();

            if ($remainingDetails->isEmpty()) {
                $order->update([
                    'status' => 'cancelado',
                    'total' => 0,
                    'amount_pending' => 0,
                ]);
                $order->table?->update(['status' => 'libre']);
            } else {
                $newTotal = $remainingDetails->sum('subtotal');
                $order->update([
                    'total' => $newTotal,
                    'amount_pending' => $newTotal,
                ]);
            }

            return true;
        });

        if (!$wasCancelled) {
            return;
        }

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
