# 📝 TodoList Distribuído — Documentação Completa

Sistema de TODO List com 3 serviços independentes, autenticação JWT/Sanctum e frontend integrado.

---

## 🏗️ Arquitetura

```
┌─────────────────────────────────────────────────────────┐
│                      FRONTEND                           │
│           (HTML + JS puro, porta 5500 ou 3000)          │
└────────────┬────────────────────────┬───────────────────┘
             │                        │
             ▼                        ▼
┌────────────────────┐    ┌───────────────────────┐
│   SERVIÇO 1        │    │   SERVIÇO 3            │
│   Laravel :8000    │───▶│   FastAPI :8001        │
│   Tarefas + Auth   │    │   Análise/SQLAlchemy   │
│   ORM: Eloquent    │    └───────────────────────┘
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│   SERVIÇO 2        │
│   Laravel :8002    │
│   Logs/Eloquent    │
└────────────────────┘
```

---

## 🚀 Como Executar

### Opção A — Docker Compose (recomendado)

```bash
# Na raiz do projeto (onde está o docker-compose.yml)
docker compose up --build
```

Serviços disponíveis:
- Serviço 1 (Tarefas): http://localhost:8000
- Serviço 2 (Logs):    http://localhost:8002
- Serviço 3 (Análise): http://localhost:8001

Frontend: abra `frontend/index.html` no navegador (ou use Live Server no VSCode).

---

### Opção B — Execução Manual

#### Serviço 1 (Laravel — Tarefas)

```bash
cd servico1-laravel
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve --port=8000
```

#### Serviço 2 (Laravel — Logs)

```bash
cd servico2-laravel-logs
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve --port=8002
```

#### Serviço 3 (Python — Análise)

```bash
cd servico3-python
cp .env.example .env
python -m venv venv
source venv/bin/activate    # Windows: venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --port 8001 --reload
```

#### Frontend

Abra `frontend/index.html` com Live Server (VSCode) ou:

```bash
cd frontend
python -m http.server 5500
# Acesse http://localhost:5500
```

---

## 🔐 Autenticação

O sistema usa **Laravel Sanctum** com tokens Bearer (stateless).

### Fluxo:
1. Usuário se cadastra → recebe token
2. Token é armazenado em memória no frontend
3. Todas as requisições ao Serviço 1 enviam `Authorization: Bearer <token>`
4. Serviço 1 valida o token antes de qualquer operação
5. Usuário só acessa as próprias tarefas

### Endpoints de Auth (Serviço 1):

| Método | Rota             | Descrição           | Auth? |
|--------|------------------|---------------------|-------|
| POST   | /api/register    | Cadastro            | Não   |
| POST   | /api/login       | Login               | Não   |
| POST   | /api/logout      | Logout              | Sim   |
| GET    | /api/me          | Dados do usuário    | Sim   |

---

## 📋 Endpoints das Tarefas (Serviço 1 — :8000)

| Método | Rota                        | Descrição                      |
|--------|-----------------------------|--------------------------------|
| GET    | /api/tarefas                | Lista tarefas do usuário       |
| POST   | /api/tarefas                | Cria tarefa                    |
| PATCH  | /api/tarefas/{id}/concluir  | Alterna status de conclusão    |
| DELETE | /api/tarefas/{id}           | Remove tarefa                  |

**Todas requerem** `Authorization: Bearer <token>`

Exemplo de criação:
```json
POST /api/tarefas
{ "titulo": "Estudar Laravel" }
```

Resposta:
```json
{ "id": 1, "titulo": "Estudar Laravel", "concluida": false, "usuarioId": 3 }
```

---

## 📄 Endpoints de Logs (Serviço 2 — :8002)

| Método | Rota                        | Descrição                    |
|--------|-----------------------------|------------------------------|
| POST   | /api/logs                   | Registra log (interno)       |
| GET    | /api/logs                   | Lista todos os logs          |
| GET    | /api/logs/usuario/{id}      | Logs por usuário             |

Rotas de escrita protegidas pelo header `X-Internal-Secret`.

Ações registradas:
- `REGISTRO` — novo usuário criado
- `LOGIN` — usuário autenticado
- `ACESSO_NEGADO` — tentativa inválida
- `CRIACAO_TAREFA` — tarefa criada
- `CONCLUSAO_TAREFA` — tarefa concluída/reaberta
- `EXCLUSAO_TAREFA` — tarefa removida

---

## 📊 Endpoints de Análise (Serviço 3 — :8001)

| Método | Rota                        | Descrição                         |
|--------|-----------------------------|-----------------------------------|
| GET    | /api/health                 | Health check                      |
| GET    | /api/analise/{usuarioId}    | Estatísticas do usuário           |
| POST   | /api/analise/sincronizar    | Sincroniza snapshot (interno)     |
| GET    | /api/analise                | Todos os snapshots (admin)        |

Exemplo de resposta:
```json
{ "usuarioId": 1, "total": 10, "concluidas": 6, "pendentes": 4 }
```

Documentação Swagger disponível em: http://localhost:8001/docs

---

## 🔗 Comunicação entre Serviços

```
Serviço 1  →  POST /api/logs              →  Serviço 2  (a cada operação)
Serviço 1  →  POST /api/analise/sincronizar →  Serviço 3  (a cada operação)
Frontend   →  GET  /api/analise/{id}       →  Serviço 3  (estatísticas)
```

A comunicação interna é protegida pelo header `X-Internal-Secret`.
Se um serviço estiver fora do ar, os outros continuam funcionando (falha silenciosa com log de aviso).

---

## 🗃️ Modelos de Dados

### Serviço 1 — users

| Campo          | Tipo    | Descrição              |
|----------------|---------|------------------------|
| id             | integer | PK                     |
| nome           | string  |                        |
| email          | string  | único                  |
| senha          | string  | hash bcrypt            |
| created_at     | datetime|                        |

### Serviço 1 — tarefas

| Campo          | Tipo    | Descrição              |
|----------------|---------|------------------------|
| id             | integer | PK                     |
| titulo         | string  |                        |
| concluida      | boolean | default false          |
| usuario_id     | integer | FK → users.id          |
| created_at     | datetime|                        |

### Serviço 2 — logs

| Campo          | Tipo    | Descrição              |
|----------------|---------|------------------------|
| id             | integer | PK                     |
| acao           | string  | tipo do evento         |
| detalhe        | string  | descrição              |
| usuario_id     | integer | nullable               |
| created_at     | datetime| timestamp do evento    |

### Serviço 3 — snapshot_tarefas

| Campo          | Tipo    | Descrição              |
|----------------|---------|------------------------|
| id             | integer | PK                     |
| usuario_id     | integer | único por usuário      |
| total          | integer |                        |
| concluidas     | integer |                        |
| pendentes      | integer |                        |
| atualizado_em  | datetime|                        |

---

## ✅ Requisitos Atendidos

| Requisito                              | Status |
|----------------------------------------|--------|
| PHP/Laravel (Serviços 1 e 2)           | ✅     |
| ORM em todos os serviços               | ✅ Eloquent (S1, S2) + SQLAlchemy (S3) |
| Autenticação (Laravel Sanctum)         | ✅     |
| Autorização (rotas protegidas)         | ✅     |
| Cadastro de usuário                    | ✅     |
| Login com token                        | ✅     |
| Cada usuário gerencia só suas tarefas  | ✅     |
| Serviço de Logs (Eloquent)             | ✅     |
| Serviço de Análise (SQLAlchemy)        | ✅     |
| Comunicação entre serviços             | ✅     |
| Integração com frontend fornecido      | ✅     |
| Sem SQL puro                           | ✅     |
| Relacionamento 1:N (usuário→tarefas)   | ✅     |

---

## 📁 Estrutura de Arquivos

```
todolist/
├── docker-compose.yml
├── README.md
│
├── servico1-laravel/           ← API Tarefas + Auth
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── AuthController.php
│   │   │   └── TarefaController.php
│   │   └── Models/
│   │       ├── User.php
│   │       └── Tarefa.php
│   ├── bootstrap/app.php
│   ├── config/
│   │   ├── cors.php
│   │   └── services.php
│   ├── database/migrations/
│   │   ├── ..._create_users_table.php
│   │   └── ..._create_tarefas_table.php
│   ├── routes/api.php
│   ├── Dockerfile
│   ├── composer.json
│   └── .env.example
│
├── servico2-laravel-logs/      ← Gerador de Logs
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/LogController.php
│   │   │   └── Middleware/InternalSecret.php
│   │   └── Models/Log.php
│   ├── bootstrap/app.php
│   ├── config/
│   │   ├── cors.php
│   │   └── services.php
│   ├── database/migrations/
│   │   └── ..._create_logs_table.php
│   ├── routes/api.php
│   ├── Dockerfile
│   ├── composer.json
│   └── .env.example
│
├── servico3-python/            ← Analisador (FastAPI + SQLAlchemy)
│   ├── main.py
│   ├── database.py
│   ├── models.py
│   ├── schemas.py
│   ├── crud.py
│   ├── Dockerfile
│   ├── requirements.txt
│   └── .env.example
│
└── frontend/                   ← Frontend integrado
    ├── index.html
    ├── css/styles.css
    └── js/
        ├── api.js
        ├── app.js
        ├── authService.js
        └── todoService.js
```
