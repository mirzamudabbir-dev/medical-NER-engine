"""
Run once before starting the AI service to create the required MySQL tables.
Usage:
    python create_tables.py
"""
from sqlalchemy import text
from database import sync_engine

TABLES = """
CREATE TABLE IF NOT EXISTS ai_processing_jobs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    job_id      VARCHAR(36)  NOT NULL UNIQUE,
    status      VARCHAR(20)  NOT NULL DEFAULT 'processing',
    filename    VARCHAR(255),
    raw_text    LONGTEXT,
    confidence  FLOAT,
    entities    JSON,
    icd_codes   JSON,
    error       TEXT,
    created_at  DATETIME     NOT NULL,
    updated_at  DATETIME     NOT NULL,
    INDEX idx_job_id (job_id),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS icd_codes (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    icd_code  VARCHAR(20)  NOT NULL,
    disease   VARCHAR(500) NOT NULL,
    INDEX idx_disease (disease(255))
);
"""

with sync_engine.begin() as conn:
    for statement in TABLES.strip().split(";"):
        statement = statement.strip()
        if statement:
            conn.execute(text(statement))

print("Tables created (or already exist).")
