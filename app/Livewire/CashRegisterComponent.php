<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

class CashRegisterComponent extends Component
{
    use WithPagination;

    public $name, $opening_amount, $notes;
    public $cash_register_id = null;
    
    public $search = '';
    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $registers = CashRegister::with(['opener', 'closer'])
            ->where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.cash-register-component', compact('registers'));
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal() { $this->isOpen = true; }

    public function closeModal() { $this->isOpen = false; }

    private function resetInputFields()
    {
        $this->reset(['name', 'opening_amount', 'notes', 'cash_register_id']);
        $this->resetValidation();
    }


    public function store()
    {
        $this->validate([
            'name' => 'required|min:3|max:50',
            'opening_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $this->name,
            'opening_amount' => $this->opening_amount,
            'notes' => $this->notes,
        ];

        if (!$this->cash_register_id) {
            $data['current_amount'] = $this->opening_amount;
            $data['status'] = 'open';
            $data['opened_by'] = Auth::id();
            $data['opened_at'] = now();
        }

        CashRegister::updateOrCreate(
            ['id' => $this->cash_register_id],
            $data
        );

        $this->dispatch('swal', [
            'title' => $this->cash_register_id ? '¡Actualizado!' : '¡Caja Abierta!',
            'text'  => 'El registro de caja se ha procesado correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $register = CashRegister::findOrFail($id);
        $this->cash_register_id = $register->id;
        $this->name = $register->name;
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
        //Verificar que el registro no esté abierto o tenga movimientos asociados antes de eliminar
        $sale = Sale::where('cash_register_id', $id)->first();
        if ($sale) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar',
                'text'  => 'Este registro de caja tiene movimientos asociados',
                'icon'  => 'error',
            ]);
            return;
        }
        CashRegister::findOrFail($id)->delete();
        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'Registro de caja eliminado',
            'icon'  => 'success',
        ]);
    }
}