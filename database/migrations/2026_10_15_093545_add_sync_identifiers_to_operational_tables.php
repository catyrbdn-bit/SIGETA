<?php
// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $tables = [
        'usuarios',
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
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->uuid('sync_uuid')->nullable();
                $table->unique('sync_uuid', $tableName . '_sync_uuid_unique');
            });

            DB::table($tableName)
                ->whereNull('sync_uuid')
                ->orderBy('id')
                // Procesa los registros existentes en grupos pequeños.
                // descripcion: procesa los registros existentes en grupos pequeños.
                // entradas: grupo de registros de la tabla
                // salida: actualiza los registros de cada grupo
                ->chunkById(100, function ($records) use ($tableName) {
                    foreach ($records as $record) {
                        DB::table($tableName)
                            ->where('id', $record->id)
                            ->update(['sync_uuid' => (string) Str::uuid()]);
                    }
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
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropUnique($tableName . '_sync_uuid_unique');
                $table->dropColumn('sync_uuid');
            });
        }
    }
};