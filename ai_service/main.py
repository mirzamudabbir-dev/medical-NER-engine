import os
# Must be set before torch/paddlepaddle/CoreFoundation get loaded, otherwise
# any subprocess fork (e.g. pdf2image -> poppler) aborts with
# "crashed on child side of fork pre-exec" on macOS.
os.environ.setdefault("OBJC_DISABLE_INITIALIZE_FORK_SAFETY", "YES")

from fastapi import FastAPI, UploadFile, File, BackgroundTasks, Header, HTTPException, Depends
from fastapi.concurrency import run_in_threadpool
from datetime import datetime
import uuid

_API_KEY = os.getenv("API_KEY")

async def verify_api_key(x_api_key: str = Header(..., description="API key for authentication")):
    if not _API_KEY or x_api_key != _API_KEY:
        raise HTTPException(status_code=403, detail="Invalid or missing API key")

from database import create_job, get_job, update_job
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
    """Background task: runs the AI pipeline and writes results to MySQL."""
    try:
        job = await get_job(job_id)
        if job and job.get("status") == "cancelled":
            return

        ext = os.path.splitext(filename)[1].lower() or ".pdf"
        temp_path = f"/tmp/{job_id}{ext}"
        with open(temp_path, "wb") as f:
            f.write(file_bytes)

        # 1. OCR
        text, confidence = await run_in_threadpool(extract_text, temp_path)

        job = await get_job(job_id)
        if job and job.get("status") == "cancelled":
            return

        # 2. NER
        entities = await run_in_threadpool(extract_entities, text)

        job = await get_job(job_id)
        if job and job.get("status") == "cancelled":
            return

        # 3. ICD mapping
        icd_mappings = {}
        for disease in entities["diseases"]:
            icd_code = await map_to_icd(disease)
            if icd_code:
                icd_mappings[disease] = icd_code

        # 4. Persist
        await update_job(
            job_id,
            status="completed",
            raw_text=text,
            confidence=confidence,
            entities=entities,
            icd_codes=icd_mappings,
        )

    except Exception as e:
        await update_job(job_id, status="failed", error=str(e))


@app.post("/process-document", dependencies=[Depends(verify_api_key)])
async def process_document(background_tasks: BackgroundTasks, file: UploadFile = File(...)):
    """Accepts a document upload, creates a job row in MySQL, and queues processing."""
    job_id = str(uuid.uuid4())
    await create_job(job_id, file.filename)
    file_bytes = await file.read()
    background_tasks.add_task(process_pipeline, job_id, file_bytes, file.filename)
    return {"job_id": job_id, "status": "processing", "filename": file.filename}


@app.get("/job-status/{job_id}", dependencies=[Depends(verify_api_key)])
async def job_status(job_id: str):
    job = await get_job(job_id)
    if job:
        return {"job_id": job_id, "status": job.get("status")}
    return {"job_id": job_id, "status": "not_found"}


@app.get("/result/{job_id}", dependencies=[Depends(verify_api_key)])
async def result(job_id: str):
    job = await get_job(job_id)
    if not job:
        return {"error": "Result not ready or job not found", "current_status": "not_found"}
    if job.get("status") in ("completed", "failed", "cancelled"):
        return {"job_id": job_id, "data": job}
    return {"error": "Result not ready", "current_status": job.get("status")}


@app.post("/cancel/{job_id}", dependencies=[Depends(verify_api_key)])
async def cancel_job(job_id: str):
    job = await get_job(job_id)
    if job and job.get("status") == "processing":
        await update_job(job_id, status="cancelled")
        return {"job_id": job_id, "status": "cancelled", "message": "Job cancellation requested."}
    return {"error": "Job not found or already completed/failed/cancelled."}
