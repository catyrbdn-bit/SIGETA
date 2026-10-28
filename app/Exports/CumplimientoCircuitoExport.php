<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CumplimientoCircuitoExport implements FromArray, WithHeadings
{
    public function __construct(private array $filas)
    {
    }

    public function array(): array
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return [
            'Circuito',
            'Colonia',
            'Calles',
            'Tandeos programados',
            'Tandeos ejecutados',
            'Tandeos con desfase',
        ];
    }
}