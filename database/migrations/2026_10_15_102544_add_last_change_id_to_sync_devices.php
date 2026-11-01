<?php
// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // crea o actualiza la estructura de la base de datos
    // Aplica los cambios de esta migración a la base de datos.
    // descripcion: aplica los cambios de esta migración a la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: actualiza la estructura de la base de datos
    public function up(): void
    {
        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('sync_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('last_change_id')->default(0);
        });
    }

    // revierte los cambios de la migracion
    // Revierte los cambios de esta migración en la base de datos.
    // descripcion: revierte los cambios de esta migración en la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: restablece la estructura anterior de la base de datos
    public function down(): void
    {
        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('sync_devices', function (Blueprint $table) {
            $table->dropColumn('last_change_id');
        });
    }
};