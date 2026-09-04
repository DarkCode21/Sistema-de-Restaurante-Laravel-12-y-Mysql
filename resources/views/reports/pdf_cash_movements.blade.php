<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Caja - {{ $caja->name }}</title>
    <style>
        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            color: #334155;
            font-size: 12px;
            line-height: 1.5;
        }

        /* Encabezado Estilo Pro */
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
        }

        .report-title {
            font-size: 14px;
            color: #64748b;
            font-weight: bold;
            margin-top: 5px;
        }

        /* Columnas de Información */
        .info-section {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-box {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .label {
            font-size: 10px;
            color: #94a3b8;
            font-weight: bold;
            text-transform: uppercase;
        }

        .value {
            font-size: 12px;
            color: #1e293b;
            font-weight: bold;
        }

        /* Tabla de Métodos de Pago */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .summary-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 10px;
            padding: 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        .summary-table td {
            padding: 10px;
            border: 1px solid #e2e8f0;
            font-weight: bold;
        }

        /* Listado de Transacciones */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.main-table th {
            background: #1e293b;
            color: white;
            padding: 8px;
            font-size: 10px;
            text-transform: uppercase;
        }

        table.main-table td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Clases de Utilidad */
        .text-right {
            text-align: right;
        }

        .ingreso {
            color: #059669;
            font-weight: bold;
        }

        .egreso {
            color: #dc2626;
            font-weight: bold;
        }

        .badge-gasto {
            background-color: #fee2e2;
            color: #b91c1c;
            font-size: 9px;
            padding: 2px 6px;
            rounded-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-venta {
            background-color: #d1fae5;
            color: #065f46;
            font-size: 9px;
            padding: 2px 6px;
            rounded-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="company-name">{{ $empresa->company_name ?? 'MI EMPRESA' }}</div>
        <div class="report-title">ARQUEO DETALLADO DE CAJA</div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <div class="label">Caja Identificador:</div>
            <div class="value">{{ $caja->name }}</div>
            <div class="label" style="margin-top:10px">Responsable:</div>
            <div class="value">{{ $caja->opener->name }}</div>
        </div>
        <div class="info-box text-right">
            <div class="label">Fecha de Apertura:</div>
            <div class="value">{{ $caja->created_at->format('d/m/Y H:i') }}</div>
            <div class="label" style="margin-top:10px">Estado Actual:</div>
            <div class="value" style="color: {{ $caja->status == 'open' ? '#059669' : '#64748b' }}">
                {{ $caja->status == 'open' ? 'SESIÓN ABIERTA' : 'SESIÓN CERRADA' }}
            </div>
        </div>
    </div>

    <h3 class="label">Resumen por Métodos de Pago</h3>
    <table class="summary-table">
        <thead>
            <tr>
                @foreach ($pagosPorMetodo as $metodo => $total)
                    <th>{{ $metodo }}</th>
                @endforeach
                <th style="background: #eef2ff; color: #4f46e5;">SALDO TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($pagosPorMetodo as $metodo => $total)
                    <td>{{ $empresa->currency_simbol ?? 'S/' }}{{ number_format($total, 2) }}</td>
                @endforeach
                <td style="background: #eef2ff; color: #4f46e5;">
                    {{ $empresa->currency_simbol ?? 'S/' }}{{ number_format($caja->current_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h3 class="label">Detalle de Movimientos</h3>
    <table class="main-table">
        <thead>
            <tr>
                <th align="left" width="10%">Hora</th>
                <th align="left" width="15%">Tipo</th>
                <th align="left">Detalle / Concepto</th>
                <th align="left" width="20%">Método</th>
                <th align="right" width="15%">Monto</th>
            </tr>
        </thead>
        <tbody>
            @php
                $movimientos = collect()
                    ->merge(
                        $cashSales->map(function ($sale) {
                $cashPayments = $sale->payments;

                            return [
                                'time' => $sale->paid_at,
                                'is_gasto' => false,
                                'concept' => 'Venta Realizada',
                                'methods' => $cashPayments->map(fn($p) => $p->method->name)->implode(', '),
                                'amount' => $cashPayments->sum('amount'),
                            ];
                        }),
                    )
                    ->merge(
                        $gastos->map(function ($gasto) {
                            return [
                                'time' => $gasto->expense_date,
                                'is_gasto' => true,
                                'concept' =>
                                    'Gasto: ' .
                                    $gasto->concept .
                                    ($gasto->description ? ' (' . $gasto->description . ')' : ''),
                                'methods' => $gasto->paymentMethod->name ?? 'N/A',
                                'amount' => $gasto->amount,
                            ];
                        }),
                    )
                    ->sortByDesc('time');
            @endphp

            @foreach ($movimientos as $m)
                <tr>
                    <td>{{ $m['time']->format('H:i') }}</td>
                    <td>
                        @if ($m['is_gasto'])
                            <span class="badge-gasto">Gasto</span>
                        @else
                            <span class="badge-venta">Venta</span>
                        @endif
                    </td>
                    <td>{{ $m['concept'] }}</td>
                    <td>{{ $m['methods'] }}</td>
                    <td class="text-right {{ $m['is_gasto'] ? 'egreso' : 'ingreso' }}">
                        {{ $m['is_gasto'] ? '-' : '+' }}
                        {{ $empresa->currency_simbol ?? 'S/' }}{{ number_format($m['amount'], 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este documento es un comprobante interno de movimientos de tesorería.
        Generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>

</html>
