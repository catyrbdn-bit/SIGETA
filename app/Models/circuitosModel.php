<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class circuitosModel extends Model
{
    protected $table = 'circuitos';

    protected $fillable = [
        'zona_id',
        'nombre',
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
}