<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tandeador_zona', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unique(['zona_id', 'usuario_id']);
        });

        $asignaciones = DB::table('tandeos_programados as t')
            ->join('circuitos as c', 'c.id', '=', 't.circuito_id')
            ->whereNotNull('t.tandeador_id')
            ->select('c.zona_id', 't.tandeador_id as usuario_id')
            ->distinct()
            ->get();

        foreach ($asignaciones as $asignacion) {
            DB::table('tandeador_zona')->insertOrIgnore([
                'zona_id' => $asignacion->zona_id,
                'usuario_id' => $asignacion->usuario_id,
            ]);
        }

        if (Schema::hasTable('tandeo_repeticiones')) {
            $series = DB::table('tandeo_repeticiones as r')
                ->join('circuitos as c', 'c.id', '=', 'r.circuito_id')
                ->whereNotNull('r.tandeador_id')
                ->select('c.zona_id', 'r.tandeador_id as usuario_id')
                ->distinct()
                ->get();

            foreach ($series as $asignacion) {
                DB::table('tandeador_zona')->insertOrIgnore([
                    'zona_id' => $asignacion->zona_id,
                    'usuario_id' => $asignacion->usuario_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tandeador_zona');
    }
};
