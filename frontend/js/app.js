/**
 * app.js — Controlador principal do frontend.
 * Gerencia autenticação e integração com os 3 serviços.
 */
import { getUsuario } from './api.js';
import { registrar, login as authLogin, logout as authLogout } from './authService.js';
import {
    getTodos,
    createTodo,
    toggleTodo,
    deleteTodo,
    getStats,
    verificarServicos,
} from './todoService.js';

// ── Referências DOM ──────────────────────────────────
const authScreen   = document.getElementById('authScreen');
const appScreen    = document.getElementById('appScreen');
const authMsg      = document.getElementById('authMsg');
const formLogin    = document.getElementById('formLogin');
const formCadastro = document.getElementById('formCadastro');
const todoForm     = document.getElementById('todoForm');
const todoList     = document.getElementById('todoList');

// ── Estado global ──────────────────────────────────--
let usuarioAtual = null;

// ════════════════════════════════════════════════════
// INICIALIZAÇÃO
// ════════════════════════════════════════════════════
function init() {
    mostrarTela('auth');
    verificarStatusServicos();
}

// ════════════════════════════════════════════════════
// NAVEGAÇÃO ENTRE TELAS
// ════════════════════════════════════════════════════
function mostrarTela(tela) {
    if (tela === 'auth') {
        authScreen.classList.remove('hidden');
        appScreen.classList.add('hidden');
    } else {
        authScreen.classList.add('hidden');
        appScreen.classList.remove('hidden');
    }
}

// Alterna entre Login e Cadastro
window.mostrarAba = function (aba) {
    document.getElementById('formLogin').classList.toggle('hidden', aba !== 'login');
    document.getElementById('formCadastro').classList.toggle('hidden', aba !== 'cadastro');
    document.getElementById('tabLogin').classList.toggle('active', aba === 'login');
    document.getElementById('tabCadastro').classList.toggle('active', aba === 'cadastro');
    esconderMsg();
};

// ════════════════════════════════════════════════════
// AUTENTICAÇÃO
// ════════════════════════════════════════════════════
formLogin.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const senha = document.getElementById('loginSenha').value;
    const btn   = document.getElementById('btnLogin');

    btn.disabled = true;
    btn.textContent = 'Entrando...';
    esconderMsg();

    try {
        const data = await authLogin(email, senha);
        usuarioAtual = data.usuario;
        entrarNoApp();
    } catch (err) {
        mostrarMsg(err.message, 'erro');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Entrar';
    }
});

formCadastro.addEventListener('submit', async (e) => {
    e.preventDefault();
    const nome  = document.getElementById('cadNome').value.trim();
    const email = document.getElementById('cadEmail').value.trim();
    const senha = document.getElementById('cadSenha').value;
    const btn   = document.getElementById('btnCadastro');

    btn.disabled = true;
    btn.textContent = 'Criando conta...';
    esconderMsg();

    try {
        const data = await registrar(nome, email, senha);
        usuarioAtual = data.usuario;
        entrarNoApp();
    } catch (err) {
        mostrarMsg(err.message, 'erro');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Criar conta';
    }
});

window.logout = async function () {
    await authLogout();
    usuarioAtual = null;
    todoList.innerHTML = '';
    mostrarTela('auth');
};

function entrarNoApp() {
    document.getElementById('nomeUsuario').textContent = `Olá, ${usuarioAtual.nome}`;
    mostrarTela('app');
    refresh();
}

// ════════════════════════════════════════════════════
// TAREFAS
// ════════════════════════════════════════════════════
todoForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const titulo = document.getElementById('title').value.trim();
    if (!titulo) return;

    try {
        await createTodo({ title: titulo });
        todoForm.reset();
        await refresh();
    } catch (err) {
        alert('Erro ao criar tarefa: ' + err.message);
    }
});

async function renderTodos() {
    const loading    = document.getElementById('loadingTarefas');
    const semTarefas = document.getElementById('semTarefas');

    loading.classList.remove('hidden');
    todoList.innerHTML = '';
    semTarefas.classList.add('hidden');

    try {
        const todos = await getTodos();
        loading.classList.add('hidden');

        if (todos.length === 0) {
            semTarefas.classList.remove('hidden');
            return;
        }

        todos.forEach(todo => {
            const li = document.createElement('li');
            if (todo.completed) li.classList.add('completed');

            li.innerHTML = `
                <span class="todo-titulo">${escapeHtml(todo.title)}</span>
                <div class="actions">
                    <button class="btn-complete" title="${todo.completed ? 'Reabrir' : 'Concluir'}">
                        ${todo.completed ? '↩' : '✔'}
                    </button>
                    <button class="btn-delete" title="Excluir">✖</button>
                </div>
            `;

            li.querySelector('.btn-complete').addEventListener('click', async () => {
                await toggleTodo(todo.id);
                await refresh();
            });

            li.querySelector('.btn-delete').addEventListener('click', async () => {
                if (confirm(`Excluir "${todo.title}"?`)) {
                    await deleteTodo(todo.id);
                    await refresh();
                }
            });

            todoList.appendChild(li);
        });
    } catch (err) {
        loading.classList.add('hidden');
        todoList.innerHTML = `<li class="erro-item">Erro ao carregar tarefas: ${err.message}</li>`;
    }
}

async function renderStats() {
    if (!usuarioAtual) return;

    try {
        const stats = await getStats(usuarioAtual.id);
        document.getElementById('total').textContent     = stats.total;
        document.getElementById('concluidas').textContent = stats.completed;
        document.getElementById('pendentes').textContent  = stats.pending;
    } catch (_) {
        // Serviço 3 indisponível: mantém zeros
    }
}

async function refresh() {
    await Promise.all([renderTodos(), renderStats()]);
}

// ════════════════════════════════════════════════════
// STATUS DOS SERVIÇOS
// ════════════════════════════════════════════════════
async function verificarStatusServicos() {
    const status = await verificarServicos();

    atualizarDot('dotS1', status.s1);
    atualizarDot('dotS2', status.s2);
    atualizarDot('dotS3', status.s3);
}

function atualizarDot(id, online) {
    const dot = document.getElementById(id);
    if (!dot) return;
    dot.className = 'servico-dot ' + (online ? 'online' : 'offline');
}

// ════════════════════════════════════════════════════
// UTILITÁRIOS
// ════════════════════════════════════════════════════
function mostrarMsg(texto, tipo = 'erro') {
    authMsg.textContent = texto;
    authMsg.className = 'auth-msg ' + tipo;
    authMsg.classList.remove('hidden');
}

function esconderMsg() {
    authMsg.classList.add('hidden');
}

function escapeHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Start ─────────────────────────────────────────
init();
