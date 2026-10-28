<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('respaldos_bd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('estado', 20)->default('disponible');
            $table->timestamps();
        });

        Schema::table('historial_actividad', function (Blueprint $table) {
            $table->foreignId('respaldo_id')->nullable()
                ->constrained('respaldos_bd')->nullOnDelete();
        });

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

            Schema::table($nombreTabla, function (Blueprint $table) use ($nombreTabla, $columnas) {
                $table->dropUnique($nombreTabla . '_sync_uuid_unique');
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

    public function down(): void
    {
        Schema::table('reprogramaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('historial_id');
        });

        Schema::table('historial_actividad', function (Blueprint $table) {
            $table->dropConstrainedForeignId('respaldo_id');
        });

        Schema::dropIfExists('respaldos_bd');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('reglas_reprogramacion');

        // Las tablas de sincronización y sus datos se recuperan desde la copia de seguridad.
    }
};
