<?php

namespace App\Livewire;

use App\Models\Ingredient;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class IngredientComponent extends Component
{
    use WithPagination;

    public $ingredient_id = null;
    public $name = '';
    public $unit = 'unit';
    public $stock = 0;
    public $minimum_stock = 0;
    public $search = '';
    public $isOpen = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $ingredients = Ingredient::query()
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.ingredient-component', ['ingredients' => $ingredients, 'units' => Ingredient::UNITS]);
    }

    public function create(): void
    {
        $this->resetInputFields();
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        $ingredient = Ingredient::findOrFail($id);
        $this->ingredient_id = $ingredient->id;
        $this->name = $ingredient->name;
        $this->unit = $ingredient->unit;
        $this->stock = $ingredient->stock;
        $this->minimum_stock = $ingredient->minimum_stock;
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function store(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('ingredients', 'name')->ignore($this->ingredient_id)],
            'unit' => ['required', Rule::in(Ingredient::UNITS)],
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        Ingredient::updateOrCreate(['id' => $this->ingredient_id], [
            'name' => $this->name,
            'unit' => $this->unit,
            'stock' => $this->stock,
            'minimum_stock' => $this->minimum_stock,
        ]);

        $this->dispatch('swal', [
            'title' => $this->ingredient_id ? 'Insumo actualizado' : 'Insumo creado',
            'text' => 'El inventario se guardó correctamente.',
            'icon' => 'success',
        ]);
        $this->closeModal();
    }

    public function deleteConfirm(int $id): void
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy(int $id): void
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($ingredient->products()->exists() || $ingredient->usages()->exists()) {
            $this->dispatch('swal', [
                'title' => 'Insumo en uso',
                'text' => 'Quita el insumo de las recetas antes de eliminarlo.',
                'icon' => 'warning',
            ]);
            return;
        }

        $ingredient->delete();
    }

    private function resetInputFields(): void
    {
        $this->reset(['ingredient_id', 'name', 'stock', 'minimum_stock']);
        $this->unit = 'unit';
        $this->resetValidation();
    }
}
