<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
    public function down(): void
    {
        Schema::dropIfExists('tandeos_programados');
    }
};