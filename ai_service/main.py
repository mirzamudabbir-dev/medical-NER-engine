import os
# Must be set before torch/paddlepaddle/CoreFoundation get loaded, otherwise
# any subprocess fork (e.g. pdf2image -> poppler) aborts with
# "crashed on child side of fork pre-exec" on macOS.
os.environ.setdefault("OBJC_DISABLE_INITIALIZE_FORK_SAFETY", "YES")

from fastapi import FastAPI, UploadFile, File, BackgroundTasks
from fastapi.concurrency import run_in_threadpool
from datetime import datetime
import uuid

# Import our custom AI models and MongoDB
from database import jobs_collection
from models.ocr import extract_text
from models.ner import extract_entities
from models.icd_mapper import map_to_icd

app = FastAPI(title="Medical Document Intelligence Engine")

@app.on_event("startup")
async def startup_event():
    print("Pre-loading AI models in the main thread to prevent threadpool deadlocks...")
    from models.ocr import get_ocr_model
    from models.ner import get_nlp_model
    get_ocr_model()
    get_nlp_model()
    print("AI models loaded and ready!")

async def process_pipeline(job_id: str, file_bytes: bytes, filename: str):
    """The actual background task that runs the AI models and updates MongoDB."""
    try:
        # Check if cancelled before starting
        job_check = await jobs_collection.find_one({"job_id": job_id})
        if job_check and job_check.get("status") == "cancelled":
            return
            
        # Get file extension
        import os
        ext = os.path.splitext(filename)[1].lower()
        if not ext:
            ext = ".pdf"
            
        # 1. Save file temporarily
        temp_path = f"/tmp/{job_id}{ext}"
        with open(temp_path, "wb") as f:
            f.write(file_bytes)
            
        # 2. Extract Text via OCR (CPU bound, run in threadpool)
        text, confidence = await run_in_threadpool(extract_text, temp_path)
        
        # Check if cancelled after OCR
        job_check = await jobs_collection.find_one({"job_id": job_id})
        if job_check and job_check.get("status") == "cancelled":
            return
            
        # 3. Extract Medical Entities (CPU bound, run in threadpool)
        entities = await run_in_threadpool(extract_entities, text)
        
        # Check if cancelled after NER
        job_check = await jobs_collection.find_one({"job_id": job_id})
        if job_check and job_check.get("status") == "cancelled":
            return
            
        # 4. Map Diseases to ICD codes (Async DB queries)
        icd_mappings = {}
        for disease in entities["diseases"]:
            icd_code = await map_to_icd(disease)
            if icd_code:
                icd_mappings[disease] = icd_code
                
        # 5. Save Results to MongoDB
        await jobs_collection.update_one(
            {"job_id": job_id},
            {"$set": {
                "status": "completed",
                "raw_text": text,
                "confidence": confidence,
                "entities": entities,
                "icd_codes": icd_mappings,
                "updated_at": datetime.utcnow()
            }}
        )
        
    except Exception as e:
        # Mark job as failed in MongoDB
        await jobs_collection.update_one(
            {"job_id": job_id},
            {"$set": {
                "status": "failed",
                "error": str(e),
                "updated_at": datetime.utcnow()
            }}
        )


@app.post("/process-document")
async def process_document(background_tasks: BackgroundTasks, file: UploadFile = File(...)):
    """Receives a document, saves initial state to MongoDB, and starts processing."""
    job_id = str(uuid.uuid4())
    
    # Store initial job state in MongoDB
    await jobs_collection.insert_one({
        "job_id": job_id,
        "status": "processing",
        "filename": file.filename,
        "created_at": datetime.utcnow(),
        "updated_at": datetime.utcnow()
    })
    
    # Read file content into memory
    file_bytes = await file.read()
    
    # Pass to FastAPI Background Tasks
    background_tasks.add_task(process_pipeline, job_id, file_bytes, file.filename)
    
    return {"job_id": job_id, "status": "processing", "filename": file.filename}


@app.get("/job-status/{job_id}")
async def job_status(job_id: str):
    """Returns the current status of a job from MongoDB."""
    job = await jobs_collection.find_one({"job_id": job_id}, {"_id": 0})
    if job:
        return {"job_id": job_id, "status": job.get("status")}
    return {"job_id": job_id, "status": "not_found"}


@app.get("/result/{job_id}")
async def result(job_id: str):
    """Returns the extracted structured data for a completed job from MongoDB."""
    job = await jobs_collection.find_one({"job_id": job_id}, {"_id": 0})
    
    if not job:
        return {"error": "Result not ready or job not found", "current_status": "not_found"}
        
    if job.get("status") in ["completed", "failed", "cancelled"]:
        return {"job_id": job_id, "data": job}
        
@app.post("/cancel/{job_id}")
async def cancel_job(job_id: str):
    """Cancels a processing job."""
    result = await jobs_collection.update_one(
        {"job_id": job_id, "status": "processing"},
        {"$set": {"status": "cancelled", "updated_at": datetime.utcnow()}}
    )
    if result.modified_count == 1:
        return {"job_id": job_id, "status": "cancelled", "message": "Job cancellation requested."}
    return {"error": "Job not found or already completed/failed/cancelled."}
        
    return {"error": "Result not ready", "current_status": job.get("status")}
