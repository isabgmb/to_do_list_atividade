<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CORS — necessário para o frontend em porta diferente
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Sanctum para autenticação stateless via token
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Retorna erros de validação em JSON
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Erro de validação.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // Retorna 404 em JSON para rotas não encontradas
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Recurso não encontrado.'], 404);
            }
        });
    })->create();
