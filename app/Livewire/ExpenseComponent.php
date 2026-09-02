<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

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

        $data = [
            'cash_register_id'  => $this->cash_register_id,
            'payment_method_id' => $this->payment_method_id,
            'user_id'           => auth()->id(),
            'concept'           => $this->concept,
            'description'       => $this->description,
            'amount'            => $this->amount,
            'expense_date'      => $this->expense_date,
        ];

        try {
            DB::transaction(function () use ($data) {
                $oldExpense = $this->expense_id
                    ? Expense::query()->whereKey($this->expense_id)->lockForUpdate()->first()
                    : null;

                if ($this->expense_id && !$oldExpense) {
                    throw new \RuntimeException('El gasto ya no está disponible.');
                }

                $methodIds = collect([$this->payment_method_id, $oldExpense?->payment_method_id])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                $methods = PaymentMethod::query()
                    ->whereIn('id', $methodIds)
                    ->get()
                    ->keyBy('id');
                $newMethod = $methods->get((int) $this->payment_method_id);
                $oldMethod = $oldExpense ? $methods->get($oldExpense->payment_method_id) : null;

                if (!$newMethod || ($oldExpense && !$oldMethod)) {
                    throw new \RuntimeException('El método de pago ya no está disponible.');
                }

                $registerIds = collect([$this->cash_register_id, $oldExpense?->cash_register_id])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();
                $registers = CashRegister::query()
                    ->whereIn('id', $registerIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($registers->count() !== $registerIds->count()
                    || $registers->contains(fn (CashRegister $register) => $register->status !== 'open')) {
                    throw new \RuntimeException('No se puede modificar una caja cerrada.');
                }

                if ($oldExpense && $oldMethod->is_efectivo) {
                    $oldRegister = $registers->get($oldExpense->cash_register_id);
                    $oldRegister->current_amount += (float) $oldExpense->amount;
                    $oldRegister->save();
                }

                $newRegister = $registers->get((int) $this->cash_register_id);
                $amount = (float) $this->amount;

                if ($newMethod->is_efectivo) {
                    if ($amount > $newRegister->current_amount) {
                        throw new \RuntimeException(
                            'El monto del gasto supera el dinero disponible en caja ('
                            . number_format($newRegister->current_amount, 2) . ').'
                        );
                    }

                    $newRegister->current_amount -= $amount;
                    $newRegister->save();
                }

                if ($oldExpense) {
                    $oldExpense->update($data);
                    return;
                }

                Expense::create($data);
            });
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', [
                'title' => 'No se pudo guardar',
                'text' => $e->getMessage(),
                'icon' => 'error',
            ]);
            return;
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

        if ($expense->cashRegister?->status !== 'open') {
            $this->dispatch('swal', [
                'title' => 'Caja cerrada',
                'text' => 'Los gastos de una caja cerrada no se pueden modificar.',
                'icon' => 'warning',
            ]);
            return;
        }

        $this->expense_id        = $expense->id;
        $this->cash_register_id  = $expense->cash_register_id;
        $this->payment_method_id = $expense->payment_method_id;
        $this->concept           = $expense->concept;
        $this->description       = $expense->description;
        $this->amount            = $expense->amount;
        $this->expense_date      = $expense->expense_date->format('Y-m-d\TH:i');

        $this->openModal();
    }

    public function deleteConfirm($id)
    {
        $this->dispatch('confirm-delete', id: $id);
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $expense = Expense::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $register = CashRegister::query()
                    ->whereKey($expense->cash_register_id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$register) {
                    throw new \RuntimeException('No se puede modificar una caja cerrada.');
                }

                if ($expense->paymentMethod?->is_efectivo) {
                    $register->increment('current_amount', $expense->amount);
                }

                $expense->delete();
            });
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', [
                'title' => 'No se pudo eliminar',
                'text' => $e->getMessage(),
                'icon' => 'error',
            ]);
            return;
        }

        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text'  => 'El gasto ha sido enviado a la papelera',
            'icon'  => 'success',
        ]);
    }

}
