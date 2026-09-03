<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'tax_id',
        'currency_simbol',
        'default_tax_rate',
        'logo_path',
        'favicon_path',
        'timezone',
        'direct_printing',
        'separate_orders',
        'alert_sounds_enabled',
        'printer_name',
        'kitchen_printer_name',
        'social_networks',
    ];

    protected $casts = [
        'social_networks' => 'array',
        'direct_printing' => 'boolean',
        'separate_orders' => 'boolean',
        'alert_sounds_enabled' => 'boolean',
        'default_tax_rate' => 'decimal:2',
    ];

    /**
     * Helper para obtener la URL del logo
     */
    public function getLogoUrlAttribute()
    {
        return $this->logo_path 
            ? Storage::url($this->logo_path) 
            : asset('images/logo.png');
    }
}
