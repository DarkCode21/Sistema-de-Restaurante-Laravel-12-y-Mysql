<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PromotionComponent extends Component
{
    use WithPagination;

    public $promotion_id = null;
    public $product_id = '';
    public $name = '';
    public $discount_type = 'percent';
    public $value = '';
    public $starts_at = '';
    public $ends_at = '';
    public $active = true;
    public $search = '';
    public $isOpen = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $promotions = Promotion::with('product')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('product', fn ($product) => $product->where('name', 'like', '%' . $this->search . '%'));
            })
            ->latest()
            ->paginate(15);

        return view('livewire.promotion-component', [
            'promotions' => $promotions,
            'products' => Product::where('status', true)->orderBy('name')->get(['id', 'name', 'price']),
            'discountTypes' => Promotion::DISCOUNT_TYPES,
        ]);
    }

    public function create(): void
    {
        $this->resetInputFields();
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        $promotion = Promotion::findOrFail($id);
        $this->promotion_id = $promotion->id;
        $this->product_id = $promotion->product_id;
        $this->name = $promotion->name;
        $this->discount_type = $promotion->discount_type;
        $this->value = $promotion->value;
        $this->starts_at = $promotion->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $promotion->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->active = $promotion->active;
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
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:100',
            'discount_type' => ['required', Rule::in(Promotion::DISCOUNT_TYPES)],
            'value' => 'required|numeric|gt:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'active' => 'required|boolean',
        ]);

        if ($this->discount_type === 'percent' && (float) $this->value > 100) {
            $this->addError('value', 'El porcentaje no puede superar 100%.');
            return;
        }

        Promotion::updateOrCreate(['id' => $this->promotion_id], [
            'product_id' => $this->product_id,
            'name' => $this->name,
            'discount_type' => $this->discount_type,
            'value' => $this->value,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'active' => $this->active,
        ]);

        $this->dispatch('swal', ['title' => 'Promoción guardada', 'text' => 'El descuento se aplicará dentro de su vigencia.', 'icon' => 'success']);
        $this->closeModal();
    }

    public function deleteConfirm(int $id): void
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy(int $id): void
    {
        Promotion::findOrFail($id)->delete();
    }

    private function resetInputFields(): void
    {
        $this->reset(['promotion_id', 'product_id', 'name', 'value', 'starts_at', 'ends_at']);
        $this->discount_type = 'percent';
        $this->active = true;
        $this->resetValidation();
    }
}
