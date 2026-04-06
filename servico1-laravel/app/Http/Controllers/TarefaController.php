<?php

namespace App\Http\Controllers;

use App\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TarefaController extends Controller
{
    /**
     * Lista apenas as tarefas do usuário autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $tarefas = Tarefa::where('usuario_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'titulo'     => $t->titulo,
                'concluida'  => (bool) $t->concluida,
                'usuarioId'  => $t->usuario_id,
            ]);

        return response()->json($tarefas);
    }

    /**
     * Cria uma nova tarefa vinculada ao usuário autenticado.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
        ]);

        $tarefa = Tarefa::create([
            'titulo'     => $request->titulo,
            'concluida'  => false,
            'usuario_id' => $request->user()->id,
        ]);

        $this->notificarLog(
            'CRIACAO_TAREFA',
            "Tarefa criada: \"{$tarefa->titulo}\"",
            $request->user()->id,
            $request->user()->currentAccessToken()->token ?? null
        );

        return response()->json([
            'id'        => $tarefa->id,
            'titulo'    => $tarefa->titulo,
            'concluida' => (bool) $tarefa->concluida,
            'usuarioId' => $tarefa->usuario_id,
        ], 201);
    }

    /**
     * Marca/desmarca tarefa como concluída — apenas do próprio usuário.
     */
    public function concluir(Request $request, int $id): JsonResponse
    {
        $tarefa = Tarefa::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->firstOrFail();

        $tarefa->concluida = ! $tarefa->concluida;
        $tarefa->save();

        $status = $tarefa->concluida ? 'concluída' : 'reaberta';

        $this->notificarLog(
            'CONCLUSAO_TAREFA',
            "Tarefa \"{$tarefa->titulo}\" marcada como {$status}",
            $request->user()->id
        );

        return response()->json([
            'id'        => $tarefa->id,
            'titulo'    => $tarefa->titulo,
            'concluida' => (bool) $tarefa->concluida,
            'usuarioId' => $tarefa->usuario_id,
        ]);
    }

    /**
     * Remove uma tarefa — apenas do próprio usuário.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tarefa = Tarefa::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->firstOrFail();

        $titulo = $tarefa->titulo;
        $tarefa->delete();

        $this->notificarLog(
            'EXCLUSAO_TAREFA',
            "Tarefa excluída: \"{$titulo}\"",
            $request->user()->id
        );

        return response()->json(null, 204);
    }

    /**
     * Envia log para o Serviço 2.
     */
    private function notificarLog(string $acao, string $detalhe, int $usuarioId): void
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
            \Log::warning('Serviço de logs indisponível: ' . $e->getMessage());
        }
    }

    /**
     * Sincroniza snapshot de contagens com o Serviço 3 (Python/FastAPI).
     */
    private function sincronizarAnalise(int $usuarioId): void
    {
        try {
            $total      = Tarefa::where('usuario_id', $usuarioId)->count();
            $concluidas = Tarefa::where('usuario_id', $usuarioId)->where('concluida', true)->count();
            $pendentes  = $total - $concluidas;

            $client = new \GuzzleHttp\Client(['timeout' => 3]);
            $client->post(config('services.analise.url') . '/analise/sincronizar', [
                'json' => [
                    'usuarioId'  => $usuarioId,
                    'total'      => $total,
                    'concluidas' => $concluidas,
                    'pendentes'  => $pendentes,
                ],
                'headers' => [
                    'X-Internal-Secret' => config('services.logs.secret'),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::warning('Serviço de análise indisponível: ' . $e->getMessage());
        }
    }
}
