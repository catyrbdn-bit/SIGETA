<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->date('fecha_repeticion')->nullable();
        });

        DB::table('tandeos_programados')
            ->whereNotNull('repeticion_uuid')
            ->update(['fecha_repeticion' => DB::raw('fecha')]);

        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropUnique('tandeos_repeticion_fecha_unica');
            $table->unique(['repeticion_uuid', 'fecha_repeticion'], 'tandeos_repeticion_fecha_programada_unica');
        });
    }

    public function down(): void
    {
        Schema::table('tandeos_programados', function (Blueprint $table) {
            $table->dropUnique('tandeos_repeticion_fecha_programada_unica');
            $table->unique(['repeticion_uuid', 'fecha'], 'tandeos_repeticion_fecha_unica');
            $table->dropColumn('fecha_repeticion');
        });
    }
};
