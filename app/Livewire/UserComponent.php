<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;

class UserComponent extends Component
{
    use WithPagination;

    public $name = '';
    public $email = '';
    public $password = '';
    public $user_id = null;
    public $search = '';
    public $isOpen = false;
    public $role = '';
    public $roles = [];

    public function mount()
    {
        $this->roles = Role::where('company_id', $this->currentCompanyId())->pluck('name')->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $companyId = $this->currentCompanyId();
        $users = User::with('roles')->where(function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%');
        })
            ->when($companyId, fn ($query) => $query->whereHas('companies', fn ($companies) => $companies->whereKey($companyId)))
            ->latest()
            ->paginate(10);

        return view('livewire.user-component', compact('users'));
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
            'email',
            'password',
            'user_id',
            'role'
        ]);

        $this->resetValidation();
    }

    /* ========= CRUD ========= */

    public function store()
    {
        $this->validate([
            'name' => 'required|min:3',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->user_id)
            ],
            'password' => $this->user_id
                ? 'nullable|min:8'
                : 'required|min:8',
            'role' => ['required', Rule::exists('roles', 'name')->where('company_id', $this->currentCompanyId())],
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'type' => 'user',
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(
            ['id' => $this->user_id],
            $data
        );

        $role = Role::where('company_id', $this->currentCompanyId())->where('name', $this->role)->firstOrFail();
        $user->syncRoles([$role]);

        if (!$this->user_id && ($branch = $this->currentBranch())) {
            $user->companies()->syncWithoutDetaching([$branch->company_id]);
            $user->branches()->syncWithoutDetaching([$branch->id]);
        }

        $this->dispatch('swal', [
            'title' => $this->user_id ? '¡Actualizado!' : '¡Creado!',
            'text'  => 'Usuario procesado correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $user = $this->tenantUsers()->findOrFail($id);

        $this->user_id = $user->id;
        $this->name    = $user->name;
        $this->email   = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name;
        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        if ($id === Auth::user()->id) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text'  => 'No puedes borrarte a ti mismo',
                'icon'  => 'error',
            ]);
            return;
        }

        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $user = $this->tenantUsers()->findOrFail($id);
        $companyId = $this->currentCompanyId();

        if ($companyId && $user->companies()->count() > 1) {
            $branchIds = $user->branches()->where('company_id', $companyId)->pluck('branches.id');
            $user->branches()->detach($branchIds);
            $user->companies()->detach($companyId);
        } else {
            $user->delete();
        }

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'Usuario eliminado correctamente',
            'icon'  => 'success',
        ]);
    }

    private function tenantUsers()
    {
        return User::query()->when(
            $this->currentCompanyId(),
            fn ($query, $companyId) => $query->whereHas('companies', fn ($companies) => $companies->whereKey($companyId)),
        );
    }

    private function currentCompanyId(): ?int
    {
        $companyId = session('company_id') ?: Auth::user()?->companies()->value('companies.id');

        return $companyId ? (int) $companyId : null;
    }

    private function currentBranch()
    {
        $user = Auth::user();

        return $user?->branches()->whereKey(session('branch_id'))->first() ?? $user?->branches()->first();
    }
}
