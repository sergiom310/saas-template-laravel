<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteResumidoExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        $rows = collect();

        // Resumen por método de pago
        $rows->push(['INGRESOS POR MÉTODO DE PAGO', '', '']);
        foreach ($this->datos['ingresos_por_metodo'] as $metodo) {
            $rows->push([
                $metodo['metodo'],
                $metodo['cantidad'],
                number_format($metodo['total'], 2)
            ]);
        }
        $rows->push(['', '', '']);

        // Resumen por profesional
        $rows->push(['INGRESOS POR PROFESIONAL', '', '']);
        foreach ($this->datos['ingresos_por_profesional'] as $prof) {
            $rows->push([
                $prof['profesional'],
                $prof['cantidad'],
                number_format($prof['total'], 2)
            ]);
        }
        $rows->push(['', '', '']);

        // Totales
        $rows->push(['TOTAL INGRESOS:', '', number_format($this->datos['totales']['ingresos'], 2)]);
        $rows->push(['TOTAL GASTOS:', '', number_format($this->datos['totales']['gastos'], 2)]);
        $rows->push(['BALANCE FINAL:', '', number_format($this->datos['totales']['balance'], 2)]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Descripción',
            'Cantidad',
            'Total'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte Resumido';
    }
}
