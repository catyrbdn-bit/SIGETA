<?php

namespace Database\Seeders;

use App\Models\UsuarioModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        UsuarioModel::firstOrCreate(
            ['usuario' => 'admin'],
            [
                'nombre' => 'Catherine Aylin',
                'apellido_paterno' => 'Ochoa',
                'apellido_materno' => 'Rabadan',
                'email' => 'rabadan.2005.c@gmail.com',
                'password' => Hash::make('12345678'),
                'rol' => 'administrador',
                'activo' => true,
            ]
        );
    }
}