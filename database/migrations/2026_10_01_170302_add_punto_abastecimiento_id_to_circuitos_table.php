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
        Schema::table('circuitos', function (Blueprint $table) {
            $table->foreignId('punto_abastecimiento_id')
                ->nullable()
                ->after('id') // opcional, en SQLite se ignora
                ->constrained('puntos_abastecimiento')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('circuitos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('punto_abastecimiento_id');
        });
    }
};
