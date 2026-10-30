<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Licencia;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\Response;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        $anioSeleccionado = $request->input('anio', now()->year);
        
        $anioSql = (new Transaccion)->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', fecha) AS INTEGER)"
            : 'YEAR(fecha)';

        $aniosDisponibles = Transaccion::selectRaw("{$anioSql} as anio")
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();

        if (empty($aniosDisponibles)) {
            $aniosDisponibles = [now()->year];
        }

        $transacciones = Transaccion::whereYear('fecha', $anioSeleccionado);

        $stats = [
            'total_ingresos' => $transacciones->sum('monto'),
            'total_ventas' => (clone $transacciones)->porTipo('venta')->sum('monto'),
            'total_alquileres' => (clone $transacciones)->porTipo('alquiler')->sum('monto'),
            'total_renovaciones' => (clone $transacciones)->porTipo('renovacion')->sum('monto'),
            'promedio_transaccion' => $transacciones->avg('monto') ?? 0,
            'cantidad_transacciones' => $transacciones->count()
        ];

        // Ingresos por mes
        $ingresosPorMes = [];
        for ($i = 1; $i <= 12; $i++) {
            $ingresosPorMes[] = [
                'mes' => $this->getNombreMes($i),
                'monto' => (clone $transacciones)->whereMonth('fecha', $i)->sum('monto') / 1000000
            ];
        }

        // Ingresos por tipo
        $ingresosPorTipo = [
            ['name' => 'Ventas', 'value' => $stats['total_ventas'], 'color' => '#3b82f6'],
            ['name' => 'Alquileres', 'value' => $stats['total_alquileres'], 'color' => '#10b981'],
            ['name' => 'Renovaciones', 'value' => $stats['total_renovaciones'], 'color' => '#f59e0b']
        ];

        // Top clientes
        $topClientes = Cliente::withSum(['transacciones' => fn ($query) => $query->whereYear('fecha', $anioSeleccionado)], 'monto')
            ->whereHas('transacciones', function($q) use ($anioSeleccionado) {
                $q->whereYear('fecha', $anioSeleccionado);
            })
            ->orderBy('transacciones_sum_monto', 'desc')
            ->take(5)
            ->get();

        // Top productos
        $topProductos = Producto::withSum(['licencias' => fn ($query) => $query->whereYear('fecha_creacion', $anioSeleccionado)], 'precio_total')
            ->orderBy('licencias_sum_precio_total', 'desc')
            ->take(5)
            ->get();

        // Transacciones recientes
        $transaccionesRecientes = Transaccion::with('cliente')
            ->whereYear('fecha', $anioSeleccionado)
            ->orderBy('fecha', 'desc')
            ->take(10)
            ->get();

        return view('reportes.index', compact(
            'stats',
            'ingresosPorMes',
            'ingresosPorTipo',
            'topClientes',
            'topProductos',
            'transaccionesRecientes',
            'anioSeleccionado',
            'aniosDisponibles'
        ));
    }

    public function exportarCsv(Request $request): Response
    {
        $anioSeleccionado = $request->input('anio', now()->year);
        
        $transacciones = Transaccion::with('cliente')
            ->whereYear('fecha', $anioSeleccionado)
            ->orderBy('fecha', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_financiero_' . $anioSeleccionado . '.csv"',
        ];

        $callback = function() use ($transacciones) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Fecha', 'Cliente', 'Tipo', 'Monto', 'Descripción', 'Método de Pago']);

            foreach ($transacciones as $transaccion) {
                fputcsv($file, [
                    $transaccion->fecha->format('Y-m-d'),
                    $transaccion->cliente->nombre,
                    $transaccion->tipo_label,
                    $transaccion->monto,
                    $transaccion->descripcion,
                    $transaccion->metodo_pago_label
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getNombreMes($mes): string
    {
        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];
        return $meses[$mes] ?? '';
    }
}
