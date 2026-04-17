<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PagosExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $pagos;

    public function __construct($pagos)
    {
        $this->pagos = $pagos;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return collect($this->pagos)->map(function ($pago, $index) {
            return [
                $index + 1,
                $pago['cliente'],
                $pago['profesional'],
                $pago['procedimiento'],
                $pago['fecha_cita'] ? date('d/m/Y', strtotime($pago['fecha_cita'])) : 'N/A',
                $pago['franja_horaria'],
                '$' . number_format($pago['monto'], 0, ',', '.'),
                $pago['metodo_pago_detalle'],
                $pago['fecha_pago'] ? date('d/m/Y H:i', strtotime($pago['fecha_pago'])) : 'N/A',
                ucfirst($pago['estado'])
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            '#',
            'Cliente',
            'Profesional',
            'Procedimiento',
            'Fecha Cita',
            'Franja Horaria',
            'Monto',
            'Método de Pago',
            'Fecha de Pago',
            'Estado'
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 25,
            'C' => 25,
            'D' => 30,
            'E' => 15,
            'F' => 20,
            'G' => 15,
            'H' => 20,
            'I' => 20,
            'J' => 12
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '607D8B']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ]
        ];
    }
}
