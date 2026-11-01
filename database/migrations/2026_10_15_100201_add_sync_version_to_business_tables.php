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
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('sync_version')->default(0);
            });
        }
    }

    // revierte los cambios de la migracion
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('sync_version');
            });
        }
    }
};