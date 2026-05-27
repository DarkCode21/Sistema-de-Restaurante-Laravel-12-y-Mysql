<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'company_name'    => 'Ceviche Flow',
                'company_email'   => 'contacto@ceviche.com',
                'company_phone'   => '+51 987 654 321',
                'company_address' => 'Av. Central 123, Centro',
                'tax_id'          => 'RUC 20123456789',
                'currency_simbol' => 'S/',
                'timezone'        => 'America/Lima',
                'logo_path'       => null,
                'favicon_path'    => null,
                'social_networks' => [
                    'facebook'  => 'https://facebook.com/ceviche',
                    'instagram' => 'https://instagram.com/ceviche',
                    'linkedin'  => 'https://linkedin.com/company/ceviche',
                    'whatsapp'  => 'https://wa.me/51987654321',
                ],
            ]
        );
    }
}