<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Recordatorio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnvioRecordatorios
{
    public function impedimento(string $canal): ?string
    {
        if ($canal === 'email') {
            if (! config('cobros.email_automatico')) {
                return 'Correo automático pendiente de activar.';
            }
            if (config('mail.default') !== 'smtp' || ! config('mail.mailers.smtp.host')
                || ! config('mail.mailers.smtp.username') || ! config('mail.mailers.smtp.password')
                || ! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
                return 'Falta conectar el correo del negocio mediante SMTP.';
            }
        } else {
            if (! config('cobros.whatsapp_api.activo')) {
                return 'WhatsApp automático pendiente de conectar.';
            }
            foreach (['token', 'phone_id', 'version', 'plantilla', 'idioma'] as $campo) {
                if (! config('cobros.whatsapp_api.'.$campo)) {
                    return 'Falta completar la conexión de WhatsApp Business Platform.';
                }
            }
        }

        return null;
    }

    public function procesar(bool $enviar): int
    {
        $lock = Cache::lock('cobros:procesar', 600);
        if (! $lock->get()) {
            return 0;
        }

        try {
            // A process interrupted during a remote send has an uncertain outcome.
            Recordatorio::where('estado', 'enviando')->where('updated_at', '<', now()->subMinutes(15))
                ->update(['estado' => 'revisar', 'detalle' => 'Envío interrumpido. Verifica con el proveedor antes de reintentar.']);

            $total = 0;
            $limiteTiempo = microtime(true) + 120;
            Cuota::porRecordar()->with('licencia.cliente', 'licencia.producto')->chunkById(100, function ($cuotas) use ($enviar, &$total, $limiteTiempo) {
                foreach ($cuotas as $cuota) {
                    foreach (['whatsapp', 'email'] as $canal) {
                        if (microtime(true) >= $limiteTiempo) {
                            return false;
                        }
                        $registro = Recordatorio::firstOrCreate(['cuota_id' => $cuota->id, 'canal' => $canal]);
                        if (in_array($registro->estado, ['aceptado', 'enviando', 'revisar', 'cancelado'])
                            || $registro->intentos >= 3 || $registro->reintentar_at?->isFuture()) {
                            continue;
                        }
                        $problema = $this->impedimento($canal);
                        if ($problema) {
                            $registro->update(['estado' => 'bloqueado', 'detalle' => $problema]);

                            continue;
                        }
                        if (! $enviar) {
                            continue;
                        }
                        if ($this->enviar($registro, $cuota)) {
                            $total++;
                        }
                    }
                }
            });
            // A confirmed payment cancels unsent work. Suspended licenses are skipped above.
            Recordatorio::whereIn('estado', ['pendiente', 'bloqueado', 'error'])
                ->whereHas('cuota', fn ($q) => $q->whereNotNull('pagada_at'))
                ->update(['estado' => 'cancelado', 'detalle' => 'La cuota ya no requiere recordatorio.']);
            Cache::forever('cobros:ultima_revision', now()->toIso8601String());

            return $total;
        } finally {
            $lock->release();
        }
    }

    private function enviar(Recordatorio $registro, Cuota $cuota): bool
    {
        // Reload immediately before sending so a confirmed payment is not reminded.
        if (! Cuota::porRecordar()->whereKey($cuota->id)->exists()) {
            $registro->update(['estado' => 'cancelado']);

            return false;
        }
        $cliente = $cuota->licencia->cliente;
        $destino = $registro->canal === 'email' ? $cliente->email : $cuota->telefono_whatsapp;
        if (! $destino || ($registro->canal === 'email' && ! filter_var($destino, FILTER_VALIDATE_EMAIL))) {
            $registro->update(['estado' => 'bloqueado', 'detalle' => 'Corrige el dato de contacto del cliente.']);

            return false;
        }
        $registro->update(['estado' => 'enviando', 'intentos' => $registro->intentos + 1, 'detalle' => null]);

        try {
            $proveedorId = null;
            if ($registro->canal === 'email') {
                $enviado = Mail::mailer('smtp')->raw($cuota->mensaje, function ($message) use ($destino) {
                    $message->to($destino)->subject('Recordatorio de mensualidad - Alfa Digital Solutions');
                });
                if (! $enviado) {
                    throw new \RuntimeException('El transporte no confirmó la aceptación.');
                }
                $proveedorId = $enviado->getMessageId();
            } else {
                $config = config('cobros.whatsapp_api');
                $respuesta = Http::withToken($config['token'])->acceptJson()->connectTimeout(10)->timeout(30)
                    ->post('https://graph.facebook.com/'.$config['version'].'/'.$config['phone_id'].'/messages', [
                        'messaging_product' => 'whatsapp',
                        'to' => $destino,
                        'type' => 'template',
                        'template' => [
                            'name' => $config['plantilla'],
                            'language' => ['code' => $config['idioma']],
                            'components' => [[
                                'type' => 'body',
                                'parameters' => array_map(fn ($valor) => ['type' => 'text', 'text' => (string) $valor], [
                                    $cliente->nombre,
                                    $cuota->licencia->producto->nombre,
                                    '$'.number_format($cuota->monto, 0, ',', '.'),
                                    $cuota->vencimiento->format('d/m/Y'),
                                    config('cobros.nequi'),
                                    '+'.config('cobros.whatsapp'),
                                ]),
                            ]],
                        ],
                    ]);
                if ($respuesta->status() === 429 || ($respuesta->clientError() && $respuesta->status() !== 408)) {
                    // Explicit rejection: retry later after correcting configuration or rate limit.
                    $registro->update([
                        'estado' => 'error',
                        'detalle' => 'WhatsApp rechazó el envío (HTTP '.$respuesta->status().'). Revisa la conexión, la plantilla o el destinatario.',
                        'reintentar_at' => now()->addHour(),
                    ]);

                    return false;
                }
                $proveedorId = $respuesta->json('messages.0.id');
                if (! $respuesta->successful() || ! $proveedorId) {
                    throw new \RuntimeException('Respuesta sin confirmación de aceptación.');
                }
            }
            $registro->update([
                'estado' => 'aceptado', 'proveedor_id' => $proveedorId,
                'aceptado_at' => now(), 'reintentar_at' => null,
                'detalle' => 'Aceptado por el proveedor. No confirma lectura ni entrega al cliente.',
            ]);

            return true;
        } catch (Throwable $exception) {
            // Never persist provider exception text: it can contain tokens or SMTP credentials.
            $registro->update([
                'estado' => 'revisar', 'reintentar_at' => null,
                'detalle' => 'No se pudo confirmar el envío. Revisa el proveedor antes de reintentar para evitar duplicados.',
            ]);

            return false;
        }
    }
}
