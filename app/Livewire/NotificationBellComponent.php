<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationBellComponent extends Component
{
    public array $knownUnreadIds = [];

    public function mount(): void
    {
        $this->knownUnreadIds = auth()->user()?->unreadNotifications()->pluck('id')->all() ?? [];
    }

    public function refreshNotifications(): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        $unreadNotifications = $user->unreadNotifications()->latest()->get();
        $newNotifications = $unreadNotifications->whereNotIn('id', $this->knownUnreadIds);

        foreach ($newNotifications as $notification) {
            $this->dispatch('waiter-order-ready', message: $notification->data['message'] ?? 'Pedido listo para entregar.');
        }

        $this->knownUnreadIds = $unreadNotifications->pluck('id')->all();
    }

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.notification-bell-component', [
            'notifications' => $user ? $user->notifications()->latest()->limit(10)->get() : collect(),
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
