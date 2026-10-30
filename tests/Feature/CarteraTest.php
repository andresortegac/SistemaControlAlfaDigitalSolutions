<?php

namespace Tests\Feature;

use App\Models\Cuota;
use App\Models\Licencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteraTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtros_separan_cuotas_pagadas_y_vencidas_y_resumen_no_cambia(): void
    {
        $this->seed();
        $licencia = Licencia::where('tipo', 'alquiler')->firstOrFail();
        $base = ['licencia_id' => $licencia->id, 'fecha_ancla' => today()->subMonths(2), 'monto' => 50000];
        $vencida = Cuota::create($base + ['periodo' => 0, 'vencimiento' => today()->subDays(3)]);
        $pagada = Cuota::create($base + ['periodo' => 1, 'vencimiento' => today(), 'pagada_at' => now()]);
        Cuota::create($base + ['periodo' => 2, 'vencimiento' => today()->addMonth()]);

        $this->get('/cobros?estado=vencidas')->assertOk()
            ->assertViewHas('cuotas', fn ($cuotas) => $cuotas->total() === 1 && $cuotas->first()->id === $vencida->id)
            ->assertViewHas('resumen', fn ($resumen) => (float) $resumen['pendiente'] === 100000.0 && (float) $resumen['vencido'] === 50000.0);
        $this->get('/cobros?estado=pagadas')->assertOk()
            ->assertViewHas('cuotas', fn ($cuotas) => $cuotas->total() === 1 && $cuotas->first()->id === $pagada->id);
        $this->get('/cobros?estado=todas&busqueda=sin-coincidencia-123')->assertOk()
            ->assertViewHas('cuotas', fn ($cuotas) => $cuotas->total() === 0);
    }
}
