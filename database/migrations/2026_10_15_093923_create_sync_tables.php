<?php
// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // crea o actualiza la estructura de la base de datos
    // Aplica los cambios de esta migración a la base de datos.
    // descripcion: aplica los cambios de esta migración a la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: actualiza la estructura de la base de datos
    public function up(): void
    {
        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::create('sync_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_uuid')->unique();
            $table->string('nombre')->nullable();
            $table->dateTime('last_sync_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::create('sync_changes', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_uuid')->unique();
            $table->foreignId('sync_device_id')->nullable()->constrained('sync_devices')->nullOnDelete();
            $table->string('entity_type', 50);
            $table->uuid('record_uuid');
            $table->string('operation', 20);
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('base_version')->nullable();
            $table->string('status', 30)->default('pendiente');
            $table->unsignedInteger('intentos')->default(0);
            $table->dateTime('enviado_en')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'record_uuid']);
            $table->index('status');
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_uuid')->unique();
            $table->foreignId('sync_change_id')->constrained('sync_changes')->cascadeOnDelete();
            $table->json('datos_locales');
            $table->json('datos_centrales');
            $table->text('motivo')->nullable();
            $table->string('estado', 30)->default('pendiente_revision');
            $table->foreignId('resuelto_por_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->json('resolucion')->nullable();
            $table->dateTime('resuelto_en')->nullable();
            $table->timestamps();
        });
    }

    // revierte los cambios de la migracion
    // Revierte los cambios de esta migración en la base de datos.
    // descripcion: revierte los cambios de esta migración en la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: restablece la estructura anterior de la base de datos
    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
        Schema::dropIfExists('sync_changes');
        Schema::dropIfExists('sync_devices');
    }
};