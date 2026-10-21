<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        Schema::table('cumplimiento_tandeos', function (Blueprint $table) {
            $table->dropForeign(['capturado_por_id']);
            $table->foreign('capturado_por_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        Schema::table('tandeo_repeticiones', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        Schema::table('reprogramaciones', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->unsignedBigInteger('usuario_id')->nullable()->change();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reprogramaciones', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->unsignedBigInteger('usuario_id')->nullable(false)->change();
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });

        Schema::table('tandeo_repeticiones', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios');
        });

        Schema::table('cumplimiento_tandeos', function (Blueprint $table) {
            $table->dropForeign(['capturado_por_id']);
            $table->foreign('capturado_por_id')->references('id')->on('usuarios');
        });

        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropForeign(['tandeador_id']);
            $table->foreign('tandeador_id')->references('id')->on('usuarios');
        });
    }
};
