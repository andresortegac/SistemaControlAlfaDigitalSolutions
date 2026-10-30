@extends('layouts.app')
@section('title', 'Mensualidades y avisos')
@section('content')
<section class="workspace-intro"><div><span class="section-eyebrow">CONTROL DE CARTERA</span><h3>Una cartera organizada. Un negocio al día.</h3><p>Consulta lo pendiente y confirma los pagos después de verificar el dinero recibido.</p></div></section>
<div class="row g-3 mb-4">
    @foreach(['pendiente' => ['Por cobrar', 'primary'], 'vencido' => ['Cartera vencida', 'danger'], 'pagado_mes' => ['Cobrado este mes', 'success']] as $clave => [$titulo, $color])
        <div class="col-md-4"><div class="stat-card {{ $color }}"><div class="stat-title">{{ $titulo }}</div><div class="stat-value">${{ number_format($resumen[$clave], 0, ',', '.') }}</div><div class="stat-label">{{ $clave === 'pagado_mes' ? 'Cuotas confirmadas en el mes actual' : 'Cuotas sin pago confirmado' }}</div></div></div>
    @endforeach
</div>
<form action="{{ route('cobros.index') }}" method="GET" class="card filter-panel mb-4" role="search" aria-label="Filtrar mensualidades">
    <div class="row g-3 align-items-end">
        <div class="col-md-6"><label class="form-label" for="busqueda">Cliente o producto</label><input class="form-control" id="busqueda" name="busqueda" maxlength="150" value="{{ request('busqueda') }}" placeholder="Buscar por nombre…"></div>
        <div class="col-md-3"><label class="form-label" for="estado">Estado de la cuota</label><select class="form-select" id="estado" name="estado">@foreach(['pendientes' => 'Pendientes de pago', 'vencidas' => 'Vencidas', 'pagadas' => 'Pagadas', 'todas' => 'Todas las cuotas'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('cobros.index') }}">Limpiar</a></div>
    </div>
    <p class="text-muted small mt-3 mb-0">{{ $cuotas->total() }} resultados · Los indicadores resumen toda la cartera.</p>
</form>
@if($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="card mb-4"><div class="card-body">
    <div class="panel-heading mb-3"><div><h4>Control de mensualidades</h4><p>Gestiona los avisos y registra únicamente pagos verificados.</p></div><span class="badge bg-light text-dark">{{ $cuotas->total() }} resultados</span></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th scope="col">Cliente / producto</th><th scope="col">Vencimiento</th><th scope="col">Valor</th><th scope="col">Estado</th><th scope="col">Recordatorio y pago</th></tr></thead>
        <tbody>
        @forelse($cuotas as $cuota)
            <tr>
                <td><div class="collection-client"><span class="collection-avatar" aria-hidden="true">{{ mb_substr($cuota->licencia->cliente->nombre, 0, 1) }}</span><div><strong>{{ $cuota->licencia->cliente->nombre }}</strong><small>{{ $cuota->licencia->producto->nombre }}</small><a class="small" href="{{ route('licencias.show', $cuota->licencia) }}">Ver licencia &#8599;</a></div></div></td>
                <td>{{ $cuota->vencimiento->format('d/m/Y') }}</td>
                <td class="money-value">${{ number_format($cuota->monto, 0, ',', '.') }}</td>
                <td><span class="badge badge-{{ $cuota->pagada_at ? 'activa' : ($cuota->vencimiento->lt(today()) ? 'vencida' : 'pendiente') }}">{{ $cuota->pagada_at ? 'Pagada' : ($cuota->vencimiento->lt(today()) ? 'Vencida' : ($cuota->vencimiento->lte(today()->addDays(5)) ? 'Por vencer / vence hoy' : 'Pendiente')) }}</span></td>
                <td>
                    @if(!$cuota->pagada_at)
                        <details class="payment-details"><summary>Gestionar cuota</summary>
                        @if($cuota->whatsapp_url)
                            <a class="btn btn-success btn-sm" href="{{ $cuota->whatsapp_url }}" target="_blank" rel="noopener">Preparar WhatsApp</a>
                        @else
                            <span class="text-muted">Agrega un teléfono válido al cliente.</span>
                        @endif
                        @if(filter_var($cuota->licencia->cliente->email, FILTER_VALIDATE_EMAIL))
                            <a class="btn btn-outline-primary btn-sm" href="mailto:{{ $cuota->licencia->cliente->email }}?subject={{ rawurlencode('Recordatorio de mensualidad - Alfa Digital') }}&amp;body={{ rawurlencode($cuota->mensaje) }}">Preparar correo</a>
                        @endif
                        <details class="mt-2"><summary>Ver mensaje</summary><p>{{ $cuota->mensaje }}</p></details>
                        <form action="{{ route('cobros.confirmar', $cuota) }}" method="POST" class="payment-form" data-confirm="¿Confirmas que verificaste el dinero recibido en Nequi? Se registrará el ingreso y se generará la siguiente cuota.">
                            @csrf
                            <label for="referencia-{{ $cuota->id }}">Referencia del pago verificado en Nequi</label>
                            <input id="referencia-{{ $cuota->id }}" class="form-control mb-2" name="referencia_pago" required maxlength="200" placeholder="Número de movimiento o referencia">
                            <button class="btn btn-primary btn-sm" type="submit">Confirmar dinero recibido</button>
                        </form>
                        </details>
                    @else
                        <small>Confirmado {{ $cuota->pagada_at->format('d/m/Y H:i') }}<br>{{ $cuota->referencia_pago }}</small>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-5"><strong>No hay cuotas para esta consulta.</strong><p class="text-muted mb-0">Prueba otro filtro o activa el control mensual de una licencia.</p></td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $cuotas->links() }}
</div></div>
<details class="section-disclosure mb-4"><summary><i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>Activar control mensual <span>Contratos existentes sin cuotas</span></summary><div class="panel-body"><div class="card"><div class="card-body">
    <h4>Activar control mensual</h4>
    <p>Selecciona únicamente contratos que cobras mensualmente. Indica la fecha original del contrato y cuántos meses después vence la primera cuota pendiente: 0 corresponde al inicio; 1, al siguiente mes. No actives aquí períodos que ya estén registrados como pagados.</p>
    @foreach($licencias as $licencia)
        <form action="{{ route('cobros.activar', $licencia) }}" method="POST" class="row g-3 activation-contract">
            @csrf
            <div class="col-md-12"><strong>{{ $licencia->cliente->nombre }} · {{ $licencia->producto->nombre }}</strong></div>
            <div class="col-md-4"><label>Fecha original del contrato</label><input aria-label="Fecha original del contrato" class="form-control" type="date" name="fecha_ancla" value="{{ $licencia->fecha_inicio->format('Y-m-d') }}" required></div>
            <div class="col-md-3"><label>Meses desde el inicio</label><input aria-label="Meses desde el inicio" class="form-control" type="number" min="0" max="1200" name="periodo" value="1" required></div>
            <div class="col-md-3"><label>Mensualidad total (COP)</label><input aria-label="Mensualidad total" class="form-control" type="number" min="0.01" step="0.01" name="monto" value="{{ $licencia->producto->precio_alquiler_mensual * $licencia->cantidad_usuarios }}" required></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary" type="submit">Activar</button></div>
        </form>
    @endforeach
</div></div>

</div></details>
<details class="section-disclosure"><summary><i class="bi bi-clock-history me-2" aria-hidden="true"></i>Centro de avisos e historial <span>Configuración y resultados de envío</span></summary><div class="panel-body"><div class="card mb-4"><div class="card-body">
    <h4>{{ $avisos }} mensualidades requieren atención</h4>
    <p>Los avisos aparecen cinco días antes del vencimiento y permanecen hasta confirmar el pago. Si el día contratado no existe en un mes, vence el último día de ese mes.</p>
    <p><strong>Nequi:</strong> {{ config('cobros.nequi') }} · <strong>Comprobantes por WhatsApp:</strong> +{{ config('cobros.whatsapp') }} · {{ config('cobros.correo') }}</p>
    <div class="alert alert-info mb-0">
        <strong>Automatización</strong>
        <p class="mb-1">Revisión diaria a las 9:00 a. m. (hora de Colombia).</p>
        <p class="mb-1">Última revisión: {{ $ultimaRevision ? \Carbon\Carbon::parse($ultimaRevision)->timezone('America/Bogota')->format('d/m/Y H:i') : 'Todavía no se ha ejecutado el proceso automático.' }}</p>
        @foreach($canales as $canal => $estadoCanal)
            <p class="mb-1"><strong>{{ $canal === 'email' ? 'Correo' : 'WhatsApp' }}:</strong> {{ $estadoCanal }}</p>
        @endforeach
        <small>Se programa un recordatorio por cuota y canal desde cinco días antes. Los botones manuales siguen disponibles. El equipo que ejecuta la revisión debe permanecer encendido.</small>
    </div>
</div></div>
<div class="card mb-4"><div class="card-body">
    <h4>Historial de recordatorios automáticos</h4>
    <div class="table-responsive"><table class="table">
        <thead><tr><th scope="col">Cliente</th><th scope="col">Canal</th><th scope="col">Estado</th><th scope="col">Intentos</th><th scope="col">Detalle</th></tr></thead>
        <tbody>
        @forelse($recordatorios as $registro)
            <tr><td>{{ $registro->cuota->licencia->cliente->nombre }}</td><td>{{ $registro->canal }}</td><td>{{ $registro->estado }}</td><td>{{ $registro->intentos }}</td><td>{{ $registro->detalle }}</td></tr>
        @empty
            <tr><td colspan="5">Aún no hay recordatorios programados.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <small>Aceptado significa que el proveedor recibió la solicitud; no confirma entrega ni lectura. Si aparece “revisar”, comprueba el envío antes de solicitar otro.</small>
</div></div>
</div></details>

@endsection
