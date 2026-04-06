<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogController;

/*
|--------------------------------------------------------------------------
| Serviço 2 — API de Logs
|--------------------------------------------------------------------------
| As rotas de escrita são protegidas por segredo interno (middleware).
| A listagem de logs é pública internamente (pode ser protegida também).
*/

// Rota interna — chamada pelos outros serviços
Route::middleware('internal.secret')->group(function () {
    Route::post('/logs', [LogController::class, 'store']);
});

// Rota de consulta (pode exigir auth se necessário)
Route::get('/logs', [LogController::class, 'index']);
Route::get('/logs/usuario/{usuarioId}', [LogController::class, 'porUsuario']);
