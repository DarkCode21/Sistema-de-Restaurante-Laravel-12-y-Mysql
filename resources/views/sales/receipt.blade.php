<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { margin: 5mm; color: #111; font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; }
        .center { text-align: center; }
        .right { text-align: right; }
        .divider { border-top: 0.5pt dashed #333; margin: 8px 0; }
        .brand { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
        .logo { display: block; margin: 0 auto 7px; max-height: 46px; max-width: 112px; }
        .ticket-title { font-size: 10pt; font-weight: bold; letter-spacing: 0.5pt; margin-top: 8px; }
        .muted { color: #555; font-size: 7.5pt; }
        table { border-collapse: collapse; width: 100%; }
        th { border-bottom: 0.5pt solid #222; font-size: 7.5pt; padding: 4px 0; text-align: left; }
        td { padding: 4px 0; vertical-align: top; }
        .total td { border-top: 0.8pt solid #222; font-size: 11pt; font-weight: bold; padding-top: 6px; }
        .payment td { font-size: 8pt; }
        .qr-wrap { width: 100%; margin: 10px 0 5px; }
        .qr-wrap td { padding: 0; text-align: center; }
        .qr { width: 100px; height: 100px; }
        .footer { font-size: 7.5pt; margin-top: 12px; }
    </style>
</head>
<body>
    <header class="center">
        @if ($receiptLogo)<img class="logo" src="{{ $receiptLogo }}" alt="Logo de {{ $receiptCompany->company_name }}">@endif
        <div class="brand">{{ $receiptCompany->company_name }}</div>
        @if ($receiptCompany->company_address)<div>{{ $receiptCompany->company_address }}</div>@endif
        @if ($receiptCompany->tax_id)<div>{{ $receiptCompany->tax_id }}</div>@endif
        @if ($receiptCompany->company_phone)<div>Tel: {{ $receiptCompany->company_phone }}</div>@endif
        <div class="ticket-title">TICKET DE VENTA</div>
    </header>

    <div class="divider"></div>

    <table class="muted">
        <tr><td>Correlativo interno</td><td class="right">{{ $receiptNumber }}</td></tr>
        <tr><td>Fecha</td><td class="right">{{ $sale->paid_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Atención</td><td class="right">{{ $sale->order?->service_label ?? 'Venta directa' }}</td></tr>
        <tr><td>Cajero</td><td class="right">{{ $sale->cashier?->name ?? $sale->order?->user?->name ?? 'No registrado' }}</td></tr>
        <tr><td>Cliente</td><td class="right">{{ $sale->customer_name && $sale->customer_name !== 'Consumidor Final' ? $sale->customer_name : 'Público general' }}</td></tr>
    </table>

    <div class="divider"></div>

    <table>
        <thead>
            <tr><th>DESCRIPCIÓN</th><th class="right">CANT.</th><th class="right">IMPORTE</th></tr>
        </thead>
        <tbody>
            @foreach ($sale->details as $item)
                <tr>
                    <td>
                        {{ $item->product_name ?: $item->product?->name ?: 'Producto histórico' }}
                        @if (!empty($item->selected_options))<br><span class="muted">{{ collect($item->selected_options)->map(fn ($option) => $option['group'] . ': ' . $option['value'])->join(' · ') }}</span>@endif
                    </td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ $receiptCompany->currency_simbol }}{{ number_format($item->subtotal + $item->tax, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr><td class="right">SUBTOTAL:</td><td class="right">{{ $receiptCompany->currency_simbol }}{{ number_format($sale->subtotal, 2) }}</td></tr>
        @if ($sale->tax > 0)<tr><td class="right">IGV:</td><td class="right">{{ $receiptCompany->currency_simbol }}{{ number_format($sale->tax, 2) }}</td></tr>@endif
        @if ($sale->manual_discount > 0)<tr><td class="right">DESCUENTO:</td><td class="right">-{{ $receiptCompany->currency_simbol }}{{ number_format($sale->manual_discount, 2) }}</td></tr>@endif
        @if ($sale->tip > 0)<tr><td class="right">PROPINA:</td><td class="right">{{ $receiptCompany->currency_simbol }}{{ number_format($sale->tip, 2) }}</td></tr>@endif
        <tr class="total"><td class="right">TOTAL:</td><td class="right">{{ $receiptCompany->currency_simbol }}{{ number_format($sale->total, 2) }}</td></tr>
    </table>

    <div class="divider"></div>

    <table class="payment">
        @foreach ($sale->payments as $payment)
            <tr><td>{{ strtoupper($payment->method?->name ?? 'MÉTODO') }}</td><td class="right">{{ $receiptCompany->currency_simbol }}{{ number_format($payment->amount, 2) }}</td></tr>
            @if ($payment->method?->is_efectivo && $payment->received_amount !== null)
                <tr><td class="muted">Efectivo recibido</td><td class="right muted">{{ $receiptCompany->currency_simbol }}{{ number_format($payment->received_amount, 2) }}</td></tr>
            @endif
            @if ($payment->method?->is_efectivo && $payment->returned_amount !== null)
                <tr><td class="muted">Vuelto</td><td class="right muted">{{ $receiptCompany->currency_simbol }}{{ number_format($payment->returned_amount, 2) }}</td></tr>
            @endif
        @endforeach
    </table>

    <div class="divider"></div>
    <table class="qr-wrap" role="presentation"><tr><td><img class="qr" src="{{ $qrCode }}" alt="QR para consultar el comprobante"></td></tr></table>
    <p class="center muted">Escanea el QR para consultar este comprobante.</p>
    <p class="center muted">Documento de control interno. No es un comprobante electrónico SUNAT.</p>
    <p class="center footer"><strong>Gracias por tu compra</strong></p>
</body>
</html>
