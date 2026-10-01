<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class puntosAbastecimientoSeeder extends Seeder
{
    
    public function run(): void
    {
        DB::table('puntos_abastecimiento')->insert([
            [
                'nombre'              => 'Pozo Tetillas',
                'tipo'                => 'pozo',
                'horas_atraso_apagon' => 1,
                'activo'              => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'nombre'              => 'Laguna Hueyapan',
                'tipo'                => 'laguna',
                'horas_atraso_apagon' => 4,
                'activo'              => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);
    }
}
