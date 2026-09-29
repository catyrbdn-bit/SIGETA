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
        'activo',
    ];

    protected $hidden = [
        // para campos ocultos, si los hubiera
    ];  

       

    public function zona()
    {
        //hace que cada circuito pertenezca a una zona
        return $this->belongsTo(Zona::class); 
        
    }


    //
}
