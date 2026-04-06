"""
Modelos SQLAlchemy para o Serviço 3.

Este serviço mantém um snapshot das contagens por usuário,
atualizado via chamadas internas do Serviço 1.
"""

from sqlalchemy import Column, Integer, DateTime, func
from database import Base


class SnapshotTarefas(Base):
    """
    Tabela de snapshot: armazena contagens agregadas por usuário.
    Permite consultas rápidas sem acessar o banco do Serviço 1.
    """
    __tablename__ = "snapshot_tarefas"

    id          = Column(Integer, primary_key=True, index=True)
    usuario_id  = Column(Integer, unique=True, index=True, nullable=False)
    total       = Column(Integer, default=0, nullable=False)
    concluidas  = Column(Integer, default=0, nullable=False)
    pendentes   = Column(Integer, default=0, nullable=False)
    atualizado_em = Column(DateTime, default=func.now(), onupdate=func.now())
