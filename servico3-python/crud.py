"""
Operações de banco de dados via SQLAlchemy ORM.
Nenhuma SQL pura — tudo via ORM.
"""

from sqlalchemy.orm import Session
from fastapi import HTTPException

import models
import schemas


def get_estatisticas_usuario(db: Session, usuario_id: int) -> schemas.EstatisticasResponse:
    """
    Retorna as estatísticas de tarefas de um usuário específico.
    Dados são filtrados por usuario_id — nunca mistura usuários.
    """
    snapshot = (
        db.query(models.SnapshotTarefas)
        .filter(models.SnapshotTarefas.usuario_id == usuario_id)
        .first()
    )

    if not snapshot:
        # Usuário ainda não tem snapshot: retorna zeros
        return schemas.EstatisticasResponse(
            usuarioId=usuario_id,
            total=0,
            concluidas=0,
            pendentes=0,
        )

    return schemas.EstatisticasResponse(
        usuarioId=snapshot.usuario_id,
        total=snapshot.total,
        concluidas=snapshot.concluidas,
        pendentes=snapshot.pendentes,
    )


def sincronizar_snapshot(db: Session, payload: schemas.SincronizarPayload) -> schemas.EstatisticasResponse:
    """
    Cria ou atualiza o snapshot de um usuário.
    Chamado pelo Serviço 1 após cada operação de tarefa.
    """
    snapshot = (
        db.query(models.SnapshotTarefas)
        .filter(models.SnapshotTarefas.usuario_id == payload.usuarioId)
        .first()
    )

    if snapshot:
        # Atualiza registro existente via ORM
        snapshot.total      = payload.total
        snapshot.concluidas = payload.concluidas
        snapshot.pendentes  = payload.pendentes
    else:
        # Cria novo snapshot via ORM
        snapshot = models.SnapshotTarefas(
            usuario_id  = payload.usuarioId,
            total       = payload.total,
            concluidas  = payload.concluidas,
            pendentes   = payload.pendentes,
        )
        db.add(snapshot)

    db.commit()
    db.refresh(snapshot)

    return schemas.EstatisticasResponse(
        usuarioId=snapshot.usuario_id,
        total=snapshot.total,
        concluidas=snapshot.concluidas,
        pendentes=snapshot.pendentes,
    )


def listar_todos_snapshots(db: Session) -> list[schemas.EstatisticasResponse]:
    """
    Lista snapshots de todos os usuários (uso administrativo).
    """
    snapshots = db.query(models.SnapshotTarefas).all()

    return [
        schemas.EstatisticasResponse(
            usuarioId=s.usuario_id,
            total=s.total,
            concluidas=s.concluidas,
            pendentes=s.pendentes,
        )
        for s in snapshots
    ]
