import os
import json
from datetime import datetime
from sqlalchemy.ext.asyncio import create_async_engine
from sqlalchemy import create_engine, text
from dotenv import load_dotenv

# Load from ai_service/.env first; fall back to backend/.env (shared deployment)
_ai_env = os.path.join(os.path.dirname(__file__), ".env")
_backend_env = os.path.join(os.path.dirname(__file__), "..", "backend", ".env")
load_dotenv(_ai_env if os.path.exists(_ai_env) else _backend_env)

_HOST = os.getenv("DB_HOST", "127.0.0.1")
_PORT = os.getenv("DB_PORT", "3306")
_DB   = os.getenv("DB_DATABASE", "medical_doc_engine")
_USER = os.getenv("DB_USERNAME", "root")
_PASS = os.getenv("DB_PASSWORD", "")

_ASYNC_URL = f"mysql+aiomysql://{_USER}:{_PASS}@{_HOST}:{_PORT}/{_DB}"
_SYNC_URL  = f"mysql+pymysql://{_USER}:{_PASS}@{_HOST}:{_PORT}/{_DB}"

# Async engine for FastAPI endpoints
engine = create_async_engine(_ASYNC_URL, echo=False, pool_pre_ping=True)

# Sync engine for one-off scripts (import_icd_codes, dump_latest)
sync_engine = create_engine(_SYNC_URL, echo=False)


# ── Job helpers ───────────────────────────────────────────────────────────────

async def create_job(job_id: str, filename: str):
    async with engine.begin() as conn:
        await conn.execute(
            text("""
                INSERT INTO ai_processing_jobs (job_id, status, filename, created_at, updated_at)
                VALUES (:job_id, 'processing', :filename, :now, :now)
            """),
            {"job_id": job_id, "filename": filename, "now": datetime.utcnow()},
        )


async def get_job(job_id: str) -> dict | None:
    async with engine.connect() as conn:
        row = (await conn.execute(
            text("SELECT * FROM ai_processing_jobs WHERE job_id = :job_id"),
            {"job_id": job_id},
        )).mappings().first()
    if row is None:
        return None
    job = dict(row)
    for col in ("entities", "icd_codes"):
        if job.get(col) and isinstance(job[col], str):
            job[col] = json.loads(job[col])
    return job


async def update_job(job_id: str, **fields):
    fields["updated_at"] = datetime.utcnow()
    for col in ("entities", "icd_codes"):
        if col in fields and fields[col] is not None:
            fields[col] = json.dumps(fields[col])
    set_clause = ", ".join(f"{k} = :{k}" for k in fields)
    async with engine.begin() as conn:
        await conn.execute(
            text(f"UPDATE ai_processing_jobs SET {set_clause} WHERE job_id = :job_id"),
            {**fields, "job_id": job_id},
        )


# ── ICD lookup ────────────────────────────────────────────────────────────────

async def get_icd_code(disease_name: str) -> str | None:
    async with engine.connect() as conn:
        row = (await conn.execute(
            text("SELECT icd_code FROM icd_codes WHERE LOWER(disease) LIKE LOWER(:pattern) LIMIT 1"),
            {"pattern": f"%{disease_name}%"},
        )).first()
    return row[0] if row else None
