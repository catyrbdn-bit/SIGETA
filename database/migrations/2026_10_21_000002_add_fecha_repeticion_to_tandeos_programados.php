<?php
// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->date('fecha_repeticion')->nullable();
        });

        DB::table('tandeos_programados')
            ->whereNotNull('repeticion_uuid')
            ->update(['fecha_repeticion' => DB::raw('fecha')]);

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropUnique('tandeos_repeticion_fecha_unica');
            $table->unique(['repeticion_uuid', 'fecha_repeticion'], 'tandeos_repeticion_fecha_programada_unica');
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
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropUnique('tandeos_repeticion_fecha_programada_unica');
            $table->unique(['repeticion_uuid', 'fecha'], 'tandeos_repeticion_fecha_unica');
            $table->dropColumn('fecha_repeticion');
        });
    }
};
