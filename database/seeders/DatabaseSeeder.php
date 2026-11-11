<?php

namespace Database\Seeders;

// archivo del sistema: archivo que carga datos iniciales de ejemplo

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // carga los datos iniciales necesarios para el sistema
    // Carga los datos iniciales necesarios para el sistema.
    // descripcion: carga los datos iniciales necesarios para el sistema.
    // entradas: no requiere datos externos
    // salida: registra los datos iniciales en la base de datos
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
        ]);
    }
}
