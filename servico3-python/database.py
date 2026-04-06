"""
Configuração do banco de dados via SQLAlchemy (ORM obrigatório).
"""

import os
from sqlalchemy import create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker

# Suporta SQLite (dev) ou qualquer banco via DATABASE_URL
DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///./todolist_analise.db")

# Para SQLite: connect_args necessário por conta do threading do FastAPI
connect_args = {"check_same_thread": False} if DATABASE_URL.startswith("sqlite") else {}

engine = create_engine(DATABASE_URL, connect_args=connect_args)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

Base = declarative_base()
