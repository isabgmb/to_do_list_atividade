<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('acao', 100);        // ex: CRIACAO_TAREFA, LOGIN, ACESSO_NEGADO
            $table->string('detalhe', 500);     // descrição detalhada do evento
            $table->unsignedBigInteger('usuario_id')->nullable(); // null = anônimo
            $table->timestamps();               // created_at = timestamp do evento

            $table->index('usuario_id');
            $table->index('acao');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
