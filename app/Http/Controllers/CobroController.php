<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Licencia;
use App\Models\Transaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CobroController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'busqueda' => 'nullable|string|max:150',
            'estado' => 'nullable|in:pendientes,vencidas,pagadas,todas',
        ]);
        $estado = $filtros['estado'] ?? 'pendientes';
        $consulta = Cuota::with('licencia.cliente', 'licencia.producto');
        if ($busqueda = trim($filtros['busqueda'] ?? '')) {
            $consulta->where(function ($query) use ($busqueda) {
                $query->whereHas('licencia.cliente', fn ($cliente) => $cliente->where('nombre', 'like', "%{$busqueda}%"))
                    ->orWhereHas('licencia.producto', fn ($producto) => $producto->where('nombre', 'like', "%{$busqueda}%"));
            });
        }
        if ($estado === 'pagadas') {
            $consulta->whereNotNull('pagada_at');
        } elseif ($estado !== 'todas') {
            $consulta->whereNull('pagada_at');
            if ($estado === 'vencidas') {
                $consulta->whereDate('vencimiento', '<', today());
            }
        }
        $resumen = [
            'pendiente' => Cuota::whereNull('pagada_at')->sum('monto'),
            'vencido' => Cuota::whereNull('pagada_at')->whereDate('vencimiento', '<', today())->sum('monto'),
            'pagado_mes' => Cuota::whereYear('pagada_at', today()->year)->whereMonth('pagada_at', today()->month)->sum('monto'),
        ];
        $cuotas = $consulta->orderByRaw('pagada_at IS NOT NULL')->orderBy('vencimiento')->paginate(20)->withQueryString();
        $licencias = Licencia::with('cliente', 'producto')->where('tipo', 'alquiler')
            ->whereDoesntHave('cuotas')->get();
        $avisos = Cuota::porRecordar()->count();
        $servicio = app(\App\Services\EnvioRecordatorios::class);
        $canales = collect(['whatsapp', 'email'])->mapWithKeys(fn ($canal) => [
            $canal => $servicio->impedimento($canal) ?? 'Configurado para envío automático; revisa el historial para comprobar los resultados.',
        ]);
        $ultimaRevision = \Illuminate\Support\Facades\Cache::get('cobros:ultima_revision');
        $recordatorios = \App\Models\Recordatorio::with('cuota.licencia.cliente')->latest('updated_at')->limit(20)->get();

        return view('cobros.index', compact('cuotas', 'licencias', 'avisos', 'canales', 'ultimaRevision', 'recordatorios', 'resumen', 'estado'));
    }

    public function activar(Request $request, Licencia $licencia)
    {
        abort_unless($licencia->tipo === 'alquiler', 422);
        $datos = $request->validate([
            'fecha_ancla' => 'required|date_format:Y-m-d',
            'periodo' => 'required|integer|min:0|max:1200',
            'monto' => 'required|numeric|min:0.01|max:9999999999999',
        ]);
        DB::transaction(function () use ($licencia, $datos) {
            Licencia::whereKey($licencia->id)->lockForUpdate()->firstOrFail();
            if ($licencia->cuotas()->exists()) {
                return;
            }
            $licencia->cuotas()->create($datos + [
                'vencimiento' => Cuota::fechaPeriodo($datos['fecha_ancla'], (int) $datos['periodo']),
            ]);
        });

        return back()->with('success', 'Control mensual activado. Las transacciones anteriores se conservaron.');
    }

    public function confirmar(Request $request, Cuota $cuota)
    {
        $datos = $request->validate(['referencia_pago' => 'required|string|max:200']);
        DB::transaction(function () use ($cuota, $datos) {
            $cuota = Cuota::whereKey($cuota->id)->lockForUpdate()->firstOrFail();
            if ($cuota->pagada_at) {
                return;
            }
            $licencia = Licencia::whereKey($cuota->licencia_id)->lockForUpdate()->firstOrFail();
            $transaccion = Transaccion::create([
                'cliente_id' => $licencia->cliente_id,
                'licencia_id' => $licencia->id,
                'tipo' => 'alquiler',
                'monto' => $cuota->monto,
                'fecha' => today(),
                'metodo_pago' => 'transferencia',
                'descripcion' => 'Mensualidad Nequi '.$cuota->vencimiento->format('d/m/Y').' - '.$datos['referencia_pago'],
            ]);
            $cuota->update($datos + ['pagada_at' => now(), 'transaccion_id' => $transaccion->id]);
            $siguiente = Cuota::fechaPeriodo($cuota->fecha_ancla->format('Y-m-d'), $cuota->periodo + 1);
            $licencia->cuotas()->firstOrCreate(['periodo' => $cuota->periodo + 1], [
                'fecha_ancla' => $cuota->fecha_ancla,
                'vencimiento' => $siguiente,
                'monto' => $cuota->monto,
            ]);
            $licencia->update([
                'fecha_vencimiento' => $siguiente,
                'estado' => $licencia->estado === 'suspendida' ? 'suspendida' : ($siguiente->lt(today()) ? 'vencida' : 'activa'),
            ]);
        });

        return back()->with('success', 'Pago confirmado. Se generó la siguiente cuota conservando el día original.');
    }
}
