<?php

namespace App\Services;

use App\Models\RespaldoBd;
use App\Models\UsuarioModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

class RespaldoBaseDatosService
{
    // Crea un archivo con una copia completa de la base SQLite actual.
    public function crear(int $usuarioId): RespaldoBd
    {
        $this->revisarMotor();
        $carpeta = storage_path('app/respaldos');
        File::ensureDirectoryExists($carpeta);

        $nombre = 'sigeta_' . now()->format('Ymd_His_u') . '.sqlite';
        $rutaCompleta = $carpeta . DIRECTORY_SEPARATOR . $nombre;
        $rutaSql = str_replace("'", "''", $rutaCompleta);
        DB::connection()->getPdo()->exec("VACUUM INTO '{$rutaSql}'");

        return RespaldoBd::create([
            'usuario_id' => $usuarioId,
            'nombre_archivo' => $nombre,
            'ruta_archivo' => 'respaldos/' . $nombre,
            'tamano_bytes' => File::size($rutaCompleta),
            'estado' => 'disponible',
        ]);
    }

    // Restaura una copia y conserva el listado de respaldos que ya existía.
    public function restaurar(RespaldoBd $respaldo, RespaldoBd $copiaActual): array
    {
        $this->revisarMotor();
        $carpeta = realpath(storage_path('app/respaldos'));
        $rutaRespaldo = realpath(storage_path('app/' . $respaldo->ruta_archivo));

        if (!$carpeta || !$rutaRespaldo || !str_starts_with($rutaRespaldo, $carpeta . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('No se encontró el archivo de respaldo seleccionado.');
        }

        $this->revisarIntegridad($rutaRespaldo);
        $registrosPrevios = RespaldoBd::all()->map(function ($registro) {
            return $registro->only(['usuario_id', 'nombre_archivo', 'ruta_archivo', 'tamano_bytes', 'estado']);
        })->all();

        $baseActual = config('database.connections.sqlite.database');
        if (!$baseActual || $baseActual === ':memory:' || !File::exists($baseActual)) {
            throw new RuntimeException('No se pudo localizar el archivo de la base de datos actual.');
        }

        $temporal = $baseActual . '.nuevo';
        $anterior = $baseActual . '.anterior';
        File::copy($rutaRespaldo, $temporal);
        $this->revisarIntegridad($temporal);
        DB::connection()->getPdo()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        DB::purge();

        try {
            if (File::exists($anterior)) File::delete($anterior);
            File::move($baseActual, $anterior);
            File::move($temporal, $baseActual);
            foreach ([$baseActual . '-wal', $baseActual . '-shm'] as $archivoAuxiliar) {
                if (File::exists($archivoAuxiliar)) File::delete($archivoAuxiliar);
            }
            DB::purge();
            DB::connection()->getPdo();
            if (File::exists($anterior)) File::delete($anterior);
        } catch (\Throwable $error) {
            if (!File::exists($baseActual) && File::exists($anterior)) File::move($anterior, $baseActual);
            DB::purge();
            throw $error;
        }

        $registrosPrevios[] = $copiaActual->only(['usuario_id', 'nombre_archivo', 'ruta_archivo', 'tamano_bytes', 'estado']);
        foreach ($registrosPrevios as $datos) {
            if (!RespaldoBd::where('ruta_archivo', $datos['ruta_archivo'])->exists()) {
                if (!UsuarioModel::whereKey($datos['usuario_id'])->exists()) $datos['usuario_id'] = null;
                RespaldoBd::create($datos);
            }
        }

        return [
            'respaldo_actual' => RespaldoBd::where('ruta_archivo', $copiaActual->ruta_archivo)->firstOrFail(),
            'respaldo_restaurado' => RespaldoBd::where('ruta_archivo', $respaldo->ruta_archivo)->firstOrFail(),
        ];
    }

    private function revisarMotor(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Los respaldos están disponibles para la base SQLite configurada en este sistema.');
        }
    }

    private function revisarIntegridad(string $ruta): void
    {
        $pdo = new PDO('sqlite:' . $ruta);
        $resultado = $pdo->query('PRAGMA integrity_check')->fetchColumn();
        $pdo = null;
        if ($resultado !== 'ok') throw new RuntimeException('El archivo de respaldo no pasó la revisión de integridad.');
    }
}
