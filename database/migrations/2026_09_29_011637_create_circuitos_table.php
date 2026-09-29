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
        Schema::create('circuitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas');
            $table->string('nombre');
            $table->integer('duracion');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['zona_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuitos');
    }
};
