<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            margin: 5mm;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .header { text-align: center; margin-bottom: 10px; }
        .brand {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .divider {
            border-top: 0.5pt dashed #000;
            margin: 8px 0;
        }
        table { width: 100%; border-collapse: collapse; }
        th { border-bottom: 1px solid #000; padding: 4px 0; text-align: left; font-size: 8pt; }
        td { padding: 4px 0; vertical-align: top; }
        .total-container { margin-top: 5px; width: 100%; }
        .total-row { font-size: 10pt; font-weight: bold; }
        .qr-section {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
        }
        .qr-section img {
            width: 100px;
            height: 100px;
        }
        .footer-msg {
            font-size: 7pt;
            margin-top: 5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="brand">{{ $empresa->company_name }}</div>
        <div>{{ $empresa->company_address }}</div>
        <div>NIT: {{ $empresa->tax_id }}</div>
        <div>TEL: {{ $empresa->phone }}</div>
    </div>

    <div class="divider"></div>

    <div style="margin-bottom: 10px;">
        <strong>TICKET:</strong> #{{ $sale->id }}<br>
        <strong>FECHA:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}<br>
        <strong>CLIENTE:</strong> {{ strtoupper($sale->customer_name ?? 'Consumidor Final') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>DESC.</th>
                <th class="text-right">CANT</th>
                <th class="text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->details as $item)
                <tr>
                    <td>
                        {{ $item->product->name }}
                        @if (!empty($item->selected_options))
                            <br><small>{{ collect($item->selected_options)->map(fn ($option) => $option['group'] . ': ' . $option['value'])->join(' · ') }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="total-container">
        <table>
            <tr>
                <td class="text-right">PAGO CON:</td>
                <td class="text-right">{{ $empresa->currency_simbol }}{{ number_format($sale->paid_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td class="text-right">TOTAL:</td>
                <td class="text-right">{{ $empresa->currency_simbol }}{{ number_format($sale->total, 2) }}</td>
            </tr>
            <tr>
                <td class="text-right">DEVUELTA:</td>
                <td class="text-right">{{ $empresa->currency_simbol }}{{ number_format($sale->change, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="qr-section">
        <img src="{{ $qrCodeBase64 }}" alt="QR Code">
        <p class="footer-msg">¡Gracias por su preferencia!<br>Escanea para ver tu factura online</p>
    </div>

</body>
</html>
