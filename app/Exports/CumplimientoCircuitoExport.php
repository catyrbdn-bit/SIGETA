<?php

namespace App\Exports;
// archivo del sistema: archivo que prepara datos para exportar

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CumplimientoCircuitoExport implements FromArray, WithHeadings
{
    // recibe los datos y servicios que necesita la clase
    public function __construct(private array $filas)
    {
    }

    // prepara los datos que se van a exportar
    public function array(): array
    {
        return $this->filas;
    }

    // define los titulos de las columnas del archivo
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