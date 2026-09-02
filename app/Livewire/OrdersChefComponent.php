<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersChefComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public array $knownKitchenDetailIds = [];

    public function mount(): void
    {
        $this->knownKitchenDetailIds = $this->kitchenDetailIds();
    }

    public function refreshForAlerts(): void
    {
        $currentDetailIds = $this->kitchenDetailIds();
        $newDetailIds = array_values(array_diff($currentDetailIds, $this->knownKitchenDetailIds));

        if ($newDetailIds !== []) {
            $this->resetPage();

            $orderIds = OrderDetail::query()
                ->whereIn('id', $newDetailIds)
                ->pluck('order_id')
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $this->dispatch('kitchen-order-received', orderIds: $orderIds);
        }

        $this->knownKitchenDetailIds = $currentDetailIds;
    }

    private function kitchenDetailIds(): array
    {
        return OrderDetail::query()
            ->where('requires_kitchen', true)
            ->where('cooking_status', '!=', 'cancelled')
            ->whereHas('order', fn ($query) => $query->where('status', 'abierto'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function kitchenPrintUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'orders.kitchen-print',
            now()->addMinutes(5),
            ['id' => $order->id, 'requires_kitchen' => true],
        );
    }

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
                  ->whereNotIn('cooking_status', ['cancelled', 'served'])
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
              ->whereNotIn('cooking_status', ['cancelled', 'served']);
        })
        ->orderByDesc('created_at')
        ->paginate(12);

    return view('livewire.orders-chef-component', [
        'orders' => $orders
    ]);
}
}
