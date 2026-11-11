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
        Schema::create('reglas_reprogramacion', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_evento', 30);
            $table->foreignId('punto_abastecimiento_id')->nullable()
                ->constrained('puntos_abastecimiento')->nullOnDelete();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->string('temporada', 50)->nullable();
            $table->decimal('horas_minimas', 5, 2);
            $table->decimal('horas_maximas', 5, 2);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('tandeo_id')->nullable()->constrained('tandeos_programados')->nullOnDelete();
            $table->string('destinatario_email', 150);
            $table->string('tipo', 50);
            $table->string('asunto', 200);
            $table->text('mensaje');
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedInteger('intentos')->default(0);
            $table->text('ultimo_error')->nullable();
            $table->dateTime('reintentar_en')->nullable();
            $table->dateTime('enviado_at')->nullable();
            $table->timestamps();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::create('respaldos_bd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('estado', 20)->default('disponible');
            $table->timestamps();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('historial_actividad', function (Blueprint $table) {
            $table->foreignId('respaldo_id')->nullable()
                ->constrained('respaldos_bd')->nullOnDelete();
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('reprogramaciones', function (Blueprint $table) {
            $table->foreignId('historial_id')->nullable()
                ->constrained('historial_actividad')->nullOnDelete();
        });

        $columnasSync = [
            'usuarios' => ['sync_uuid'],
            'zonas' => ['sync_uuid', 'sync_version'],
            'puntos_abastecimiento' => ['sync_uuid', 'sync_version'],
            'circuitos' => ['sync_uuid', 'sync_version'],
            'tandeos_programados' => ['sync_uuid', 'sync_version'],
            'cumplimiento_tandeos' => ['sync_uuid', 'sync_version'],
            'reprogramaciones' => ['sync_uuid'],
            'tandeo_repeticiones' => ['sync_uuid', 'sync_version'],
        ];

        foreach ($columnasSync as $nombreTabla => $columnas) {
            if (! Schema::hasColumn($nombreTabla, 'sync_uuid')) {
                continue;
            }

            // Define los campos y restricciones de la tabla.
            // descripcion: define los campos y restricciones de la tabla.
            // entradas: columnas de la tabla que se crea o modifica
            // salida: deja preparada la estructura que utilizará el sistema
            Schema::table($nombreTabla, function (Blueprint $table) use ($nombreTabla, $columnas) {
                $table->dropUnique($nombreTabla.'_sync_uuid_unique');
                $table->dropColumn($columnas);
            });
        }

        Schema::dropIfExists('sync_conflicts');
        Schema::dropIfExists('sync_changes');
        Schema::dropIfExists('sync_devices');

        // Estas dos tablas antiguas están vacías y SIGETA usa otras tablas para esos datos.
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }

    // revierte los cambios de la migracion
    // Revierte los cambios de esta migración en la base de datos.
    // descripcion: revierte los cambios de esta migración en la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: restablece la estructura anterior de la base de datos
    public function down(): void
    {
        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('reprogramaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('historial_id');
        });

        // Define los campos y restricciones de la tabla.
        // descripcion: define los campos y restricciones de la tabla.
        // entradas: columnas de la tabla que se crea o modifica
        // salida: deja preparada la estructura que utilizará el sistema
        Schema::table('historial_actividad', function (Blueprint $table) {
            $table->dropConstrainedForeignId('respaldo_id');
        });

        Schema::dropIfExists('respaldos_bd');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('reglas_reprogramacion');

        // Las tablas de sincronización y sus datos se recuperan desde la copia de seguridad.
    }
};
