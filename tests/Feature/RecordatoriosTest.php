<?php

namespace Tests\Feature;

use App\Models\Cuota;
use App\Models\Licencia;
use App\Models\Recordatorio;
use App\Services\EnvioRecordatorios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecordatoriosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['cobros.email_automatico' => false, 'cobros.whatsapp_api.activo' => false]);
        $this->seed();
        $this->travelTo(now()->setDate(2027, 2, 23)->startOfDay());
    }

    private function cuota(int $dias = 5): Cuota
    {
        $licencia = Licencia::where('tipo', 'alquiler')->firstOrFail();
        $licencia->update(['estado' => 'activa']);
        $licencia->cliente->update(['telefono' => '3001234567', 'email' => 'cliente@example.com']);

        return $licencia->cuotas()->create([
            'fecha_ancla' => '2027-01-31', 'periodo' => 1,
            'vencimiento' => today()->addDays($dias), 'monto' => 50000,
        ]);
    }

    private function conectarWhatsApp(): void
    {
        config(['cobros.whatsapp_api' => [
            'activo' => true, 'token' => 'token-falso-solo-test', 'phone_id' => '123456',
            'version' => 'v99.0', 'plantilla' => 'recordatorio_mensualidad', 'idioma' => 'es_CO',
        ]]);
    }

    public function test_sin_conexion_no_finge_envios_y_no_duplica_avisos(): void
    {
        $this->cuota();
        Mail::shouldReceive('mailer')->never();
        $servicio = app(EnvioRecordatorios::class);
        $this->assertSame(0, $servicio->procesar(true));
        $servicio->procesar(true);
        $this->assertSame(2, Recordatorio::count());
        $this->assertSame(2, Recordatorio::where('estado', 'bloqueado')->count());
        $this->assertSame(0, Recordatorio::sum('intentos'));
        Http::assertNothingSent();
    }

    public function test_whatsapp_usa_plantilla_y_no_repite_aceptados(): void
    {
        $this->cuota();
        $this->conectarWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.prueba']]])]);
        $servicio = app(EnvioRecordatorios::class);
        $servicio->procesar(false);
        Http::assertNothingSent();
        $this->assertSame(1, $servicio->procesar(true));
        $this->assertSame(0, $servicio->procesar(true));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === '573001234567'
            && $request['type'] === 'template'
            && count($request['template']['components'][0]['parameters']) === 6
            && $request['template']['components'][0]['parameters'][4]['text'] === '3238080851');
        $this->assertDatabaseHas('recordatorios', ['canal' => 'whatsapp', 'estado' => 'aceptado', 'proveedor_id' => 'wamid.prueba']);
    }

    public function test_timeout_no_reintenta_a_ciegas_ni_guarda_secretos(): void
    {
        $this->cuota();
        $this->conectarWhatsApp();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('token-falso-solo-test'));
        $servicio = app(EnvioRecordatorios::class);
        $servicio->procesar(true);
        $registro = Recordatorio::where('canal', 'whatsapp')->firstOrFail();
        $this->assertSame('revisar', $registro->estado);
        $this->assertStringNotContainsString('token-falso', $registro->detalle);
        $this->assertSame(0, $servicio->procesar(true));
        $this->assertSame(1, $registro->fresh()->intentos);
    }

    public function test_cuotas_pagadas_futuras_y_suspendidas_no_se_envian(): void
    {
        $cuota = $this->cuota(6);
        $servicio = app(EnvioRecordatorios::class);
        $servicio->procesar(true);
        $this->assertSame(0, Recordatorio::count());
        $cuota->update(['vencimiento' => today()]);
        $cuota->licencia->update(['estado' => 'suspendida']);
        $servicio->procesar(true);
        $this->assertSame(0, Recordatorio::count());
        $cuota->licencia->update(['estado' => 'activa']);
        $servicio->procesar(false);
        $cuota->update(['pagada_at' => now()]);
        $servicio->procesar(true);
        $this->assertSame(2, Recordatorio::where('estado', 'cancelado')->count());
        Http::assertNothingSent();
    }

    public function test_correo_smtp_se_envia_una_sola_vez(): void
    {
        $this->cuota();
        config([
            'cobros.email_automatico' => true, 'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => 'negocio@example.com',
            'mail.mailers.smtp.password' => 'clave-falsa-test',
            'mail.from.address' => 'negocio@example.com',
        ]);
        $mensaje = \Mockery::mock(\Illuminate\Mail\SentMessage::class);
        $mensaje->shouldReceive('getMessageId')->once()->andReturn('correo-prueba');
        Mail::shouldReceive('mailer')->with('smtp')->once()->andReturnSelf();
        Mail::shouldReceive('raw')->once()->withArgs(function ($texto, $callback) {
            $this->assertStringContainsString('3238080851', $texto);
            $email = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email);
            $callback($email);
            $this->assertSame('cliente@example.com', $email->getSymfonyMessage()->getTo()[0]->getAddress());

            return true;
        })->andReturn($mensaje);
        $servicio = app(EnvioRecordatorios::class);
        $this->assertSame(1, $servicio->procesar(true));
        $this->assertSame(0, $servicio->procesar(true));
        $this->assertDatabaseHas('recordatorios', ['canal' => 'email', 'estado' => 'aceptado']);
    }

    public function test_rechazos_reintentan_con_espera_y_limite(): void
    {
        $this->cuota();
        $this->conectarWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response([], 429)]);
        $servicio = app(EnvioRecordatorios::class);
        $servicio->procesar(true);
        $servicio->procesar(true);
        Http::assertSentCount(1);
        $this->travel(61)->minutes();
        $servicio->procesar(true);
        $this->travel(61)->minutes();
        $servicio->procesar(true);
        $this->travel(61)->minutes();
        $servicio->procesar(true);
        Http::assertSentCount(3);
        $this->assertDatabaseHas('recordatorios', ['canal' => 'whatsapp', 'estado' => 'error', 'intentos' => 3]);
    }
}
