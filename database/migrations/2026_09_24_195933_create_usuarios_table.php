<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * migraciones y tabla de usuarios
     */
  public function up(): void
{
    Schema::create('usuarios', function (Blueprint $table) {
        $table->id();

        $table->string('nombre', 100);
        $table->string('apellido_paterno', 50);
        $table->string('apellido_materno', 50);
        $table->string('usuario', 50)->unique();
        $table->string('email', 150)->unique();
        $table->string('password');
        $table->enum('rol', [
            'administrador',
            'tandeador',
            'capturista'
        ]);

        $table->boolean('activo')->default(true);

        $table->timestamps();
    });
}

    /**
     * 
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
