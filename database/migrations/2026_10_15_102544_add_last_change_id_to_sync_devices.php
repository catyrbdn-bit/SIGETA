<?php
// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // crea o actualiza la estructura de la base de datos
    public function up(): void
    {
        Schema::table('sync_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('last_change_id')->default(0);
        });
    }

    // revierte los cambios de la migracion
    public function down(): void
    {
        Schema::table('sync_devices', function (Blueprint $table) {
            $table->dropColumn('last_change_id');
        });
    }
};