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
        //hasMany sirve para definir una relación de uno a muchos, 
        //donde un circuito puede tener múltiples tandeos programados asociados a él
        return $this->hasMany(TandeoProgramado::class, 'circuito_id');
    }

    


}
