@extends('layouts.app')

@section('title', 'Nueva Licencia')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Crear Nueva Licencia</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('licencias.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cliente_id" class="form-label">Cliente *</label>
                            <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
                                <option value="">Selecciona el cliente</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }} - {{ $cliente->empresa }}</option>
                                @endforeach
                            </select>
                            @error('cliente_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="producto_id" class="form-label">Producto *</label>
                            <select class="form-select @error('producto_id') is-invalid @enderror" id="producto_id" name="producto_id" required>
                                <option value="">Selecciona el producto</option>
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id }}" {{ old('producto_id') == $producto->id ? 'selected' : '' }}>{{ $producto->nombre }}</option>
                                @endforeach
                            </select>
                            @error('producto_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de Licencia *</label>
                            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required data-license-type-toggle>
                                @foreach($tipos as $key => $label)
                                    <option value="{{ $key }}" {{ old('tipo') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="cantidad_usuarios" class="form-label">Cantidad de Usuarios *</label>
                            <input type="number" class="form-control @error('cantidad_usuarios') is-invalid @enderror" id="cantidad_usuarios" name="cantidad_usuarios" value="{{ old('cantidad_usuarios', 1) }}" min="1" required>
                            @error('cantidad_usuarios')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                            <input type="date" class="form-control @error('fecha_inicio') is-invalid @enderror" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio', date('Y-m-d')) }}" required>
                            @error('fecha_inicio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6" id="duracion_container" data-duration-container>
                            <label for="duracion" class="form-label">Duracion (Alquiler)</label>
                            <select class="form-select @error('duracion') is-invalid @enderror" id="duracion" name="duracion">
                                <option value="1">Mensual (cuotas por calendario; pago inicial pendiente de confirmar)</option>
                                <option value="3">3 meses</option>
                                <option value="6">6 meses</option>
                                <option value="12">1 ano</option>
                                <option value="anual">Anual (precio especial)</option>
                            </select>
                            @error('duracion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="renovacion_automatica" name="renovacion_automatica" value="1" {{ old('renovacion_automatica') ? 'checked' : '' }}>
                                <label class="form-check-label" for="renovacion_automatica">Renovacion automatica</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="2">{{ old('notas') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('licencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Crear Licencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
