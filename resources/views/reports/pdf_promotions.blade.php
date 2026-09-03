<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Promociones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 16px; }
        .totals { display: flex; gap: 12px; margin-bottom: 16px; }
        .totals .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; min-width: 140px; }
        .totals .label { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .totals .value { font-size: 13px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; color: #475569; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tfoot td { background: #f8fafc; font-weight: bold; border-top: 1px solid #cbd5e1; }
        .right { text-align: right; }
        .rose { color: #be123c; }
        .emerald { color: #047857; }
    </style>
</head>
<body>
    <h1>Reporte de Promociones</h1>
    <p class="meta">Periodo: {{ $start_date }} al {{ $end_date }}</p>

    <div class="totals">
        <div class="card">
            <div class="label">Ingresos netos</div>
            <div class="value">{{ $empresa->currency_simbol }}{{ number_format($totals['net_revenue'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">Ingresos brutos</div>
            <div class="value">{{ $empresa->currency_simbol }}{{ number_format($totals['gross_revenue'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">Descuento total</div>
            <div class="value rose">-{{ $empresa->currency_simbol }}{{ number_format($totals['total_discount'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">Aplicaciones</div>
            <div class="value">{{ $totals['times_applied'] }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Promoción</th>
                <th>Producto</th>
                <th class="right">Aplic.</th>
                <th class="right">Unid.</th>
                <th class="right">Descuento</th>
                <th class="right">Ing. neto</th>
                <th class="right">Ing. bruto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($promotions as $row)
                <tr>
                    <td>
                        {{ $row['promotion']->name }}
                        <div style="font-size: 9px; color: #047857; text-transform: uppercase;">
                            {{ $row['promotion']->discount_type === 'percent' ? $row['promotion']->value . '%' : $empresa->currency_simbol . number_format($row['promotion']->value, 2) }}
                        </div>
                    </td>
                    <td>{{ $row['promotion']->product->name ?? '—' }}</td>
                    <td class="right">{{ $row['times_applied'] }}</td>
                    <td class="right">{{ $row['qty'] }}</td>
                    <td class="right rose">-{{ $empresa->currency_simbol }}{{ number_format($row['total_discount'], 2) }}</td>
                    <td class="right emerald">{{ $empresa->currency_simbol }}{{ number_format($row['net_revenue'], 2) }}</td>
                    <td class="right">{{ $empresa->currency_simbol }}{{ number_format($row['gross_revenue'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center; padding: 24px; color: #94a3b8;">Sin datos en el periodo.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="right">{{ $totals['times_applied'] }}</td>
                <td class="right">{{ $totals['qty'] }}</td>
                <td class="right rose">-{{ $empresa->currency_simbol }}{{ number_format($totals['total_discount'], 2) }}</td>
                <td class="right emerald">{{ $empresa->currency_simbol }}{{ number_format($totals['net_revenue'], 2) }}</td>
                <td class="right">{{ $empresa->currency_simbol }}{{ number_format($totals['gross_revenue'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
