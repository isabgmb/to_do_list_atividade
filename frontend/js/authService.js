/**
 * Serviço de autenticação — comunica com o Serviço 1 (Laravel + Sanctum).
 */
import { API_CONFIG, apiFetch, setAuth, clearAuth } from './api.js';

const BASE = API_CONFIG.LARAVEL;

/**
 * Realiza o cadastro de um novo usuário.
 * @returns {{ token: string, usuario: object }}
 */
export async function registrar(nome, email, senha) {
    const res = await fetch(`${BASE}/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ nome, email, senha }),
    });

    const data = await res.json();

    if (!res.ok) {
        // Laravel retorna erros de validação em data.errors
        const mensagem = extrairErro(data);
        throw new Error(mensagem);
    }

    setAuth(data.token, data.usuario);
    return data;
}

/**
 * Realiza o login de um usuário existente.
 * @returns {{ token: string, usuario: object }}
 */
export async function login(email, senha) {
    const res = await fetch(`${BASE}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email, senha }),
    });

    const data = await res.json();

    if (!res.ok) {
        const mensagem = extrairErro(data);
        throw new Error(mensagem);
    }

    setAuth(data.token, data.usuario);
    return data;
}

/**
 * Realiza o logout — revoga o token no servidor.
 */
export async function logout() {
    try {
        await apiFetch(`${BASE}/logout`, { method: 'POST' });
    } catch (_) {
        // Ignora erros de rede: limpa auth localmente de qualquer forma
    } finally {
        clearAuth();
    }
}

// ── helpers ────────────────────────────────────────

function extrairErro(data) {
    if (data.errors) {
        // Pega o primeiro erro de validação do Laravel
        const firstKey = Object.keys(data.errors)[0];
        return data.errors[firstKey][0];
    }
    return data.message || 'Erro desconhecido.';
}
