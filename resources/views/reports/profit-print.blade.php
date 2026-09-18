<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Utilidad operativa</title>
    <style>
        body { color: #172033; font-family: Arial, sans-serif; font-size: 12px; margin: 32px; }
        h1, h2, p { margin: 0; }
        header { border-bottom: 2px solid #172033; padding-bottom: 16px; }
        h1 { font-size: 22px; }
        .meta { color: #596579; margin-top: 6px; }
        .summary { display: table; margin: 22px 0; table-layout: fixed; width: 100%; }
        .summary div { display: table-cell; border: 1px solid #d9dee7; padding: 12px; }
        .label { color: #64748b; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .value { font-size: 17px; font-weight: bold; margin-top: 6px; }
        .positive { color: #047857; }
        .negative { color: #be123c; }
        table { border-collapse: collapse; margin-top: 10px; width: 100%; }
        th { background: #f1f5f9; color: #475569; font-size: 9px; letter-spacing: 1px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #d9dee7; padding: 9px; }
        .right { text-align: right; }
        .stock { margin-top: 24px; }
        .stock li { margin: 4px 0; }
        @media print { body { margin: 16px; } }
    </style>
</head>
<body onload="window.print()">
    <header>
        <h1>Utilidad operativa</h1>
        <p class="meta">{{ $empresa->company_name }} · {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}@if ($selectedCategory) · {{ $selectedCategory->name }}@endif@if ($productSearch) · Producto: {{ $productSearch }}@endif</p>
    </header>

    <section class="summary">
        <div><p class="label">Ventas netas</p><p class="value">{{ $empresa->currency_simbol }}{{ number_format($totals['sales'], 2) }}</p></div>
        <div><p class="label">Costo vendido</p><p class="value negative">-{{ $empresa->currency_simbol }}{{ number_format($totals['cost'], 2) }}</p></div>
        <div><p class="label">Utilidad bruta</p><p class="value positive">{{ $empresa->currency_simbol }}{{ number_format($totals['gross_profit'], 2) }}</p></div>
        <div><p class="label">Gastos</p><p class="value negative">-{{ $empresa->currency_simbol }}{{ number_format($totals['expenses'], 2) }}</p></div>
        <div><p class="label">Resultado operativo</p><p class="value {{ $totals['net_profit'] >= 0 ? 'positive' : 'negative' }}">{{ $empresa->currency_simbol }}{{ number_format($totals['net_profit'], 2) }}</p></div>
    </section>

    <h2>Rentabilidad por producto</h2>
    <table>
        <thead><tr><th>Producto</th><th class="right">Unidades</th><th class="right">Costo</th><th class="right">Utilidad</th><th class="right">Margen</th></tr></thead>
        <tbody>
            @forelse ($products as $product)
                @php
                    $revenue = (float) $product->cost + (float) $product->gross_profit;
                    $margin = $revenue == 0.0 ? 0 : ((float) $product->gross_profit / $revenue) * 100;
                @endphp
                <tr><td>{{ $product->product_name ?: 'Producto histórico' }}</td><td class="right">{{ rtrim(rtrim(number_format($product->quantity, 3, '.', ''), '0'), '.') }}</td><td class="right">{{ $empresa->currency_simbol }}{{ number_format($product->cost, 2) }}</td><td class="right">{{ $empresa->currency_simbol }}{{ number_format($product->gross_profit, 2) }}</td><td class="right">{{ number_format($margin, 1) }}%</td></tr>
            @empty
                <tr><td colspan="5">No hay ventas con costo histórico para estos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($totals['missing_cost_lines'])
        <p class="meta" style="margin-top: 16px;">{{ $totals['missing_cost_lines'] }} líneas no tienen costo histórico y no intervienen en la utilidad bruta.</p>
    @endif

    @if ($lowStockIngredients->isNotEmpty())
        <section class="stock"><h2>Reposición pendiente</h2><ul>@foreach ($lowStockIngredients as $ingredient)<li>{{ $ingredient->name }}: {{ rtrim(rtrim(number_format($ingredient->stock, 3, '.', ''), '0'), '.') }} {{ $ingredient->unit }} disponibles, mínimo {{ rtrim(rtrim(number_format($ingredient->minimum_stock, 3, '.', ''), '0'), '.') }}</li>@endforeach</ul></section>
    @endif
</body>
</html>
