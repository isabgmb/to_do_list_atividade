<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    /**
     * Lista todos os logs (uso administrativo/debug).
     */
    public function index(): JsonResponse
    {
        $logs = Log::orderBy('created_at', 'desc')->paginate(50);

        return response()->json($logs);
    }

    /**
     * Lista logs de um usuário específico.
     */
    public function porUsuario(int $usuarioId): JsonResponse
    {
        $logs = Log::where('usuario_id', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    /**
     * Registra um novo log.
     * Chamado internamente pelos Serviços 1 e 3.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'acao'      => 'required|string|max:100',
            'detalhe'   => 'required|string|max:500',
            'usuarioId' => 'nullable|integer',
        ]);

        $log = Log::create([
            'acao'       => $request->acao,
            'detalhe'    => $request->detalhe,
            'usuario_id' => $request->usuarioId,
        ]);

        return response()->json([
            'id'        => $log->id,
            'acao'      => $log->acao,
            'detalhe'   => $log->detalhe,
            'usuarioId' => $log->usuario_id,
            'timestamp' => $log->created_at,
        ], 201);
    }
}
