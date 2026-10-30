<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recordatorio extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'aceptado_at' => 'datetime',
        'reintentar_at' => 'datetime',
    ];

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class);
    }
}
