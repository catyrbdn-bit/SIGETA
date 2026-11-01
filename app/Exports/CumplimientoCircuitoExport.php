<?php

namespace App\Exports;
// archivo del sistema: archivo que prepara datos para exportar

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CumplimientoCircuitoExport implements FromArray, WithHeadings
{
    // recibe los datos y servicios que necesita la clase
    // Recibe y guarda los datos que necesita este componente.
    // descripcion: recibe y guarda los datos que necesita este componente.
    // entradas: lista de datos que se deben procesar
    // salida: deja preparados los datos para usarlos en el componente
    public function __construct(private array $filas)
    {
    }

    // prepara los datos que se van a exportar
    // Entrega las filas que se incluirán en el archivo de Excel.
    // descripcion: entrega las filas que se incluirán en el archivo de Excel.
    // entradas: no requiere datos externos
    // salida: devuelve las filas del reporte
    public function array(): array
    {
        return $this->filas;
    }

    // define los titulos de las columnas del archivo
    // Define los encabezados de las columnas del archivo de Excel.
    // descripcion: define los encabezados de las columnas del archivo de Excel.
    // entradas: no requiere datos externos
    // salida: devuelve los nombres de las columnas
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