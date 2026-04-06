<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de segurança interna.
 *
 * Garante que apenas serviços internos (Serviço 1, 3) possam
 * criar logs — via cabeçalho X-Internal-Secret.
 */
class InternalSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.internal.secret');

        if ($request->header('X-Internal-Secret') !== $secret) {
            // Loga a tentativa de acesso não autorizado diretamente no BD
            \DB::table('logs')->insert([
                'acao'       => 'ACESSO_NEGADO',
                'detalhe'    => 'Tentativa de acesso à API de logs sem segredo válido. IP: ' . $request->ip(),
                'usuario_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['erro' => 'Acesso não autorizado.'], 403);
        }

        return $next($request);
    }
}
