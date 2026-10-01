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
        Schema::create('reprogramaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tandeo_id')->constrained('tandeos_programados')->cascadeOnDelete();
            $table->uuid('cadena_uuid')->index(); // agrupa todos los movimientos de un mismo evento

            $table->string('tipo_origen', 30); // apagon_general, fuga, manual_solo_este, manual_este_y_siguientes
            $table->boolean('es_manual')->default(false);
            $table->boolean('es_efecto_cadena')->default(false); // false = el que disparó, true = movido por arrastre

            $table->foreignId('punto_abastecimiento_id')->nullable()->constrained('puntos_abastecimiento')->nullOnDelete();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete(); // para fuga

            $table->dateTime('inicio_anterior');
            $table->dateTime('fin_anterior');
            $table->dateTime('inicio_nuevo');
            $table->dateTime('fin_nuevo');
            $table->decimal('horas_atraso', 4, 1);

            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reprogramaciones');
    }
};
