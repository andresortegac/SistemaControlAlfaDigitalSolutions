<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Licencia;
use App\Models\Producto;
use App\Models\Transaccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenciaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Licencia::with(['cliente', 'producto']);

        if ($request->filled('busqueda')) {
            $busqueda = $request->input('busqueda');
            $query->where(function ($q) use ($busqueda) {
                $q->where('clave_licencia', 'like', "%{$busqueda}%")
                    ->orWhereHas('cliente', function ($qc) use ($busqueda) {
                        $qc->where('nombre', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('producto', function ($qp) use ($busqueda) {
                        $qp->where('nombre', 'like', "%{$busqueda}%");
                    });
            });
        }

        if ($request->filled('tipo') && $request->input('tipo') !== 'todos') {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('estado') && $request->input('estado') !== 'todos') {
            $query->where('estado', $request->input('estado'));
        }

        $licencias = $query->orderBy('fecha_creacion', 'desc')->paginate(12);
        $tipos = Licencia::getTipos();
        $estados = Licencia::getEstados();

        $stats = [
            'activas' => Licencia::activas()->count(),
            'compras' => Licencia::porTipo('compra')->count(),
            'alquileres' => Licencia::porTipo('alquiler')->count(),
            'por_vencer' => Licencia::porVencer()->count(),
        ];

        return view('licencias.index', compact('licencias', 'tipos', 'estados', 'stats'));
    }

    public function create(): View
    {
        $clientes = Cliente::activos()->orderBy('nombre')->get();
        $productos = Producto::activos()->orderBy('nombre')->get();
        $tipos = Licencia::getTipos();

        return view('licencias.create', compact('clientes', 'productos', 'tipos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'producto_id' => 'required|exists:productos,id',
            'tipo' => 'required|in:compra,alquiler',
            'cantidad_usuarios' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'duracion' => 'required_if:tipo,alquiler|nullable|in:1,3,6,12,anual',
            'renovacion_automatica' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validated) {
            $mensual = $validated['tipo'] === 'alquiler' && $request->input('duracion') === '1';
            $producto = Producto::find($validated['producto_id']);

            // Calcular precio total
            if ($validated['tipo'] === 'compra') {
                $precioTotal = $producto->precio_compra * $validated['cantidad_usuarios'];
                $fechaVencimiento = null;
            } else {
                $duracion = $request->input('duracion');
                if ($duracion === 'anual') {
                    $precioTotal = $producto->precio_alquiler_anual * $validated['cantidad_usuarios'];
                    $fechaVencimiento = now()->parse($validated['fecha_inicio'])->addYearNoOverflow();
                } else {
                    $precioTotal = $producto->precio_alquiler_mensual * $validated['cantidad_usuarios'] * (int) $duracion;
                    $fechaVencimiento = now()->parse($validated['fecha_inicio'])->addMonthsNoOverflow((int) $duracion);
                }
            }

            $licencia = Licencia::create([
                'cliente_id' => $validated['cliente_id'],
                'producto_id' => $validated['producto_id'],
                'tipo' => $validated['tipo'],
                'estado' => $mensual ? 'pendiente' : 'activa',
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_vencimiento' => $fechaVencimiento,
                'cantidad_usuarios' => $validated['cantidad_usuarios'],
                'clave_licencia' => Licencia::generarClave(),
                'precio_total' => $precioTotal,
                'renovacion_automatica' => $request->boolean('renovacion_automatica', false),
                'notas' => $validated['notas'] ?? null,
                'fecha_creacion' => now(),
            ]);

            // Crear transacción
            if ($mensual) {
                $licencia->cuotas()->create([
                    'fecha_ancla' => $validated['fecha_inicio'],
                    'periodo' => 0,
                    'vencimiento' => $validated['fecha_inicio'],
                    'monto' => $precioTotal,
                ]);
            } else {
                Transaccion::create([
                    'cliente_id' => $validated['cliente_id'],
                    'licencia_id' => $licencia->id,
                    'tipo' => $validated['tipo'] === 'compra' ? 'venta' : 'alquiler',
                    'monto' => $precioTotal,
                    'fecha' => now(),
                    'metodo_pago' => 'transferencia',
                    'descripcion' => ($validated['tipo'] === 'compra' ? 'Compra' : 'Alquiler').' de '.$producto->nombre.' - '.$validated['cantidad_usuarios'].' usuarios',
                ]);

            }

            return redirect()->route($mensual ? 'cobros.index' : 'licencias.index')
                ->with('success', 'Licencia creada exitosamente.');
        });
    }

    public function show(Licencia $licencia): View
    {
        $licencia->load(['cliente', 'producto', 'registrosUso']);

        return view('licencias.show', compact('licencia'));
    }

    public function edit(Licencia $licencia): View
    {
        $clientes = Cliente::activos()->orderBy('nombre')->get();
        $productos = Producto::activos()->orderBy('nombre')->get();
        $tipos = Licencia::getTipos();
        $estados = Licencia::getEstados();

        return view('licencias.edit', compact('licencia', 'clientes', 'productos', 'tipos', 'estados'));
    }

    public function update(Request $request, Licencia $licencia): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'producto_id' => 'required|exists:productos,id',
            'tipo' => 'required|in:compra,alquiler',
            'estado' => 'required|in:activa,vencida,suspendida,pendiente',
            'cantidad_usuarios' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'renovacion_automatica' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);

        $licencia->update($validated);

        return redirect()->route('licencias.index')
            ->with('success', 'Licencia actualizada exitosamente.');
    }

    public function destroy(Licencia $licencia): RedirectResponse
    {
        $licencia->delete();

        return redirect()->route('licencias.index')
            ->with('success', 'Licencia eliminada exitosamente.');
    }

    public function toggleEstado(Licencia $licencia): RedirectResponse
    {
        $nuevoEstado = $licencia->estado === 'activa' ? 'suspendida' : 'activa';
        $licencia->update(['estado' => $nuevoEstado]);

        return redirect()->route('licencias.index')
            ->with('success', 'Estado de la licencia actualizado.');
    }
}
