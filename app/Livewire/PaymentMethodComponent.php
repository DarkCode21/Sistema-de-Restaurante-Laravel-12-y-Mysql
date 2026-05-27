<?php

namespace App\Livewire;

use App\Models\PaymentMethod;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;

class PaymentMethodComponent extends Component
{
    use WithPagination;

    public $name = '';
    public $is_efectivo = false;
    public $payment_method_id = null;
    public $search = '';
    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $paymentMethods = PaymentMethod::where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.payment-method-component', compact('paymentMethods'));
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
            'is_efectivo',
            'payment_method_id'
        ]);
        $this->resetValidation();
    }

    /* ========= CRUD ========= */

    public function store()
    {
        $this->validate([
            'name' => [
                'required',
                'min:2',
                'max:50',
                Rule::unique('payment_methods', 'name')->ignore($this->payment_method_id),
            ],
        ]);

        //VALIDAR SI HAY YA EFECTIVO
        if ($this->is_efectivo) {
            $existingEfectivo = PaymentMethod::where('is_efectivo', true)
                ->where('id', '!=', $this->payment_method_id)
                ->first();

            if ($existingEfectivo) {
                $this->dispatch('swal', [
                    'title' => 'Error',
                    'text'  => 'Ya existe un método de pago marcado como efectivo.',
                    'icon'  => 'warning',
                ]);
                return;
            }
        }

        PaymentMethod::updateOrCreate(
            ['id' => $this->payment_method_id],
            [
                'name' => $this->name,
                'is_efectivo' => $this->is_efectivo
            ]
        );

        $this->dispatch('swal', [
            'title' => $this->payment_method_id ? '¡Actualizado!' : '¡Creado!',
            'text'  => 'Método de pago procesado correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $method = PaymentMethod::findOrFail($id);

        $this->payment_method_id = $method->id;
        $this->name = $method->name;
        $this->is_efectivo = $method->is_efectivo;
        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        PaymentMethod::findOrFail($id)->delete();

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'Método de pago enviado a la papelera',
            'icon'  => 'success',
        ]);
    }
}
