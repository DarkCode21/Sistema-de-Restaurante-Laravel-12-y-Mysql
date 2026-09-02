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
use Illuminate\Support\Facades\URL;
use Livewire\WithPagination;

class OrderCreateComponent extends Component
{
    use WithPagination;

    public $table;
    public $search = '';
    public $category_id = '';
    public $order;
    public bool $orderWasReadyForService = false;

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
    public $direct_printing = false;
    public $printer_name;
    public $separate_orders = false;
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
        $this->direct_printing = $setting->direct_printing;
        $this->printer_name = $setting->printer_name;
        $this->kitchen_printer_name = $setting->kitchen_printer_name;
        $this->separate_orders = $setting->separate_orders;

        $this->order = Order::with('details.product')
            ->where('table_id', $this->table->id)
            ->where('status', 'abierto')
            ->first();
        $this->hydrateCartFromOrder();
        $this->orderWasReadyForService = $this->orderIsReadyForService($this->order);

        $this->paymentMethods = PaymentMethod::all();
        $this->categories = Category::whereHas('products', function ($query) {
            $query->where('status', true);
        })->orderBy('name')->get();
    }

    public function refreshReadyOrderAlert(): void
    {
        if (!$this->order) {
            return;
        }

        $order = Order::with(['details', 'table'])->find($this->order->id);

        if (!$order || $order->status !== 'abierto') {
            $this->order = null;
            $this->orderWasReadyForService = false;
            return;
        }

        $isReadyForService = $this->orderIsReadyForService($order);

        if ($isReadyForService && !$this->orderWasReadyForService) {
            $this->dispatch(
                'order-ready-for-service',
                orderId: $order->id,
                tableName: $order->table?->name ?? 'Sin mesa'
            );
        }

        $this->syncCartCookingStatuses($order);
        $this->order = $order;
        $this->orderWasReadyForService = $isReadyForService;
    }

    private function orderIsReadyForService(?Order $order): bool
    {
        if (!$order) {
            return false;
        }

        $kitchenDetails = $order->details
            ->filter(fn (OrderDetail $detail) => $detail->requires_kitchen && $detail->cooking_status !== 'cancelled');

        return $kitchenDetails->isNotEmpty()
            && $kitchenDetails->every(fn (OrderDetail $detail) => in_array($detail->cooking_status, ['ready', 'served'], true));
    }

    private function hydrateCartFromOrder(): void
    {
        if (!$this->order) {
            $this->cart = [];
            $this->cartTotal = 0;
            return;
        }

        $this->order->loadMissing('details.product');
        $this->cart = $this->order->details
            ->filter(fn (OrderDetail $detail) => $detail->product && $detail->cooking_status !== 'cancelled')
            ->mapWithKeys(fn (OrderDetail $detail) => [
                $detail->product_id => [
                    'detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'price' => (float) $detail->price,
                    'quantity' => $detail->quantity,
                    'subtotal' => (float) $detail->subtotal,
                    'notes' => $detail->notes,
                    'requires_kitchen' => (bool) $detail->requires_kitchen,
                    'cooking_status' => $detail->cooking_status,
                ],
            ])
            ->all();
        $this->customer_id = $this->order->customer_id;
        $this->customer_name = $this->order->customer_name ?: 'Consumidor Final';
        $this->calculateCartTotal();
    }

    private function syncCartCookingStatuses(Order $order): void
    {
        foreach ($order->details as $detail) {
            $productId = $detail->product_id;

            if (($this->cart[$productId]['detail_id'] ?? null) === $detail->id) {
                $this->cart[$productId]['cooking_status'] = $detail->cooking_status;
            }
        }
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
                $this->removeItem($productId);
                return;
            }
            $this->calculateCartTotal();
            $this->checkEmptyOrder();
        }
    }

    public function removeItem($productId)
    {
        if (!isset($this->cart[$productId])) {
            return;
        }

        $item = $this->cart[$productId];

        try {
            if ($item['detail_id'] && $this->order) {
                DB::transaction(function () use ($item) {
                    $order = Order::query()
                        ->whereKey($this->order->id)
                        ->lockForUpdate()
                        ->first();
                    $detail = OrderDetail::query()
                        ->whereKey($item['detail_id'])
                        ->where('order_id', $this->order->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$order || !$detail) {
                        throw new \RuntimeException('El producto ya no está disponible en esta orden.');
                    }

                    $product = Product::query()
                        ->whereKey($detail->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment('stock', $detail->quantity);
                    }

                    $detail->delete();

                    $total = $order->details()
                        ->where('cooking_status', '!=', 'cancelled')
                        ->sum('subtotal');
                    $order->update([
                        'total' => $total,
                        'amount_pending' => $total,
                    ]);
                });
            }

            unset($this->cart[$productId]);
            $this->calculateCartTotal();
            $this->checkEmptyOrder();

            $this->dispatch('swal', [
                'title' => 'Producto Quitado',
                'text' => 'El ítem fue removido del listado.',
                'icon' => 'success'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al quitar producto de la orden', [
                'message' => $e->getMessage(),
                'product_id' => $productId,
            ]);
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => 'No se pudo quitar el producto.',
                'icon' => 'error',
            ]);
        }
    }

    public function updateNote($detailId, $notes): void
    {
        if (!is_string($notes) || mb_strlen($notes) > 1000) {
            $this->dispatch('swal', [
                'title' => 'Nota no válida',
                'text' => 'La nota debe tener como máximo 1000 caracteres.',
                'icon' => 'warning',
            ]);
            return;
        }

        $detail = OrderDetail::query()
            ->whereKey($detailId)
            ->where('order_id', $this->order?->id)
            ->first();

        if (!$detail) {
            return;
        }

        $notes = trim($notes);
        $detail->update(['notes' => $notes ?: null]);

        foreach ($this->cart as $productId => $item) {
            if ($item['detail_id'] == $detail->id) {
                $this->cart[$productId]['notes'] = $notes;
                break;
            }
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
        if (!$this->order) {
            return collect();
        }

        $details = $this->order->details()
            ->where('cooking_status', 'pending')
            ->with('product')
            ->get();

        if (!$this->separate_orders) {
            return collect([
                [
                    'requires_kitchen' => false,
                    'printer_name'     => $this->printer_name,
                    'items'            => $details->map(function ($d) {
                        return [
                            'id'       => $d->id,
                            'name'     => $d->product->name,
                            'quantity' => $d->quantity,
                            'notes'    => $d->notes ?? ''
                        ];
                    })->toArray()
                ]
            ]);
        }

        return $details
            ->groupBy('requires_kitchen')
            ->map(function ($details, $requiresKitchen) {

                $printerName = $requiresKitchen
                    ? $this->kitchen_printer_name
                    : $this->printer_name;

                return [
                    'requires_kitchen' => (bool) $requiresKitchen,
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
            })
            ->values();
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

                $product = Product::query()
                    ->whereKey($item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new \Exception("Producto no encontrado");
                }

                if ($item['detail_id']) {
                    $oldDetail = OrderDetail::query()
                        ->whereKey($item['detail_id'])
                        ->where('order_id', $this->order->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$oldDetail) {
                        throw new \Exception('El detalle de la orden ya no está disponible.');
                    }

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

            $newTotal = $this->order->details()
                ->where('cooking_status', '!=', 'cancelled')
                ->sum('subtotal');

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
                        'url' => URL::temporarySignedRoute(
                            'orders.kitchen-print',
                            now()->addMinutes(5),
                            [
                                'id' => $this->order->id,
                                ...($this->separate_orders
                                    ? ['requires_kitchen' => (bool) $catData['requires_kitchen']]
                                    : []),
                            ],
                        ),
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
            $this->hydrateCartFromOrder();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en saveOrderTransaction: " . $e->getMessage());
            $this->dispatch('swal', ['title' => 'Error', 'text' => 'No se pudo procesar la orden.', 'icon' => 'error']);
        }
    }

    public function markAsServed($detailId): void
    {
        $detail = OrderDetail::query()
            ->whereKey($detailId)
            ->where('order_id', $this->order?->id)
            ->first();

        if (!$detail || $detail->cooking_status !== 'ready') {
            $this->dispatch('swal', [
                'title' => 'Aún no disponible',
                'text' => 'Solo se pueden retirar platos marcados como listos por Cocina.',
                'icon' => 'warning',
                'timer' => 1500,
            ]);
            return;
        }

        $detail->update(['cooking_status' => 'served']);

        foreach ($this->cart as $productId => $item) {
            if ($item['detail_id'] == $detailId) {
                $this->cart[$productId]['cooking_status'] = 'served';
                break;
            }
        }

        $this->dispatch('swal', [
            'title' => '¡Entregado!',
            'text' => 'El plato fue retirado y marcado como entregado.',
            'icon' => 'success',
            'timer' => 1000
        ]);
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
