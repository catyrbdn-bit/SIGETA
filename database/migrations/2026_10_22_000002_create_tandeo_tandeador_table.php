<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tandeo_tandeador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tandeo_id')->constrained('tandeos_programados')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unique(['tandeo_id', 'usuario_id']);
        });

        $tandeos = DB::table('tandeos_programados as t')
            ->join('circuitos as c', 'c.id', '=', 't.circuito_id')
            ->leftJoin('tandeador_zona as tz', 'tz.zona_id', '=', 'c.zona_id')
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

    public function down(): void
    {
        Schema::dropIfExists('tandeo_tandeador');
    }
};
