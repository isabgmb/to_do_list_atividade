<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Campos preenchíveis em massa.
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',
    ];

    /**
     * Campos ocultados na serialização.
     */
    protected $hidden = [
        'senha',
        'remember_token',
    ];

    /**
     * Mapeamento do campo senha para o atributo padrão do Laravel.
     * O Laravel Sanctum/Auth usa internamente 'password'.
     */
    protected $casts = [];

    /**
     * Getter/setter para compatibilidade com o hash do Laravel.
     * Laravel usa internamente `password`; aqui redirecionamos para `senha`.
     */
    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    /**
     * Relacionamento: um usuário tem muitas tarefas.
     */
    public function tarefas()
    {
        return $this->hasMany(Tarefa::class, 'usuario_id');
    }
}
