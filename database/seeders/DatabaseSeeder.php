<?php

namespace Database\Seeders;
// archivo del sistema: archivo que carga datos iniciales de ejemplo

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // carga los datos iniciales necesarios para el sistema
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
        ]);
    }
}