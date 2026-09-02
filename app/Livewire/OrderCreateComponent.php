<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Sale;
use App\Models\OrderDetail;
use App\Models\OrderCorrection;
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
            $this->clearClosedOrder();
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
                $this->cartKeyForDetail($detail) => [
                    'detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'price' => (float) $detail->price,
                    'quantity' => $detail->quantity,
                    'subtotal' => (float) $detail->subtotal,
                    'notes' => $detail->notes,
                    'requires_kitchen' => (bool) $detail->requires_kitchen,
                    'cooking_status' => $detail->cooking_status,
                    'is_printed' => (bool) $detail->is_printed,
                ],
            ])
            ->all();
        $this->customer_id = $this->order->customer_id;
        $this->customer_name = $this->order->customer_name ?: 'Consumidor Final';
        $this->calculateCartTotal();
    }

    private function syncCartCookingStatuses(Order $order): void
    {
        $details = $order->details->keyBy('id');

        foreach ($this->cart as $cartKey => $item) {
            if (!$item['detail_id']) {
                continue;
            }

            $detail = $details->get($item['detail_id']);

            if (!$detail || $detail->cooking_status === 'cancelled') {
                unset($this->cart[$cartKey]);
                continue;
            }

            $this->cart[$cartKey]['cooking_status'] = $detail->cooking_status;
            $this->cart[$cartKey]['is_printed'] = (bool) $detail->is_printed;
        }

        $this->calculateCartTotal();
    }

    private function clearClosedOrder(): void
    {
        $this->order = null;
        $this->cart = [];
        $this->cartTotal = 0;
        $this->customer_id = null;
        $this->customer_name = 'Consumidor Final';
        $this->orderWasReadyForService = false;
    }

    private function cartKeyForDetail(OrderDetail $detail): string
    {
        return "detail-{$detail->id}";
    }

    private function draftCartKey(int $productId): string
    {
        return "new-{$productId}";
    }

    private function itemWasSent(array $item): bool
    {
        return (bool) ($item['is_printed'] ?? false)
            || ($item['cooking_status'] ?? 'pending') !== 'pending';
    }

    private function canEditCartItem($cartKey): bool
    {
        $item = $this->cart[$cartKey] ?? null;

        if (!$item) {
            return false;
        }

        if (($item['cooking_status'] ?? null) !== 'served') {
            return true;
        }

        $this->dispatch('swal', [
            'title' => 'Plato entregado',
            'text' => 'Un plato ya servido no se puede modificar ni devolver al stock.',
            'icon' => 'warning',
        ]);

        return false;
    }

    private function cartKeyForProduct(int $productId): ?string
    {
        foreach ($this->cart as $cartKey => $item) {
            if ((int) $item['product_id'] === $productId && !$item['detail_id']) {
                return $cartKey;
            }
        }

        foreach ($this->cart as $cartKey => $item) {
            if ((int) $item['product_id'] === $productId && !$this->itemWasSent($item)) {
                return $cartKey;
            }
        }

        return null;
    }

    private function hasStockForOneMore(array $item, Product $product): bool
    {
        return $product->stock > ($item['detail_id'] ? 0 : $item['quantity']);
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

        if (!$product || ($this->order && $this->order->status !== 'abierto')) {
            return;
        }

        $cartKey = $this->cartKeyForProduct($product->id);

        if ($cartKey !== null) {
            $item = $this->cart[$cartKey];

            if (!$this->hasStockForOneMore($item, $product)) {
                $this->dispatch('swal', [
                    'title' => 'Sin stock',
                    'text' => 'No hay suficiente stock para ' . $product->name,
                    'icon' => 'warning'
                ]);
                return;
            }

            $this->cart[$cartKey]['quantity']++;
            $this->cart[$cartKey]['subtotal'] =
                $this->cart[$cartKey]['quantity'] * $this->cart[$cartKey]['price'];
        } elseif ($product->stock < 1) {
            $this->dispatch('swal', [
                'title' => 'Sin stock',
                'text' => 'No hay suficiente stock para ' . $product->name,
                'icon' => 'warning'
            ]);
            return;
        } else {
            $this->cart[$this->draftCartKey($product->id)] = [
                'detail_id' => null,
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
                'subtotal' => (float) $product->price,
                'requires_kitchen' => $product->requires_kitchen,
                'cooking_status' => 'pending',
                'is_printed' => false,
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

    public function increment($cartKey)
    {
        if (!$this->canEditCartItem($cartKey)) {
            return;
        }

        $item = $this->cart[$cartKey];
        $product = Product::find($item['product_id']);

        if (!$product) {
            return;
        }

        if (!$this->hasStockForOneMore($item, $product)) {
            $this->dispatch('swal', [
                'title' => 'Sin stock',
                'text' => 'No puedes agregar más de ' . $product->name,
                'icon' => 'warning'
            ]);
            return;
        }

        $this->cart[$cartKey]['quantity']++;
        $this->cart[$cartKey]['subtotal'] =
            $this->cart[$cartKey]['quantity'] * $this->cart[$cartKey]['price'];

        $this->calculateCartTotal();
    }

    public function decrement($cartKey)
    {
        if (!$this->canEditCartItem($cartKey)) {
            return;
        }

        if ($this->cart[$cartKey]['quantity'] > 1) {
            $this->cart[$cartKey]['quantity']--;
            $this->cart[$cartKey]['subtotal'] = $this->cart[$cartKey]['quantity'] * $this->cart[$cartKey]['price'];
            $this->calculateCartTotal();
            return;
        }

        $this->removeItem($cartKey);
    }

    public function removeItem($cartKey)
    {
        if (!$this->canEditCartItem($cartKey)) {
            return;
        }

        $item = $this->cart[$cartKey];

        try {
            $result = null;

            if ($item['detail_id'] && $this->order) {
                $result = DB::transaction(function () use ($item) {
                    $order = Order::query()
                        ->whereKey($this->order->id)
                        ->where('status', 'abierto')
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

                    if (in_array($detail->cooking_status, ['served', 'cancelled'], true)) {
                        throw new \RuntimeException('Este plato ya no se puede eliminar.');
                    }

                    $wasSent = $detail->is_printed || $detail->cooking_status !== 'pending';

                    $product = Product::query()
                        ->whereKey($detail->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment('stock', $detail->quantity);
                    }

                    if ($wasSent) {
                        $detail->update(['cooking_status' => 'cancelled']);
                        $correction = OrderCorrection::record($detail, 'cancel');
                    } else {
                        $detail->delete();
                        $correction = null;
                    }

                    $remainingDetails = $order->details()
                        ->where('cooking_status', '!=', 'cancelled')
                        ->get();

                    $orderClosed = false;

                    if ($remainingDetails->isEmpty()) {
                        $hasSentDetails = $order->details()
                            ->where(function ($query) {
                                $query->where('is_printed', true)
                                    ->orWhere('cooking_status', '!=', 'pending');
                            })
                            ->exists();

                        if ($hasSentDetails) {
                            $order->update([
                                'status' => 'cancelado',
                                'total' => 0,
                                'amount_pending' => 0,
                            ]);
                        } else {
                            $order->delete();
                        }

                        $order->table?->update(['status' => 'libre']);
                        $orderClosed = true;
                    } else {
                        $total = $remainingDetails->sum('subtotal');
                        $order->update([
                            'total' => $total,
                            'amount_pending' => $total,
                        ]);
                    }

                    return [
                        'detail_id' => $detail->id,
                        'order_id' => $order->id,
                        'was_sent' => $wasSent,
                        'correction_id' => $correction?->id,
                        'order_closed' => $orderClosed,
                    ];
                });
            }

            unset($this->cart[$cartKey]);

            if ($result['order_closed'] ?? false) {
                $this->clearClosedOrder();
            } else {
                $this->calculateCartTotal();
                $this->checkEmptyOrder();
            }

            if ($result['was_sent'] ?? false) {
                $this->dispatchKitchenCorrections($result['order_id'], [$result['correction_id']]);
            }

            $this->dispatch('swal', [
                'title' => 'Producto Quitado',
                'text' => 'El ítem fue removido del listado.',
                'icon' => 'success'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al quitar producto de la orden', [
                'message' => $e->getMessage(),
                'cart_key' => $cartKey,
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

        $notes = trim($notes);

        try {
            $result = DB::transaction(function () use ($detailId, $notes) {
                $order = Order::query()
                    ->whereKey($this->order?->id)
                    ->where('status', 'abierto')
                    ->lockForUpdate()
                    ->first();
                $detail = OrderDetail::query()
                    ->whereKey($detailId)
                    ->where('order_id', $order?->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order || !$detail) {
                    throw new \RuntimeException('El producto ya no está disponible en esta orden.');
                }

                if (in_array($detail->cooking_status, ['served', 'cancelled'], true)) {
                    throw new \RuntimeException('Este plato ya no se puede modificar.');
                }

                $newNotes = $notes ?: null;
                $changed = $detail->notes !== $newNotes;

                $correction = null;

                if ($changed) {
                    $detail->update(['notes' => $newNotes]);

                    if ($detail->is_printed || $detail->cooking_status !== 'pending') {
                        $correction = OrderCorrection::record($detail, 'update');
                    }
                }

                return [
                    'changed' => $changed,
                    'detail_id' => $detail->id,
                    'order_id' => $order->id,
                    'correction_id' => $correction?->id,
                ];
            });

            foreach ($this->cart as $cartKey => $item) {
                if ($item['detail_id'] == $detailId) {
                    $this->cart[$cartKey]['notes'] = $notes;
                    break;
                }
            }

            if ($result['correction_id']) {
                $this->dispatchKitchenCorrections($result['order_id'], [$result['correction_id']]);
            }
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'title' => 'No se pudo modificar',
                'text' => $e->getMessage(),
                'icon' => 'warning',
            ]);
        }
    }

    private function calculateCartTotal()
    {
        $this->cartTotal = collect($this->cart)->sum('subtotal');
    }

    private function checkEmptyOrder()
    {
        if (!empty($this->cart) || !$this->order) {
            return;
        }

        $orderWasDeleted = DB::transaction(function () {
            $order = Order::query()
                ->whereKey($this->order->id)
                ->where('status', 'abierto')
                ->lockForUpdate()
                ->first();

            if (!$order || $order->details()->where('cooking_status', '!=', 'cancelled')->exists()) {
                return false;
            }

            $order->delete();
            $order->table?->update(['status' => 'libre']);

            return true;
        });

        if ($orderWasDeleted) {
            $this->clearClosedOrder();
        }
    }

    public function getItemsToPrintProperty()
    {
        if (!$this->order) {
            return collect();
        }

        $details = $this->order->details()
            ->where('cooking_status', 'pending')
            ->where('is_printed', false)
            ->with('product')
            ->get();

        if ($details->isEmpty()) {
            return collect();
        }

        if (!$this->separate_orders) {
            return collect([
                [
                    'requires_kitchen' => false,
                    'printer_name'     => $this->printer_name,
                    'detail_ids'       => $details->pluck('id')->all(),
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
                    'detail_ids'       => $details->pluck('id')->all(),
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

    private function dispatchKitchenCorrections(int $orderId, array $correctionIds): void
    {
        $corrections = OrderCorrection::query()
            ->where('order_id', $orderId)
            ->whereIn('id', $correctionIds)
            ->get();

        if ($corrections->isEmpty()) {
            return;
        }

        $setting = Setting::first();
        $separateOrders = (bool) ($setting?->separate_orders);
        $groups = $separateOrders
            ? $corrections->groupBy('requires_kitchen')
            : collect([false => $corrections]);

        $this->dispatch(
            'auto-print-kitchen-correction',
            $groups->map(function ($group, $requiresKitchen) use ($orderId, $separateOrders, $setting) {
                return [
                    'url' => URL::temporarySignedRoute(
                        'orders.kitchen-print',
                        now()->addMinutes(5),
                        [
                            'id' => $orderId,
                            'correction' => true,
                            'correction_ids' => $group->pluck('id')->all(),
                            ...($separateOrders
                                ? ['requires_kitchen' => (bool) $requiresKitchen]
                                : []),
                        ],
                    ),
                    'printer_name' => $separateOrders && $requiresKitchen
                        ? $setting?->kitchen_printer_name
                        : $setting?->printer_name,
                ];
            })->values()->all(),
        );
    }

    public function saveOrderTransaction()
    {
        if (empty($this->cart)) {
            return;
        }

        $existingOrderId = $this->order?->id;

        try {
            $result = DB::transaction(function () use ($existingOrderId) {
                if ($existingOrderId) {
                    $order = Order::query()
                        ->whereKey($existingOrderId)
                        ->where('status', 'abierto')
                        ->lockForUpdate()
                        ->first();

                    if (!$order) {
                        return ['order' => null, 'correction_ids' => []];
                    }
                } else {
                    $order = Order::create([
                        'table_id' => $this->table->id,
                        'customer_id' => $this->customer_id ?? null,
                        'user_id' => auth()->id(),
                        'customer_name' => $this->customer_name ?? 'Consumidor Final',
                        'status' => 'abierto',
                        'total' => 0,
                        'amount_pending' => 0,
                    ]);
                    $this->table->update(['status' => 'ocupada']);
                }

                $correctionIds = [];

                foreach ($this->cart as $item) {
                    $quantity = (int) ($item['quantity'] ?? 0);
                    $notes = is_string($item['notes'] ?? null) ? trim($item['notes']) : null;

                    if ($quantity < 1 || ($notes !== null && mb_strlen($notes) > 1000)) {
                        throw new \RuntimeException('El producto o su nota no son válidos.');
                    }

                    if ($item['detail_id']) {
                        $detail = OrderDetail::query()
                            ->whereKey($item['detail_id'])
                            ->where('order_id', $order->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$detail) {
                            throw new \RuntimeException('El detalle de la orden ya no está disponible.');
                        }

                        if (in_array($detail->cooking_status, ['served', 'cancelled'], true)) {
                            throw new \RuntimeException('Un plato entregado o cancelado no se puede modificar.');
                        }

                        $product = Product::query()
                            ->whereKey($detail->product_id)
                            ->lockForUpdate()
                            ->first();

                        if (!$product) {
                            throw new \RuntimeException('Producto no encontrado.');
                        }

                        $difference = $quantity - $detail->quantity;
                        $wasSent = $detail->is_printed || $detail->cooking_status !== 'pending';
                        $newNotes = $notes ?: null;

                        if ($wasSent && $difference > 0) {
                            if ($product->stock < $difference) {
                                throw new \RuntimeException("Stock insuficiente para {$product->name}");
                            }

                            $product->decrement('stock', $difference);
                            $order->details()->create([
                                'product_id' => $product->id,
                                'quantity' => $difference,
                                'price' => $detail->price,
                                'subtotal' => $detail->price * $difference,
                                'notes' => $newNotes,
                                'requires_kitchen' => $detail->requires_kitchen,
                                'cooking_status' => 'pending',
                                'is_printed' => false,
                            ]);

                            if ($detail->notes !== $newNotes) {
                                $detail->update(['notes' => $newNotes]);
                                $correctionIds[] = OrderCorrection::record($detail, 'update')->id;
                            }

                            continue;
                        }

                        if ($difference > 0 && $product->stock < $difference) {
                            throw new \RuntimeException("Stock insuficiente para {$product->name}");
                        }

                        if ($difference !== 0) {
                            if ($difference > 0) {
                                $product->decrement('stock', $difference);
                            } else {
                                $product->increment('stock', -$difference);
                            }
                        }

                        $changed = $difference !== 0 || $detail->notes !== $newNotes;
                        $detail->update([
                            'quantity' => $quantity,
                            'subtotal' => $detail->price * $quantity,
                            'notes' => $newNotes,
                        ]);

                        if ($wasSent && $changed) {
                            $correctionIds[] = OrderCorrection::record($detail, 'update')->id;
                        }

                        continue;
                    }

                    $product = Product::query()
                        ->whereKey($item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
                        throw new \RuntimeException('Producto no encontrado.');
                    }

                    if ($product->stock < $quantity) {
                        throw new \RuntimeException("Stock insuficiente para {$product->name}");
                    }

                    $product->decrement('stock', $quantity);
                    $order->details()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'subtotal' => $product->price * $quantity,
                        'notes' => $notes ?: null,
                        'requires_kitchen' => $product->requires_kitchen,
                        'cooking_status' => 'pending',
                        'is_printed' => false,
                    ]);
                }

                $newTotal = $order->details()
                    ->where('cooking_status', '!=', 'cancelled')
                    ->sum('subtotal');

                $order->update([
                    'total' => $newTotal,
                    'amount_pending' => $newTotal,
                ]);

                return [
                    'order' => $order,
                    'correction_ids' => array_values(array_unique($correctionIds)),
                ];
            });

            if (!$result['order']) {
                $this->clearClosedOrder();
                $this->dispatch('swal', [
                    'title' => 'Orden cerrada',
                    'text' => 'La orden fue cerrada o cancelada en otra pantalla.',
                    'icon' => 'warning',
                ]);
                return;
            }

            $this->order = $result['order'];
            $this->order->load('details.product.category');
            $itemsToPrint = $this->itemsToPrint;

            if ($itemsToPrint->isNotEmpty()) {
                $this->dispatch(
                    'auto-print-kitchen',
                    $itemsToPrint->map(function ($catData) {
                        return [
                            'url' => URL::temporarySignedRoute(
                                'orders.kitchen-print',
                                now()->addMinutes(5),
                                [
                                    'id' => $this->order->id,
                                    'detail_ids' => $catData['detail_ids'],
                                    ...($this->separate_orders
                                        ? ['requires_kitchen' => (bool) $catData['requires_kitchen']]
                                        : []),
                                ],
                            ),
                            'printer_name' => $catData['printer_name'],
                            'requires_kitchen' => $catData['requires_kitchen'],
                        ];
                    })->all(),
                );
            }

            if ($result['correction_ids'] !== []) {
                $this->dispatchKitchenCorrections($this->order->id, $result['correction_ids']);
            }

            $this->dispatch('swal', [
                'title' => 'Orden Guardada',
                'text' => 'El pedido fue registrado exitosamente.',
                'icon' => 'success'
            ]);
            $this->hydrateCartFromOrder();
        } catch (\Throwable $e) {
            Log::error("Error en saveOrderTransaction: " . $e->getMessage());
            $this->dispatch('swal', ['title' => 'Error', 'text' => $e instanceof \RuntimeException ? $e->getMessage() : 'No se pudo procesar la orden.', 'icon' => 'error']);
        }
    }

    public function markAsServed($detailId): void
    {
        $updated = DB::transaction(function () use ($detailId) {
            $order = Order::query()
                ->whereKey($this->order?->id)
                ->where('status', 'abierto')
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return false;
            }

            return OrderDetail::query()
                ->whereKey($detailId)
                ->where('order_id', $order->id)
                ->where(function ($query) {
                    $query->where('cooking_status', 'ready')
                        ->orWhere(fn ($query) => $query
                            ->where('requires_kitchen', false)
                            ->whereNotIn('cooking_status', ['served', 'cancelled']));
                })
                ->lockForUpdate()
                ->update(['cooking_status' => 'served']) === 1;
        });

        if (!$updated) {
            $this->dispatch('swal', [
                'title' => 'Aún no disponible',
                'text' => 'Solo se pueden entregar productos listos o que no requieren cocina.',
                'icon' => 'warning',
                'timer' => 1500,
            ]);
            return;
        }

        foreach ($this->cart as $cartKey => $item) {
            if ($item['detail_id'] == $detailId) {
                $this->cart[$cartKey]['cooking_status'] = 'served';
                break;
            }
        }

        $this->dispatch('swal', [
            'title' => '¡Entregado!',
            'text' => 'El producto fue marcado como entregado.',
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
