<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $empresa = Setting::first() ?? new Setting([
                'company_name' => config('app.name', 'Ceviche Flow'),
                'currency_simbol' => 'S/',
                'logo_path' => null,
                'favicon_path' => null,
                'direct_printing' => false,
                'separate_orders' => false,
            ]);

            $view->with('empresa', $empresa);
        });
    }
}
