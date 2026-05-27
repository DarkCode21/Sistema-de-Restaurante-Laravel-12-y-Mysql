<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Sale;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

class OrderCreateComponent extends Component
{
    use WithPagination;

    public $table;
    public $search = '';
    public $category_id = '';
    public $order;

    public $cart = [];
    public $cartTotal = 0;

    public $selectedDetails = [];
    public $showPaymentModal = false;
    public $paymentAmount = 0;
    public $payments = [];
    public $categories = [];
    public $detailsToPay = [];
    public $paymentMethods = [];
    public $selectedMethod = null;

    public $isOpenCustomerModal = false;
    public $searchCustomer = '';
    public $customer_id;
    public $customer_name = 'Consumidor Final';
    public $printer_name;
    public $kitchen_printer_name;

    public $newCustomer = [
        'name' => '',
        'document_number' => '',
        'phone' => ''
    ];

    protected $updatesQueryString = ['searchCustomer', 'search'];

    public function mount($table)
    {
        $this->table = $table;

        $setting = Setting::first();
        $this->printer_name = $setting->printer_name;
        $this->kitchen_printer_name = $setting->kitchen_printer_name;

        $this->order = Order::with('details.product')
            ->where('table_id', $this->table->id)
            ->where('status', 'abierto')
            ->first();

        $this->paymentMethods = PaymentMethod::all();
        $this->categories = Category::all();
    }

    public function updatingSearch()
    {
        $this->resetPage('pageProducts');
    }

    public function updatingSearchCustomer()
    {
        $this->resetPage('pageCustomers');
    }

    public function openCustomerModal()
    {
        $this->resetValidation();
        $this->isOpenCustomerModal = true;
    }

    public function closeCustomerModal()
    {
        $this->isOpenCustomerModal = false;
        $this->reset(['newCustomer', 'searchCustomer']);
    }

    public function selectCustomer($id)
    {
        $client = User::find($id);

        if ($client) {
            $this->customer_id = $client->id;
            $this->customer_name = $client->name;

            if ($this->order) {
                try {
                    $this->order->update([
                        'customer_id' => $client->id,
                        'customer_name' => $client->name,
                    ]);

                    $this->dispatch('swal', [
                        'title' => 'Cliente Vinculado',
                        'text' => 'La orden ahora pertenece a ' . $client->name,
                        'icon' => 'success',
                        'timer' => 1500
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error al vincular cliente a orden: " . $e->getMessage());
                }
            }
        }

        $this->closeCustomerModal();
    }

    public function saveCustomer()
    {
        $this->validate([
            'newCustomer.name' => 'required|min:3',
            'newCustomer.document_number' => 'nullable|numeric|unique:users,document_number',
            'newCustomer.phone' => 'nullable'
        ]);

        $namePart = strtolower(explode(' ', trim($this->newCustomer['name']))[0]);
        $uniqueId = $this->newCustomer['document_number'] ?: rand(1000, 9999);

        $domain   = config('restaurant.customer.domain');
        $password = config('restaurant.customer.password');
        $type     = config('restaurant.customer.type');

        $email = $namePart . '_' . $uniqueId . '@' . $domain;

        $client = User::create([
            'name'              => $this->newCustomer['name'],
            'email'             => $email,
            'document_number'   => $this->newCustomer['document_number'],
            'phone'             => $this->newCustomer['phone'],
            'type'              => $type,
            'password'          => bcrypt($password),
            'email_verified_at' => now(),
        ]);

        $this->selectCustomer($client->id);
        $this->reset('newCustomer');
    }

    public function addToOrder($productId)
    {
        $product = Product::find($productId);

        if (!$product || ($this->order && $this->order->status === 'cerrado')) return;

        // cantidad actual en carrito
        $currentQty = $this->cart[$productId]['quantity'] ?? 0;

        // VALIDAR STOCK
        if ($product->stock <= $currentQty) {
            $this->dispatch('swal', [
                'title' => 'Sin stock',
                'text' => 'No hay suficiente stock para ' . $product->name,
                'icon' => 'warning'
            ]);
            return;
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
            $this->cart[$productId]['subtotal'] =
                $this->cart[$productId]['quantity'] * $this->cart[$productId]['price'];
        } else {
            $this->cart[$productId] = [
                'detail_id' => null,
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
                'subtotal' => (float) $product->price,
                'requires_kitchen' => $product->requires_kitchen,
                'cooking_status' => 'pending'
            ];
        }

        $this->calculateCartTotal();

        $this->dispatch('swal', [
            'title' => 'Agregado al Carrito',
            'text' => $product->name . ' se añadió al carrito.',
            'icon' => 'success',
            'timer' => 1000
        ]);
    }

    public function increment($productId)
    {
        if (!isset($this->cart[$productId])) return;

        $product = Product::find($this->cart[$productId]['product_id']);

        if (!$product) return;

        $currentQty = $this->cart[$productId]['quantity'];

        // VALIDAR STOCK
        if ($product->stock <= $currentQty) {
            $this->dispatch('swal', [
                'title' => 'Sin stock',
                'text' => 'No puedes agregar más de ' . $product->name,
                'icon' => 'warning'
            ]);
            return;
        }

        $this->cart[$productId]['quantity']++;
        $this->cart[$productId]['subtotal'] =
            $this->cart[$productId]['quantity'] * $this->cart[$productId]['price'];

        $this->calculateCartTotal();
    }

    public function decrement($productId)
    {
        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['quantity'] > 1) {
                $this->cart[$productId]['quantity']--;
                $this->cart[$productId]['subtotal'] = $this->cart[$productId]['quantity'] * $this->cart[$productId]['price'];
            } else {
                if ($this->cart[$productId]['detail_id'] && $this->order) {
                    OrderDetail::destroy($this->cart[$productId]['detail_id']);
                }
                unset($this->cart[$productId]);
            }
            $this->calculateCartTotal();
            $this->checkEmptyOrder();
        }
    }

    public function removeItem($productId)
    {
        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['detail_id'] && $this->order) {
                OrderDetail::destroy($this->cart[$productId]['detail_id']);
            }
            unset($this->cart[$productId]);
            $this->calculateCartTotal();
            $this->checkEmptyOrder();

            $this->dispatch('swal', [
                'title' => 'Producto Quitado',
                'text' => 'El ítem fue removido del listado.',
                'icon' => 'success'
            ]);
        }
    }

    private function calculateCartTotal()
    {
        $this->cartTotal = collect($this->cart)->sum('subtotal');
    }

    private function checkEmptyOrder()
    {
        if (empty($this->cart) && $this->order) {
            $this->order->delete();
            $this->table->update(['status' => 'libre']);
            $this->order = null;
            $this->reset(['customer_id', 'customer_name']);
        }
    }

    public function getItemsToPrintProperty()
    {
        if (!$this->order) return collect();

        return $this->order->details()
            ->where('cooking_status', 'pending')
            ->with('product')
            ->get()
            ->groupBy('requires_kitchen')
            ->map(function ($details, $requiresKitchen) {
                $printerName = $requiresKitchen ? $this->kitchen_printer_name : $this->printer_name;

                return [
                    'requires_kitchen' => (bool)$requiresKitchen,
                    'printer_name'     => $printerName,
                    'items'            => $details->map(function ($d) {
                        return [
                            'id'       => $d->id,
                            'name'     => $d->product->name,
                            'quantity' => $d->quantity,
                            'notes'    => $d->notes ?? ''
                        ];
                    })->toArray()
                ];
            })->values();
    }

    public function saveOrderTransaction()
    {
        if (empty($this->cart)) return;

        try {
            DB::beginTransaction();

            if (!$this->order) {
                $this->order = Order::create([
                    'table_id' => $this->table->id,
                    'customer_id' => $this->customer_id ?? null,
                    'user_id' => auth()->id(),
                    'customer_name' => $this->customer_name ?? 'Consumidor Final',
                    'status' => 'abierto',
                    'total' => $this->cartTotal,
                    'amount_pending' => $this->cartTotal,
                ]);
                $this->table->update(['status' => 'ocupada']);
            }

            foreach ($this->cart as $productId => $item) {

                $product = Product::find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Producto no encontrado");
                }

                if ($item['detail_id']) {
                    $oldDetail = OrderDetail::find($item['detail_id']);

                    $difference = $item['quantity'] - $oldDetail->quantity;

                    // Validar stock si aumenta cantidad
                    if ($difference > 0 && $product->stock < $difference) {
                        throw new \Exception("Stock insuficiente para {$product->name}");
                    }

                    // actualizar stock
                    $product->stock -= $difference;
                    $product->save();

                    $oldDetail->update([
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                        'notes'    => $item['notes'] ?? null
                    ]);
                } else {
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stock insuficiente para {$product->name}");
                    }

                    $product->stock -= $item['quantity'];
                    $product->save();

                    $newDetail = $this->order->details()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'],
                        'subtotal'   => $item['subtotal'],
                        'notes'      => $item['notes'] ?? null,
                        'requires_kitchen' => $item['requires_kitchen'] ?? true,
                        'cooking_status' => $item['cooking_status'] ?? 'pending'
                    ]);

                    $this->cart[$productId]['detail_id'] = $newDetail->id;
                }
            }

            $newTotal = $this->order->details()->sum('subtotal');

            $this->order->update([
                'total' => $newTotal,
                'amount_pending' => $newTotal
            ]);

            DB::commit();

            $this->order->load('details.product.category');
            $this->dispatch(
                'auto-print-kitchen',
                $this->itemsToPrint->map(function ($catData) {

                    return [
                        'url' => route('orders.kitchen-print', [
                            'id' => $this->order->id
                        ]),
                        'printer_name' => $catData['printer_name'],
                        'requires_kitchen'  => $catData['requires_kitchen'],
                    ];
                })->toArray()
            );
            $this->dispatch('swal', [
                'title' => 'Orden Guardada',
                'text' => 'El pedido fue registrado exitosamente.',
                'icon' => 'success'
            ]);
            $this->reset('cart');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en saveOrderTransaction: " . $e->getMessage());
            $this->dispatch('swal', ['title' => 'Error', 'text' => 'No se pudo procesar la orden.', 'icon' => 'error']);
        }
    }

    public function markAsServed($detailId)
    {
        $detail = OrderDetail::find($detailId);
        if ($detail) {
            $detail->update(['cooking_status' => 'served']);

            foreach ($this->cart as $productId => $item) {
                if ($item['detail_id'] == $detailId) {
                    $this->cart[$productId]['cooking_status'] = 'served';
                    break;
                }
            }

            $this->dispatch('swal', [
                'title' => '¡Servido!',
                'text' => 'El producto ha sido marcado como entregado.',
                'icon' => 'success',
                'timer' => 1000
            ]);
        }
    }

    public function render()
    {
        $customers = User::where('type', 'client')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->searchCustomer . '%')
                    ->orWhere('document_number', 'like', '%' . $this->searchCustomer . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate(5, ['*'], 'pageCustomers');

        $products = Product::where('status', 1)
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->paginate(12, ['*'], 'pageProducts');

        return view('livewire.order-create-component', compact('customers', 'products'));
    }
}
