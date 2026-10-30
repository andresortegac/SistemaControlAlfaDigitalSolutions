<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cuota extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'fecha_ancla' => 'immutable_date',
        'vencimiento' => 'immutable_date',
        'pagada_at' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class);
    }

    public static function fechaPeriodo(string $ancla, int $periodo): CarbonImmutable
    {
        return CarbonImmutable::parse($ancla)->startOfDay()->addMonthsNoOverflow($periodo);
    }

    public function scopePorRecordar($query)
    {
        return $query->whereNull('pagada_at')
            ->whereDate('vencimiento', '<=', today()->addDays(config('cobros.dias_aviso')))
            ->whereHas('licencia', fn ($q) => $q->whereIn('estado', ['activa', 'pendiente', 'vencida']));
    }

    public function getMensajeAttribute(): string
    {
        $licencia = $this->licencia;
        $monto = '$'.number_format($this->monto, 0, ',', '.');
        $fecha = $this->vencimiento->format('d/m/Y');

        return "Hola, {$licencia->cliente->nombre}. Tu mensualidad de {$licencia->producto->nombre} por {$monto} "
            ."tiene fecha de pago {$fecha}. Puedes pagar por Nequi al ".config('cobros.nequi')
            .'. Envía la captura del comprobante al WhatsApp +'.config('cobros.whatsapp')
            .'. Si ya pagaste, envíanos el comprobante para verificarlo. Alfa Digital Solutions SAS.';
    }

    public function getTelefonoWhatsappAttribute(): ?string
    {
        $numero = preg_replace('/\D/', '', $this->licencia->cliente->telefono ?? '');
        if (strlen($numero) === 10) {
            $numero = '57'.$numero;
        }
        if (! preg_match('/^[1-9][0-9]{10,14}$/', $numero)) {
            return null;
        }

        return $numero;
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        return $this->telefono_whatsapp
            ? 'https://wa.me/'.$this->telefono_whatsapp.'?text='.rawurlencode($this->mensaje)
            : null;
    }
}
