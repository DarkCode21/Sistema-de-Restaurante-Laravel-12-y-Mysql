<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\Setting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithStyles, WithCustomStartCell, WithMapping, ShouldAutoSize
{
    protected $request;
    protected $config;

    public function __construct($request)
    {
        $this->request = $request;
        // Cargamos la configuración o creamos un objeto vacío para evitar errores de null
        $this->config = Setting::first() ?? new Setting(['company_name' => 'Sistema', 'currency_simbol' => '$']);
    }

    public function collection()
    {
        return Sale::with(['order.table', 'order.user'])
            ->when(isset($this->request['from']), fn($q) => $q->whereDate('paid_at', '>=', $this->request['from']))
            ->when(isset($this->request['to']), fn($q) => $q->whereDate('paid_at', '<=', $this->request['to']))
            ->when($this->request['search'] ?? null, function ($query) {
                $search = $this->request['search'];
                $query->where(function ($query) use ($search) {
                    $query->where('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('id', $search)
                        ->orWhereHas('order', fn ($order) => $order
                            ->where('customer_name', 'like', '%' . $search . '%')
                            ->orWhereHas('table', fn ($table) => $table->where('name', 'like', '%' . $search . '%')));
                });
            })
            ->get();
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->paid_at->format('d/m/Y H:i'),
            $sale->order?->service_label ?? 'N/A',
            $sale->order?->user?->name ?? 'Sistema',
            $sale->total,
        ];
    }

    public function headings(): array
    {
        return ['ID VENTA', 'FECHA/HORA', 'MESA', 'ATENDIDO POR', 'TOTAL'];
    }

    public function styles(Worksheet $sheet)
    {
        // Título de la Empresa en Mayúsculas Correctamente
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', mb_strtoupper($this->config->company_name, 'UTF-8'));
        
        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'RUC: ' . ($this->config->tax_id ?? '---') . ' | TEL: ' . ($this->config->company_phone ?? '---'));

        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A3', 'DIRECCIÓN: ' . ($this->config->company_address ?? '---'));

        $sheet->mergeCells('A5:E5');
        $sheet->setCellValue('A5', 'REPORTE DETALLADO DE VENTAS');

        // Aplicar Estilos
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:A5')->getAlignment()->setHorizontal('center');

        // Estilo Encabezado Tabla
        $sheet->getStyle('A7:E7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => 'center'],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
        ]);

        return [];
    }
}
