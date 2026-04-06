<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Cadastro de novo usuário.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nome'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'senha' => 'required|string|min:6',
        ]);

        $user = User::create([
            'nome'  => $request->nome,
            'email' => $request->email,
            'senha' => Hash::make($request->senha),
        ]);

        // Notifica o serviço de logs
        $this->notificarLog('REGISTRO', "Novo usuário cadastrado: {$user->email}", $user->id);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token'  => $token,
            'usuario' => [
                'id'    => $user->id,
                'nome'  => $user->nome,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Login do usuário.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->senha, $user->senha)) {
            // Loga tentativa de acesso indevido
            $this->notificarLog(
                'ACESSO_NEGADO',
                "Tentativa de login inválida para: {$request->email}",
                null
            );

            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        // Revoga tokens antigos (sessão única)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->notificarLog('LOGIN', "Usuário logado: {$user->email}", $user->id);

        return response()->json([
            'token'  => $token,
            'usuario' => [
                'id'    => $user->id,
                'nome'  => $user->nome,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Logout do usuário.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensagem' => 'Logout realizado com sucesso.']);
    }

    /**
     * Retorna dados do usuário autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'nome'  => $user->nome,
            'email' => $user->email,
        ]);
    }

    /**
     * Envia log para o Serviço 2 (Laravel Logs).
     */
    private function notificarLog(string $acao, string $detalhe, ?int $usuarioId): void
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 3]);
            $client->post(config('services.logs.url') . '/logs', [
                'json' => [
                    'acao'      => $acao,
                    'detalhe'   => $detalhe,
                    'usuarioId' => $usuarioId,
                ],
                'headers' => [
                    'X-Internal-Secret' => config('services.logs.secret'),
                ],
            ]);
        } catch (\Exception $e) {
            // Falha silenciosa: log não bloqueia operação principal
            \Log::warning('Serviço de logs indisponível: ' . $e->getMessage());
        }
    }
}
