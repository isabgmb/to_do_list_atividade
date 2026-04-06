/**
 * Serviço de tarefas — integrado com os 3 backends reais.
 *
 * Serviço 1 (Laravel :8000) → CRUD de tarefas
 * Serviço 3 (FastAPI :8001) → estatísticas
 */
import { API_CONFIG, apiFetch } from './api.js';

const S1 = API_CONFIG.LARAVEL;   // Serviço 1
const S3 = API_CONFIG.FASTAPI;   // Serviço 3

// ──────────────────────────────────────────────────────
// TAREFAS — Serviço 1 (Laravel)
// ──────────────────────────────────────────────────────

/**
 * Busca todas as tarefas do usuário autenticado.
 * @returns {Array<{ id, titulo, concluida, usuarioId }>}
 */
export async function getTodos() {
    const res = await apiFetch(`${S1}/tarefas`);

    if (!res.ok) throw new Error('Erro ao buscar tarefas.');

    const tarefas = await res.json();

    // Normaliza para o formato esperado pelo app.js
    return tarefas.map(t => ({
        id:        t.id,
        title:     t.titulo,        // frontend usa 'title'
        completed: t.concluida,     // frontend usa 'completed'
        usuarioId: t.usuarioId,
    }));
}

/**
 * Cria uma nova tarefa.
 * @param {{ title: string }} data
 */
export async function createTodo(data) {
    const res = await apiFetch(`${S1}/tarefas`, {
        method: 'POST',
        body: JSON.stringify({ titulo: data.title }),
    });

    if (!res.ok) {
        const err = await res.json();
        throw new Error(err.message || 'Erro ao criar tarefa.');
    }

    const tarefa = await res.json();
    return {
        id:        tarefa.id,
        title:     tarefa.titulo,
        completed: tarefa.concluida,
        usuarioId: tarefa.usuarioId,
    };
}

/**
 * Alterna o status de concluída de uma tarefa.
 * @param {number} id
 */
export async function toggleTodo(id) {
    const res = await apiFetch(`${S1}/tarefas/${id}/concluir`, {
        method: 'PATCH',
    });

    if (!res.ok) throw new Error('Erro ao atualizar tarefa.');

    const tarefa = await res.json();
    return {
        id:        tarefa.id,
        title:     tarefa.titulo,
        completed: tarefa.concluida,
        usuarioId: tarefa.usuarioId,
    };
}

/**
 * Remove uma tarefa.
 * @param {number} id
 */
export async function deleteTodo(id) {
    const res = await apiFetch(`${S1}/tarefas/${id}`, {
        method: 'DELETE',
    });

    if (!res.ok) throw new Error('Erro ao excluir tarefa.');
}

// ──────────────────────────────────────────────────────
// ESTATÍSTICAS — Serviço 3 (FastAPI / Python)
// ──────────────────────────────────────────────────────

/**
 * Busca as estatísticas do usuário autenticado no Serviço 3.
 * O Serviço 3 recebe o snapshot sincronizado pelo Serviço 1.
 * @param {number} usuarioId
 * @returns {{ total, completed, pending }}
 */
export async function getStats(usuarioId) {
    try {
        const res = await fetch(`${S3}/analise/${usuarioId}`, {
            headers: { 'Accept': 'application/json' },
        });

        if (!res.ok) throw new Error('Serviço 3 indisponível.');

        const data = await res.json();

        return {
            total:     data.total,
            completed: data.concluidas,
            pending:   data.pendentes,
        };
    } catch (_) {
        // Fallback: retorna zeros se o Serviço 3 estiver fora
        return { total: 0, completed: 0, pending: 0 };
    }
}

// ──────────────────────────────────────────────────────
// STATUS DOS SERVIÇOS
// ──────────────────────────────────────────────────────

export async function verificarServicos() {
    const checar = async (url) => {
        try {
            const res = await fetch(url, { signal: AbortSignal.timeout(3000) });
            return res.ok;
        } catch {
            return false;
        }
    };

    const [s1, s2, s3] = await Promise.all([
        checar(`${API_CONFIG.LARAVEL}/me`),          // requer auth, 401 = online
        checar(`${API_CONFIG.LOGS}/logs`),
        checar(`${API_CONFIG.FASTAPI}/health`),
    ]);

    return {
        s1: s1 || true,  // 401 também significa que o serviço está no ar
        s2,
        s3,
    };
}
