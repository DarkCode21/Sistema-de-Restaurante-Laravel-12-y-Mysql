<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first() ?: new Setting();
        return view('settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = Setting::first() ?: new Setting();

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'required|string|max:20',
            'company_address' => 'required|string',
            'currency_simbol' => 'required|string',
            'tax_id' => 'nullable|string|max:50',
            'timezone' => 'required|string',
            'logo_path' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'favicon_path' => 'nullable|image|mimes:png,ico|max:512',
            'social_networks' => 'nullable|array',

            'direct_printing' => 'boolean',
            'printer_name' => 'required_if:direct_printing,1|nullable|string|max:255',
            'kitchen_printer_name' => 'required_if:direct_printing,1|nullable|string|max:255',
        ], [
            'company_name.required' => 'El nombre comercial de la empresa es obligatorio.',
            'company_email.required' => 'El correo electrónico de soporte es requerido.',
            'company_email.email' => 'Por favor, ingresa una dirección de correo válida.',
            'company_phone.required' => 'El número de teléfono es obligatorio.',
            'company_address.required' => 'La dirección principal es requerida.',
            'currency_simbol.required' => 'El símbolo de la moneda es obligatorio.',
            'timezone.required' => 'Debes seleccionar una zona horaria.',
        
            'logo_path.image' => 'El logo debe ser una imagen válida.',
            'logo_path.mimes' => 'El logo solo acepta formatos: jpeg, png, jpg o svg.',
            'logo_path.max' => 'El logo no debe pesar más de 2 MB (2048 KB).',
            
            'favicon_path.image' => 'El favicon debe ser una imagen.',
            'favicon_path.mimes' => 'El favicon solo acepta formatos: png o ico.',
            'favicon_path.max' => 'El favicon no debe pesar más de 512 KB.',
        
            'printer_name.required_if' => 'El nombre de la impresora es obligatorio si la impresión directa está activa.',
            'printer_name.max' => 'El nombre de la impresora no debe superar los 255 caracteres.',
            'kitchen_printer_name.required_if' => 'El nombre de la impresora de cocina es obligatorio si la impresión directa está activa.',
            'kitchen_printer_name.max' => 'El nombre de la impresora de cocina no debe superar los 255 caracteres.',
        ]);

        $validated['social_networks'] = $request->input('social_networks', []);
        $validated['direct_printing'] = $request->boolean('direct_printing');
        $validated['printer_name'] = $validated['direct_printing'] ? $request->input('printer_name') : null;
        $validated['kitchen_printer_name'] = $validated['direct_printing'] ? $request->input('kitchen_printer_name') : null;

        if ($request->hasFile('logo_path')) {
            if ($setting->exists && $setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $validated['logo_path'] = $request->file('logo_path')->store('branding', 'public');
        }

        if ($request->hasFile('favicon_path')) {
            if ($setting->exists && $setting->favicon_path) {
                Storage::disk('public')->delete($setting->favicon_path);
            }
            $validated['favicon_path'] = $request->file('favicon_path')->store('branding', 'public');
        }

        Setting::updateOrCreate(['id' => 1], $validated);

        cache()->forget('app_settings');

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
