import os
from pymongo import MongoClient
from dotenv import load_dotenv

backend_env_path = os.path.join(os.path.dirname(__file__), "..", "backend", ".env")
load_dotenv(dotenv_path=backend_env_path)

client = MongoClient(os.getenv("MONGODB_URI"))
db = client["medical_doc_engine"]
jobs = db["processing_jobs"]

# Get the most recent job
latest_job = jobs.find_one(sort=[("created_at", -1)])

if latest_job:
    print("=== RAW TEXT ===")
    print(repr(latest_job.get("raw_text", "")))
    print("=== ENTITIES ===")
    print(latest_job.get("entities", {}))
    print("=== STATUS ===")
    print(latest_job.get("status"))
    print("=== ERROR ===")
    print(latest_job.get("error"))
else:
    print("No jobs found")
