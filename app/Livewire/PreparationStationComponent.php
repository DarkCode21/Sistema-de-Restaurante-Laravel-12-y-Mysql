<?php

namespace App\Livewire;

use App\Models\PreparationStation;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PreparationStationComponent extends Component
{
    use WithPagination;

    public ?int $station_id = null;
    public string $name = '';
    public string $printer_name = '';
    public array $user_ids = [];
    public bool $isOpen = false;

    public function render()
    {
        return view('livewire.preparation-station-component', [
            'stations' => PreparationStation::with(['users' => fn ($users) => $this->scopeBranchUsers($users)])->orderBy('name')->paginate(10),
            'cooks' => $this->branchUsers()->orderBy('name')->get(),
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    public function edit(int $stationId): void
    {
        $station = PreparationStation::with(['users' => fn ($users) => $this->scopeBranchUsers($users)])->findOrFail($stationId);
        $this->station_id = $station->id;
        $this->name = $station->name;
        $this->printer_name = $station->printer_name ?? '';
        $this->user_ids = $station->users->pluck('id')->all();
        $this->isOpen = true;
    }

    public function store(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('preparation_stations')->where('branch_id', session('branch_id'))->ignore($this->station_id)],
            'printer_name' => ['nullable', 'string', 'max:255'],
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($this->branchUsers()->whereKey($this->user_ids)->count() !== count(array_unique($this->user_ids))) {
            $this->addError('user_ids', 'Solo puedes asignar personal de la sede activa.');
            return;
        }

        $station = PreparationStation::updateOrCreate(
            ['id' => $this->station_id],
            ['name' => trim($this->name), 'printer_name' => trim($this->printer_name) ?: null],
        );
        $station->users()->sync($this->user_ids);

        $this->isOpen = false;
        $this->resetForm();
        $this->dispatch('swal', [
            'title' => 'Estación guardada',
            'text' => 'La estación y su equipo quedaron configurados.',
            'icon' => 'success',
        ]);
    }

    public function deleteConfirm(int $stationId): void
    {
        $this->dispatch('confirm-delete', id: $stationId);
    }

    #[On('delete-confirmed')]
    public function destroy(int $id): void
    {
        $station = PreparationStation::findOrFail($id);

        if ($station->products()->exists()) {
            $this->dispatch('swal', [
                'title' => 'Estación en uso',
                'text' => 'Reasigna sus productos antes de eliminarla.',
                'icon' => 'warning',
            ]);
            return;
        }

        $station->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['station_id', 'name', 'printer_name', 'user_ids']);
        $this->resetValidation();
    }

    private function branchUsers()
    {
        return $this->scopeBranchUsers(User::query());
    }

    private function scopeBranchUsers($users)
    {
        return $users
            ->whereHas('branches', fn ($branches) => $branches->whereKey(session('branch_id')))
            ->where(fn ($query) => $query->whereNull('type')->orWhere('type', '!=', 'client'));
    }
}
