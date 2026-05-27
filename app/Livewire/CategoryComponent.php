<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;

class CategoryComponent extends Component
{
    use WithPagination;

    public $name = '';
    public $category_id = null;
    public $search = '';
    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $categories = Category::where(function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%');
        })
            ->latest()
            ->paginate(10);

        return view('livewire.category-component', compact('categories'));
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
        $this->reset([
            'name',
            'category_id'
        ]);
        $this->resetValidation();
    }


    public function store()
    {
        $this->validate([
            'name' => [
                'required',
                'min:3',
                Rule::unique('categories', 'name')->ignore($this->category_id),
            ],
        ]);

        Category::updateOrCreate(
            ['id' => $this->category_id],
            [
                'name' => $this->name
            ]
        );

        $this->dispatch('swal', [
            'title' => $this->category_id ? '¡Actualizado!' : '¡Creado!',
            'text'  => 'Categoría procesada correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);

        $this->category_id = $category->id;
        $this->name = $category->name;

        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        Category::findOrFail($id)->delete();

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'Categoría eliminada correctamente',
            'icon'  => 'success',
        ]);
    }
}