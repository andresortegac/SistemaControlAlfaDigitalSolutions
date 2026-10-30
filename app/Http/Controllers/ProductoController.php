<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Producto::query();

        if ($request->filled('busqueda')) {
            $busqueda = $request->input('busqueda');
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%")
                  ->orWhere('version', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('categoria') && $request->input('categoria') !== 'todas') {
            $query->where('categoria', $request->input('categoria'));
        }

        $productos = $query->withCount('licencias')->orderBy('nombre')->paginate(12)->withQueryString();
        $conteos = Producto::query()->selectRaw('categoria, COUNT(*) as total')->groupBy('categoria')->pluck('total', 'categoria');
        $categorias = Producto::getCategorias();
        $iconos = Producto::getIconos();

        return view('productos.index', compact('productos', 'categorias', 'iconos', 'conteos'));
    }

    public function create(): View
    {
        $categorias = Producto::getCategorias();
        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'categoria' => 'required|in:ofimatica,diseno,desarrollo,seguridad,contabilidad,otro',
            'version' => 'required|string|max:50',
            'precio_compra' => 'required|numeric|min:0',
            'precio_alquiler_mensual' => 'required|numeric|min:0',
            'precio_alquiler_anual' => 'required|numeric|min:0',
            'requisitos_sistema' => 'nullable|string|max:500',
            'fecha_lanzamiento' => 'required|date'
        ]);

        $validated['activo'] = true;

        Producto::create($validated);

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    public function show(Producto $producto): View
    {
        $producto->load('licencias.cliente');
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto): View
    {
        $categorias = Producto::getCategorias();
        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'categoria' => 'required|in:ofimatica,diseno,desarrollo,seguridad,contabilidad,otro',
            'version' => 'required|string|max:50',
            'precio_compra' => 'required|numeric|min:0',
            'precio_alquiler_mensual' => 'required|numeric|min:0',
            'precio_alquiler_anual' => 'required|numeric|min:0',
            'requisitos_sistema' => 'nullable|string|max:500',
            'fecha_lanzamiento' => 'required|date',
            'activo' => 'boolean'
        ]);

        $producto->update($validated);

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()->route('productos.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    public function toggleActivo(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => !$producto->activo]);

        return redirect()->route('productos.index')
            ->with('success', 'Estado del producto actualizado.');
    }
}
