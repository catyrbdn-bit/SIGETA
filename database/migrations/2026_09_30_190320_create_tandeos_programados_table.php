<?php
// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
        Schema::create('tandeos_programados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuito_id')->constrained('circuitos');
            $table->date('fecha');
            $table->time('hora_inicio_programada');
            $table->time('hora_fin_programada')->nullable();
            $table->time('hora_inicio_real')->nullable();
            $table->time('hora_fin_real')->nullable();
            $table->string('estado')->default('programado');
            $table->foreignId('tandeador_id')->nullable()->constrained('usuarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    // Revierte los cambios de esta migración en la base de datos.
    // descripcion: revierte los cambios de esta migración en la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: restablece la estructura anterior de la base de datos
    public function down(): void
    {
        Schema::dropIfExists('tandeos_programados');
    }
};