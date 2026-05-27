<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('settings')) {
            $settings = Setting::first();

            if ($settings && $settings->timezone) {
                // 1. Sobreescribe la zona horaria de la configuración
                Config::set('app.timezone', $settings->timezone);

                // 2. Establece la zona horaria en PHP para funciones nativas
                date_default_timezone_set($settings->timezone);
            }
        }
    }
}
