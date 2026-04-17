<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteDetalladoExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        $rows = collect();

        // Ingresos
        $rows->push(['INGRESOS', '', '', '', '']);
        foreach ($this->datos['ingresos'] as $ingreso) {
            $rows->push([
                $ingreso['fecha'],
                $ingreso['cliente'],
                $ingreso['profesional'],
                $ingreso['metodo_pago'],
                number_format($ingreso['monto'], 2)
            ]);
        }

        $rows->push(['', '', '', 'TOTAL INGRESOS:', number_format($this->datos['totales']['ingresos'], 2)]);
        $rows->push(['', '', '', '', '']);

        // Gastos
        $rows->push(['GASTOS', '', '', '', '']);
        foreach ($this->datos['gastos'] as $gasto) {
            $rows->push([
                $gasto['fecha'],
                $gasto['descripcion'],
                '',
                '',
                number_format($gasto['monto'], 2)
            ]);
        }

        $rows->push(['', '', '', 'TOTAL GASTOS:', number_format($this->datos['totales']['gastos'], 2)]);
        $rows->push(['', '', '', '', '']);
        $rows->push(['', '', '', 'BALANCE FINAL:', number_format($this->datos['totales']['balance'], 2)]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Descripción / Cliente',
            'Profesional',
            'Método de Pago',
            'Monto'
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
        return 'Reporte Detallado';
    }
}
