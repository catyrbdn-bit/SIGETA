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
        foreach ($movimientos as $movimiento) {
            $tandeo = $movimiento['tandeo'];
            $tandeo->loadMissing(['circuito.zona', 'tandeadores', 'tandeador']);

            $inicioAnterior = $movimiento['inicio_anterior'];
            $finAnterior = $movimiento['fin_anterior'];
            $inicioNuevo = $movimiento['inicio_nuevo'];
            $finNuevo = $movimiento['fin_nuevo'];
            $motivo = $movimiento['motivo'] ?? $tipo;
            $etiquetas = [
                'reprogramacion' => 'reprogramación',
                'apagon_general' => 'apagón general',
                'fuga_laguna' => 'fuga en laguna',
                'fuga_pozo' => 'fuga en pozo',
            ];
            $etiqueta = $etiquetas[$tipo] ?? 'incidencia';
            $asunto = $tipo === 'reprogramacion'
                ? 'Cambio de horario de un tandeo'
                : 'Aviso de atraso por ' . $etiqueta;

            $mensaje = "Se modificó un tandeo del sistema.\n\n"
                . 'Colonia: ' . ($tandeo->circuito->zona->nombre ?? 'No disponible') . "\n"
                . 'Calles: ' . ($tandeo->circuito->descripcion ?: 'Sin calles registradas') . "\n"
                . 'Horario anterior: ' . $inicioAnterior->format('d/m/Y H:i') . ' a ' . $finAnterior->format('d/m/Y H:i') . "\n"
                . 'Horario nuevo: ' . $inicioNuevo->format('d/m/Y H:i') . ' a ' . $finNuevo->format('d/m/Y H:i') . "\n"
                . 'Motivo: ' . $motivo;

            $this->avisarUsuarios($tandeo, $asunto, $mensaje, $tipo);
        }
    }

    // Prepara el resumen de los tandeos de los siguientes siete días.
    public function crearResumenSemanal(): int
    {
        $inicio = now()->startOfDay();
        $fin = $inicio->copy()->addDays(6)->endOfDay();
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
            if (!$tandeador || !$tandeador->email) continue;

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
                $this->textoResumen($asignados, $inicio, $fin)
            ) ? 1 : 0;
        }

        $administradores = UsuarioModel::where('rol', 'administrador')
            ->where('activo', true)->whereNotNull('email')->get();
        foreach ($administradores as $administrador) {
            $cantidad += $this->guardarAviso(
                $administrador,
                null,
                'resumen_semanal',
                'Resumen semanal de tandeos',
                $this->textoResumen($tandeos, $inicio, $fin)
            ) ? 1 : 0;
        }

        return $cantidad;
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
        if (!$usuario || !$usuario->email) return false;

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
    private function textoResumen(Collection $tandeos, Carbon $inicio, Carbon $fin): string
    {
        $lineas = [
            'Tandeos programados del ' . $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y') . ':',
            '',
        ];

        foreach ($tandeos as $tandeo) {
            $lineas[] = $tandeo->fecha->format('d/m/Y') . ' | '
                . ($tandeo->circuito->zona->nombre ?? 'Colonia no disponible') . ' | '
                . ($tandeo->circuito->descripcion ?: $tandeo->circuito->nombre) . ' | '
                . $tandeo->hora_inicio_programada . ' - ' . $tandeo->hora_fin_programada;
        }

        if ($tandeos->isEmpty()) $lineas[] = 'No hay tandeos programados para este periodo.';
        return implode("\n", $lineas);
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
            if (!$principalYaExiste) $personas[] = $tandeo->tandeador;
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
