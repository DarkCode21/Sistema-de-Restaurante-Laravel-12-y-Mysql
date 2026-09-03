<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderCorrection;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\WithPagination;

class OrdersIndexComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public array $knownReadyOrderIds = [];

    public function mount(): void
    {
        $this->knownReadyOrderIds = $this->readyOrders()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function refreshReadyOrderAlerts(): void
    {
        $readyOrders = $this->readyOrders();
        $newReadyOrders = $readyOrders->filter(
            fn (Order $order) => !in_array($order->id, $this->knownReadyOrderIds, true)
        );

        if ($newReadyOrders->isNotEmpty()) {
            $this->dispatch(
                'order-ready-for-service',
                orderId: $newReadyOrders->pluck('id')->all(),
                tableName: $newReadyOrders->pluck('service_label')->join(', '),
            );
        }

        $this->knownReadyOrderIds = $readyOrders->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function readyOrders()
    {
        return Order::query()
            ->with('table')
            ->where('status', 'abierto')
            ->whereHas('details', fn ($query) => $query
                ->where('requires_kitchen', true)
                ->where('cooking_status', '!=', 'cancelled'))
            ->whereDoesntHave('details', fn ($query) => $query
                ->where('requires_kitchen', true)
                ->where('cooking_status', '!=', 'cancelled')
                ->whereNotIn('cooking_status', ['ready', 'served']))
            ->get();
    }

    public function markDetailAsServed($detailId): void
    {
        $updated = DB::transaction(function () use ($detailId) {
            $detail = OrderDetail::query()
                ->whereKey($detailId)
                ->whereHas('order', fn ($query) => $query->where('status', 'abierto'))
                ->lockForUpdate()
                ->first();

            if (!$detail) {
                return false;
            }

            $components = OrderDetail::query()
                ->where('parent_detail_id', $detail->id)
                ->lockForUpdate()
                ->get();

            if ($components->isNotEmpty()) {
                if ($components->where('requires_kitchen', true)
                    ->contains(fn (OrderDetail $component) => !in_array($component->cooking_status, ['ready', 'served'], true))) {
                    return false;
                }

                $components->where('cooking_status', 'ready')->each->update(['cooking_status' => 'served']);
                $detail->update(['cooking_status' => 'served']);
                return true;
            }

            if ($detail->cooking_status === 'ready' || (!$detail->requires_kitchen && !in_array($detail->cooking_status, ['served', 'cancelled'], true))) {
                $detail->update(['cooking_status' => 'served']);
                return true;
            }

            return false;
        });

        if ($updated !== 1) {
            $this->dispatch('swal', [
                'title' => 'Aún no disponible',
                'text' => 'Solo se pueden entregar productos listos o que no requieren cocina.',
                'icon' => 'warning',
                'timer' => 1500,
            ]);
            return;
        }

        $this->dispatch('swal', [
            'title' => '¡Entregado!',
            'text' => 'El producto fue marcado como entregado.',
            'icon' => 'success',
            'timer' => 1000,
        ]);
    }

    public function cancelarDetalle($detailId): void
    {
        $result = DB::transaction(function () use ($detailId) {
            $currentDetail = OrderDetail::query()
                ->whereKey($detailId)
                ->first();

            if (!$currentDetail) {
                return null;
            }

            $order = Order::query()
                ->whereKey($currentDetail->order_id)
                ->where('status', 'abierto')
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return null;
            }

            $detail = OrderDetail::query()
                ->whereKey($detailId)
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$detail || in_array($detail->cooking_status, ['cancelled', 'served'], true)) {
                return null;
            }

            $wasSent = $detail->is_printed || $detail->cooking_status !== 'pending';

            $detail->update([
                'cooking_status' => 'cancelled'
            ]);

            $detail->restoreInventory();

            $correction = $wasSent ? OrderCorrection::record($detail, 'cancel') : null;

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
                $newTotal = (float) $remainingDetails->sum('subtotal') + (float) $remainingDetails->sum('tax');
                $order->update([
                    'total' => $newTotal,
                    'amount_pending' => $newTotal,
                ]);
            }

            return [
                'detail_id' => $detail->id,
                'order_id' => $order->id,
                'requires_kitchen' => (bool) $detail->requires_kitchen,
                'was_sent' => $wasSent,
                'correction_id' => $correction?->id,
            ];
        });

        if (!$result) {
            return;
        }

        if ($result['was_sent']) {
            $this->dispatchKitchenCorrection($result);
        }

        $this->dispatch('swal', [
            'title' => '¡Cancelado!',
            'text' => $result['was_sent']
                ? 'El producto fue cancelado, el stock fue devuelto y se envió la corrección.'
                : 'El producto fue cancelado y el stock fue devuelto.',
            'icon' => 'error'
        ]);
    }

    private function dispatchKitchenCorrection(array $result): void
    {
        $setting = Setting::first();
        $separateOrders = (bool) ($setting?->separate_orders);

        $this->dispatch('auto-print-kitchen-correction', [[
            'url' => URL::temporarySignedRoute(
                'orders.kitchen-print',
                now()->addMinutes(5),
                [
                    'id' => $result['order_id'],
                    'correction' => true,
                    'correction_ids' => [$result['correction_id']],
                    ...($separateOrders
                        ? ['requires_kitchen' => $result['requires_kitchen']]
                        : []),
                ],
            ),
            'printer_name' => $separateOrders && $result['requires_kitchen']
                ? $setting?->kitchen_printer_name
                : $setting?->printer_name,
        ]]);
    }

    public function render()
    {
        $orders = Order::with([
            'table',
            'details' => function ($q) {
                $q->whereNull('parent_detail_id')
                    ->where('cooking_status', '!=', 'cancelled')
                    ->with(['product', 'components']);
            },
            'user'
        ])
            ->where('status', 'abierto')
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('customer_name', 'like', '%' . $this->search . '%')
                        ->orWhere('delivery_address', 'like', '%' . $this->search . '%')
                        ->orWhereHas('table', fn ($table) => $table->where('name', 'like', '%' . $this->search . '%'));
                });
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
