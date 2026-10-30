<?php

namespace Tests\Feature;

use App\Models\Cuota;
use App\Models\Licencia;
use App\Models\Transaccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobrosTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendario_conserva_el_dia_original(): void
    {
        $this->assertSame('2027-02-28', Cuota::fechaPeriodo('2027-01-31', 1)->toDateString());
        $this->assertSame('2027-03-31', Cuota::fechaPeriodo('2027-01-31', 2)->toDateString());
        $this->assertSame('2027-04-30', Cuota::fechaPeriodo('2027-01-31', 3)->toDateString());
        $this->assertSame('2028-02-29', Cuota::fechaPeriodo('2028-01-31', 1)->toDateString());
        $this->assertSame('2027-02-10', Cuota::fechaPeriodo('2027-01-10', 1)->toDateString());
    }

    public function test_avisa_cinco_dias_antes_y_confirmar_dos_veces_no_duplica_ingresos(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2027, 2, 23)->startOfDay());
        $licencia = Licencia::where('tipo', 'alquiler')->firstOrFail();
        $this->post(route('cobros.activar', $licencia), [
            'fecha_ancla' => '2027-01-31', 'periodo' => 1, 'monto' => 50000,
        ])->assertRedirect();
        $this->assertSame(1, Cuota::porRecordar()->count());
        $this->travelBack();
        $this->travelTo(now()->setDate(2027, 2, 22)->startOfDay());
        $this->assertSame(0, Cuota::porRecordar()->count());
        $this->travelTo(now()->setDate(2027, 3, 2)->startOfDay());
        $this->assertSame(1, Cuota::porRecordar()->count());
        $cuota = Cuota::firstOrFail();
        $cantidad = Transaccion::count();
        $this->post(route('cobros.confirmar', $cuota), ['referencia_pago' => 'NEQUI-123'])->assertRedirect();
        $this->post(route('cobros.confirmar', $cuota), ['referencia_pago' => 'NEQUI-123'])->assertRedirect();
        $this->assertSame($cantidad + 1, Transaccion::count());
        $this->assertSame(2, Cuota::count());
        $this->assertDatabaseHas('cuotas', ['periodo' => 2, 'vencimiento' => '2027-03-31 00:00:00']);
        $this->get(route('cobros.index'))->assertOk()->assertSee('3238080851');
        $this->travelBack();
    }

    public function test_nueva_licencia_mensual_no_registra_dinero_sin_confirmacion(): void
    {
        $this->seed();
        $ejemplo = Licencia::where('tipo', 'alquiler')->firstOrFail();
        $cantidad = Transaccion::count();
        $this->post(route('licencias.store'), [
            'cliente_id' => $ejemplo->cliente_id,
            'producto_id' => $ejemplo->producto_id,
            'tipo' => 'alquiler', 'cantidad_usuarios' => 1,
            'fecha_inicio' => '2027-01-31', 'duracion' => '1',
        ])->assertRedirect(route('cobros.index'));
        $this->assertSame($cantidad, Transaccion::count());
        $this->assertSame(1, Cuota::count());
        $this->assertNull(Cuota::firstOrFail()->pagada_at);
    }
}
