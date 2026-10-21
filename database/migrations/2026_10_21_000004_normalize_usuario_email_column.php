<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('usuarios', 'correo') && !Schema::hasColumn('usuarios', 'email')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->renameColumn('correo', 'email');
            });
        }
    }

    public function down(): void
    {
        // Se conserva email como nombre estándar de la aplicación.
    }
};
