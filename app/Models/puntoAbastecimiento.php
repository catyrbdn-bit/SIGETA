<?php

namespace App\Models;

// archivo del sistema: archivo que representa datos y relaciones de la base de datos

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
