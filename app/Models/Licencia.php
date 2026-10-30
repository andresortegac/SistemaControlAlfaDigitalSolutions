<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Licencia extends Model
{
    use HasFactory;

    protected $table = 'licencias';

    protected $fillable = [
        'cliente_id',
        'producto_id',
        'tipo',
        'estado',
        'fecha_inicio',
        'fecha_vencimiento',
        'cantidad_usuarios',
        'clave_licencia',
        'precio_total',
        'renovacion_automatica',
        'notas',
        'fecha_creacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_creacion' => 'date',
        'renovacion_automatica' => 'boolean',
        'precio_total' => 'decimal:2',
        'cantidad_usuarios' => 'integer',
    ];

    const TIPO_COMPRA = 'compra';

    const TIPO_ALQUILER = 'alquiler';

    const ESTADO_ACTIVA = 'activa';

    const ESTADO_VENCIDA = 'vencida';

    const ESTADO_SUSPENDIDA = 'suspendida';

    const ESTADO_PENDIENTE = 'pendiente';

    public static function getTipos(): array
    {
        return [
            self::TIPO_COMPRA => 'Compra',
            self::TIPO_ALQUILER => 'Alquiler',
        ];
    }

    public static function getEstados(): array
    {
        return [
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_VENCIDA => 'Vencida',
            self::ESTADO_SUSPENDIDA => 'Suspendida',
            self::ESTADO_PENDIENTE => 'Pendiente',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function registrosUso(): HasMany
    {
        return $this->hasMany(RegistroUso::class);
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class);
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class);
    }

    public function getTipoLabelAttribute(): string
    {
        return self::getTipos()[$this->tipo] ?? $this->tipo;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::getEstados()[$this->estado] ?? $this->estado;
    }

    public function getDiasRestantesAttribute(): ?int
    {
        if (! $this->fecha_vencimiento) {
            return null;
        }

        return now()->diffInDays($this->fecha_vencimiento, false);
    }

    public function getEstaVencidaAttribute(): bool
    {
        if (! $this->fecha_vencimiento) {
            return false;
        }

        return now()->isAfter($this->fecha_vencimiento);
    }

    public function getPorVencerAttribute(): bool
    {
        $dias = $this->dias_restantes;

        return $dias !== null && $dias <= 30 && $dias > 0;
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    public function scopePorVencer($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA)
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', now()->addDays(30))
            ->where('fecha_vencimiento', '>', now());
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', self::ESTADO_VENCIDA)
            ->orWhere(function ($q) {
                $q->whereNotNull('fecha_vencimiento')
                    ->where('fecha_vencimiento', '<', now());
            });
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public static function generarClave(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $clave = '';
        for ($i = 0; $i < 16; $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $clave .= '-';
            }
            $clave .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $clave;
    }

    public function getPrecioTotalFormateadoAttribute(): string
    {
        return '$'.number_format($this->precio_total, 0, ',', '.');
    }
}
