<?php

namespace App\Livewire;

use App\Models\DiningArea;
use App\Models\RestaurantFloor;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class TableComponent extends Component
{
    private const CANVAS_WIDTH = 1200;
    private const CANVAS_HEIGHT = 820;
    private const GRID_SIZE = 20;
    private const TABLE_CLEARANCE = 48;

    public $name = '';
    public $capacity = '';
    public $status = 'libre';
    public $restaurant_floor_id = null;
    public $dining_area_id = null;
    public $shape = 'square';
    public $layout_width = 1;
    public $layout_height = 1;
    public $table_width = 118;
    public $table_height = 118;
    public $orientation = 'square';
    public $showPhysicalDimensions = false;
    public $layout_x = null;
    public $layout_y = null;
    public $table_id = null;
    public $search = '';
    public $statusFilter = '';
    public $selectedFloorId = null;
    public $selectedAreaId = '';
    public $selectedTableId = null;
    public $layoutEditor = false;
    public $isOpen = false;

    public function mount(): void
    {
        $this->selectedFloorId = RestaurantFloor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('id');
    }

    public function updatedSelectedFloorId(): void
    {
        $this->selectedAreaId = '';
        $this->selectedTableId = null;
    }

    public function updatedRestaurantFloorId(): void
    {
        $this->dining_area_id = DiningArea::query()
            ->where('restaurant_floor_id', $this->restaurant_floor_id)
            ->orderBy('sort_order')
            ->value('id');
    }

    public function updatedShape(): void
    {
        if ($this->shape === 'rectangle') {
            $this->orientation = 'horizontal';
        } else {
            $this->orientation = 'square';
        }

        $this->applyDimensionPreset();
    }

    public function updatedOrientation(): void
    {
        if ($this->shape === 'rectangle') {
            $this->applyDimensionPreset();
        }
    }

    public function create(): void
    {
        $this->resetInputFields();
        $this->restaurant_floor_id = $this->selectedFloorId ?: RestaurantFloor::query()->orderBy('sort_order')->value('id');
        $this->updatedRestaurantFloorId();
        $this->isOpen = true;
    }

    public function openLayoutEditor(): void
    {
        $this->ensureCanConfigureLayout();
        $this->layoutEditor = true;
        $this->selectedTableId = null;
    }

    public function closeLayoutEditor(): void
    {
        $this->layoutEditor = false;
    }

    public function selectTable(int $tableId): void
    {
        $this->selectedTableId = $this->selectedTableId === $tableId ? null : $tableId;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function updatePosition($id, $x, $y): array
    {
        $this->ensureCanConfigureLayout();

        $table = Table::findOrFail($id);
        [$x, $y] = $this->normalizePosition($x, $y, $table->table_width, $table->table_height);

        if ($this->layoutCollides($table->restaurant_floor_id, $table->id, $x, $y, $table->table_width, $table->table_height)) {
            $this->dispatch('swal', [
                'title' => 'Espacio ocupado',
                'text' => 'Esa posición invade otra mesa. Elige un espacio libre.',
                'icon' => 'warning',
            ]);

            return ['accepted' => false, 'x' => (int) $table->x_pos, 'y' => (int) $table->y_pos];
        }

        $table->update(['x_pos' => $x, 'y_pos' => $y]);

        return ['accepted' => true, 'x' => $x, 'y' => $y];
    }

    public function savePositions(array $positions): array
    {
        $this->ensureCanConfigureLayout();

        $saved = DB::transaction(function () use ($positions) {
            $tables = Table::query()
                ->where('restaurant_floor_id', $this->selectedFloorId)
                ->lockForUpdate()
                ->get();
            $layout = $tables->mapWithKeys(fn (Table $table) => [$table->id => [
                'x' => (int) $table->x_pos,
                'y' => (int) $table->y_pos,
                'width' => (int) $table->table_width,
                'height' => (int) $table->table_height,
            ]]);

            foreach ($positions as $position) {
                if (!is_array($position) || !isset($position['id'], $layout[$position['id']])) {
                    continue;
                }

                $tablePosition = $layout->get($position['id']);
                [$x, $y] = $this->normalizePosition(
                    $position['x'] ?? 0,
                    $position['y'] ?? 0,
                    $tablePosition['width'],
                    $tablePosition['height'],
                );
                $layout->put($position['id'], [...$tablePosition, 'x' => $x, 'y' => $y]);
            }

            $positionsToCheck = $layout->values();
            for ($index = 0; $index < $positionsToCheck->count(); $index++) {
                for ($otherIndex = $index + 1; $otherIndex < $positionsToCheck->count(); $otherIndex++) {
                    if ($this->positionsCollide($positionsToCheck[$index], $positionsToCheck[$otherIndex])) {
                        return false;
                    }
                }
            }

            foreach ($tables as $table) {
                $position = $layout[$table->id];
                if ((int) $table->x_pos !== $position['x'] || (int) $table->y_pos !== $position['y']) {
                    $table->update(['x_pos' => $position['x'], 'y_pos' => $position['y']]);
                }
            }

            return true;
        });

        $persistedPositions = Table::query()
            ->where('restaurant_floor_id', $this->selectedFloorId)
            ->get(['id', 'x_pos', 'y_pos'])
            ->map(fn (Table $table) => ['id' => $table->id, 'x' => (int) $table->x_pos, 'y' => (int) $table->y_pos])
            ->values()
            ->all();

        if (!$saved) {
            $this->dispatch('swal', [
                'title' => 'Distribución no guardada',
                'text' => 'Dos mesas invaden el mismo espacio.',
                'icon' => 'warning',
            ]);

            return ['accepted' => false, 'positions' => $persistedPositions];
        }

        $this->dispatch('swal', [
            'title' => 'Distribución guardada',
            'text' => 'Las posiciones del plano fueron actualizadas.',
            'icon' => 'success',
        ]);

        return ['accepted' => true, 'positions' => $persistedPositions];
    }

    public function store(): void
    {
        $this->validate([
            'name' => ['required', 'min:2', 'max:50', Rule::unique('tables', 'name')->ignore($this->table_id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:99'],
            'status' => ['required', Rule::in(['libre', 'ocupada', 'reservada'])],
            'restaurant_floor_id' => ['required', 'exists:restaurant_floors,id'],
            'dining_area_id' => ['required', 'exists:dining_areas,id'],
            'shape' => ['required', Rule::in(['round', 'square', 'rectangle'])],
            'table_width' => ['required', 'integer', 'min:80', 'max:360'],
            'table_height' => ['required', 'integer', 'min:80', 'max:360'],
            'orientation' => ['required', Rule::in(['square', 'horizontal', 'vertical'])],
            'layout_x' => ['nullable', 'numeric', 'min:0'],
            'layout_y' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (!DiningArea::query()
            ->whereKey($this->dining_area_id)
            ->where('restaurant_floor_id', $this->restaurant_floor_id)
            ->exists()) {
            $this->addError('dining_area_id', 'La zona debe pertenecer al piso seleccionado.');
            return;
        }

        $existing = $this->table_id ? Table::findOrFail($this->table_id) : null;
        if (!$existing || (int) $existing->restaurant_floor_id !== (int) $this->restaurant_floor_id) {
            [$x, $y] = $this->nextAvailablePosition($this->restaurant_floor_id, $this->table_width, $this->table_height);
        } else {
            [$x, $y] = $this->normalizePosition($this->layout_x, $this->layout_y, $this->table_width, $this->table_height);
        }

        if ($this->layoutCollides($this->restaurant_floor_id, $this->table_id, $x, $y, $this->table_width, $this->table_height)) {
            $this->addError('layout', 'El tamaño de la mesa invade otra mesa. Reduce su tamaño o muévela primero.');
            return;
        }

        Table::updateOrCreate(
            ['id' => $this->table_id],
            [
                'name' => trim($this->name),
                'capacity' => $this->capacity,
                'status' => $this->status,
                'restaurant_floor_id' => $this->restaurant_floor_id,
                'dining_area_id' => $this->dining_area_id,
                'shape' => $this->shape,
                'layout_width' => $this->table_width > 200 ? 2 : 1,
                'layout_height' => $this->table_height > 200 ? 2 : 1,
                'table_width' => $this->table_width,
                'table_height' => $this->table_height,
                'orientation' => $this->orientation,
                'x_pos' => $x,
                'y_pos' => $y,
            ]
        );

        $this->dispatch('swal', [
            'title' => $this->table_id ? 'Mesa actualizada' : 'Mesa creada',
            'text' => 'La mesa quedó ubicada en un espacio libre del plano.',
            'icon' => 'success',
        ]);

        $this->closeModal();
    }

    public function edit($id): void
    {
        $table = Table::findOrFail($id);
        $this->table_id = $table->id;
        $this->name = $table->name;
        $this->capacity = $table->capacity;
        $this->status = $table->status;
        $this->restaurant_floor_id = $table->restaurant_floor_id;
        $this->dining_area_id = $table->dining_area_id;
        $this->shape = $table->shape;
        $this->table_width = $table->table_width;
        $this->table_height = $table->table_height;
        $this->orientation = $table->orientation;
        $this->showPhysicalDimensions = false;
        $this->layout_x = $table->x_pos;
        $this->layout_y = $table->y_pos;
        $this->isOpen = true;
    }

    public function deleteConfirm($id): void
    {
        $this->ensureCanDeleteTable();
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id): void
    {
        $this->ensureCanDeleteTable();
        Table::findOrFail($id)->delete();
        $this->dispatch('swal', [
            'title' => 'Mesa eliminada',
            'text' => 'La mesa fue retirada del plano.',
            'icon' => 'success',
        ]);
    }

    public function render()
    {
        $floors = RestaurantFloor::query()
            ->with(['areas' => fn ($query) => $query->orderBy('sort_order')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $areas = $floors->firstWhere('id', (int) $this->selectedFloorId)?->areas ?? collect();
        $tableAreas = DiningArea::query()
            ->where('restaurant_floor_id', $this->restaurant_floor_id)
            ->orderBy('sort_order')
            ->get();
        $defaultFloorId = $floors->first()?->id;
        $tables = Table::query()
            ->with([
                'diningArea',
                'orders' => fn ($query) => $query->where('status', 'abierto')
                    ->whereHas('details', fn ($details) => $details->where('cooking_status', '!=', 'cancelled'))
                    ->with('details'),
            ])
            ->when($this->selectedFloorId, function ($query) use ($defaultFloorId) {
                $query->where(function ($query) use ($defaultFloorId) {
                    $query->where('restaurant_floor_id', $this->selectedFloorId);

                    if ((int) $this->selectedFloorId === (int) $defaultFloorId) {
                        $query->orWhereNull('restaurant_floor_id');
                    }
                });
            })
            ->when($this->selectedAreaId, fn ($query) => $query->where('dining_area_id', $this->selectedAreaId))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('orders', fn ($orders) => $orders
                            ->where('status', 'abierto')
                            ->where('customer_name', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('y_pos')
            ->orderBy('x_pos')
            ->get();

        $selectedTable = $tables->firstWhere('id', (int) $this->selectedTableId);

        return view('livewire.table-component', compact('floors', 'areas', 'tableAreas', 'tables', 'selectedTable'));
    }

    private function resetInputFields(): void
    {
        $this->reset(['name', 'capacity', 'status', 'restaurant_floor_id', 'dining_area_id', 'shape', 'layout_width', 'layout_height', 'table_width', 'table_height', 'orientation', 'showPhysicalDimensions', 'layout_x', 'layout_y', 'table_id']);
        $this->status = 'libre';
        $this->shape = 'square';
        $this->layout_width = 1;
        $this->layout_height = 1;
        $this->table_width = 118;
        $this->table_height = 118;
        $this->orientation = 'square';
        $this->resetValidation();
    }

    private function ensureCanConfigureLayout(): void
    {
        abort_unless(auth()->user()?->can('mesas.editar'), 403);
    }

    private function ensureCanDeleteTable(): void
    {
        abort_unless(auth()->user()?->can('mesas.eliminar'), 403);
    }

    private function applyDimensionPreset(): void
    {
        if ($this->shape !== 'rectangle') {
            $this->table_width = 118;
            $this->table_height = 118;
            return;
        }

        if ($this->orientation === 'vertical') {
            $this->table_width = 130;
            $this->table_height = 280;
            return;
        }

        $this->table_width = 250;
        $this->table_height = 130;
    }

    private function nextAvailablePosition(int $floorId, int $width, int $height): array
    {
        $maxX = self::CANVAS_WIDTH - (max(1, (int) $width) + self::TABLE_CLEARANCE);
        $maxY = self::CANVAS_HEIGHT - (max(1, (int) $height) + self::TABLE_CLEARANCE);

        for ($y = self::GRID_SIZE; $y <= $maxY; $y += self::GRID_SIZE) {
            for ($x = self::GRID_SIZE; $x <= $maxX; $x += self::GRID_SIZE) {
                if (!$this->layoutCollides($floorId, null, $x, $y, $width, $height)) {
                    return [$x, $y];
                }
            }
        }

        return [self::GRID_SIZE, self::GRID_SIZE];
    }

    private function normalizePosition($x, $y, $width, $height): array
    {
        $maxX = self::CANVAS_WIDTH - (max(1, (int) $width) + self::TABLE_CLEARANCE);
        $maxY = self::CANVAS_HEIGHT - (max(1, (int) $height) + self::TABLE_CLEARANCE);
        $x = max(0, min($maxX, (int) round((float) $x)));
        $y = max(0, min($maxY, (int) round((float) $y)));

        return [$x, $y];
    }

    private function layoutCollides($floorId, $ignoreId, $x, $y, $width, $height): bool
    {
        $width = max(1, (int) $width) + self::TABLE_CLEARANCE;
        $height = max(1, (int) $height) + self::TABLE_CLEARANCE;

        return Table::query()
            ->where('restaurant_floor_id', $floorId)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get(['x_pos', 'y_pos', 'table_width', 'table_height'])
            ->contains(fn (Table $table) => $this->positionsCollide(
                ['x' => $x, 'y' => $y, 'width' => $width - self::TABLE_CLEARANCE, 'height' => $height - self::TABLE_CLEARANCE],
                ['x' => $table->x_pos, 'y' => $table->y_pos, 'width' => $table->table_width, 'height' => $table->table_height],
            ));
    }

    private function positionsCollide(array $first, array $second): bool
    {
        $firstWidth = max(1, (int) $first['width']) + self::TABLE_CLEARANCE;
        $firstHeight = max(1, (int) $first['height']) + self::TABLE_CLEARANCE;
        $secondWidth = max(1, (int) $second['width']) + self::TABLE_CLEARANCE;
        $secondHeight = max(1, (int) $second['height']) + self::TABLE_CLEARANCE;

        return $first['x'] < $second['x'] + $secondWidth
            && $first['x'] + $firstWidth > $second['x']
            && $first['y'] < $second['y'] + $secondHeight
            && $first['y'] + $firstHeight > $second['y'];
    }

}
