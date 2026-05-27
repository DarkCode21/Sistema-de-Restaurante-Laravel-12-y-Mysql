<?php

namespace App\Exports;

use App\Models\Setting;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExpensesExport implements FromQuery, WithMapping, WithHeadings, WithStyles, WithCustomStartCell, ShouldAutoSize, WithEvents
{
    protected $query;
    protected $settings;

    public function __construct($query)
    {
        $this->query = $query;
        $this->settings = Setting::first();
    }

    public function query()
    {
        return $this->query;
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function headings(): array
    {
        $simbol = $this->settings->currency_simbol ?? 'S/';
        return [
            'Concepto',
            'Descripción',
            'Caja',
            'Método de Pago',
            'Monto (' . $simbol . ')',
            'Fecha',
            'Usuario Registra'
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->concept,
            $expense->description ?? '-',
            $expense->cashRegister->name ?? 'N/A',
            $expense->paymentMethod->name ?? 'N/A',
            -1 * abs($expense->amount),
            $expense->expense_date->format('d/m/Y h:i A'),
            $expense->user->name ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            7 => [
                'font' => [
                    'bold' => true, 
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $companyName    = $this->settings->company_name ?? 'CEVICHE FLOW';
                $companyTaxId   = $this->settings->tax_id ? 'RUC/TAX ID: ' . $this->settings->tax_id : '';
                $companyPhone   = $this->settings->company_phone ? 'Tel: ' . $this->settings->company_phone : '';
                $companyAddress = $this->settings->company_address ?? '';
                $reportTitle    = 'REPORTE DE FLUJO Y EGRESOS DE CAJA';
                $generatedAt    = 'Generado el: ' . now()->format('d/m/Y h:i A');

                $sheet->setCellValue('A2', strtoupper($companyName));
                $sheet->setCellValue('A3', $companyTaxId . ($companyPhone ? ' | ' . $companyPhone : ''));
                $sheet->setCellValue('A4', $companyAddress);
                
                $sheet->setCellValue('E2', $reportTitle);
                $sheet->setCellValue('E3', $generatedAt);

                $sheet->mergeCells('A2:D2');
                $sheet->mergeCells('A3:D3');
                $sheet->mergeCells('A4:D4');
                $sheet->mergeCells('E2:G2');
                $sheet->mergeCells('E3:G3');

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '4F46E5']],
                ]);
                $sheet->getStyle('A3:A4')->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '64748B']],
                ]);
                
                $sheet->getStyle('E2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ]);
                $sheet->getStyle('E3')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '94A3B8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ]);

                $highestRow = $sheet->getHighestRow();
                if ($highestRow >= 7) {
                    $sheet->getRowDimension(7)->setRowHeight(26);

                    $sheet->getStyle('E8:E' . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00;"-"');

                    $sheet->getStyle('A8:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('C8:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E8:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('F8:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle('A7:G' . $highestRow)->applyFromArray([
                        'borders' => [
                            'inside' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E2E8F0'],
                            ],
                            'outline' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }
}