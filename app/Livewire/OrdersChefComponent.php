<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderDetail;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersChefComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';

    public function markDetailAsReady($detailId)
    {
        $detail = OrderDetail::find($detailId);

        if ($detail) {
            $detail->update([
                'cooking_status' => 'ready'
            ]);

            $this->dispatch('swal', [
                'title' => '¡Listo!',
                'text' => 'El producto ha sido marcado como listo para servir.',
                'icon' => 'success'
            ]);
        }
    }

    public function render()
{
    $orders = Order::with([
            'table',
            'details' => function ($q) {
                $q->where('requires_kitchen', true)
                  ->where('cooking_status', '!=', 'cancelled')
                  // OPCIONAL: ->where('cooking_status', '!=', 'delivered') 
                  ->with('product');
            },
            'user'
        ])->where('status', 'abierto') 
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        
        ->whereHas('table', function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%');
        })
        
        ->whereHas('details', function ($q) {
            $q->where('requires_kitchen', true)
              ->where('cooking_status', '!=', 'cancelled');
              // Si quieres que al terminar de cocinar todo la tarjeta desaparezca, añade:
              // ->where('cooking_status', '!=', 'ready') 
        })
        ->orderBy('created_at', 'asc') 
        ->paginate(12);

    return view('livewire.orders-chef-component', [
        'orders' => $orders
    ]);
}
}
