<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Category;
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
    public $stock = '';
    public $status = 1;
    public $requires_kitchen = 0;
    public $image, $old_image;

    public $search = '';
    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::with('category')
            ->where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        $categories = Category::orderBy('name', 'asc')->get();

        return view('livewire.product-component', compact('products', 'categories'));
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
        $this->reset(['product_id', 'category_id', 'name', 'requires_kitchen', 'price', 'stock', 'status', 'image', 'old_image']);
        $this->status = 1;
        $this->resetValidation();
    }

    public function store()
    {
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name' => ['required', 'min:2', Rule::unique('products', 'name')->ignore($this->product_id)],
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'status' => 'required|boolean',
            'requires_kitchen' => 'required|boolean',
            'image' => $this->product_id ? 'nullable|image|max:2048' : 'required|image|max:2048',
        ];

        $this->validate($rules);

        $data = [
            'category_id' => $this->category_id,
            'name'        => $this->name,
            'price'       => $this->price,
            'stock'       => $this->stock,
            'status'      => $this->status,
            'requires_kitchen' => $this->requires_kitchen
        ];

        if ($this->image) {
            // Eliminar imagen anterior si existe
            if ($this->old_image) {
                Storage::disk('public')->delete($this->old_image);
            }
            $data['image'] = $this->image->store('products', 'public');
        }

        Product::updateOrCreate(['id' => $this->product_id], $data);

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
        $this->price       = $product->price;
        $this->stock       = $product->stock;
        $this->status      = $product->status;
        $this->requires_kitchen = $product->requires_kitchen;
        $this->old_image   = $product->image;

        $this->openModal();
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
}
