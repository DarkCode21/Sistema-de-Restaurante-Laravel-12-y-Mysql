<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Efectivo', 'is_efectivo' => true],
            ['name' => 'Tarjeta crédito', 'is_efectivo' => false],
            ['name' => 'Tarjeta débito', 'is_efectivo' => false],
            ['name' => 'Transferencia bancaria', 'is_efectivo' => false],
            ['name' => 'Cheque', 'is_efectivo' => false],
            ['name' => 'Pago móvil', 'is_efectivo' => false],
            ['name' => 'Criptomonedas', 'is_efectivo' => false],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}
