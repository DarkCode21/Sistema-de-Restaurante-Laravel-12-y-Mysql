<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Transacciones</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Encabezado */
        .header-container {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .company-data {
            width: 50%;
            float: left;
        }

        .report-data {
            width: 50%;
            float: right;
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
        }

        .clear {
            clear: both;
        }

        /* Resumen */
        .summary-boxes {
            margin-bottom: 20px;
        }

        .box {
            float: left;
            width: 30%;
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-right: 2%;
        }

        /* Tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #4f46e5;
            color: white;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            font-size: 10px;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Badges de Pago */
        .payment-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 9px;
            margin-right: 3px;
            border: 1px solid #c7d2fe;
        }

        /* Estilos de texto */
        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .ingreso {
            color: #059669;
            font-weight: bold;
        }

        .egreso {
            color: #dc2626;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            color: #999;
            font-size: 9px;
        }
    </style>
</head>

<body>

    <div class="header-container">
        <div class="company-data">
            <div class="company-name">{{ $empresa->company_name }}</div>
            <div>NIT: {{ $empresa->tax_id }}</div>
        </div>
        <div class="report-data">
            <h1>BALANCE GENERAL</h1>
            <div>Periodo: {{ $start_date }} al {{ $end_date }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="summary-boxes">
        <div class="box" style="border-color: #059669">
            <div class="ingreso">INGRESOS: {{ $empresa->currency_simbol }}{{ number_format($totalIngresos, 2) }}</div>
        </div>
        <div class="box" style="border-color: #dc2626">
            <div class="egreso">EGRESOS: {{ $empresa->currency_simbol }}{{ number_format($totalEgresos, 2) }}</div>
        </div>
        <div class="box" style="background: #4f46e5; color: white">
            <div>NETO: {{ $empresa->currency_simbol }}{{ number_format($balanceNeto, 2) }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <h3>DETALLE POR CATEGORÍAS</h3>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Tipo</th>
                <th class="text-right">Total Acumulado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ingresosPorCategoria->concat($egresosPorCategoria) as $item)
                <tr>
                    <td>{{ $item->category->name }}</td>
                    <td>{{ ucfirst($item->type) }}</td>
                    <td class="text-right font-bold {{ $item->type == 'ingreso' ? 'ingreso' : 'egreso' }}">
                        {{ $empresa->currency_simbol }}{{ number_format($item->total, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Software de Gestión Financiera - Página <span class="pagenum"></span>
    </div>

</body>

</html>
