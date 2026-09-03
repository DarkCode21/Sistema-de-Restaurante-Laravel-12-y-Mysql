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

        .correction {
            border: 2px solid #000;
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0;
            padding: 5px;
            text-align: center;
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

        @if ($isCorrection)
            <div class="correction">CORRECCIÓN DE COMANDA</div>
        @endif

        <div class="sale-details">
            @php
                $correction = $isCorrection ? $corrections->first() : null;
                $ticketService = $correction?->table_name ?? $order->service_label;
                $ticketDate = $correction?->created_at ?? $order->created_at;
            @endphp
            <p><strong>TICKET:</strong> #{{ $order->id }}</p>
            <p><strong>PEDIDO:</strong> {{ $ticketService }}</p>
            <p><strong>FECHA:</strong> {{ $ticketDate->format('d/m/Y H:i') }}</p>
            <p><strong>CLIENTE:</strong> {{ strtoupper($order->customer_name ?? 'Consumidor Final') }}</p>
            @if ($order->order_type === 'delivery' && $order->delivery_address)
                <p><strong>DIRECCIÓN:</strong> {{ strtoupper($order->delivery_address) }}</p>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 75%;">DESC.</th>
                    <th style="width: 25%;" class="text-right">{{ $isCorrection ? 'ACCIÓN' : 'CANT' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($isCorrection ? $corrections : $order->details) as $item)
                    @php
                        $name = $isCorrection ? $item->product_name : $item->product->name;
                        $quantity = $item->quantity;
                        $notes = $item->notes;
                        $selectedOptions = $item->selected_options ?? [];
                    @endphp
                    <tr>
                        <td style="width: 75%;">
                            {{ $name }}
                            @if ($selectedOptions)
                                <br><small>{{ collect($selectedOptions)->map(fn ($option) => $option['group'] . ': ' . $option['value'])->join(' · ') }}</small>
                            @endif
                            @if ($notes)
                                <br><small><strong>NOTA:</strong> {{ $notes }}</small>
                            @elseif ($isCorrection && $item->action !== 'cancel')
                                <br><small><strong>NOTA:</strong> SIN NOTA</small>
                            @endif
                        </td>
                        <td style="width: 25%;" class="text-right">
                            @if ($isCorrection)
                                {{ $item->action === 'cancel' ? 'ANULAR ' . $quantity : 'ACTUALIZAR A ' . $quantity }}
                            @else
                                {{ $quantity }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @unless ($isCorrection)
            @php
                $subtotalSum = (float) $order->details->sum('subtotal');
                $discountSum = (float) $order->details->sum('discount');
                $taxSum = (float) $order->details->sum('tax');
            @endphp
            @if ($discountSum > 0)
                <div class="total-container" style="font-weight:normal;">
                    <span>DESCUENTO:</span>
                    <span>-{{ $empresa->currency_simbol }} {{ number_format($discountSum, 2) }}</span>
                </div>
            @endif
            @if ($taxSum > 0)
                <div class="total-container" style="font-weight:normal;">
                    <span>IMPUESTO:</span>
                    <span>{{ $empresa->currency_simbol }} {{ number_format($taxSum, 2) }}</span>
                </div>
            @endif
            <div class="total-container">
                <span>TOTAL:</span>
                <span>
                    {{ $empresa->currency_simbol }}
                    {{ number_format($subtotalSum + $taxSum, 2) }}
                </span>
            </div>
        @endunless

        <div class="footer-msg">
            <p>{{ $isCorrection ? 'Reemplaza la indicación anterior.' : '¡Gracias por su visita!' }}</p>
        </div>

    </div>

</body>

</html>
