<?php

namespace App\Livewire;

use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseComponent extends Component
{
    use WithPagination;

    public $supplier_id = '';
    public $reference = '';
    public $purchased_at = '';
    public array $items = [['ingredient_id' => '', 'quantity' => '', 'unit_cost' => '']];
    public $search = '';
    public $isSupplierOpen = false;
    public $supplier_name = '';
    public $supplier_contact_name = '';
    public $supplier_phone = '';
    public $supplier_document_number = '';

    public function mount(): void
    {
        $this->purchased_at = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function addItem(): void
    {
        $this->items[] = ['ingredient_id' => '', 'quantity' => '', 'unit_cost' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function loadLowStockItems(): void
    {
        $items = Ingredient::query()
            ->whereColumn('stock', '<', 'minimum_stock')
            ->orderBy('name')
            ->get()
            ->map(fn (Ingredient $ingredient) => [
                'ingredient_id' => $ingredient->id,
                'quantity' => round((float) $ingredient->minimum_stock - (float) $ingredient->stock, 3),
                'unit_cost' => $this->moneyForInput($ingredient->unit_cost),
            ])
            ->all();

        if ($items === []) {
            $this->dispatch('swal', [
                'title' => 'Stock suficiente',
                'text' => 'No hay insumos por debajo de su mínimo.',
                'icon' => 'success',
            ]);
            return;
        }

        $this->items = $items;
        $this->resetValidation('items');
    }

    public function saveSupplier(): void
    {
        $this->validate([
            'supplier_name' => 'required|string|max:100',
            'supplier_contact_name' => 'nullable|string|max:100',
            'supplier_phone' => 'nullable|string|max:30',
            'supplier_document_number' => 'nullable|string|max:30',
        ]);

        $supplier = Supplier::create([
            'name' => trim($this->supplier_name),
            'contact_name' => trim($this->supplier_contact_name) ?: null,
            'phone' => trim($this->supplier_phone) ?: null,
            'document_number' => trim($this->supplier_document_number) ?: null,
        ]);

        $this->supplier_id = $supplier->id;
        $this->isSupplierOpen = false;
        $this->reset(['supplier_name', 'supplier_contact_name', 'supplier_phone', 'supplier_document_number']);
    }

    public function store(): void
    {
        $this->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference' => 'nullable|string|max:255',
            'purchased_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|distinct|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () {
            $purchase = Purchase::create([
                'supplier_id' => $this->supplier_id ?: null,
                'user_id' => auth()->id(),
                'reference' => trim($this->reference) ?: null,
                'purchased_at' => $this->purchased_at,
            ]);
            $total = 0.0;

            foreach ($this->items as $item) {
                $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($item['ingredient_id']);
                $quantity = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $stock = (float) $ingredient->stock;
                $purchaseCost = $quantity * $unitCost;
                $lineTotal = round($quantity * $unitCost, 2);
                $averageCost = $ingredient->unit_cost === null || $stock <= 0
                    ? $unitCost
                    : (($stock * (float) $ingredient->unit_cost) + $purchaseCost) / ($stock + $quantity);

                $purchase->details()->create([
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal,
                ]);
                $ingredient->update([
                    'stock' => $stock + $quantity,
                    'unit_cost' => round($averageCost, 4),
                ]);
                $total += $lineTotal;
            }

            $purchase->update(['total' => round($total, 2)]);
        });

        $this->dispatch('swal', [
            'title' => 'Compra registrada',
            'text' => 'El stock y el costo promedio fueron actualizados.',
            'icon' => 'success',
        ]);
        $this->reset(['supplier_id', 'reference']);
        $this->items = [['ingredient_id' => '', 'quantity' => '', 'unit_cost' => '']];
        $this->purchased_at = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.purchase-component', [
            'ingredients' => Ingredient::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'purchases' => Purchase::query()->with(['supplier', 'user', 'details.ingredient'])
                ->when($this->search, fn ($query) => $query->where(function ($query) {
                    $query->where('reference', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%' . $this->search . '%'));
                }))
                ->latest('purchased_at')
                ->paginate(10),
        ]);
    }

    private function moneyForInput($value): string
    {
        return $value === null ? '' : number_format((float) $value, 2, '.', '');
    }
}
