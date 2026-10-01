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
        Schema::create('puntos_abastecimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('tipo', 20); // pozo, laguna
            $table->unsignedTinyInteger('horas_atraso_apagon')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_abastecimiento');
    }
};
