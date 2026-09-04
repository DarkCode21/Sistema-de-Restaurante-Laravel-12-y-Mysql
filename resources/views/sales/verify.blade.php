<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta de comprobante</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 p-5 text-slate-900">
    <main class="mx-auto mt-10 max-w-md overflow-hidden rounded-3xl bg-white shadow-xl">
        <div class="bg-orange-600 px-7 py-8 text-center text-white">
            <p class="text-xs font-bold uppercase tracking-[0.2em]">Comprobante verificado</p>
            <h1 class="mt-2 text-2xl font-black">{{ $receiptCompany->company_name }}</h1>
        </div>
        <div class="space-y-5 p-7">
            <div class="rounded-2xl bg-orange-50 p-4 text-center">
                <p class="text-xs font-bold uppercase tracking-wider text-orange-700">Operación</p>
                <p class="mt-1 text-lg font-black text-orange-950">{{ $receiptNumber }}</p>
            </div>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Fecha</dt><dd class="text-right font-semibold">{{ $sale->paid_at->format('d/m/Y H:i') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Atención</dt><dd class="text-right font-semibold">{{ $sale->order?->service_label ?? 'Venta directa' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Pago</dt><dd class="text-right font-semibold">{{ $sale->payments->pluck('method.name')->filter()->join(' + ') }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base"><dt class="font-bold">Total pagado</dt><dd class="font-black">{{ $receiptCompany->currency_simbol }}{{ number_format($sale->total, 2) }}</dd></div>
            </dl>
            <p class="text-center text-xs leading-5 text-slate-500">Documento de control interno. No es un comprobante electrónico SUNAT.</p>
        </div>
    </main>
</body>
</html>
