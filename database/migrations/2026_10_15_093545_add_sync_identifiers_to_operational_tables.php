<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $tables = [
        'usuarios',
        'zonas',
        'puntos_abastecimiento',
        'circuitos',
        'tandeos_programados',
        'cumplimiento_tandeos',
        'reprogramaciones',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->uuid('sync_uuid')->nullable();
                $table->unique('sync_uuid', $tableName . '_sync_uuid_unique');
            });

            DB::table($tableName)
                ->whereNull('sync_uuid')
                ->orderBy('id')
                ->chunkById(100, function ($records) use ($tableName) {
                    foreach ($records as $record) {
                        DB::table($tableName)
                            ->where('id', $record->id)
                            ->update(['sync_uuid' => (string) Str::uuid()]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropUnique($tableName . '_sync_uuid_unique');
                $table->dropColumn('sync_uuid');
            });
        }
    }
};