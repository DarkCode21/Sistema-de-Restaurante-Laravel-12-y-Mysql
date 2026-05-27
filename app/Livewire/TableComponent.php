<?php

namespace App\Livewire;

use App\Models\Table;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;

class TableComponent extends Component
{
    use WithPagination;

    public $name = '';
    public $capacity = '';
    public $status = 'libre';
    public $table_id = null;
    public $search = '';
    public $isOpen = false;

    protected $listeners = ['updatePosition'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $tables = Table::where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.table-component', compact('tables'));
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
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->reset(['name', 'capacity', 'status', 'table_id']);
        $this->resetValidation();
    }

    public function updatePosition($id, $x, $y)
    {
        Table::where('id', $id)->update([
            'x_pos' => $x,
            'y_pos' => $y
        ]);
    }

    public function store()
    {
        $this->validate([
            'name' => [
                'required',
                'min:2',
                'max:50',
                Rule::unique('tables', 'name')->ignore($this->table_id),
            ],
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:libre,ocupada,reservada',
        ]);

        Table::updateOrCreate(
            ['id' => $this->table_id],
            [
                'name' => $this->name,
                'capacity' => $this->capacity,
                'status' => $this->status,
                'x_pos' => $this->table_id ? Table::find($this->table_id)->x_pos : 0,
                'y_pos' => $this->table_id ? Table::find($this->table_id)->y_pos : 0,
            ]
        );

        $this->dispatch('swal', [
            'title' => $this->table_id ? '¡Actualizada!' : '¡Creada!',
            'text'  => 'Mesa procesada correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
    }

    public function edit($id)
    {
        $table = Table::findOrFail($id);
        $this->table_id = $table->id;
        $this->name = $table->name;
        $this->capacity = $table->capacity;
        $this->status = $table->status;
        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        Table::findOrFail($id)->delete();
        $this->dispatch('swal', [
            'title' => 'Eliminada',
            'text'  => 'Mesa enviada a la papelera',
            'icon'  => 'success',
        ]);
    }
}