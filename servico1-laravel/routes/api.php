<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TarefaController;

// Rotas públicas (autenticação)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/tarefas', [TarefaController::class, 'index']);
    Route::post('/tarefas', [TarefaController::class, 'store']);
    Route::patch('/tarefas/{id}/concluir', [TarefaController::class, 'concluir']);
    Route::delete('/tarefas/{id}', [TarefaController::class, 'destroy']);
});
