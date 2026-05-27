<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            padding: 3px;
            color: #000;
        }

        .ticket {
            width: 95%;
            max-width: 300px;
            margin: 0 auto;
            padding: 5px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .brand {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
            word-break: break-word;
        }

        .header div {
            font-size: 12px;
            margin: 1px 0;
            word-break: break-word;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
            width: 100%;
        }

        .sale-details {
            font-size: 12px;
            margin-bottom: 10px;
            line-height: 1.4;
            word-break: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5px;
        }

        th,
        td {
            padding: 3px;
            text-align: left;
            font-size: 11px;
            word-break: break-word;
        }

        th {
            border-bottom: 1px solid #000;
        }

        /* Estilos para la sección del total */
        .total-container {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed #000;
            font-size: 13px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
        }

        .footer-msg {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <div class="ticket">

        <div class="header">
            <div class="brand">{{ $empresa->company_name }}</div>
            <div>{{ $empresa->company_address }}</div>
            <div>NIT: {{ $empresa->tax_id }}</div>
            <div>TEL: {{ $empresa->phone }}</div>
        </div>

        <div class="divider"></div>

        <div class="sale-details">
            <p><strong>TICKET:</strong> #{{ $order->id }}</p>
            <p><strong>Mesa:</strong> {{ $order->table->name }}</p>
            <p><strong>FECHA:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>CLIENTE:</strong> {{ strtoupper($order->customer_name ?? 'Consumidor Final') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 75%;">DESC.</th>
                    <th style="width: 25%;" class="text-right">CANT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->details as $item)
                    <tr>
                        <td style="width: 75%;">{{ $item->product->name }}</td>
                        <td style="width: 25%;" class="text-right">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- SECCIÓN DEL TOTAL --}}
        <div class="total-container">
            <span>TOTAL:</span>
            <span>
                {{ $empresa->currency_simbol }}
                {{ number_format($order->details->sum('subtotal'), 2) }}
            </span>
        </div>

        <div class="footer-msg">
            <p>¡Gracias por su visita!</p>
        </div>

    </div>

</body>

</html>
