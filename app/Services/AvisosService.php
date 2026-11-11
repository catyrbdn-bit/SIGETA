<?php

namespace App\Services;

use App\Jobs\EnviarCorreoPendiente;
use App\Models\Notificacion;
use App\Models\TandeoProgramado;
use App\Models\UsuarioModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AvisosService
{
    // Guarda avisos para los administradores y tandeadores relacionados.
    public function avisarCambiosHorario(array $movimientos, string $tipo): void
    {
        if (empty($movimientos)) {
            return;
        }

        $etiquetas = [
            'reprogramacion' => 'reprogramación manual',
            'apagon_general' => 'apagón general',
            'fuga_laguna' => 'fuga en laguna',
            'fuga_pozo' => 'fuga en pozo',
        ];
        $etiqueta = $etiquetas[$tipo] ?? 'incidencia';
        $asunto = $tipo === 'reprogramacion'
            ? 'Cambios de horario de tandeos'
            : 'Aviso por '.$etiqueta;

        $administradores = UsuarioModel::where('rol', 'administrador')
            ->where('activo', true)
            ->whereNotNull('email')
            ->get();
        $avisosPorUsuario = [];

        foreach ($movimientos as $movimiento) {
            $tandeo = $movimiento['tandeo'];
            $tandeo->loadMissing(['circuito.zona', 'tandeadores', 'tandeador']);
            $usuarios = collect($this->buscarTandeadores($tandeo))
                ->concat($administradores)
                ->filter(fn ($usuario) => $usuario->email)
                ->unique(fn ($usuario) => (int) $usuario->id);

            $cambio = [
                'circuito' => $tandeo->circuito->nombre ?? 'Sin circuito',
                'colonia' => $tandeo->circuito->zona->nombre ?? 'Colonia no disponible',
                'lugar' => $tandeo->circuito->descripcion ?: 'Sin lugar registrado',
                'horario_anterior' => $this->formatearHorario($movimiento['inicio_anterior'], $movimiento['fin_anterior']),
                'horario_nuevo' => $this->formatearHorario($movimiento['inicio_nuevo'], $movimiento['fin_nuevo']),
                'motivo' => $movimiento['motivo'] ?? $etiqueta,
            ];

            foreach ($usuarios as $usuario) {
                $id = (int) $usuario->id;
                $avisosPorUsuario[$id]['usuario'] = $usuario;
                $avisosPorUsuario[$id]['cambios'][] = $cambio;
            }
        }

        foreach ($avisosPorUsuario as $aviso) {
            $this->guardarAviso(
                $aviso['usuario'],
                null,
                $tipo,
                $asunto,
                $this->mensajeCambios($aviso['cambios'], $etiqueta)
            );
        }
    }

    // Prepara el resumen de los tandeos de los siguientes siete días.
    public function crearResumenSemanal(): int
    {
        $inicio = now()->startOfDay();
        $fin = $inicio->copy()->addDays(6)->endOfDay();
        $inicioCiclo = $this->inicioCicloResumen();
        $tandeos = TandeoProgramado::with(['circuito.zona', 'tandeadores', 'tandeador'])
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        $cantidad = 0;
        $idsTandeadores = [];
        foreach ($tandeos as $tandeo) {
            foreach ($this->buscarTandeadores($tandeo) as $tandeador) {
                $idsTandeadores[] = $tandeador->id;
            }
        }
        $idsTandeadores = array_unique($idsTandeadores);

        foreach ($idsTandeadores as $id) {
            $tandeador = UsuarioModel::find($id);
            if (! $tandeador || ! $tandeador->email) {
                continue;
            }

            if ($this->yaSePreparoResumen($tandeador, $inicioCiclo)) {
                continue;
            }

            $asignados = collect();
            foreach ($tandeos as $tandeo) {
                foreach ($this->buscarTandeadores($tandeo) as $asignado) {
                    if ((int) $asignado->id === (int) $tandeador->id) {
                        $asignados->push($tandeo);
                        break;
                    }
                }
            }
            $cantidad += $this->guardarAviso(
                $tandeador,
                null,
                'resumen_semanal',
                'Resumen semanal de tandeos',
                $this->mensajeResumen($asignados, $inicio, $fin)
            ) ? 1 : 0;
        }

        $administradores = UsuarioModel::where('rol', 'administrador')
            ->where('activo', true)->whereNotNull('email')->get();
        foreach ($administradores as $administrador) {
            if ($this->yaSePreparoResumen($administrador, $inicioCiclo)) {
                continue;
            }

            $cantidad += $this->guardarAviso(
                $administrador,
                null,
                'resumen_semanal',
                'Resumen semanal de tandeos',
                $this->mensajeResumen($tandeos, $inicio, $fin)
            ) ? 1 : 0;
        }

        return $cantidad;
    }

    // Prepara el resumen pendiente cuando se abre el sistema después del viernes a las nueve.
    public function revisarResumenSemanalAlAbrir(): int
    {
        return $this->crearResumenSemanal();
    }

    // Intenta enviar de nuevo los avisos que siguen pendientes.
    public function revisarAvisosPendientes(): int
    {
        $pendientes = Notificacion::where('estado', 'pendiente')
            ->where(function ($consulta) {
                $consulta->whereNull('reintentar_en')->orWhere('reintentar_en', '<=', now());
            })->get();

        foreach ($pendientes as $notificacion) {
            $this->ponerEnCola($notificacion);
        }

        return $pendientes->count();
    }

    // Registra el aviso en la base y lo pone en la cola de correo.
    private function guardarAviso(?UsuarioModel $usuario, ?TandeoProgramado $tandeo, string $tipo, string $asunto, string $mensaje): bool
    {
        if (! $usuario || ! $usuario->email) {
            return false;
        }

        try {
            $notificacion = Notificacion::create([
                'usuario_id' => $usuario->getKey(),
                'tandeo_id' => $tandeo?->getKey(),
                'destinatario_email' => $usuario->email,
                'tipo' => $tipo,
                'asunto' => $asunto,
                'mensaje' => $mensaje,
                'estado' => 'pendiente',
            ]);
            $this->ponerEnCola($notificacion);

            return true;
        } catch (Throwable $error) {
            Log::error('No se pudo guardar una notificación.', ['error' => $error->getMessage()]);

            return false;
        }
    }

    // Evita duplicar trabajos y deja pendiente el aviso si todavía falta configurar SMTP.
    private function ponerEnCola(Notificacion $notificacion): void
    {
        if (config('mail.default') !== 'smtp') {
            $notificacion->update([
                'ultimo_error' => 'Configura el servidor SMTP para enviar correos.',
                'reintentar_en' => now()->addMinutes(5),
            ]);

            return;
        }

        try {
            $notificacion->update(['estado' => 'en_cola', 'ultimo_error' => null]);
            EnviarCorreoPendiente::dispatch((int) $notificacion->id);
        } catch (Throwable $error) {
            $notificacion->update([
                'estado' => 'pendiente',
                'ultimo_error' => $error->getMessage(),
                'reintentar_en' => now()->addMinutes(5),
            ]);
        }
    }

    // Arma el texto que recibe cada usuario.
    private function mensajeResumen(Collection $tandeos, Carbon $inicio, Carbon $fin): string
    {
        $filas = $tandeos->map(function ($tandeo) {
            return [
                'fecha' => $tandeo->fecha->copy()->locale('es')->translatedFormat('l d/m/Y'),
                'colonia' => $tandeo->circuito->zona->nombre ?? 'Colonia no disponible',
                'lugar' => $tandeo->circuito->descripcion ?: $tandeo->circuito->nombre,
                'horario' => substr($tandeo->hora_inicio_programada, 0, 5)
                    .' – '.substr($tandeo->hora_fin_programada, 0, 5),
            ];
        });

        return view('correo-resumen-semanal', [
            'filas' => $filas,
            'inicio' => $inicio->format('d/m/Y'),
            'fin' => $fin->format('d/m/Y'),
        ])->render();
    }

    // Devuelve el viernes a las nueve que inicia el ciclo de envío actual.
    private function inicioCicloResumen(): Carbon
    {
        $viernesActual = now()->startOfWeek(Carbon::MONDAY)
            ->addDays(4)
            ->setTime(9, 0);

        if (now()->lt($viernesActual)) {
            $viernesActual->subWeek();
        }

        return $viernesActual;
    }

    // Evita preparar más de un resumen para la misma persona durante el ciclo semanal.
    private function yaSePreparoResumen(UsuarioModel $usuario, Carbon $inicioCiclo): bool
    {
        return Notificacion::where('usuario_id', $usuario->id)
            ->where('tipo', 'resumen_semanal')
            ->where('created_at', '>=', $inicioCiclo)
            ->where('created_at', '<', $inicioCiclo->copy()->addWeek())
            ->exists();
    }

    // Ordena los cambios en bloques separados para facilitar su lectura.
    private function mensajeCambios(array $cambios, string $evento): string
    {
        return view('correo-cambios', [
            'cambios' => $cambios,
            'evento' => $evento,
        ])->render();
    }

    // Muestra una sola fecha cuando el tandeo empieza y termina el mismo día.
    private function formatearHorario(Carbon $inicio, Carbon $fin): string
    {
        if ($inicio->isSameDay($fin)) {
            return $inicio->format('d/m/Y').' · '.$inicio->format('H:i').'–'.$fin->format('H:i');
        }

        return $inicio->format('d/m/Y H:i').' – '.$fin->format('d/m/Y H:i');
    }

    // Devuelve los tandeadores directos y los relacionados con el tandeo.
    private function buscarTandeadores(TandeoProgramado $tandeo): array
    {
        $personas = $tandeo->tandeadores->all();
        if ($tandeo->tandeador) {
            $principalYaExiste = false;
            foreach ($personas as $persona) {
                if ((int) $persona->id === (int) $tandeo->tandeador->id) {
                    $principalYaExiste = true;
                    break;
                }
            }
            if (! $principalYaExiste) {
                $personas[] = $tandeo->tandeador;
            }
        }

        $tandeadores = [];
        foreach ($personas as $persona) {
            if ($persona->rol === 'tandeador' && $persona->activo) {
                $tandeadores[] = $persona;
            }
        }

        return $tandeadores;
    }

    // Avisa a los tandeadores asignados y a los administradores.
    private function avisarUsuarios(TandeoProgramado $tandeo, string $asunto, string $mensaje, string $tipo): void
    {
        $destinatarios = $this->buscarTandeadores($tandeo);
        foreach (UsuarioModel::where('rol', 'administrador')->where('activo', true)->get() as $administrador) {
            $destinatarios[] = $administrador;
        }

        foreach ($destinatarios as $usuario) {
            $this->guardarAviso($usuario, $tandeo, $tipo, $asunto, $mensaje);
        }
    }
}
