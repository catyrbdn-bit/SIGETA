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
    public function up(): void
    {
        Schema::table('circuitos', function (Blueprint $table) {
            $table->string('descripcion', 255)->nullable()->after('nombre');
        });
    }

    // revierte los cambios de la migracion
    public function down(): void
    {
        Schema::table('circuitos', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });
    }
};
