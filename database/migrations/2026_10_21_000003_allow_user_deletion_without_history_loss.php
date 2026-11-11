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
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('cumplimiento_tandeos', function (Blueprint $table) {
            $table->dropForeign(['capturado_por_id']);
            $table->foreign('capturado_por_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('tandeo_repeticiones', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('reprogramaciones', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->unsignedBigInteger('usuario_id')->nullable()->change();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
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
        Schema::table('reprogramaciones', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->unsignedBigInteger('usuario_id')->nullable(false)->change();
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('tandeo_repeticiones', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios');
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('cumplimiento_tandeos', function (Blueprint $table) {
            $table->dropForeign(['capturado_por_id']);
            $table->foreign('capturado_por_id')->references('id')->on('usuarios');
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios');
        });
    }
};
