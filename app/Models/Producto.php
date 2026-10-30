<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'version',
        'precio_compra',
        'precio_alquiler_mensual',
        'precio_alquiler_anual',
        'requisitos_sistema',
        'activo',
        'fecha_lanzamiento'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'precio_compra' => 'decimal:2',
        'precio_alquiler_mensual' => 'decimal:2',
        'precio_alquiler_anual' => 'decimal:2',
        'fecha_lanzamiento' => 'date'
    ];

    const CATEGORIA_OFIMATICA = 'ofimatica';
    const CATEGORIA_DISENO = 'diseno';
    const CATEGORIA_DESARROLLO = 'desarrollo';
    const CATEGORIA_SEGURIDAD = 'seguridad';
    const CATEGORIA_CONTABILIDAD = 'contabilidad';
    const CATEGORIA_OTRO = 'otro';

    public static function getCategorias(): array
    {
        return [
            self::CATEGORIA_OFIMATICA => 'Ofimática',
            self::CATEGORIA_DISENO => 'Diseño',
            self::CATEGORIA_DESARROLLO => 'Desarrollo',
            self::CATEGORIA_SEGURIDAD => 'Seguridad',
            self::CATEGORIA_CONTABILIDAD => 'Contabilidad',
            self::CATEGORIA_OTRO => 'Otro'
        ];
    }

    public static function getIconos(): array
    {
        return [
            self::CATEGORIA_OFIMATICA => 'file-earmark-text',
            self::CATEGORIA_DISENO => 'vector-pen',
            self::CATEGORIA_DESARROLLO => 'code-square',
            self::CATEGORIA_SEGURIDAD => 'shield-lock',
            self::CATEGORIA_CONTABILIDAD => 'bar-chart-line',
            self::CATEGORIA_OTRO => 'box-seam'
        ];
    }

    public function licencias(): HasMany
    {
        return $this->hasMany(Licencia::class);
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::getCategorias()[$this->categoria] ?? $this->categoria;
    }

    public function getIconoAttribute(): string
    {
        return self::getIconos()[$this->categoria] ?? 'box-seam';
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function getPrecioFormateado($tipo = 'compra'): string
    {
        $precio = match($tipo) {
            'compra' => $this->precio_compra,
            'mensual' => $this->precio_alquiler_mensual,
            'anual' => $this->precio_alquiler_anual,
            default => 0
        };
        return '$' . number_format($precio, 0, ',', '.');
    }
}
