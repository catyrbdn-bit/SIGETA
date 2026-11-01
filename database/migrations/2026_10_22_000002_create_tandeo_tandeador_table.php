<?php
// archivo del sistema: archivo que aplica cambios a la estructura de la base de datos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('tandeo_tandeador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tandeo_id')->constrained('tandeos_programados')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unique(['tandeo_id', 'usuario_id']);
        });

        $tandeos = DB::table('tandeos_programados as t')
            ->join('circuitos as c', 'c.id', '=', 't.circuito_id')
            ->leftJoin('tandeador_zona as tz', 'tz.zona_id', '=', 'c.zona_id')
            // Agrega una condición para filtrar los registros consultados.
            // descripcion: agrega una condición para filtrar los registros consultados.
            // entradas: consulta que se está filtrando
            // salida: deja la condición agregada a la consulta
            ->where(function ($consulta) {
                $consulta->whereNotNull('tz.usuario_id')->orWhereNotNull('t.tandeador_id');
            })
            ->select('t.id as tandeo_id', 'tz.usuario_id', 't.tandeador_id')
            ->get();

        foreach ($tandeos as $tandeo) {
            foreach (array_unique(array_filter([$tandeo->usuario_id, $tandeo->tandeador_id])) as $usuarioId) {
                DB::table('tandeo_tandeador')->insertOrIgnore([
                    'tandeo_id' => $tandeo->tandeo_id,
                    'usuario_id' => $usuarioId,
                ]);
            }
        }
    }

    // revierte los cambios de la migracion
    // Revierte los cambios de esta migración en la base de datos.
    // descripcion: revierte los cambios de esta migración en la base de datos.
    // entradas: no recibe datos; utiliza la definición de esta migración
    // salida: restablece la estructura anterior de la base de datos
    public function down(): void
    {
        Schema::dropIfExists('tandeo_tandeador');
    }
};
