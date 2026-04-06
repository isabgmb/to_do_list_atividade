<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'acao',
        'detalhe',
        'usuario_id',
    ];

    protected $casts = [
        'usuario_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scopes úteis
     */

    // Filtra por tipo de ação
    public function scopeAcao($query, string $acao)
    {
        return $query->where('acao', $acao);
    }

    // Filtra por usuário
    public function scopeUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    // Filtra tentativas de acesso indevido
    public function scopeAcessosNegados($query)
    {
        return $query->where('acao', 'ACESSO_NEGADO');
    }
}
