<?php

namespace Database\Seeders;

use App\Models\usuarioModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        usuarioModel::create([
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