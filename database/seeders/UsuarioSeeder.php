<?php

namespace Database\Seeders;

// archivo del sistema: archivo que carga datos iniciales de ejemplo

use App\Models\UsuarioModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    // carga los datos iniciales necesarios para el sistema
    // Carga los datos iniciales necesarios para el sistema.
    // descripcion: carga los datos iniciales necesarios para el sistema.
    // entradas: no requiere datos externos
    // salida: registra los datos iniciales en la base de datos
    public function run(): void
    {
        UsuarioModel::create([
            'nombre' => 'Administrador',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => 'SIGETA',
            'usuario' => 'admin',
            'email' => 'admin@sigeta.test',
            'password' => Hash::make('12345678'),
            'rol' => 'administrador',
            'activo' => true,
        ]);
    }
}
