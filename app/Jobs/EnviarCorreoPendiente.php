<?php

namespace App\Jobs;

use App\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarCorreoPendiente implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 300;

    public function __construct(public int $notificacionId) {}

    public function handle(): void
    {
        $notificacion = Notificacion::find($this->notificacionId);
        if (! $notificacion || $notificacion->estado === 'enviada') {
            return;
        }

        if (config('mail.default') !== 'smtp') {
            throw new \RuntimeException('Configura el servidor SMTP para enviar correos.');
        }

        $notificacion->increment('intentos');

        try {
            if (stripos($notificacion->mensaje, '<html') !== false) {
                Mail::html($notificacion->mensaje, function ($correo) use ($notificacion) {
                    $correo->to($notificacion->destinatario_email)
                        ->subject($notificacion->asunto);
                });
            } else {
                // Mantiene legibles los avisos antiguos que siguen pendientes.
                Mail::raw($notificacion->mensaje, function ($correo) use ($notificacion) {
                    $correo->to($notificacion->destinatario_email)
                        ->subject($notificacion->asunto);
                });
            }

            $notificacion->update([
                'estado' => 'enviada',
                'ultimo_error' => null,
                'reintentar_en' => null,
                'enviado_at' => now(),
            ]);
        } catch (Throwable $error) {
            $notificacion->update(['ultimo_error' => $error->getMessage()]);
            throw $error;
        }
    }

    public function failed(?Throwable $error): void
    {
        $notificacion = Notificacion::find($this->notificacionId);
        if (! $notificacion) {
            return;
        }

        $notificacion->update([
            'estado' => 'pendiente',
            'ultimo_error' => $error?->getMessage() ?: 'No se pudo enviar el correo.',
            'reintentar_en' => now()->addMinutes(5),
        ]);
    }
}
