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
        Schema::create('cumplimiento_tandeos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tandeo_id')->constrained('tandeos_programados');
            $table->enum('resultado', ['cumplido', 'atraso', 'no_cumplido']);
            $table->integer('horas_atraso')->nullable();
            $table->enum('motivo', ['apagon_general', 'fuga', 'otro'])->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('capturado_por_id')->nullable()->constrained('usuarios');
            $table->dateTime('fecha_captura')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cumplimiento_tandeos');
    }
};