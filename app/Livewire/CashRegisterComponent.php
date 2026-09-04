<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\CashTerminal;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashRegisterComponent extends Component
{
    use WithPagination;

    public $terminal_id, $opening_amount, $notes;
    public $new_terminal_name = '';
    public $cash_register_id = null;
    
    public $search = '';
    public $isOpen = false;
    public $showTerminalForm = false;

    public function mount(): void
    {
        CashTerminal::firstOrCreate(['name' => 'Caja principal'], ['is_active' => true]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $registers = CashRegister::with(['opener', 'closer', 'terminal'])
            ->where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        $terminals = CashTerminal::query()->where('is_active', true)->orderBy('name')->get();

        return view('livewire.cash-register-component', compact('registers', 'terminals'));
    }

    public function create()
    {
        $this->resetInputFields();
        $this->terminal_id = CashTerminal::query()->where('is_active', true)->orderBy('id')->value('id');
        $this->openModal();
    }

    public function openModal() { $this->isOpen = true; }

    public function closeModal() { $this->isOpen = false; }

    private function resetInputFields()
    {
        $this->reset(['terminal_id', 'opening_amount', 'notes', 'cash_register_id']);
        $this->resetValidation();
    }


    public function store()
    {
        $this->validate([
            'terminal_id' => $this->cash_register_id ? 'nullable' : 'required|exists:cash_terminals,id',
            'opening_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $data = [
            'opening_amount' => $this->opening_amount,
            'notes' => $this->notes,
        ];

        if (!$this->cash_register_id) {
            $opened = DB::transaction(function () use ($data) {
                $terminal = CashTerminal::query()
                    ->whereKey($this->terminal_id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$terminal || CashRegister::query()->where('cash_terminal_id', $this->terminal_id)->where('status', 'open')->exists()) {
                    return false;
                }

                CashRegister::create([
                    ...$data,
                    'name' => $terminal->name,
                    'cash_terminal_id' => $terminal->id,
                    'current_amount' => $this->opening_amount,
                    'status' => 'open',
                    'opened_by' => Auth::id(),
                    'opened_at' => now(),
                ]);

                return true;
            });

            if (!$opened) {
                $this->addError('terminal_id', 'Esta caja ya tiene una sesión abierta.');
                return;
            }
        } else {
            $updated = DB::transaction(function () use ($data) {
                $register = CashRegister::query()
                    ->whereKey($this->cash_register_id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$register) {
                    return 'closed';
                }

                $hasMovements = $register->sales()->exists() || $register->expenses()->exists();

                if ($hasMovements && (float) $register->opening_amount !== (float) $data['opening_amount']) {
                    return 'has_movements';
                }

                if (!$hasMovements) {
                    $data['current_amount'] = $data['opening_amount'];
                }

                $register->update($data);

                return 'updated';
            });

            if ($updated === 'closed') {
                $this->dispatch('swal', [
                    'title' => 'Caja cerrada',
                    'text' => 'Una caja cerrada no se puede modificar.',
                    'icon' => 'warning',
                ]);
                return;
            }

            if ($updated === 'has_movements') {
                $this->dispatch('swal', [
                    'title' => 'Caja con movimientos',
                    'text' => 'No se puede cambiar el monto de apertura después de registrar movimientos.',
                    'icon' => 'warning',
                ]);
                return;
            }
        }

        $this->dispatch('swal', [
            'title' => $this->cash_register_id ? '¡Actualizado!' : '¡Caja Abierta!',
            'text'  => 'El registro de caja se ha procesado correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function addTerminal(): void
    {
        $this->validate(['new_terminal_name' => 'required|string|min:3|max:50|unique:cash_terminals,name']);

        $terminal = CashTerminal::create(['name' => trim($this->new_terminal_name), 'is_active' => true]);
        $this->terminal_id = $terminal->id;
        $this->new_terminal_name = '';
        $this->showTerminalForm = false;
    }

    public function edit($id)
    {
        $register = CashRegister::findOrFail($id);
        $this->cash_register_id = $register->id;
        $this->terminal_id = $register->cash_terminal_id;
        $this->opening_amount = $register->opening_amount;
        $this->notes = $register->notes;

        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $deleted = DB::transaction(function () use ($id) {
            $register = CashRegister::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($register->status !== 'open' || $register->sales()->exists() || $register->expenses()->exists()) {
                return false;
            }

            $register->delete();

            return true;
        });

        if (!$deleted) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar',
                'text'  => 'La caja está cerrada o tiene movimientos asociados.',
                'icon'  => 'error',
            ]);
            return;
        }

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'Registro de caja eliminado',
            'icon'  => 'success',
        ]);
    }
}
