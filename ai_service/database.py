import os
from motor.motor_asyncio import AsyncIOMotorClient
from dotenv import load_dotenv

# Load environment variables from the backend's .env file
backend_env_path = os.path.join(os.path.dirname(__file__), "..", "backend", ".env")
load_dotenv(dotenv_path=backend_env_path)

MONGODB_URI = os.getenv("MONGODB_URI")
MONGODB_DATABASE = os.getenv("MONGODB_DATABASE", "medical_doc_engine")

# Initialize the async MongoDB client
client = AsyncIOMotorClient(MONGODB_URI)
db = client[MONGODB_DATABASE]

# Collections
jobs_collection = db.get_collection("processing_jobs")
icd_collection = db.get_collection("icd_codes")
