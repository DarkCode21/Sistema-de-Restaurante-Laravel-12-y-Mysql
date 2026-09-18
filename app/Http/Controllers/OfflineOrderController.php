<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfflineOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->hasRole('mesero')) {
            return response()->json(['message' => 'Solo los meseros pueden sincronizar pedidos sin conexion.'], 403);
        }

        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'branch_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'order_type' => ['required', Rule::in(Order::ORDER_TYPES)],
            'table_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((int) $data['user_id'] !== (int) $request->user()->id
            || (int) $data['branch_id'] !== (int) session('branch_id')
            || !$request->user()->branches()->whereKey($data['branch_id'])->exists()) {
            return response()->json(['message' => 'El pedido no pertenece a la sesion o sede activa.'], 409);
        }

        if (($data['order_type'] === 'dine_in' && !$data['table_id'])
            || ($data['order_type'] === 'delivery' && (blank($data['customer_name'] ?? null) || blank($data['customer_phone'] ?? null) || blank($data['delivery_address'] ?? null)))) {
            return response()->json(['message' => 'Faltan datos obligatorios para el pedido.'], 422);
        }

        $existingOrder = Order::query()
            ->where('branch_id', $data['branch_id'])
            ->where('offline_token', $data['token'])
            ->first();
        if ($existingOrder) {
            return response()->json([
                'order_id' => $existingOrder->id,
                'synchronized' => true,
                'print_jobs' => PrintJob::where('order_id', $existingOrder->id)->where('status', 'queued')->get()
                    ->map(function (PrintJob $job): array {
                        $job->increment('attempts');

                        return $job->fresh()->payload();
                    })->all(),
            ]);
        }

        try {
            $order = DB::transaction(function () use ($data, $request) {
                $table = null;
                if ($data['order_type'] === 'dine_in') {
                    $table = Table::query()->whereKey($data['table_id'])->lockForUpdate()->first();

                    if (!$table || $table->status !== 'libre') {
                        throw new \RuntimeException('La mesa ya no esta disponible.');
                    }
                }

                $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->all();
                $products = Product::query()
                    ->availableInActiveBranch()
                    ->with(['recipeIngredients', 'activePromotion', 'branchStocks'])
                    ->withCount('optionGroups')
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($products->count() !== count($productIds)) {
                    throw new \RuntimeException('Uno de los productos ya no esta disponible.');
                }

                if ($products->contains(fn (Product $product) => $product->is_combo || $product->option_groups_count > 0)) {
                    throw new \RuntimeException('Los combos y productos con opciones deben registrarse con conexion.');
                }

                $order = Order::create([
                    'table_id' => $table?->id,
                    'user_id' => $request->user()->id,
                    'order_type' => $data['order_type'],
                    'customer_name' => trim(($data['customer_name'] ?? null) ?: 'Consumidor Final'),
                    'customer_phone' => filled($data['customer_phone'] ?? null) ? trim($data['customer_phone']) : null,
                    'delivery_address' => $data['order_type'] === 'delivery' ? trim($data['delivery_address'] ?? '') : null,
                    'status' => 'abierto',
                    'total' => 0,
                    'amount_pending' => 0,
                    'offline_token' => $data['token'],
                ]);

                foreach ($data['items'] as $item) {
                    $product = $products->get((int) $item['product_id']);
                    $quantity = (int) $item['quantity'];
                    $notes = filled($item['notes'] ?? null) ? trim($item['notes']) : null;
                    $breakdown = $product->unitBreakdown($quantity);

                    $detail = $order->details()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $breakdown['price'],
                        'discount' => $breakdown['discount'],
                        'tax_rate' => $breakdown['tax_rate'],
                        'tax' => $breakdown['tax'],
                        'promotion_id' => $breakdown['promotion_id'],
                        'subtotal' => $breakdown['subtotal'],
                        'notes' => $notes,
                        'selected_options' => [],
                        'preparation_station_id' => $product->preparation_station_id,
                        'requires_kitchen' => $product->requires_kitchen,
                        'cooking_status' => 'pending',
                        'is_printed' => false,
                    ]);
                    $detail->consumeInventory($product, $quantity);
                }

                $total = (float) $order->details()->sum('subtotal') + (float) $order->details()->sum('tax');
                $order->update(['total' => $total, 'amount_pending' => $total]);
                $table?->update(['status' => 'ocupada']);

                $jobs = collect();
                $setting = Setting::first();
                if ($setting?->direct_printing) {
                    $jobs = $order->details()->with('preparationStation')->get()
                        ->groupBy(fn ($detail) => $detail->preparation_station_id ?: 'general')
                        ->map(function ($details) use ($order, $setting) {
                            $station = $details->first()->preparationStation;
                            $printerName = $station?->printer_name ?: $setting->printer_name;

                            return filled($printerName) ? PrintJob::create([
                                'branch_id' => $order->branch_id,
                                'order_id' => $order->id,
                                'preparation_station_id' => $station?->id,
                                'printer_name' => $printerName,
                                'detail_ids' => $details->pluck('id')->all(),
                            ]) : null;
                        })->filter()->values();
                }

                return [$order, $jobs];
            });
        } catch (QueryException $exception) {
            $existingOrder = Order::withoutGlobalScopes()
                ->where('branch_id', $data['branch_id'])
                ->where('offline_token', $data['token'])
                ->first();

            if ($existingOrder) {
                return response()->json(['order_id' => $existingOrder->id, 'synchronized' => true]);
            }

            throw $exception;
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'order_id' => $order[0]->id,
            'synchronized' => false,
            'print_jobs' => $order[1]->map(function (PrintJob $job): array {
                $job->increment('attempts');

                return $job->fresh()->payload();
            })->all(),
        ], 201);
    }
}
