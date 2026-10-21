<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->uuid('repeticion_uuid')->nullable()->index();
            $table->unsignedSmallInteger('repetir_cada_dias')->nullable();
            $table->unique(['repeticion_uuid', 'fecha'], 'tandeos_repeticion_fecha_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tandeo_repeticiones');

        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropUnique('tandeos_repeticion_fecha_unica');
            $table->dropIndex(['repeticion_uuid']);
            $table->dropColumn(['repeticion_uuid', 'repetir_cada_dias']);
        });
    }
};
