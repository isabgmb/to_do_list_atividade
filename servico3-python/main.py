"""
Serviço 3 – Analisador / Contador de Tarefas
FastAPI + SQLAlchemy (ORM obrigatório)

Porta padrão: 8001
"""

from fastapi import FastAPI, Depends, HTTPException, Header
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.orm import Session
from typing import Optional

from database import SessionLocal, engine
import models
import schemas
import crud

# Cria as tabelas se não existirem
models.Base.metadata.create_all(bind=engine)

app = FastAPI(
    title="TodoList – Serviço 3 (Analisador)",
    description="Conta e analisa tarefas por usuário usando SQLAlchemy",
    version="1.0.0",
)

# CORS — permite chamadas do frontend e dos outros serviços
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Em produção, restringir aos domínios necessários
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ──────────────────────────────────────────────
# Dependência: sessão do banco de dados
# ──────────────────────────────────────────────
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


# ──────────────────────────────────────────────
# Verificação de segredo interno
# ──────────────────────────────────────────────
import os

INTERNAL_SECRET = os.getenv("INTERNAL_SECRET", "segredo-interno-123")


def verificar_segredo(x_internal_secret: Optional[str] = Header(None)):
    """
    Protege rotas de escrita chamadas pelos outros serviços.
    """
    if x_internal_secret != INTERNAL_SECRET:
        raise HTTPException(status_code=403, detail="Acesso não autorizado.")


# ──────────────────────────────────────────────
# Rotas
# ──────────────────────────────────────────────

@app.get("/api/health")
def health_check():
    return {"status": "ok", "servico": "analisador"}


@app.get(
    "/api/analise/{usuario_id}",
    response_model=schemas.EstatisticasResponse,
    summary="Retorna estatísticas de tarefas do usuário",
)
def get_estatisticas(usuario_id: int, db: Session = Depends(get_db)):
    """
    Retorna o total, concluídas e pendentes de um usuário específico.
    Dados NUNCA são misturados entre usuários.
    """
    stats = crud.get_estatisticas_usuario(db, usuario_id)
    return stats


@app.post(
    "/api/analise/sincronizar",
    response_model=schemas.EstatisticasResponse,
    summary="Sincroniza/atualiza o snapshot de tarefas de um usuário",
    dependencies=[Depends(verificar_segredo)],
)
def sincronizar_tarefa(payload: schemas.SincronizarPayload, db: Session = Depends(get_db)):
    """
    Chamado pelo Serviço 1 sempre que uma tarefa é criada ou alterada.
    Mantém o snapshot local atualizado para consultas rápidas.
    """
    stats = crud.sincronizar_snapshot(db, payload)
    return stats


@app.get(
    "/api/analise",
    response_model=list[schemas.EstatisticasResponse],
    summary="Lista estatísticas de todos os usuários (uso administrativo)",
)
def listar_todos(db: Session = Depends(get_db)):
    return crud.listar_todos_snapshots(db)
