<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model
{
    use HasFactory;

    protected $table = 'tarefas';

    protected $fillable = [
        'titulo',
        'concluida',
        'usuario_id',
    ];

    protected $casts = [
        'concluida' => 'boolean',
    ];

    /**
     * Relacionamento: tarefa pertence a um usuário.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
