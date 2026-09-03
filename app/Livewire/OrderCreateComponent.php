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
    public bool $isOpenProductOptions = false;
    public array $configuringProduct = [];
    public array $selectedOptionValueIds = [];

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

        $this->order = Order::with(['details.product', 'details.components.product'])
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

        $order = Order::with(['details.components', 'table'])->find($this->order->id);

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

        $this->order->loadMissing(['details.product', 'details.components.product']);
        $this->cart = $this->order->details
            ->filter(fn (OrderDetail $detail) => !$detail->parent_detail_id && $detail->product && $detail->cooking_status !== 'cancelled')
            ->mapWithKeys(fn (OrderDetail $detail) => [
                $this->cartKeyForDetail($detail) => [
                    'detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'price' => (float) $detail->price,
                    'quantity' => $detail->quantity,
                    'subtotal' => (float) $detail->subtotal,
                    'notes' => $detail->notes,
                    'selected_options' => $detail->selected_options ?? [],
                    'option_key' => collect($detail->selected_options ?? [])->pluck('value_id')->sort()->join('-'),
                    'preparation_station_id' => $detail->preparation_station_id,
                    'requires_kitchen' => (bool) ($detail->requires_kitchen || $detail->components->contains('requires_kitchen', true)),
                    'cooking_status' => $detail->service_status,
                    'is_printed' => (bool) $detail->is_printed,
                    'is_combo' => $detail->components->isNotEmpty(),
                    'components' => $detail->components->map(fn (OrderDetail $component) => [
                        'product_id' => $component->product_id,
                        'quantity' => $component->quantity / $detail->quantity,
                        'selected_options' => $component->selected_options ?? [],
                        'preparation_station_id' => $component->preparation_station_id,
                        'requires_kitchen' => (bool) $component->requires_kitchen,
                    ])->all(),
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

            $this->cart[$cartKey]['cooking_status'] = $detail->service_status;
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

    private function draftCartKey(int $productId, string $optionKey = ''): string
    {
        return "new-{$productId}-" . ($optionKey ?: 'base');
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

    private function cartKeyForProduct(int $productId, string $optionKey = ''): ?string
    {
        foreach ($this->cart as $cartKey => $item) {
            if ((int) $item['product_id'] === $productId
                && ($item['option_key'] ?? '') === $optionKey
                && !$item['detail_id']) {
                return $cartKey;
            }
        }

        foreach ($this->cart as $cartKey => $item) {
            if ((int) $item['product_id'] === $productId
                && ($item['option_key'] ?? '') === $optionKey
                && !$this->itemWasSent($item)) {
                return $cartKey;
            }
        }

        return null;
    }

    private function hasStockForOneMore(array $item, Product $product): bool
    {
        if ($item['detail_id']) {
            return $product->stock > 0;
        }

        $draftQuantity = collect($this->cart)
            ->filter(fn ($cartItem) => (int) $cartItem['product_id'] === $product->id && !$cartItem['detail_id'])
            ->sum('quantity');

        return $product->stock > $draftQuantity;
    }

    private function hasStockForCombo(array $components): bool
    {
        foreach ($components as $component) {
            $product = Product::find($component['product_id']);
            $draftQuantity = collect($this->cart)
                ->filter(fn ($item) => !$item['detail_id'] && ($item['is_combo'] ?? false))
                ->sum(fn ($item) => collect($item['components'] ?? [])
                    ->where('product_id', $component['product_id'])
                    ->sum(fn ($draftComponent) => $draftComponent['quantity'] * $item['quantity']));

            if (!$product || $product->stock < $draftQuantity + $component['quantity']) {
                return false;
            }
        }

        return true;
    }

    private function hasStockForOneMoreCombo(array $item): bool
    {
        foreach ($item['components'] as $component) {
            $product = Product::find($component['product_id']);
            $draftQuantity = collect($this->cart)
                ->filter(fn ($cartItem) => !$cartItem['detail_id'] && ($cartItem['is_combo'] ?? false))
                ->sum(fn ($cartItem) => collect($cartItem['components'] ?? [])
                    ->where('product_id', $component['product_id'])
                    ->sum(fn ($draftComponent) => $draftComponent['quantity'] * $cartItem['quantity']));

            if (!$product || $product->stock < $draftQuantity + $component['quantity']) {
                return false;
            }
        }

        return true;
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
        $product = Product::with(['optionGroups.values', 'components.optionGroups.values'])->find($productId);

        if (!$product || ($this->order && $this->order->status !== 'abierto')) {
            return;
        }

        if ($product->is_combo && $product->components->isEmpty()) {
            $this->dispatch('swal', [
                'title' => 'Combo incompleto',
                'text' => 'Configura al menos un componente antes de vender este combo.',
                'icon' => 'warning',
            ]);
            return;
        }

        $optionGroups = $product->is_combo
            ? $product->components->flatMap(fn (Product $component) => $component->optionGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => "{$component->name}: {$group->name}",
                'required' => $group->required,
                'values' => $group->values->map(fn ($value) => [
                    'id' => $value->id,
                    'name' => $value->name,
                    'price_adjustment' => (float) $value->price_adjustment,
                ])->all(),
            ]))->all()
            : $product->optionGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'required' => $group->required,
                'values' => $group->values->map(fn ($value) => [
                    'id' => $value->id,
                    'name' => $value->name,
                    'price_adjustment' => (float) $value->price_adjustment,
                ])->all(),
            ])->all();

        if ($optionGroups !== []) {
            $this->configuringProduct = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'option_groups' => $optionGroups,
            ];
            $this->selectedOptionValueIds = [];
            $this->isOpenProductOptions = true;
            return;
        }

        $this->addProductToCart($product, [], $product->components->map(fn (Product $component) => [
            'product_id' => $component->id,
            'quantity' => $component->pivot->quantity,
            'selected_options' => [],
            'preparation_station_id' => $component->preparation_station_id,
            'requires_kitchen' => $component->requires_kitchen,
        ])->all());
    }

    public function confirmProductOptions(): void
    {
        $product = Product::with(['optionGroups.values', 'components.optionGroups.values'])->find($this->configuringProduct['id'] ?? null);

        if (!$product || !$product->status) {
            $this->isOpenProductOptions = false;
            return;
        }

        $selectedOptions = [];

        $productsToConfigure = $product->is_combo ? $product->components : collect([$product]);
        $components = [];

        foreach ($productsToConfigure as $configuredProduct) {
            $componentOptions = [];

            foreach ($configuredProduct->optionGroups as $group) {
                $valueId = $this->selectedOptionValueIds[$group->id] ?? null;
                $value = $group->values->firstWhere('id', (int) $valueId);

                if ($group->required && !$value) {
                    $this->dispatch('swal', [
                        'title' => 'Falta una selección',
                        'text' => "Elige una opción para {$configuredProduct->name}: {$group->name}.",
                        'icon' => 'warning',
                    ]);
                    return;
                }

                if ($value) {
                    $option = [
                        'group' => $product->is_combo ? "{$configuredProduct->name}: {$group->name}" : $group->name,
                        'value' => $value->name,
                        'value_id' => $value->id,
                        'price_adjustment' => (float) $value->price_adjustment,
                    ];
                    $selectedOptions[] = $option;
                    $componentOptions[] = $option;
                }
            }

            if ($product->is_combo) {
                $components[] = [
                    'product_id' => $configuredProduct->id,
                    'quantity' => $configuredProduct->pivot->quantity,
                    'selected_options' => $componentOptions,
                    'preparation_station_id' => $configuredProduct->preparation_station_id,
                    'requires_kitchen' => $configuredProduct->requires_kitchen,
                ];
            }
        }

        $this->addProductToCart($product, $selectedOptions, $components);
        $this->isOpenProductOptions = false;
        $this->configuringProduct = [];
        $this->selectedOptionValueIds = [];
    }

    private function addProductToCart(Product $product, array $selectedOptions, array $components = []): void
    {
        $optionKey = collect($selectedOptions)->pluck('value_id')->sort()->join('-');
        $cartKey = $this->cartKeyForProduct($product->id, $optionKey);

        if ($cartKey !== null) {
            $item = $this->cart[$cartKey];

            if (($item['is_combo'] ?? false) ? !$this->hasStockForOneMoreCombo($item) : !$this->hasStockForOneMore($item, $product)) {
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
        } elseif (($product->is_combo && !$this->hasStockForCombo($components)) || (!$product->is_combo && $product->stock <= collect($this->cart)
            ->filter(fn ($item) => (int) $item['product_id'] === $product->id && !$item['detail_id'])
            ->sum('quantity'))) {
            $this->dispatch('swal', [
                'title' => 'Sin stock',
                'text' => 'No hay suficiente stock para ' . $product->name,
                'icon' => 'warning'
            ]);
            return;
        } else {
            $price = (float) $product->price + collect($selectedOptions)->sum('price_adjustment');
            $this->cart[$this->draftCartKey($product->id, $optionKey)] = [
                'detail_id' => null,
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $price,
                'quantity' => 1,
                'subtotal' => $price,
                'selected_options' => $selectedOptions,
                'option_key' => $optionKey,
                'preparation_station_id' => $product->preparation_station_id,
                'requires_kitchen' => $product->is_combo
                    ? collect($components)->contains('requires_kitchen', true)
                    : $product->requires_kitchen,
                'cooking_status' => 'pending',
                'is_printed' => false,
                'is_combo' => $product->is_combo,
                'components' => $components,
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

        if (($item['is_combo'] ?? false) && $item['detail_id']) {
            $this->dispatch('swal', [
                'title' => 'Combo ya enviado',
                'text' => 'Agrega otro combo para pedir una porción adicional.',
                'icon' => 'warning',
            ]);
            return;
        }

        if (($item['is_combo'] ?? false) ? !$this->hasStockForOneMoreCombo($item) : !$this->hasStockForOneMore($item, $product)) {
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

        if (($this->cart[$cartKey]['is_combo'] ?? false) && $this->cart[$cartKey]['detail_id']) {
            $this->dispatch('swal', [
                'title' => 'Combo ya enviado',
                'text' => 'No se puede cambiar la cantidad de un combo ya enviado.',
                'icon' => 'warning',
            ]);
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

                    $components = OrderDetail::query()
                        ->where('parent_detail_id', $detail->id)
                        ->lockForUpdate()
                        ->get();
                    $correctionIds = [];

                    if ($components->isNotEmpty()) {
                        if ($components->contains(fn (OrderDetail $component) => in_array($component->cooking_status, ['served', 'cancelled'], true))) {
                            throw new \RuntimeException('Un componente del combo ya no se puede eliminar.');
                        }

                        foreach ($components as $component) {
                            $product = Product::query()->whereKey($component->product_id)->lockForUpdate()->first();
                            $product?->increment('stock', $component->quantity);

                            if ($component->is_printed || $component->cooking_status !== 'pending') {
                                $component->update(['cooking_status' => 'cancelled']);
                                $correctionIds[] = OrderCorrection::record($component, 'cancel')->id;
                            }
                        }

                        $detail->delete();
                        $wasSent = $correctionIds !== [];
                    } else {
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
                            $correctionIds[] = OrderCorrection::record($detail, 'cancel')->id;
                        } else {
                            $detail->delete();
                        }
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
                        'correction_ids' => $correctionIds,
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
                $this->dispatchKitchenCorrections($result['order_id'], $result['correction_ids']);
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
                $correctionIds = [];
                $components = OrderDetail::query()
                    ->where('parent_detail_id', $detail->id)
                    ->lockForUpdate()
                    ->get();

                if ($changed) {
                    $detail->update(['notes' => $newNotes]);

                    if ($components->isNotEmpty()) {
                        foreach ($components as $component) {
                            if (in_array($component->cooking_status, ['served', 'cancelled'], true)) {
                                throw new \RuntimeException('Un componente ya entregado no se puede modificar.');
                            }

                            $component->update(['notes' => $newNotes]);

                            if ($component->is_printed || $component->cooking_status !== 'pending') {
                                $correctionIds[] = OrderCorrection::record($component, 'update')->id;
                            }
                        }
                    } elseif ($detail->is_printed || $detail->cooking_status !== 'pending') {
                        $correctionIds[] = OrderCorrection::record($detail, 'update')->id;
                    }
                }

                return [
                    'changed' => $changed,
                    'detail_id' => $detail->id,
                    'order_id' => $order->id,
                    'correction_ids' => $correctionIds,
                ];
            });

            foreach ($this->cart as $cartKey => $item) {
                if ($item['detail_id'] == $detailId) {
                    $this->cart[$cartKey]['notes'] = $notes;
                    break;
                }
            }

            if ($result['correction_ids'] !== []) {
                $this->dispatchKitchenCorrections($result['order_id'], $result['correction_ids']);
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

                        if ($product->is_combo) {
                            if ($difference !== 0) {
                                throw new \RuntimeException('La cantidad de un combo enviado no se puede cambiar.');
                            }

                            $components = OrderDetail::query()
                                ->where('parent_detail_id', $detail->id)
                                ->lockForUpdate()
                                ->get();
                            $componentCorrectionIds = [];

                            foreach ($components as $component) {
                                if (in_array($component->cooking_status, ['served', 'cancelled'], true)) {
                                    throw new \RuntimeException('Un componente ya entregado no se puede modificar.');
                                }

                                if ($component->notes !== $newNotes) {
                                    $component->update(['notes' => $newNotes]);

                                    if ($component->is_printed || $component->cooking_status !== 'pending') {
                                        $componentCorrectionIds[] = OrderCorrection::record($component, 'update')->id;
                                    }
                                }
                            }

                            $detail->update(['notes' => $newNotes]);
                            $correctionIds = [...$correctionIds, ...$componentCorrectionIds];
                            continue;
                        }

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
                                'selected_options' => $detail->selected_options,
                                'preparation_station_id' => $detail->preparation_station_id,
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

                    if ($product->is_combo) {
                        $components = $item['components'] ?? [];

                        if ($components === []) {
                            throw new \RuntimeException("El combo {$product->name} no tiene componentes.");
                        }

                        $componentProducts = Product::query()
                            ->whereIn('id', collect($components)->pluck('product_id')->sort())
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->get()
                            ->keyBy('id');
                        $parent = $order->details()->create([
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $item['price'],
                            'subtotal' => $item['price'] * $quantity,
                            'notes' => $notes ?: null,
                            'selected_options' => $item['selected_options'] ?? [],
                            'requires_kitchen' => false,
                            'cooking_status' => 'pending',
                            'is_printed' => false,
                        ]);

                        foreach ($components as $component) {
                            $componentProduct = $componentProducts->get($component['product_id']);
                            $componentQuantity = (int) $component['quantity'] * $quantity;

                            if (!$componentProduct || $componentProduct->is_combo || $componentProduct->stock < $componentQuantity) {
                                throw new \RuntimeException("Stock insuficiente para un componente de {$product->name}");
                            }

                            $componentProduct->decrement('stock', $componentQuantity);
                            $order->details()->create([
                                'parent_detail_id' => $parent->id,
                                'product_id' => $componentProduct->id,
                                'quantity' => $componentQuantity,
                                'price' => 0,
                                'subtotal' => 0,
                                'notes' => $notes ?: null,
                                'selected_options' => $component['selected_options'] ?? [],
                                'preparation_station_id' => $componentProduct->preparation_station_id,
                                'requires_kitchen' => $componentProduct->requires_kitchen,
                                'cooking_status' => 'pending',
                                'is_printed' => false,
                            ]);
                        }

                        continue;
                    }

                    if ($product->stock < $quantity) {
                        throw new \RuntimeException("Stock insuficiente para {$product->name}");
                    }

                    $product->decrement('stock', $quantity);
                    $order->details()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $item['price'],
                        'subtotal' => $item['price'] * $quantity,
                        'notes' => $notes ?: null,
                        'selected_options' => $item['selected_options'] ?? [],
                        'preparation_station_id' => $item['preparation_station_id'] ?? $product->preparation_station_id,
                        'requires_kitchen' => $item['requires_kitchen'],
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
            $this->order->load(['details.product.category', 'details.components.product']);
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

            $detail = OrderDetail::query()
                ->whereKey($detailId)
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$detail) {
                return false;
            }

            $components = OrderDetail::query()
                ->where('parent_detail_id', $detail->id)
                ->lockForUpdate()
                ->get();

            if ($components->isNotEmpty()) {
                if ($components->where('requires_kitchen', true)
                    ->contains(fn (OrderDetail $component) => !in_array($component->cooking_status, ['ready', 'served'], true))) {
                    return false;
                }

                $components->where('cooking_status', 'ready')->each->update(['cooking_status' => 'served']);
                $detail->update(['cooking_status' => 'served']);
                return true;
            }

            if ($detail->cooking_status === 'ready' || (!$detail->requires_kitchen && !in_array($detail->cooking_status, ['served', 'cancelled'], true))) {
                $detail->update(['cooking_status' => 'served']);
                return true;
            }

            return false;
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

        $products = Product::with('optionGroups.values')->where('status', 1)
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->paginate(12, ['*'], 'pageProducts');

        return view('livewire.order-create-component', compact('customers', 'products'));
    }
}
