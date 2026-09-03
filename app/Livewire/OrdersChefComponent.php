<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderCorrection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersChefComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public array $knownKitchenDetailIds = [];
    public array $knownKitchenCorrectionIds = [];

    public function mount(): void
    {
        $this->knownKitchenDetailIds = $this->kitchenDetailIds();
        $this->knownKitchenCorrectionIds = $this->kitchenCorrectionIds();
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

        $currentCorrectionIds = $this->kitchenCorrectionIds();
        $newCorrectionIds = array_values(array_diff($currentCorrectionIds, $this->knownKitchenCorrectionIds));

        if ($newCorrectionIds !== []) {
            $this->resetPage();
            $this->dispatch('kitchen-correction-received', correctionIds: $newCorrectionIds);
        }

        $this->knownKitchenDetailIds = $currentDetailIds;
        $this->knownKitchenCorrectionIds = $currentCorrectionIds;
    }

    private function kitchenDetailIds(): array
    {
        return $this->applyStationScope(OrderDetail::query())
            ->where('requires_kitchen', true)
            ->where('cooking_status', '!=', 'cancelled')
            ->whereHas('order', fn ($query) => $query->where('status', 'abierto'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function visibleStationIds(): array
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('admin')) {
            return [];
        }

        return $user->preparationStations()->pluck('preparation_stations.id')->map(fn ($id) => (int) $id)->all();
    }

    private function applyStationScope($query)
    {
        $stationIds = $this->visibleStationIds();

        return $stationIds === []
            ? $query
            : $query->whereIn('preparation_station_id', $stationIds);
    }

    private function kitchenCorrectionIds(): array
    {
        return $this->applyStationScope(OrderCorrection::query())
            ->whereNull('acknowledged_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function markDetailAsReady($detailId)
    {
        $updated = $this->applyStationScope(OrderDetail::query())
            ->whereKey($detailId)
            ->whereIn('cooking_status', ['pending', 'in_progress'])
            ->whereHas('order', fn ($query) => $query->where('status', 'abierto'))
            ->update(['cooking_status' => 'ready']);

        if ($updated === 1) {

            $this->dispatch('swal', [
                'title' => '¡Listo!',
                'text' => 'El producto ha sido marcado como listo para servir.',
                'icon' => 'success'
            ]);
        }
    }

    public function markOrderAsReady($orderId): void
    {
        $updated = $this->applyStationScope(OrderDetail::query())
            ->where('order_id', $orderId)
            ->where('requires_kitchen', true)
            ->whereIn('cooking_status', ['pending', 'in_progress'])
            ->whereHas('order', fn ($query) => $query->where('status', 'abierto'))
            ->update(['cooking_status' => 'ready']);

        if ($updated > 0) {
            $this->dispatch('swal', [
                'title' => 'Pedido listo',
                'text' => 'Todos los platos pendientes fueron marcados como listos para entregar.',
                'icon' => 'success',
                'timer' => 1500,
            ]);
        }
    }

    public function acknowledgeCorrection($correctionId): void
    {
        $result = DB::transaction(function () use ($correctionId) {
            $correction = $this->applyStationScope(OrderCorrection::query())
                ->whereKey($correctionId)
                ->whereNull('acknowledged_at')
                ->lockForUpdate()
                ->first();

            if (!$correction) {
                return 'missing';
            }

            $earlierCorrection = OrderCorrection::query()
                ->where('order_detail_id', $correction->order_detail_id)
                ->whereNull('acknowledged_at')
                ->where('id', '<', $correction->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($earlierCorrection) {
                return 'earlier';
            }

            $correction->update(['acknowledged_at' => now()]);

            return 'acknowledged';
        });

        if ($result === 'missing') {
            return;
        }

        if ($result === 'earlier') {
            $this->dispatch('swal', [
                'title' => 'Corrección anterior pendiente',
                'text' => 'Confirma primero las correcciones anteriores de este plato.',
                'icon' => 'warning',
            ]);
            return;
        }

        $this->dispatch('swal', [
            'title' => 'Corrección confirmada',
            'text' => 'La corrección fue revisada por cocina.',
            'icon' => 'success',
            'timer' => 1500,
        ]);
    }

    public function render()
{
        $stationIds = $this->visibleStationIds();
        $orders = Order::with([
            'table',
            'details' => function ($q) use ($stationIds) {
                $q->where('requires_kitchen', true)
                  ->whereNotIn('cooking_status', ['cancelled', 'served'])
                  ->when($stationIds !== [], fn ($q) => $q->whereIn('preparation_station_id', $stationIds))
                  ->with(['product', 'preparationStation']);
            },
            'user'
        ])->where('status', 'abierto') 
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        
        ->when($this->search, function ($query) {
            $query->where(function ($query) {
                $query->where('customer_name', 'like', '%' . $this->search . '%')
                    ->orWhere('delivery_address', 'like', '%' . $this->search . '%')
                    ->orWhereHas('table', fn ($table) => $table->where('name', 'like', '%' . $this->search . '%'));
            });
        })
        
        ->whereHas('details', function ($q) use ($stationIds) {
            $q->where('requires_kitchen', true)
              ->whereNotIn('cooking_status', ['cancelled', 'served'])
              ->when($stationIds !== [], fn ($q) => $q->whereIn('preparation_station_id', $stationIds));
        })
        ->orderByDesc('created_at')
        ->paginate(12);

    $corrections = $this->applyStationScope(OrderCorrection::with(['order.table', 'preparationStation']))
        ->whereNull('acknowledged_at')
        ->oldest()
        ->get();

    return view('livewire.orders-chef-component', [
        'orders' => $orders,
        'corrections' => $corrections,
    ]);
}
}
