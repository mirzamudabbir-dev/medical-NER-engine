"""
Run once before starting the AI service to create the required tables.
Usage:
    python create_tables.py
"""
from sqlalchemy import text
from database import sync_engine

TABLES = [
    """
    CREATE TABLE IF NOT EXISTS ai_processing_jobs (
        id          SERIAL PRIMARY KEY,
        job_id      VARCHAR(36)  NOT NULL UNIQUE,
        status      VARCHAR(20)  NOT NULL DEFAULT 'processing',
        filename    VARCHAR(255),
        raw_text    TEXT,
        confidence  FLOAT,
        entities    TEXT,
        icd_codes   TEXT,
        error       TEXT,
        created_at  TIMESTAMP    NOT NULL,
        updated_at  TIMESTAMP    NOT NULL
    )
    """,
    "CREATE INDEX IF NOT EXISTS idx_job_id ON ai_processing_jobs (job_id)",
    "CREATE INDEX IF NOT EXISTS idx_status  ON ai_processing_jobs (status)",
    """
    CREATE TABLE IF NOT EXISTS icd_codes (
        id        SERIAL PRIMARY KEY,
        icd_code  VARCHAR(20)  NOT NULL,
        disease   VARCHAR(500) NOT NULL
    )
    """,
    "CREATE INDEX IF NOT EXISTS idx_disease ON icd_codes (disease)",
]

with sync_engine.begin() as conn:
    for statement in TABLES:
        conn.execute(text(statement.strip()))

print("Tables created (or already exist).")
