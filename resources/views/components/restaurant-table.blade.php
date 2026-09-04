@props([
    'table',
    'order' => null,
    'selected' => false,
    'configuration' => false,
    'preview' => false,
    'width' => null,
    'height' => null,
    'capacity' => null,
    'orientation' => null,
])

@php
    $tableWidth = max(80, (int) ($width ?? $table->table_width ?? 118));
    $tableHeight = max(80, (int) ($height ?? $table->table_height ?? 118));
    $tableCapacity = max(1, (int) ($capacity ?? $table->capacity ?? 1));
    $tableOrientation = $orientation ?? $table->orientation ?? 'square';
    $displayCapacity = min($tableCapacity, 12);
    $chairCounts = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
    $remainingChairs = $displayCapacity;

    // Allocate chair pairs to the longer sides first while keeping both axes symmetric.
    while ($remainingChairs >= 2) {
        $horizontalSpace = $tableWidth / ($chairCounts['top'] + 1);
        $verticalSpace = $tableHeight / ($chairCounts['left'] + 1);

        if ($horizontalSpace >= $verticalSpace) {
            $chairCounts['top']++;
            $chairCounts['bottom']++;
        } else {
            $chairCounts['left']++;
            $chairCounts['right']++;
        }

        $remainingChairs -= 2;
    }

    if ($remainingChairs === 1) {
        $chairCounts[$tableWidth >= $tableHeight ? 'top' : 'left']++;
    }

    $chairs = [];
    foreach ($chairCounts as $side => $count) {
        for ($index = 1; $index <= $count; $index++) {
            $chairs[] = ['side' => $side, 'position' => round(($index / ($count + 1)) * 100, 2)];
        }
    }

    $label = preg_replace('/^mesa\s+(\d+)$/iu', 'T$1', trim($table->name));
    $elapsedMinutes = $order?->created_at?->diffInMinutes(now()) ?? 0;
    $elapsed = sprintf('%02d:%02d', intdiv($elapsedMinutes, 60), $elapsedMinutes % 60);
    $guestCount = $order ? $tableCapacity : 0;
@endphp

@if ($preview)
    <div class="restaurant-table restaurant-table--preview" data-status="{{ $table->status }}" data-orientation="{{ $tableOrientation }}"
        style="--restaurant-table-width: {{ $tableWidth }}px; --restaurant-table-height: {{ $tableHeight }}px;">
@elseif ($configuration)
    <div wire:key="table-{{ $table->id }}" data-id="{{ $table->id }}" data-x="{{ $table->x_pos }}" data-y="{{ $table->y_pos }}"
        data-status="{{ $table->status }}" data-orientation="{{ $tableOrientation }}" aria-label="{{ $table->name }}"
        class="restaurant-table restaurant-table--canvas restaurant-table--configuration draggable-table"
        style="--restaurant-table-x: {{ (int) $table->x_pos }}px; --restaurant-table-y: {{ (int) $table->y_pos }}px; --restaurant-table-width: {{ $tableWidth }}px; --restaurant-table-height: {{ $tableHeight }}px;">
@else
    <button type="button" wire:key="table-{{ $table->id }}" data-id="{{ $table->id }}" data-x="{{ $table->x_pos }}" data-y="{{ $table->y_pos }}"
        data-status="{{ $table->status }}" data-orientation="{{ $tableOrientation }}" aria-label="{{ $table->name }}"
        aria-pressed="{{ $selected ? 'true' : 'false' }}"
        wire:click="selectTable({{ $table->id }})"
        class="restaurant-table restaurant-table--canvas draggable-table"
        style="--restaurant-table-x: {{ (int) $table->x_pos }}px; --restaurant-table-y: {{ (int) $table->y_pos }}px; --restaurant-table-width: {{ $tableWidth }}px; --restaurant-table-height: {{ $tableHeight }}px;">
@endif
        <span class="restaurant-table__frame">
            @foreach ($chairs as $chair)
                <span class="restaurant-table__chair" data-side="{{ $chair['side'] }}" style="--chair-position: {{ $chair['position'] }}%;"></span>
            @endforeach

            <span class="restaurant-table__body" data-shape="{{ $table->shape }}">
                <span class="restaurant-table__label">{{ $label }}</span>

                @if ($configuration && !$preview)
                    <span class="restaurant-table__drag-handle drag-handle" title="Arrastrar mesa"><i class="fa-solid fa-grip"></i></span>
                    <button type="button" wire:click.stop="edit({{ $table->id }})" class="restaurant-table__edit-control" title="Editar mesa"><i class="fa-solid fa-pen"></i></button>
                    @can('mesas.eliminar')
                        <button type="button" wire:click.stop="deleteConfirm({{ $table->id }})" class="restaurant-table__delete-control" title="Eliminar mesa"><i class="fa-solid fa-trash-can"></i></button>
                    @endcan
                @endif

                @if ($order)
                    <span class="restaurant-table__badges">
                        <span class="restaurant-table__badge"><i class="fa-solid fa-coins"></i>S/ {{ number_format($order->amount_pending, 0) }}</span>
                        <span class="restaurant-table__badge"><i class="fa-regular fa-clock"></i>{{ $elapsed }}</span>
                        <span class="restaurant-table__badge"><i class="fa-regular fa-user"></i>{{ $guestCount }}/{{ $tableCapacity }}</span>
                    </span>
                @endif
            </span>
        </span>
@if ($preview || $configuration)
    </div>
@else
    </button>
@endif
