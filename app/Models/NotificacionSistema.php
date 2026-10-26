<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificacionSistema extends Model
{
    protected $table = 'notificaciones_sistema';

    protected $fillable = [
        'user_id', 'titulo', 'mensaje', 'tipo',
        'modulo', 'url_accion', 'leida', 'leida_en',
    ];

    protected $casts = [
        'leida'    => 'boolean',
        'leida_en' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function marcarLeida(): void
    {
        $this->update(['leida' => true, 'leida_en' => now()]);
    }
}
