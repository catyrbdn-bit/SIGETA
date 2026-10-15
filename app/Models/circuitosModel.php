<?php

namespace App\Models;

use App\Traits\TracksSyncChanges;

use App\Traits\HasSyncUuid;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class circuitosModel extends Model
{
    use TracksSyncChanges;
    use HasSyncUuid;
    protected $table = 'circuitos';

    protected $fillable = [
        'zona_id',
        'nombre',
        'descripcion',
        'duracion',
        'punto_abastecimiento_id',
        'activo',
    ];

    protected $hidden = [
        // para campos ocultos, si los hubiera
    ];

    public function zona()
    {
        return $this->belongsTo(zonasModel::class);
    }

    public function tandeos()
    {
        // hasMany define una relación de uno a muchos:
        // un circuito puede tener múltiples tandeos programados
        return $this->hasMany(tandeoProgramado::class, 'circuito_id');
    }

    public function puntoAbastecimiento()
    {
        return $this->belongsTo(PuntoAbastecimiento::class, 'punto_abastecimiento_id');
    }

    /**
     * Genera el código del circuito: C + consecutivo de 2 dígitos + iniciales de la zona.
     * Ejemplo: C01-LJ (La Joya), C01-T (Tetillas).
     * El consecutivo es el número más alto que ya existe en la zona más uno.
     */
    public static function generarCodigo(int $zonaId): string
    {
        $zona = zonasModel::findOrFail($zonaId);

        $iniciales = collect(preg_split('/\s+/', trim(Str::ascii($zona->nombre))))
            ->filter()
            ->map(fn ($palabra) => strtoupper(substr($palabra, 0, 1)))
            ->implode('');

        $maximo = self::where('zona_id', $zonaId)
            ->pluck('nombre')
            ->map(fn ($nombre) => preg_match('/^C(\d+)-/', $nombre, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return sprintf('C%02d-%s', $maximo + 1, $iniciales);
    }
}