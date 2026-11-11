<?php

namespace App\Models;

// archivo del sistema: archivo que representa datos y relaciones de la base de datos

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CircuitosModel extends Model
{
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

    // relaciona el registro con su colonia
    // Relaciona el circuito con la colonia a la que pertenece.
    // descripcion: relaciona el circuito con la colonia a la que pertenece.
    // entradas: registro actual del modelo
    // salida: permite consultar la colonia del circuito
    public function zona()
    {
        return $this->belongsTo(ZonasModel::class);
    }

    // relaciona el circuito con sus tandeos programados
    // Relaciona el circuito con los tandeos que tiene programados.
    // descripcion: relaciona el circuito con los tandeos que tiene programados.
    // entradas: registro actual del modelo
    // salida: permite consultar los tandeos del circuito
    public function tandeos()
    {
        // hasMany define una relación de uno a muchos:
        // un circuito puede tener múltiples tandeos programados
        return $this->hasMany(TandeoProgramado::class, 'circuito_id');
    }

    // relaciona el circuito con su punto de abastecimiento
    // Relaciona el circuito con su punto de abastecimiento.
    // descripcion: relaciona el circuito con su punto de abastecimiento.
    // entradas: registro actual del modelo
    // salida: permite consultar el punto de abastecimiento del circuito
    public function puntoAbastecimiento()
    {
        return $this->belongsTo(PuntoAbastecimiento::class, 'punto_abastecimiento_id');
    }

    /**
     * Genera el código del circuito: C + consecutivo de 2 dígitos + iniciales de la zona.
     * Ejemplo: C01-LJ (La Joya), C01-T (Tetillas).
     * El consecutivo es el número más alto que ya existe en la zona más uno.
     */
    // Genera el siguiente código disponible para el circuito.
    // descripcion: genera el siguiente código disponible para el circuito.
    // entradas: identificador del registro relacionado
    // salida: devuelve el código que se asignará al circuito
    public static function generarCodigo(int $zonaId): string
    {
        $zona = ZonasModel::findOrFail($zonaId);

        $iniciales = collect(preg_split('/\s+/', trim(Str::ascii($zona->nombre))))
            ->filter()
            // Prepara cada registro para guardarlo o mostrarlo.
            // descripcion: prepara cada registro para guardarlo o mostrarlo.
            // entradas: registro que se está transformando
            // salida: devuelve el registro con sus datos preparados
            ->map(fn ($palabra) => strtoupper(substr($palabra, 0, 1)))
            ->implode('');

        $maximo = self::where('zona_id', $zonaId)
            ->pluck('nombre')
            // Prepara cada registro para guardarlo o mostrarlo.
            // descripcion: prepara cada registro para guardarlo o mostrarlo.
            // entradas: registro que se está transformando
            // salida: devuelve el registro con sus datos preparados
            ->map(fn ($nombre) => preg_match('/^C(\d+)-/', $nombre, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return sprintf('C%02d-%s', $maximo + 1, $iniciales);
    }
}
