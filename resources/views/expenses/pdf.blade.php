<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Egresos - {{ $empresa->company_name ?? 'Ceviche Flow' }}</title>
    <style>
        @page { 
            size: A4;
            margin: 18mm 15mm; 
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #334155; 
            font-size: 11px; 
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        
        /* Encabezado e Identidad del Sistema */
        .header { 
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 16px; 
            margin-bottom: 24px; 
        }
        .company-row {
            width: 100%;
        }
        .company-name { 
            font-size: 20px; 
            font-weight: 800; 
            color: #0f172a; 
            letter-spacing: -0.025em;
        }
        .company-name span {
            color: #4f46e5; /* Toque azul de Ceviche Flow */
        }
        .report-title { 
            font-size: 12px; 
            color: #64748b; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 4px; 
        }
        
        /* Bloques de Filtros e Información de Búsqueda */
        .filter-section { 
            width: 100%; 
            margin-bottom: 24px; 
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
        }
        .filter-table {
            width: 100%;
            border-collapse: collapse;
        }
        .filter-box { 
            vertical-align: top; 
        }
        .label { 
            font-size: 9px; 
            color: #94a3b8; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .value { 
            font-size: 11px; 
            color: #1e293b; 
            font-weight: 700; 
        }

        /* Tabla Principal Estilo UI */
        table.main-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        table.main-table th { 
            background: #f8fafc; 
            color: #94a3b8; 
            padding: 12px 14px; 
            font-size: 9px; 
            text-transform: uppercase; 
            font-weight: 700;
            letter-spacing: 0.1em;
            border-bottom: 1px solid #e2e8f0;
        }
        table.main-table td { 
            padding: 12px 14px; 
            border-bottom: 1px solid #f1f5f9; 
            vertical-align: middle;
        }
        
        /* Estilos de Concepto e Icono simulado */
        .concept-container {
            position: relative;
        }
        .concept-title {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
        }
        .concept-desc {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Contenedores Flex-Vertical para Celdas Múltiples */
        .stack-layout {
            margin: 0;
            padding: 0;
        }
        .stack-item {
            margin-bottom: 4px;
        }
        .stack-item:last-child {
            margin-bottom: 0;
        }

        /* Componentes de Badges (Copias de la Interfaz Web) */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-caja {
            color: #475569;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
        }
        .badge-efectivo {
            color: #b45309;
            background-color: #fef3c7;
            border: 1px solid #fde68a;
        }
        .badge-banco {
            color: #1d4ed8;
            background-color: #dbeafe;
            border: 1px solid #bfdbfe;
        }
        .badge-monto {
            font-size: 11px;
            font-weight: 900;
            color: #e11d48;
            background-color: #fff1f2;
            border: 1px solid #ffe4e6;
            padding: 4px 10px;
            border-radius: 8px;
        }
        .badge-user {
            font-size: 9px;
            color: #64748b;
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            padding: 2px 6px;
            border-radius: 6px;
            margin-top: 3px;
            display: inline-block;
        }

        /* Clases de Utilidad */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .date-text {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
        }
        
        /* Evitar que se corten filas entre páginas de forma fea */
        tr { page-break-inside: avoid; }
        
        .footer { 
            position: fixed; 
            bottom: 0; 
            width: 100%; 
            text-align: center; 
            font-size: 9px; 
            color: #94a3b8; 
            border-top: 1px solid #e2e8f0; 
            padding-top: 12px; 
        }
    </style>
</head>
<body>

    <div class="header">
        <table class="company-row" style="width: 100%;">
            <tr>
                <td>
                    <div class="company-name">{{ $empresa->company_name ?? 'CEVICHE' }}</div>
                    <div class="report-title">Flujo de Efectivo y Egresos</div>
                </td>
                <td class="text-right" style="vertical-align: bottom;">
                    <div class="label">Generado el</div>
                    <div class="value" style="color: #64748b;">{{ now()->format('d/m/Y h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="filter-section">
        <table class="filter-table">
            <tr>
                <td class="filter-box" width="40%">
                    <div class="label">Criterio de Búsqueda:</div>
                    <div class="value">{{ $search ? '"'.$search.'"' : 'Todos los registros' }}</div>
                </td>
                <td class="filter-box" width="30%">
                    <div class="label">Desde:</div>
                    <div class="value">{{ $start_date ? \Carbon\Carbon::parse($start_date)->format('d/m/Y') : 'Inicio del tiempo' }}</div>
                </td>
                <td class="filter-box" width="30%">
                    <div class="label">Hasta:</div>
                    <div class="value">{{ $end_date ? \Carbon\Carbon::parse($end_date)->format('d/m/Y') : 'Fin del tiempo' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th align="left" width="35%">Concepto</th>
                <th align="left" width="22%">Caja / Método</th>
                <th align="left" width="15%">Monto</th>
                <th align="left" width="28%">Fecha / Usuario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>
                        <div class="concept-container">
                            <div class="concept-title">{{ $expense->concept }}</div>
                            @if ($expense->description)
                                <div class="concept-desc">{{ $expense->description }}</div>
                            @endif
                        </div>
                    </td>

                    <td>
                        <div class="stack-layout">
                            <div class="stack-item">
                                <span class="badge badge-caja">
                                    {{ $expense->cashRegister->name }}
                                </span>
                            </div>
                            <div class="stack-item">
                                @if ($expense->paymentMethod->is_efectivo)
                                    <span class="badge badge-efectivo">
                                        • {{ $expense->paymentMethod->name }}
                                    </span>
                                @else
                                    <span class="badge badge-banco">
                                        • {{ $expense->paymentMethod->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>

                    <td>
                        <span class="badge-monto">
                            -{{ $empresa->currency_simbol ?? 'S/' }}{{ number_format($expense->amount, 2) }}
                        </span>
                    </td>

                    <td>
                        <div class="stack-layout">
                            <div class="date-text">
                                {{ $expense->expense_date->format('d/m/Y h:i A') }}
                            </div>
                            <div>
                                <span class="badge-user">
                                    {{ $expense->user->name }}
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 40px 0; color: #94a3b8;">
                        <div style="font-size: 14px; font-weight: bold; color: #64748b;">No se encontraron egresos</div>
                        <div style="margin-top: 4px; font-size: 10px;">No existen egresos registrados que coincidan con los filtros aplicados.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento emitido de forma automatizada por el módulo de administración Ceviche Flow. Página 1 de 1
    </div>

</body>
</html>