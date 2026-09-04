<div class="relative" x-data="{ open: false }" @click.outside="open = false" wire:poll.5s.keep-alive="refreshNotifications">
    <button type="button" @click="open = !open" class="relative rounded-lg p-2 text-slate-500 transition-colors hover:bg-orange-50 hover:text-orange-600" aria-label="Notificaciones">
        <i class="fa-regular fa-bell text-lg"></i>
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-orange-600 px-1 text-center text-[9px] font-black leading-4 text-white">{{ $unreadCount }}</span>
        @endif
    </button>

    <section x-cloak x-show="open" x-transition class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
        <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Notificaciones</p>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" type="button" class="text-[10px] font-bold uppercase text-orange-600 hover:text-orange-700">Marcar leídas</button>
            @endif
        </header>
        <div class="max-h-80 overflow-y-auto">
            @forelse ($notifications as $notification)
                <a href="{{ route('orders.manage', $notification->data['order_id']) }}" class="block border-b border-slate-100 px-4 py-3 transition-colors hover:bg-orange-50 {{ $notification->read_at ? '' : 'bg-orange-50/40' }}">
                    <p class="text-xs font-bold text-slate-700">{{ $notification->data['message'] }}</p>
                    <p class="mt-1 text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-xs text-slate-400">No tienes notificaciones.</p>
            @endforelse
        </div>
    </section>
</div>
