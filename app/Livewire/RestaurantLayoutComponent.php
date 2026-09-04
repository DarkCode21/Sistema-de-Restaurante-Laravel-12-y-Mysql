<?php

namespace App\Livewire;

use App\Models\DiningArea;
use App\Models\RestaurantFloor;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RestaurantLayoutComponent extends Component
{
    public $floor_name = '';
    public $restaurant_floor_id = '';
    public $area_name = '';
    public $area_type = 'salon';

    public function mount(): void
    {
        $this->restaurant_floor_id = RestaurantFloor::query()->where('is_active', true)->orderBy('sort_order')->value('id');
    }

    public function addFloor(): void
    {
        $this->ensureCanConfigureLayout();
        $this->validate(['floor_name' => ['required', 'string', 'max:50', Rule::unique('restaurant_floors', 'name')]]);

        $floor = RestaurantFloor::create([
            'name' => trim($this->floor_name),
            'sort_order' => (int) RestaurantFloor::max('sort_order') + 1,
            'is_active' => true,
        ]);

        $this->restaurant_floor_id = $floor->id;
        $this->floor_name = '';
    }

    public function addArea(): void
    {
        $this->ensureCanConfigureLayout();
        $this->validate([
            'restaurant_floor_id' => ['required', 'exists:restaurant_floors,id'],
            'area_name' => ['required', 'string', 'max:50'],
            'area_type' => ['required', Rule::in(DiningArea::TYPES)],
        ]);

        if (DiningArea::query()
            ->where('restaurant_floor_id', $this->restaurant_floor_id)
            ->where('name', trim($this->area_name))
            ->exists()) {
            $this->addError('area_name', 'Ya existe una zona con ese nombre en este piso.');
            return;
        }

        DiningArea::create([
            'restaurant_floor_id' => $this->restaurant_floor_id,
            'name' => trim($this->area_name),
            'type' => $this->area_type,
            'color' => match ($this->area_type) {
                'terraza' => 'emerald',
                'barra' => 'violet',
                'privado' => 'sky',
                default => 'orange',
            },
            'sort_order' => (int) DiningArea::where('restaurant_floor_id', $this->restaurant_floor_id)->max('sort_order') + 1,
        ]);

        $this->area_name = '';
        $this->area_type = 'salon';
    }

    public function render()
    {
        $floors = RestaurantFloor::query()->with('areas')->where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.restaurant-layout-component', compact('floors'));
    }

    private function ensureCanConfigureLayout(): void
    {
        abort_unless(auth()->user()?->can('empresa.editar'), 403);
    }
}
