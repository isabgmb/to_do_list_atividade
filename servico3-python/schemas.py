"""
Schemas Pydantic para validação de entrada/saída.
"""

from pydantic import BaseModel
from typing import Optional
from datetime import datetime


class EstatisticasResponse(BaseModel):
    usuarioId: int
    total: int
    concluidas: int
    pendentes: int

    class Config:
        from_attributes = True

    @classmethod
    def from_orm_model(cls, obj):
        return cls(
            usuarioId=obj.usuario_id,
            total=obj.total,
            concluidas=obj.concluidas,
            pendentes=obj.pendentes,
        )


class SincronizarPayload(BaseModel):
    """
    Payload enviado pelo Serviço 1 para atualizar o snapshot.
    """
    usuarioId: int
    total: int
    concluidas: int
    pendentes: int
