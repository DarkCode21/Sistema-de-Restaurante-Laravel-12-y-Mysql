<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ExpenseComponent extends Component
{
    use WithPagination;

    public $expense_id = null;
    public $cash_register_id = '';
    public $payment_method_id = '';
    public $concept = '';
    public $description = '';
    public $amount = '';
    public $expense_date = '';

    public $search = '';
    public $start_date = '';
    public $end_date = '';
    
    public $isOpen = false;

    public $cashRegisters = [];
    public $paymentMethods = [];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate() { $this->resetPage(); }

    public function mount()
    {
        $this->cashRegisters = CashRegister::where('status', 'open')
            ->orWhere('id', $this->cash_register_id)
            ->orderBy('name', 'asc')
            ->get();

        $this->paymentMethods = PaymentMethod::orderBy('name', 'asc')->get();
        $this->expense_date = now()->format('Y-m-d\TH:i');
        
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    public function render()
    {
        $expenses = Expense::with(['cashRegister', 'paymentMethod', 'user'])
            ->where(function($query) {
                $query->where('concept', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->start_date, function($query) {
                $query->whereDate('expense_date', '>=', $this->start_date);
            })
            ->when($this->end_date, function($query) {
                $query->whereDate('expense_date', '<=', $this->end_date);
            })
            ->latest()
            ->paginate(10);

        $exportParams = [
            'search'     => $this->search,
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ];

        $pdfUrl   = route('expenses.export.pdf', $exportParams);
        $excelUrl = route('expenses.export.excel', $exportParams);

        return view('livewire.expense-component', compact('expenses', 'pdfUrl', 'excelUrl'));
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
        $this->reset(['expense_id', 'cash_register_id', 'payment_method_id', 'concept', 'description', 'amount']);
        $this->expense_date = now()->format('Y-m-d\TH:i');
        $this->resetValidation();
    }

    public function store()
    {
        $rules = [
            'cash_register_id'  => 'required|exists:cash_registers,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'concept'           => 'required|string|min:3|max:255',
            'description'       => 'nullable|string|max:500',
            'amount'            => 'required|numeric|min:0.01',
            'expense_date'      => 'required|date',
        ];

        $this->validate($rules);

        $isEfectivo = PaymentMethod::find($this->payment_method_id)?->is_efectivo ?? false;
        $amountDifference = 0;

        if ($isEfectivo) {
            $register = CashRegister::find($this->cash_register_id);
            $availableAmount = $register ? $register->current_amount : 0;

            if ($this->expense_id) {
                $oldExpense = Expense::find($this->expense_id);
                $amountDifference = $this->amount - $oldExpense->amount;
                
                if ($amountDifference > $availableAmount) {
                    $this->dispatch('swal', [
                        'title' => 'Saldo Insuficiente',
                        'text'  => 'El aumento del gasto supera el dinero disponible en caja (' . number_format($availableAmount, 2) . ')',
                        'icon'  => 'error',
                    ]);
                    return;
                }
            } else {
                $amountDifference = $this->amount;

                if ($this->amount > $availableAmount) {
                    $this->dispatch('swal', [
                        'title' => 'Saldo Insuficiente',
                        'text'  => 'El monto del gasto supera el dinero disponible en caja (' . number_format($availableAmount, 2) . ')',
                        'icon'  => 'error',
                    ]);
                    return;
                }
            }
        }

        $data = [
            'cash_register_id'  => $this->cash_register_id,
            'payment_method_id' => $this->payment_method_id,
            'user_id'           => auth()->id(),
            'concept'           => $this->concept,
            'description'       => $this->description,
            'amount'            => $this->amount,
            'expense_date'      => $this->expense_date,
        ];

        Expense::updateOrCreate(['id' => $this->expense_id], $data);

        if ($amountDifference != 0) {
            $this->updateCashRegisterAmount($this->cash_register_id, $amountDifference);
        }

        $this->dispatch('swal', [
            'title' => $this->expense_id ? '¡Actualizado!' : '¡Creado!',
            'text'  => 'El gasto se registró correctamente',
            'icon'  => 'success',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $expense = Expense::findOrFail($id);
        
        $this->expense_id        = $expense->id;
        $this->cash_register_id  = $expense->cash_register_id;
        $this->payment_method_id = $expense->payment_method_id;
        $this->concept           = $expense->concept;
        $this->description       = $expense->description;
        $this->amount            = $expense->amount;
        $this->expense_date      = $expense->expense_date->format('Y-m-d\TH:i');

        $this->mount(); 
        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);

        $isEfectivo = $expense->paymentMethod?->is_efectivo ?? false;
        if ($isEfectivo) {
            $this->updateCashRegisterAmount($expense->cash_register_id, -$expense->amount);
        }

        $expense->delete();

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'El gasto ha sido enviado a la papelera',
            'icon'  => 'success',
        ]);
    }

    private function updateCashRegisterAmount($cashRegisterId, $amountDifference)
    {
        $register = CashRegister::find($cashRegisterId);
        if ($register) {
            $register->current_amount -= $amountDifference;
            $register->save();
        }
    }
}