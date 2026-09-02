<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GrillDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $operator = User::firstOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'name' => 'Administrador',
                    'password' => Hash::make('admin123'),
                    'type' => 'user',
                    'email_verified_at' => now(),
                ],
            );

            Product::query()->update(['status' => false]);

            $categories = collect([
                'Parrillas de Pollo',
                'Parrillas de Carne',
                'Parrillas de Cerdo',
                'Combos Parrilleros',
                'Acompañamientos',
                'Extras y Salsas',
                'Bebidas',
            ])->mapWithKeys(function (string $name): array {
                $category = Category::withTrashed()->firstOrNew(['name' => $name]);
                $category->save();

                if ($category->trashed()) {
                    $category->restore();
                }

                return [$name => $category];
            });

            $menu = [
                ['Parrillas de Pollo', 'Parrilla de Pollo - Pecho', 24.00, 45, 'products/grill-chicken.png', true],
                ['Parrillas de Pollo', 'Parrilla de Pollo - Pierna', 22.00, 48, 'products/grill-chicken.png', true],
                ['Parrillas de Pollo', '¼ Pollo a la Parrilla', 16.00, 60, 'products/grill-chicken.png', true],
                ['Parrillas de Pollo', 'Pollo Entero a la Parrilla', 58.00, 20, 'products/grill-chicken.png', true],
                ['Parrillas de Cerdo', 'Parrilla de Cerdo', 30.00, 35, 'products/grill-pork.png', true],
                ['Parrillas de Cerdo', 'Costillas de Cerdo BBQ', 34.00, 30, 'products/grill-pork.png', true],
                ['Parrillas de Cerdo', 'Brochetas de Cerdo', 18.00, 34, 'products/grill-pork.png', true],
                ['Parrillas de Carne', 'Parrilla de Carne', 36.00, 32, 'products/grill-beef.png', true],
                ['Parrillas de Carne', 'Bife a la Parrilla', 42.00, 25, 'products/grill-beef.png', true],
                ['Parrillas de Carne', 'Anticuchos de Corazón', 22.00, 40, 'products/grill-beef.png', true],
                ['Combos Parrilleros', 'Combo Parrillero Personal', 34.00, 28, 'products/grill-combo.png', true],
                ['Combos Parrilleros', 'Combo Parrillero Dúo', 64.00, 18, 'products/grill-combo.png', true],
                ['Combos Parrilleros', 'Parrilla Familiar', 118.00, 12, 'products/grill-combo.png', true],
                ['Acompañamientos', 'Porción de Arroz Blanco', 5.00, 80, 'products/grill-sides.png', true],
                ['Acompañamientos', 'Papas Ancochadas', 8.00, 65, 'products/grill-sides.png', true],
                ['Acompañamientos', 'Ensalada Criolla', 6.00, 55, 'products/grill-sides.png', true],
                ['Acompañamientos', 'Choclo a la Parrilla', 7.00, 45, 'products/grill-sides.png', true],
                ['Acompañamientos', 'Plátano a la Parrilla', 6.00, 40, 'products/grill-sides.png', true],
                ['Extras y Salsas', 'Huevo Frito', 4.00, 45, 'products/grill-sides.png', true],
                ['Extras y Salsas', 'Salsa Chimichurri', 2.00, 120, 'products/grill-sides.png', false],
                ['Extras y Salsas', 'Salsa de Ají', 2.00, 120, 'products/grill-sides.png', false],
                ['Bebidas', 'Chicha Morada', 7.00, 90, 'products/grill-drinks.png', false],
                ['Bebidas', 'Maracuyá Frozen', 9.00, 60, 'products/grill-drinks.png', false],
                ['Bebidas', 'Inca Kola Personal', 6.00, 100, 'products/grill-drinks.png', false],
                ['Bebidas', 'Coca-Cola Personal', 6.00, 100, 'products/grill-drinks.png', false],
                ['Bebidas', 'Agua Mineral', 4.00, 80, 'products/grill-drinks.png', false],
                ['Bebidas', 'Cerveza Pilsen', 10.00, 70, 'products/grill-drinks.png', false],
            ];

            $products = collect($menu)->mapWithKeys(function (array $item) use ($categories): array {
                [$categoryName, $name, $price, $stock, $image, $requiresKitchen] = $item;

                $product = Product::updateOrCreate(
                    ['name' => $name],
                    [
                        'category_id' => $categories[$categoryName]->id,
                        'price' => $price,
                        'stock' => $stock,
                        'status' => true,
                        'image' => $image,
                        'requires_kitchen' => $requiresKitchen,
                    ],
                );

                return [$name => $product];
            });

            $tableDefinitions = [
                ['Mesa 1', 2, 40, 40], ['Mesa 2', 2, 280, 40], ['Mesa 3', 4, 520, 40],
                ['Mesa 4', 4, 40, 310], ['Mesa 5', 4, 280, 310], ['Mesa 6', 6, 520, 310],
                ['Terraza 1', 2, 760, 40], ['Terraza 2', 4, 760, 310], ['Terraza 3', 4, 1000, 40],
                ['Mesa Familiar', 8, 1000, 310],
            ];

            $tables = collect($tableDefinitions)->mapWithKeys(function (array $item): array {
                [$name, $capacity, $x, $y] = $item;
                $table = Table::updateOrCreate(
                    ['name' => $name],
                    ['capacity' => $capacity, 'x_pos' => $x, 'y_pos' => $y, 'status' => 'libre'],
                );

                return [$name => $table];
            });

            $methods = collect([
                ['Efectivo', true], ['Yape', false], ['Tarjeta', false],
            ])->mapWithKeys(function (array $item): array {
                [$name, $cash] = $item;
                $method = PaymentMethod::withTrashed()->firstOrNew(['name' => $name]);
                $method->is_efectivo = $cash;
                $method->save();

                if ($method->trashed()) {
                    $method->restore();
                }

                return [$name => $method];
            });

            $cashRegister = CashRegister::withTrashed()->firstOrNew(['name' => 'Caja Principal']);
            $cashRegister->fill([
                'opening_amount' => 300,
                'current_amount' => 300,
                'status' => 'open',
                'opened_by' => $operator->id,
                'opened_at' => now()->startOfDay(),
                'notes' => 'Caja de demostración para parrillería',
            ]);
            $cashRegister->save();
            if ($cashRegister->trashed()) {
                $cashRegister->restore();
            }

            $this->removePreviousDemoData();

            $historicTickets = [
                [now()->subMonths(5)->setDay(12)->setTime(13, 20), 'Mesa 1', 'Familia Quispe', 'Efectivo', [['Parrilla de Pollo - Pecho', 2], ['Papas Ancochadas', 2], ['Chicha Morada', 2]]],
                [now()->subMonths(4)->setDay(18)->setTime(20, 10), 'Mesa 3', 'Carlos Ramírez', 'Tarjeta', [['Parrilla de Carne', 2], ['Porción de Arroz Blanco', 2], ['Coca-Cola Personal', 2]]],
                [now()->subMonths(3)->setDay(8)->setTime(19, 40), 'Terraza 1', 'Ana Torres', 'Yape', [['Parrilla de Cerdo', 2], ['Choclo a la Parrilla', 2], ['Maracuyá Frozen', 2]]],
                [now()->subMonths(2)->setDay(22)->setTime(14, 15), 'Mesa Familiar', 'Familia Salazar', 'Efectivo', [['Parrilla Familiar', 1], ['Chicha Morada', 4], ['Ensalada Criolla', 2]]],
                [now()->subMonth()->setDay(15)->setTime(21, 0), 'Mesa 5', 'Jorge Medina', 'Tarjeta', [['Combo Parrillero Dúo', 1], ['Cerveza Pilsen', 2], ['Papas Ancochadas', 1]]],
                [today()->setTime(12, 40), 'Mesa 4', 'Lucía Paredes', 'Efectivo', [['¼ Pollo a la Parrilla', 2], ['Porción de Arroz Blanco', 2], ['Chicha Morada', 2]]],
                [today()->setTime(14, 5), 'Terraza 2', 'Miguel Rojas', 'Yape', [['Costillas de Cerdo BBQ', 1], ['Papas Ancochadas', 1], ['Inca Kola Personal', 1]]],
                [today()->setTime(19, 15), 'Mesa 6', 'Grupo Empresa', 'Tarjeta', [['Parrilla Familiar', 1], ['Anticuchos de Corazón', 2], ['Cerveza Pilsen', 4]]],
                [today()->setTime(20, 5), 'Terraza 3', 'Rosa Flores', 'Efectivo', [['Combo Parrillero Personal', 2], ['Salsa Chimichurri', 2], ['Maracuyá Frozen', 2]]],
            ];

            foreach ($historicTickets as $index => [$paidAt, $tableName, $customer, $methodName, $lines]) {
                $this->createCompletedTicket(
                    $tables[$tableName],
                    $operator,
                    $cashRegister,
                    $methods[$methodName],
                    $products,
                    $paidAt,
                    $customer,
                    sprintf('DEMO-GRILL-%02d', $index + 1),
                    $lines,
                );
            }

            $this->createOpenOrder($tables['Mesa 2'], $operator, $products, 'María López', 'DEMO-GRILL-OPEN-01', [
                ['Parrilla de Pollo - Pecho', 1, 'Bien dorado, con papas ancochadas'],
                ['Papas Ancochadas', 1, null],
                ['Chicha Morada', 2, null],
            ]);
            $this->createOpenOrder($tables['Mesa 5'], $operator, $products, 'Roberto Díaz', 'DEMO-GRILL-OPEN-02', [
                ['Parrilla de Carne', 1, 'Término medio'],
                ['Porción de Arroz Blanco', 1, null],
                ['Salsa Chimichurri', 1, null],
            ]);
            $this->createOpenOrder($tables['Terraza 1'], $operator, $products, 'Familia Castro', 'DEMO-GRILL-OPEN-03', [
                ['Parrilla de Cerdo', 2, 'Sin cebolla'],
                ['Choclo a la Parrilla', 2, null],
                ['Coca-Cola Personal', 2, null],
            ]);
        });
    }

    private function removePreviousDemoData(): void
    {
        $orderIds = Order::where('customer_phone', 'like', 'DEMO-GRILL-%')->pluck('id');
        $saleIds = Sale::whereIn('order_id', $orderIds)->pluck('id');

        Payment::whereIn('sale_id', $saleIds)->delete();
        SaleDetail::whereIn('sale_id', $saleIds)->delete();
        Sale::whereIn('id', $saleIds)->delete();
        Order::whereIn('id', $orderIds)->delete();
    }

    private function createCompletedTicket(Table $table, User $operator, CashRegister $cashRegister, PaymentMethod $method, $products, Carbon $paidAt, string $customer, string $marker, array $lines): void
    {
        $total = collect($lines)->sum(fn (array $line) => $products[$line[0]]->price * $line[1]);
        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => $operator->id,
            'customer_name' => $customer,
            'customer_phone' => $marker,
            'status' => 'cerrado',
            'total' => $total,
            'amount_pending' => 0,
        ]);
        $order->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->saveQuietly();

        foreach ($lines as [$productName, $quantity]) {
            $product = $products[$productName];
            $subtotal = $product->price * $quantity;
            $order->details()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'requires_kitchen' => $product->requires_kitchen,
                'price' => $product->price,
                'subtotal' => $subtotal,
                'cooking_status' => 'served',
            ]);
        }

        $tip = round($total * 0.06, 2);
        $sale = Sale::create([
            'order_id' => $order->id,
            'cash_register_id' => $cashRegister->id,
            'subtotal' => $total,
            'tax' => 0,
            'tip' => $tip,
            'total' => $total,
            'paid_amount' => $total,
            'change' => 0,
            'paid_at' => $paidAt,
        ]);
        $sale->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->saveQuietly();

        foreach ($lines as [$productName, $quantity]) {
            $product = $products[$productName];
            SaleDetail::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
                'tax' => 0,
                'subtotal' => $product->price * $quantity,
            ]);
        }

        $payment = Payment::create([
            'sale_id' => $sale->id,
            'payment_method_id' => $method->id,
            'amount' => $total,
            'received_amount' => $total,
            'returned_amount' => 0,
            'reference' => 'DEMO-' . $marker,
        ]);
        $payment->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->saveQuietly();
    }

    private function createOpenOrder(Table $table, User $operator, $products, string $customer, string $marker, array $lines): void
    {
        $total = collect($lines)->sum(fn (array $line) => $products[$line[0]]->price * $line[1]);
        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => $operator->id,
            'customer_name' => $customer,
            'customer_phone' => $marker,
            'status' => 'abierto',
            'total' => $total,
            'amount_pending' => $total,
        ]);

        foreach ($lines as $position => [$productName, $quantity, $notes]) {
            $product = $products[$productName];
            $order->details()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'requires_kitchen' => $product->requires_kitchen,
                'price' => $product->price,
                'subtotal' => $product->price * $quantity,
                'notes' => $notes,
                'cooking_status' => $product->requires_kitchen
                    ? ($position === 0 ? 'in_progress' : 'pending')
                    : 'pending',
            ]);
        }

        $table->update(['status' => 'ocupada']);
    }
}
