<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Category;
use App\Models\PreparationStation;
use App\Models\Ingredient;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class ProductComponent extends Component
{
    use WithPagination, WithFileUploads;

    public $product_id = null;
    public $category_id = '';
    public $name = '';
    public $price = '';
    public $cost = null;
    public $tax_rate = 0;
    public $stock = '';
    public $status = 1;
    public $requires_kitchen = 0;
    public $is_combo = 0;
    public $preparation_station_id = '';
    public array $option_groups = [];
    public array $components = [];
    public array $recipe_ingredients = [];
    public $image, $old_image;

    public $search = '';
    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::with('category')->withCount('recipeIngredients')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderByDesc('status')
            ->latest()
            ->paginate(10);

        $categories = Category::orderBy('name', 'asc')->get();
        $stations = PreparationStation::orderBy('name')->get();
        $componentProducts = Product::query()
            ->where('is_combo', false)
            ->when($this->product_id, fn ($query) => $query->whereKeyNot($this->product_id))
            ->orderBy('name')
            ->get(['id', 'name', 'preparation_station_id']);
        $ingredients = Ingredient::orderBy('name')->get(['id', 'name', 'unit', 'stock']);

        return view('livewire.product-component', compact('products', 'categories', 'stations', 'componentProducts', 'ingredients'));
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields()
    {
        $this->reset(['product_id', 'category_id', 'name', 'requires_kitchen', 'is_combo', 'preparation_station_id', 'option_groups', 'components', 'recipe_ingredients', 'price', 'cost', 'tax_rate', 'stock', 'status', 'image', 'old_image']);
        $this->status = 1;
        $this->tax_rate = (float) (Setting::first()?->default_tax_rate ?? 0);
        $this->resetValidation();
    }

    public function store()
    {
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name' => ['required', 'min:2', Rule::unique('products', 'name')->ignore($this->product_id)],
            'price' => 'required|numeric',
            'cost' => 'nullable|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'stock' => $this->is_combo ? 'nullable|integer' : 'required|integer',
            'status' => 'required|boolean',
            'requires_kitchen' => 'required|boolean',
            'preparation_station_id' => 'nullable|required_if:requires_kitchen,1|exists:preparation_stations,id',
            'is_combo' => 'required|boolean',
            'components' => $this->is_combo ? 'required|array|min:1' : 'array',
            'components.*.product_id' => $this->is_combo ? 'required|distinct|exists:products,id' : 'nullable',
            'components.*.quantity' => $this->is_combo ? 'required|integer|min:1' : 'nullable',
            'recipe_ingredients' => $this->is_combo ? 'array' : 'array',
            'recipe_ingredients.*.ingredient_id' => $this->is_combo ? 'nullable' : 'required|distinct|exists:ingredients,id',
            'recipe_ingredients.*.quantity' => $this->is_combo ? 'nullable' : 'required|numeric|min:0.001',
            'option_groups' => 'array',
            'option_groups.*.name' => 'required_unless:is_combo,1|string|max:100',
            'option_groups.*.required' => 'boolean',
            'option_groups.*.values' => 'required_unless:is_combo,1|array|min:1',
            'option_groups.*.values.*.name' => 'required_unless:is_combo,1|string|max:100',
            'option_groups.*.values.*.price_adjustment' => 'required_unless:is_combo,1|numeric',
            'image' => $this->product_id ? 'nullable|image|max:2048' : 'required|image|max:2048',
        ];

        $this->validate($rules);

        $componentIds = collect($this->components)->pluck('product_id')->filter()->map(fn ($id) => (int) $id);
        if ($this->is_combo && Product::query()->whereIn('id', $componentIds)->where('is_combo', false)->count() !== $componentIds->count()) {
            $this->addError('components', 'Los componentes deben ser productos simples.');
            return;
        }

        $data = [
            'category_id' => $this->category_id,
            'name'        => $this->name,
            'price'       => $this->price,
            'cost'        => $this->is_combo || $this->cost === '' ? null : $this->cost,
            'tax_rate'    => $this->tax_rate,
            'stock'       => $this->is_combo ? 0 : $this->stock,
            'status'      => $this->status,
            'is_combo' => $this->is_combo,
            'requires_kitchen' => $this->is_combo ? false : $this->requires_kitchen,
            'preparation_station_id' => $this->is_combo || !$this->requires_kitchen ? null : $this->preparation_station_id,
        ];

        if ($this->image) {
            // Eliminar imagen anterior si existe
            if ($this->old_image) {
                Storage::disk('public')->delete($this->old_image);
            }
            $data['image'] = $this->image->store('products', 'public');
        }

        DB::transaction(function () use ($data) {
            $product = Product::updateOrCreate(['id' => $this->product_id], $data);
            $product->optionGroups()->delete();

            foreach ($this->is_combo ? [] : $this->option_groups as $group) {
                $optionGroup = $product->optionGroups()->create([
                    'name' => $group['name'],
                    'required' => (bool) ($group['required'] ?? false),
                ]);

                $optionGroup->values()->createMany($group['values']);
            }

            $product->components()->sync($this->is_combo
                ? collect($this->components)->mapWithKeys(fn ($component) => [
                    $component['product_id'] => ['quantity' => $component['quantity']],
                ])->all()
                : []);
            $product->recipeIngredients()->sync($this->is_combo
                ? []
                : collect($this->recipe_ingredients)->mapWithKeys(fn ($ingredient) => [
                    $ingredient['ingredient_id'] => ['quantity' => $ingredient['quantity']],
                ])->all());
        });

        $this->dispatch('swal', [
            'title' => $this->product_id ? '¡Actualizado!' : '¡Creado!',
            'text'  => 'El plato se guardó correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $this->product_id  = $product->id;
        $this->category_id = $product->category_id;
        $this->name        = $product->name;
        $this->price       = $this->moneyForInput($product->price);
        $this->cost        = $this->moneyForInput($product->cost);
        $this->tax_rate    = $this->numberForInput($product->tax_rate);
        $this->stock       = $product->stock;
        $this->status      = $product->status;
        $this->requires_kitchen = $product->requires_kitchen;
        $this->is_combo = $product->is_combo;
        $this->preparation_station_id = $product->preparation_station_id;
        $this->option_groups = $product->optionGroups()->with('values')->get()
            ->map(fn ($group) => [
                'name' => $group->name,
                'required' => $group->required,
                'values' => $group->values->map(fn ($value) => [
                    'name' => $value->name,
                    'price_adjustment' => $this->moneyForInput($value->price_adjustment),
                ])->all(),
            ])->all();
        $this->old_image   = $product->image;
        $this->components = $product->components()->get()
            ->map(fn ($component) => ['product_id' => $component->id, 'quantity' => $component->pivot->quantity])
            ->all();
        $this->recipe_ingredients = $product->recipeIngredients()->get()
            ->map(fn ($ingredient) => ['ingredient_id' => $ingredient->id, 'quantity' => $ingredient->pivot->quantity])
            ->all();

        $this->openModal();
    }

    public function addOptionGroup(): void
    {
        $this->option_groups[] = [
            'name' => '',
            'required' => false,
            'values' => [['name' => '', 'price_adjustment' => 0]],
        ];
    }

    public function removeOptionGroup(int $groupIndex): void
    {
        unset($this->option_groups[$groupIndex]);
        $this->option_groups = array_values($this->option_groups);
    }

    public function addOptionValue(int $groupIndex): void
    {
        $this->option_groups[$groupIndex]['values'][] = ['name' => '', 'price_adjustment' => 0];
    }

    public function removeOptionValue(int $groupIndex, int $valueIndex): void
    {
        unset($this->option_groups[$groupIndex]['values'][$valueIndex]);
        $this->option_groups[$groupIndex]['values'] = array_values($this->option_groups[$groupIndex]['values']);
    }

    public function addComponent(): void
    {
        $this->components[] = ['product_id' => '', 'quantity' => 1];
    }

    public function updatedIsCombo($isCombo): void
    {
        if ($isCombo) {
            $this->requires_kitchen = false;
            $this->preparation_station_id = '';
            $this->option_groups = [];
            $this->recipe_ingredients = [];

            if ($this->components === []) {
                $this->addComponent();
            }

            return;
        }

        $this->components = [];
    }

    public function removeComponent(int $componentIndex): void
    {
        unset($this->components[$componentIndex]);
        $this->components = array_values($this->components);
    }

    public function addRecipeIngredient(): void
    {
        $this->recipe_ingredients[] = ['ingredient_id' => '', 'quantity' => 1];
    }

    public function removeRecipeIngredient(int $ingredientIndex): void
    {
        unset($this->recipe_ingredients[$ingredientIndex]);
        $this->recipe_ingredients = array_values($this->recipe_ingredients);
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'Producto enviado a la papelera',
            'icon'  => 'success',
        ]);
    }

    private function numberForInput($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }

    private function moneyForInput($value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }
}
