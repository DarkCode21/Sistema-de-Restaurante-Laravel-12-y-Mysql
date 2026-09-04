<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class TenantComponent extends Component
{
    public string $company_name = '';
    public string $initial_branch_name = 'Sede principal';
    public string $initial_branch_code = 'PRINCIPAL';
    public ?int $branch_company_id = null;
    public string $branch_name = '';
    public string $branch_code = '';
    public string $branch_address = '';
    public string $branch_phone = '';

    public function mount(): void
    {
        $this->branch_company_id = session('company_id') ?: $this->companies()->value('companies.id');
    }

    public function render()
    {
        $companies = $this->companies()
            ->with(['branches' => fn ($query) => $query->whereHas('users', fn ($users) => $users->whereKey(auth()->id()))->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('livewire.tenant-component', compact('companies'));
    }

    public function createCompany(): void
    {
        $this->ensureCanManage();
        $this->company_name = trim($this->company_name);
        $this->initial_branch_name = trim($this->initial_branch_name);
        $this->initial_branch_code = Str::upper(trim($this->initial_branch_code));

        $this->validate([
            'company_name' => ['required', 'string', 'max:100'],
            'initial_branch_name' => ['required', 'string', 'max:100'],
            'initial_branch_code' => ['required', 'string', 'max:50'],
        ]);

        $slug = Str::slug($this->company_name);
        if ($slug === '' || Company::where('slug', $slug)->exists()) {
            $this->addError('company_name', 'Ya existe una empresa con ese nombre.');
            return;
        }

        $sourceCompanyId = session('company_id');
        $sourceRoleNames = auth()->user()->getRoleNames();
        $sourcePermissions = auth()->user()->getDirectPermissions();

        [$company, $branch] = DB::transaction(function () use ($slug, $sourceCompanyId, $sourceRoleNames, $sourcePermissions): array {
            $company = Company::create(['name' => $this->company_name, 'slug' => $slug, 'is_active' => true]);
            $branch = $company->branches()->create([
                'name' => $this->initial_branch_name,
                'code' => $this->initial_branch_code,
                'is_active' => true,
            ]);

            auth()->user()->companies()->syncWithoutDetaching([$company->id]);
            auth()->user()->branches()->syncWithoutDetaching([$branch->id]);
            Setting::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'company_name' => $company->name,
                'currency_simbol' => 'S/',
                'timezone' => config('app.timezone', 'UTC'),
            ]);

            $roles = Role::where('company_id', $sourceCompanyId)->with('permissions')->get()->mapWithKeys(function (Role $role) use ($company) {
                $clone = new Role();
                $clone->name = $role->name;
                $clone->guard_name = $role->guard_name;
                $clone->company_id = $company->id;
                $clone->save();
                $clone->syncPermissions($role->permissions);

                return [$clone->name => $clone];
            });

            setPermissionsTeamId($company->id);
            $owner = auth()->user()->unsetRelation('roles')->unsetRelation('permissions');
            $ownerRoles = $roles->filter(fn (Role $role) => $sourceRoleNames->contains($role->name))->all();
            if ($ownerRoles !== []) {
                $owner->assignRole(...$ownerRoles);
            }
            if ($sourcePermissions->isNotEmpty()) {
                $owner->givePermissionTo(...$sourcePermissions->all());
            }

            return [$company, $branch];
        });

        session()->put(['company_id' => $company->id, 'branch_id' => $branch->id]);
        $this->reset(['company_name']);
        $this->initial_branch_name = 'Sede principal';
        $this->initial_branch_code = 'PRINCIPAL';
        $this->branch_company_id = $company->id;
        $this->dispatch('swal', ['title' => 'Empresa creada', 'text' => 'La sede inicial ya está lista para configurarse.', 'icon' => 'success']);
    }

    public function createBranch(): void
    {
        $this->ensureCanManage();
        $this->branch_name = trim($this->branch_name);
        $this->branch_code = Str::upper(trim($this->branch_code));

        $this->validate([
            'branch_company_id' => ['required', 'integer'],
            'branch_name' => ['required', 'string', 'max:100'],
            'branch_code' => ['required', 'string', 'max:50'],
            'branch_address' => ['nullable', 'string', 'max:255'],
            'branch_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $company = $this->companies()->findOrFail($this->branch_company_id);
        $this->validate([
            'branch_code' => [Rule::unique('branches', 'code')->where('company_id', $company->id)],
        ]);

        $branch = $company->branches()->create([
            'name' => $this->branch_name,
            'code' => $this->branch_code,
            'address' => $this->branch_address ?: null,
            'phone' => $this->branch_phone ?: null,
            'is_active' => true,
        ]);
        auth()->user()->branches()->syncWithoutDetaching([$branch->id]);

        $this->reset(['branch_name', 'branch_code', 'branch_address', 'branch_phone']);
        $this->dispatch('swal', ['title' => 'Sede creada', 'text' => 'Puedes seleccionarla desde la barra superior.', 'icon' => 'success']);
    }

    private function companies()
    {
        return auth()->user()->companies();
    }

    private function ensureCanManage(): void
    {
        abort_unless(auth()->user()?->can('empresa.editar'), 403);
    }
}
