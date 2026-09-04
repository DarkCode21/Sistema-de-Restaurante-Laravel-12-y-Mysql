<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderReadyForService extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'order-ready-for-service';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'service_label' => $this->order->service_label,
            'message' => "{$this->order->service_label}: pedido listo para entregar.",
        ];
    }
}
