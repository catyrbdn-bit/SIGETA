<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuntoAbastecimiento extends Model
{
    
    protected $table = 'puntos_abastecimiento';

    protected $fillable = [
        'nombre', 
        'tipo', 
        'horas_atraso_apagon', 
        'activo'];

}
