<?php

// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'zonas',
        'puntos_abastecimiento',
        'circuitos',
        'tandeos_programados',
        'cumplimiento_tandeos',
        'reprogramaciones',
    ];

    // crea o actualiza la estructura de la base de datos
    // Aplica los cambios de esta migración a la base de datos.
    // descripcion: aplica los cambios de esta migración a la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: actualiza la estructura de la base de datos
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            // Define los campos y restricciones de la tabla.
            // descripcion: define los campos y restricciones de la tabla.
            // entradas: columnas de la tabla que se crea o modifica
            // salida: deja preparada la estructura que utilizará el sistema
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('sync_version')->default(0);
            });
        }
    }

    // revierte los cambios de la migracion
    // Revierte los cambios de esta migración en la base de datos.
    // descripcion: revierte los cambios de esta migración en la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: restablece la estructura anterior de la base de datos
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            // Define los campos y restricciones de la tabla.
            // descripcion: define los campos y restricciones de la tabla.
            // entradas: columnas de la tabla que se crea o modifica
            // salida: deja preparada la estructura que utilizará el sistema
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('sync_version');
            });
        }
    }
};
