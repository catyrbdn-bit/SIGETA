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
        Schema::create('tandeo_repeticiones', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_uuid')->unique();
            $table->unsignedBigInteger('sync_version')->default(1);
            $table->uuid('repeticion_uuid')->unique();
            $table->foreignId('circuito_id')->constrained('circuitos');
            $table->date('fecha_ancla');
            $table->time('hora_inicio');
            $table->unsignedSmallInteger('duracion_minutos');
            $table->foreignId('tandeador_id')->nullable()->constrained('usuarios');
            $table->unsignedSmallInteger('cada_dias');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->uuid('repeticion_uuid')->nullable()->index();
            $table->unsignedSmallInteger('repetir_cada_dias')->nullable();
            $table->unique(['repeticion_uuid', 'fecha'], 'tandeos_repeticion_fecha_unica');
        });
    }

    // revierte los cambios de la migracion
    // Revierte los cambios de esta migración en la base de datos.
    // descripcion: revierte los cambios de esta migración en la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: restablece la estructura anterior de la base de datos
    public function down(): void
    {
        Schema::dropIfExists('tandeo_repeticiones');

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropUnique('tandeos_repeticion_fecha_unica');
            $table->dropIndex(['repeticion_uuid']);
            $table->dropColumn(['repeticion_uuid', 'repetir_cada_dias']);
        });
    }
};
