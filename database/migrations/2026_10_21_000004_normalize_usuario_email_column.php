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
        if (Schema::hasColumn('usuarios', 'correo') && !Schema::hasColumn('usuarios', 'email')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->renameColumn('correo', 'email');
            });
        }
    }

    // revierte los cambios de la migracion
    public function down(): void
    {
        // Se conserva email como nombre estándar de la aplicación.
    }
};
