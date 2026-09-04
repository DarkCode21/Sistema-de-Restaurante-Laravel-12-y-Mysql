<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        /* Header */
        .header-table { width: 100%; border-bottom: 2px solid #fd5102; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #1e293b; text-transform: uppercase; }
        .report-title { text-align: right; font-size: 16px; color: #fd5102; font-weight: bold; }
        
        /* Company Info */
        .info-text { color: #64748b; font-size: 10px; }
        
        /* Stats Boxes */
        .stats-table { width: 100%; margin-bottom: 20px; }
        .stat-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; text-align: center; border-radius: 8px; }
        .stat-label { font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .stat-value { font-size: 14px; font-weight: bold; color: #0f172a; }

        /* Table Style */
        table.main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.main-table th {
            background-color: #fd5102;
            color: white;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            font-size: 9px;
        }
        table.main-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .tr-even { background-color: #f1f5f9; }
        
        /* Helper Classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="50%">
                <span class="company-name">{{ $config->company_name }}</span><br>
                <span class="info-text">
                    {{ $config->tax_id }}<br>
                    {{ $config->company_address }}<br>
                    Tel: {{ $config->company_phone }} | {{ $config->company_email }}
                </span>
            </td>
            <td width="50%" class="report-title">
                REPORTE DE VENTAS<br>
                <span style="font-size: 10px; color: #64748b;">
                    Periodo: {{ $from ?? 'Inicio' }} al {{ $to ?? 'Hoy' }}
                </span>
            </td>
        </tr>
    </table>

    <table class="stats-table">
        <tr>
            <td width="33%">
                <div class="stat-box">
                    <div class="stat-label">Total Transacciones</div>
                    <div class="stat-value">{{ $sales->count() }}</div>
                </div>
            </td>
            <td width="33%">
                <div class="stat-box">
                    <div class="stat-label">Promedio de Venta</div>
                    <div class="stat-value">
                        {{ $config->currency_simbol }}{{ number_format($sales->avg('total'), 2) }}
                    </div>
                </div>
            </td>
            <td width="33%">
                <div class="stat-box" style="border-left: 4px solid #10b981;">
                    <div class="stat-label">Ingreso Total</div>
                    <div class="stat-value" style="color: #10b981;">
                        {{ $config->currency_simbol }}{{ number_format($sales->sum('total'), 2) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha / Hora</th>
                <th>Atención</th>
                <th>Atendido por</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $index => $sale)
            <tr class="{{ $index % 2 == 0 ? '' : 'tr-even' }}">
                <td class="font-bold">#{{ $sale->id }}</td>
                <td>{{ $sale->paid_at->format('d/m/Y h:i A') }}</td>
                <td>{{ $sale->order?->service_label ?? 'N/A' }}</td>
                <td>{{ $sale->order?->user?->name ?? 'Sistema' }}</td>
                <td class="text-right font-bold">
                    {{ $config->currency_simbol }}{{ number_format($sale->total, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right font-bold" style="padding-top: 15px; font-size: 12px;">TOTAL GENERAL:</td>
                <td class="text-right font-bold" style="padding-top: 15px; font-size: 12px; color: #fd5102;">
                    {{ $config->currency_simbol }}{{ number_format($sales->sum('total'), 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generado el {{ date('d/m/Y H:i:s') }} - {{ $config->company_name }}
    </div>

</body>
</html>
