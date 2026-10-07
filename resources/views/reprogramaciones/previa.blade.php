<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa - SIGETA</title>

    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 24px; }
        h2 { margin: 0; }
        .sub { color: #666; margin: 4px 0 16px; }

        #buscar { width: 320px; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 14px; }

        table { border-collapse: collapse; width: 100%; }
        th { text-align: left; font-size: 12px; letter-spacing: .5px; color: #777; padding: 10px 8px; border-bottom: 2px solid #ddd; text-transform: uppercase; }
        td { padding: 12px 8px; border-bottom: 1px solid #eee; vertical-align: middle; }

        .circuito { font-weight: bold; }
        .zona { font-size: 12px; color: #777; margin-top: 2px; }
        .hora { white-space: nowrap; }
        .dia { font-size: 11px; color: #888; margin-bottom: 2px; }
        .fin { font-size: 12px; color: #777; margin-top: 4px; }

        input[type=datetime-local], input[type=number], input[type=text] { padding: 6px; border: 1px solid #bbb; border-radius: 6px; }
        input.atraso { width: 70px; text-align: center; }
        input.motivo { width: 100%; min-width: 200px; box-sizing: border-box; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-left: 6px; }
        .badge.manual { background: #fde68a; color: #92400e; }

        .aviso { color: #b36b00; }
        button.principal { background: #1f4e79; color: #fff; border: 0; padding: 9px 18px; border-radius: 6px; cursor: pointer; }
        button.secundario { background: #fff; border: 1px solid #aaa; padding: 9px 18px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

    @php
        $hora = fn ($i, $f) => $i->format('H:i') . ' – ' . $f->format('H:i')
            . ($f->isSameDay($i) ? '' : ' (+1 día)');

        $dia = fn ($c) => ucfirst($c->copy()->locale('es')->translatedFormat('D d/m'));

        $fechaTitulo = ucfirst($desde->copy()->locale('es')->translatedFormat('l, j \d\e F \d\e Y'));

        $filas = collect($resultado['movimientos'])
            ->sortBy(fn ($m) => $m['inicio_anterior']->timestamp);
    @endphp

    <h2>Vista previa del apagón general</h2>
    <p class="sub">{{ $fechaTitulo }} &middot; {{ $desde->format('H:i') }}</p>

    @if ($errors->any())
        <ul style="color:red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @foreach ($resultado['advertencias'] as $aviso)
        <p class="aviso">⚠ {{ $aviso }}</p>
    @endforeach

    @if ($filas->count())

        <input type="text" id="buscar" placeholder="Buscar circuito o zona…">

        <form action="{{ route('reprogramaciones.apagon.store') }}" method="POST">
            @csrf
            <input type="hidden" name="desde" value="{{ $desde->format('Y-m-d\TH:i') }}">

            <table>
                <thead>
                    <tr>
                        <th>Circuito / Zona</th>
                        <th>Horario actual</th>
                        <th>Horario nuevo</th>
                        <th style="text-align:center;">Atraso (h)</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $m)
                        @php
                            $id = $m['tandeo']->id;
                            $propuesto = $m['inicio_nuevo']->format('Y-m-d\TH:i');
                            $motivoBase = $m['es_cadena'] ? 'Recorrido por empalme' : 'Afectado por el apagón';
                        @endphp

                        <tr class="dato"
                            data-base="{{ $m['inicio_anterior']->format('Y-m-d\TH:i') }}"
                            data-dur="{{ $m['inicio_anterior']->diffInMinutes($m['fin_anterior']) }}">

                            <td>
                                <div class="circuito">
                                    {{ $m['tandeo']->circuito->nombre }}
                                    @if ($m['manual'])
                                        <span class="badge manual">Ajustado</span>
                                    @endif
                                </div>
                                <div class="zona">
                                    {{ $m['tandeo']->circuito->zona->nombre ?? 'Sin zona' }}
                                    &middot;
                                    {{ $m['tandeo']->circuito->puntoAbastecimiento->nombre ?? 'Sin punto fijo' }}
                                </div>
                            </td>

                            <td class="hora">
                                <div class="dia">{{ $dia($m['inicio_anterior']) }}</div>
                                {{ $hora($m['inicio_anterior'], $m['fin_anterior']) }}
                            </td>

                            <td class="hora">
                                <input type="datetime-local" class="nuevo" name="nuevo[{{ $id }}]" value="{{ $propuesto }}">
                                <div class="fin">
                                    Termina: {{ $m['fin_nuevo']->format('H:i') }}{{ $m['fin_nuevo']->isSameDay($m['inicio_nuevo']) ? '' : ' (+1 día)' }}
                                </div>
                                <input type="hidden" name="propuesto[{{ $id }}]" value="{{ $propuesto }}">
                                <input type="hidden" name="manual_prev[{{ $id }}]" value="{{ $m['manual'] ? 1 : 0 }}">
                            </td>

                            <td class="num" style="text-align:center;">
                                <input type="number" class="atraso" step="any"
                                       value="{{ round($m['inicio_anterior']->diffInMinutes($m['inicio_nuevo']) / 60, 2) }}">
                            </td>

                            <td>
                                <input type="text" class="motivo" name="motivo[{{ $id }}]"
                                       value="{{ $m['motivo'] ?? $motivoBase }}" maxlength="255">
                                <input type="hidden" name="motivo_base[{{ $id }}]" value="{{ $motivoBase }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <br>

            <button type="submit" class="secundario"
                    formaction="{{ route('reprogramaciones.apagon.previa') }}">Recalcular</button>
            <button type="submit" class="principal">Confirmar y guardar</button>
            <button type="button" class="secundario"
                    onclick="window.location.href='{{ route('cumplimiento.index') }}'">Cancelar</button>
        </form>

        <script>
            const pad = n => String(n).padStart(2, '0');
            const aLocal = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
                + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            const hhmm = d => pad(d.getHours()) + ':' + pad(d.getMinutes());

            document.querySelectorAll('tr.dato').forEach(function (fila) {
                const base = new Date(fila.dataset.base);
                const dur = parseInt(fila.dataset.dur, 10);
                const nuevo = fila.querySelector('.nuevo');
                const atraso = fila.querySelector('.atraso');
                const fin = fila.querySelector('.fin');

                function pintarFin() {
                    if (!nuevo.value) return;
                    const ini = new Date(nuevo.value);
                    const f = new Date(ini.getTime() + dur * 60000);
                    fin.textContent = 'Termina: ' + hhmm(f)
                        + (f.toDateString() === ini.toDateString() ? '' : ' (+1 día)');
                }

                atraso.addEventListener('input', function () {
                    if (atraso.value === '') return;
                    nuevo.value = aLocal(new Date(base.getTime() + parseFloat(atraso.value) * 3600000));
                    pintarFin();
                });

                nuevo.addEventListener('input', function () {
                    if (!nuevo.value) return;
                    atraso.value = Math.round((new Date(nuevo.value) - base) / 3600000 * 100) / 100;
                    pintarFin();
                });
            });

            document.getElementById('buscar').addEventListener('input', function () {
                const texto = this.value.toLowerCase();
                document.querySelectorAll('tbody tr.dato').forEach(function (fila) {
                    fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
                });
            });
        </script>
    @else
        <p>No hay tandeos de hoy afectados por el apagón.</p>

        <button type="button" class="secundario" onclick="window.location.href='{{ route('cumplimiento.index') }}'">Volver</button>
    @endif

</body>
</html>