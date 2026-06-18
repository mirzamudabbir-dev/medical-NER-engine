"""Dev utility: print the most recent processing job from MySQL."""
from sqlalchemy import text
from database import sync_engine

with sync_engine.connect() as conn:
    row = conn.execute(
        text("SELECT * FROM ai_processing_jobs ORDER BY created_at DESC LIMIT 1")
    ).mappings().first()

if row:
    import json
    job = dict(row)
    print("=== STATUS ===")
    print(job.get("status"))
    print("=== RAW TEXT ===")
    print(repr(job.get("raw_text", "")))
    print("=== ENTITIES ===")
    entities = job.get("entities")
    if isinstance(entities, str):
        entities = json.loads(entities)
    print(entities)
    print("=== ERROR ===")
    print(job.get("error"))
else:
    print("No jobs found.")
