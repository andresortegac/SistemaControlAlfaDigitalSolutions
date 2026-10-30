<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Licencia;
use App\Models\Transaccion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_clientes' => Cliente::activos()->count(),
            'total_productos' => Producto::activos()->count(),
            'licencias_activas' => Licencia::activas()->count(),
            'licencias_por_vencer' => Licencia::porVencer()->count(),
            'ingresos_mes' => Transaccion::esteMes()->sum('monto'),
            'ingresos_anio' => Transaccion::esteAnio()->sum('monto'),
            'alquileres_activos' => Licencia::activas()->porTipo('alquiler')->count(),
            'compras_totales' => Licencia::porTipo('compra')->count()
        ];

        $stats['cuotas_vencidas'] = \App\Models\Cuota::whereNull('pagada_at')->whereDate('vencimiento', '<', today())->count();
        $stats['cartera_vencida'] = \App\Models\Cuota::whereNull('pagada_at')->whereDate('vencimiento', '<', today())->sum('monto');
        $stats['cartera_pendiente'] = \App\Models\Cuota::whereNull('pagada_at')->sum('monto');
        $stats['avisos'] = \App\Models\Cuota::porRecordar()->count();

        $mesSql = (new Transaccion)->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', fecha) AS INTEGER)"
            : 'MONTH(fecha)';

        $ingresosPorMes = Transaccion::selectRaw("{$mesSql} as mes, SUM(monto) as total")
            ->whereYear('fecha', now()->year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();

        $licenciasPorTipo = [
            ['name' => 'Compras', 'value' => Licencia::porTipo('compra')->count(), 'color' => '#3b82f6'],
            ['name' => 'Alquileres', 'value' => Licencia::porTipo('alquiler')->count(), 'color' => '#10b981']
        ];

        $productosMasVendidos = Producto::withCount('licencias')
            ->orderBy('licencias_count', 'desc')
            ->take(5)
            ->get();

        $clientesTop = Cliente::withSum('transacciones', 'monto')
            ->orderBy('transacciones_sum_monto', 'desc')
            ->take(5)
            ->get();

        $licenciasRecientes = Licencia::with(['cliente', 'producto'])
            ->orderBy('fecha_creacion', 'desc')
            ->take(5)
            ->get();

        $transaccionesRecientes = Transaccion::with('cliente')
            ->orderBy('fecha', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'ingresosPorMes',
            'licenciasPorTipo',
            'productosMasVendidos',
            'clientesTop',
            'licenciasRecientes',
            'transaccionesRecientes'
        ));
    }
}
