<?php

namespace Database\Seeders;

// archivo del sistema: archivo que carga datos iniciales de ejemplo

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class puntosAbastecimientoSeeder extends Seeder
{
    // carga los datos iniciales necesarios para el sistema
    // Carga los datos iniciales necesarios para el sistema.
    // descripcion: carga los datos iniciales necesarios para el sistema.
    // entradas: no requiere datos externos
    // salida: registra los datos iniciales en la base de datos
    public function run(): void
    {
        DB::table('puntos_abastecimiento')->insert([
            [
                'nombre' => 'Pozo Tetillas',
                'tipo' => 'pozo',
                'horas_atraso_apagon' => 1,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Laguna Hueyapan',
                'tipo' => 'laguna',
                'horas_atraso_apagon' => 4,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
