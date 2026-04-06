/**
 * Configuração das URLs dos serviços backend.
 * Altere conforme o ambiente de execução.
 */
export const API_CONFIG = {
    LARAVEL:  "http://localhost:8000/api",   // Serviço 1 – Tarefas + Auth
    LOGS:     "http://localhost:8002/api",   // Serviço 2 – Logs
    FASTAPI:  "http://localhost:8001/api",   // Serviço 3 – Análise
};

/**
 * Token de autenticação armazenado em memória.
 * (Não usar localStorage por questões de segurança XSS em produção)
 */
let _token = null;
let _usuario = null;

export function setAuth(token, usuario) {
    _token = token;
    _usuario = usuario;
}

export function getToken() {
    return _token;
}

export function getUsuario() {
    return _usuario;
}

export function clearAuth() {
    _token = null;
    _usuario = null;
}

/**
 * Wrapper para fetch com token de autenticação.
 */
export async function apiFetch(url, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(options.headers || {}),
    };

    if (_token) {
        headers['Authorization'] = `Bearer ${_token}`;
    }

    const response = await fetch(url, { ...options, headers });

    if (response.status === 401) {
        // Token expirado ou inválido: força logout
        clearAuth();
        window.location.reload();
        throw new Error('Sessão expirada. Faça login novamente.');
    }

    return response;
}
