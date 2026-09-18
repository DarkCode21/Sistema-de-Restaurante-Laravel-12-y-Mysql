<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->firstOrFail();
        $company->update(['name' => 'PARRILLAS KING', 'slug' => 'parrillas-king']);
        $logoPath = 'branding/parrillas-king.webp';
        Storage::disk('public')->put($logoPath, File::get(database_path('seeders/media/parrillas-king.webp')));

        Setting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'company_id'      => $company->id,
                'company_name'    => $company->name,
                'company_email'   => 'contacto@ceviche.com',
                'company_phone'   => '+51 987 654 321',
                'company_address' => 'Av. Central 123, Centro',
                'tax_id'          => 'RUC 20123456789',
                'currency_simbol' => 'S/',
                'default_tax_rate' => 18,
                'timezone'        => 'America/Lima',
                'logo_path'       => $logoPath,
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
