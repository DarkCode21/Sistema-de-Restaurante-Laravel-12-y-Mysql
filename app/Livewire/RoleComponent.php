<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleComponent extends Component
{
    use WithPagination;

    public $name = '';
    public $role_id = null;
    public $search = '';
    public $isOpen = false;

    public $permissions = [];
    public $selectedPermissions = [];

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->permissions = Permission::orderBy('name')->get();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $roles = Role::where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.role-component', compact('roles'));
    }

    /* ========= MODAL ========= */

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
            'role_id',
            'selectedPermissions'
        ]);

        $this->resetValidation();
    }

    /* ========= CRUD ========= */

    public function store()
    {
        $this->validate([
            'name' => [
                'required',
                'min:3',
                Rule::unique('roles', 'name')->ignore($this->role_id),
            ],
            'selectedPermissions' => 'array',
        ]);

        $role = Role::updateOrCreate(
            ['id' => $this->role_id],
            ['name' => $this->name, 'guard_name' => 'web']
        );

        $role->syncPermissions($this->selectedPermissions);

        $this->dispatch('swal', [
            'title' => $this->role_id ? '¡Actualizado!' : '¡Creado!',
            'text'  => 'Rol procesado correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        $this->role_id = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        Role::findOrFail($id)->delete();

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'Rol eliminado correctamente',
            'icon'  => 'success',
        ]);
    }
}
