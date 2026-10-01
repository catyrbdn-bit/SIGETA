@extends('layouts.app')

@section('content')
<h2>Vista previa: apagón en {{ $punto->nombre }}</h2>
<p>Atraso: {{ $resultado['horas'] }} horas, a partir del {{ $desde->format('d/m/Y H:i') }}</p>

@foreach ($resultado['advertencias'] as $aviso)
    <p>⚠ {{ $aviso }}</p>
@endforeach

@if (count($resultado['movimientos']))
    <table>
        <thead>
            <tr>
                <th>Circuito</th>
                <th>Horario actual</th>
                <th>Horario nuevo</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($resultado['movimientos'] as $m)
                <tr>
                    <td>{{ $m['tandeo']->circuito->nombre }}</td>
                    <td>{{ $m['inicio_anterior']->format('d/m H:i') }} - {{ $m['fin_anterior']->format('d/m H:i') }}</td>
                    <td>{{ $m['inicio_nuevo']->format('d/m H:i') }} - {{ $m['fin_nuevo']->format('d/m H:i') }}</td>
                    <td>{{ $m['es_cadena'] ? 'Recorrido por empalme' : 'Afectado por el apagón' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <form method="POST" action="{{ route('reprogramaciones.apagon.store') }}">
        @csrf
        <input type="hidden" name="punto_abastecimiento_id" value="{{ $punto->id }}">
        <input type="hidden" name="desde" value="{{ $desde->format('Y-m-d\TH:i') }}">
        <input type="hidden" name="horas" value="{{ $resultado['horas'] }}">

        <button type="submit">Confirmar y guardar</button>
        <a href="{{ route('reprogramaciones.apagon.create') }}">Cancelar</a>
    </form>
@else
    <p>No hay tandeos pendientes afectados por este apagón.</p>
    <a href="{{ route('reprogramaciones.apagon.create') }}">Volver</a>
@endif
@endsection