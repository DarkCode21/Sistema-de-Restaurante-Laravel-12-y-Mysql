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
    public array $user_ids = [];
    public bool $isOpen = false;

    public function render()
    {
        return view('livewire.preparation-station-component', [
            'stations' => PreparationStation::with('users')->orderBy('name')->paginate(10),
            'cooks' => User::query()
                ->where(fn ($query) => $query->whereNull('type')->orWhere('type', '!=', 'client'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    public function edit(int $stationId): void
    {
        $station = PreparationStation::with('users')->findOrFail($stationId);
        $this->station_id = $station->id;
        $this->name = $station->name;
        $this->user_ids = $station->users->pluck('id')->all();
        $this->isOpen = true;
    }

    public function store(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('preparation_stations')->ignore($this->station_id)],
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $station = PreparationStation::updateOrCreate(
            ['id' => $this->station_id],
            ['name' => trim($this->name)],
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
        $this->reset(['station_id', 'name', 'user_ids']);
        $this->resetValidation();
    }
}
