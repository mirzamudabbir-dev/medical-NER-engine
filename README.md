# Medical Document Intelligence Engine

An AI-powered system that extracts structured clinical data from medical documents. Upload a PDF or scanned image — it detects the document type, extracts all relevant fields, and auto-fills a review form ready for insurance processing or records management.

---

## Architecture

```
┌─────────────────────────────┐        ┌───────────────────────────────────┐
│   Laravel Backend (PHP)     │        │   FastAPI AI Service (Python)     │
│                             │        │                                   │
│  • Upload & auth            │──────▶ │  1. OCR  (pdfplumber / Tesseract) │
│  • Job status polling       │        │  2. NER  (SciSpacy + regex)       │
│  • Claim form UI (Blade)    │◀────── │  3. ICD  (MongoDB lookup)         │
│  • CSV export               │        │                                   │
└─────────────────────────────┘        └───────────────────────────────────┘
         │                                             │
         └──────────────── MongoDB ───────────────────┘
                    (jobs + ICD-10 codes)
```

| Layer | Stack |
|-------|-------|
| Backend | Laravel 11, PHP 8.2, Blade |
| AI Service | FastAPI, Python 3.12 |
| OCR | pdfplumber (native PDFs) · Tesseract + OpenCV (scanned) |
| NER | SciSpacy `en_ner_bc5cdr_md` + regex |
| Database | MongoDB (jobs, ICD codes) · MySQL (Laravel models) |

---

## Supported Document Types

The engine auto-detects the document type and extracts the appropriate fields. All types are verified at **100% field extraction accuracy** on structured test cases.

### 1. Pathology / Lab Report
CBC, biochemistry, urine, serology reports.

| Field | Example |
|-------|---------|
| Patient name, age, gender | Yashvi M. Patel, 21, Female |
| Referring doctor | Dr. Hiren Shah |
| Collection date | 15-Jun-2024 |
| Lab test names | Hemoglobin (Hb), Total WBC count … |
| Lab test values | 13.00, 10000 … |
| Lab test status | Normal / High / Low |
| Reference ranges | 13.00 – 17.00, 4000 – 11000 … |
| Diagnosis (interpretation) | Anemia |

---

### 2. Discharge Summary
IPD admission/discharge records.

| Field | Example |
|-------|---------|
| Patient name, age, gender | Ramesh Kumar, 52, Male |
| Admission date / Discharge date | 10-May-2024 / 15-May-2024 |
| Duration of stay (auto-computed) | 5 days |
| Primary diagnosis | Acute Myocardial Infarction |
| Secondary diagnosis / comorbidities | Hypertension, Type 2 Diabetes |
| Nature of treatment | Medical Management |
| Room rent category | Semi-Private Ward |
| Total bill / claim amount | ₹85,000 |
| Policy number | POL-2024-99812 |
| Vital signs | BP 130/85, Pulse 72, SpO2 98% |
| Prescriptions (with dosage + timing) | Aspirin 75mg once daily |
| Follow-up instructions | Cardiology review after 2 weeks |

---

### 3. Prescription
OPD prescriptions and e-prescriptions.

| Field | Example |
|-------|---------|
| Patient name, age, gender | Priya Sharma, 34, Female |
| Prescribing doctor | Dr. Anita Mehta |
| Date of service | 18-Jun-2024 |
| Chief complaints | Fever, sore throat, body ache |
| Medications (name + dosage + timing) | Amoxicillin 500mg twice daily |
| Follow-up instructions | Return after 5 days |

---

### 4. Radiology Report
X-ray, MRI, CT scan, ultrasound, echocardiography reports.

| Field | Example |
|-------|---------|
| Patient name, age, gender | John D Souza, 45, Male |
| Radiologist / referring doctor | Dr. Kapoor |
| Date of service | 10-Jun-2024 |
| Findings | T2 hyperintensity in right frontal lobe … |
| Impression / conclusion | Early ischemic changes in right MCA territory |

---

### 5. Insurance Claim Form
Cashless and reimbursement claim forms (TPA / insurer formats).

| Field | Example |
|-------|---------|
| Insured name, age, gender | Vikram Malhotra, 41, Male |
| Policy number | POL-IND-88821 |
| Admission / discharge dates | 05-Jun-2024 / 08-Jun-2024 |
| Primary diagnosis + ICD-10 code | Appendicitis · K37 |
| Nature of treatment | Surgical |
| Total claim amount | ₹1,20,000 |

---

### 6. Consultation / OPD Note
General physician, specialist, and outpatient clinical notes.

| Field | Example |
|-------|---------|
| Patient name, age, gender | Aisha Khan, 29, Female |
| Consulting doctor | Dr. Ravi Patel |
| Date of service | 17-Jun-2024 |
| Vital signs | BP 118/76, Pulse 80, Temp 98.6°F, SpO2 99%, Wt 58 kg |
| Chief complaints | Severe headache, nausea, sensitivity to light |
| Diagnosis | Migraine without aura |
| Prescriptions | Sumatriptan 50mg SOS, Propranolol 40mg once daily |
| Follow-up instructions | Return in 4 weeks |

---

## Common Fields (All Document Types)

These are extracted regardless of document type:

- `patient_name` · `age` · `gender` · `dob`
- `doctor`
- `facility` (hospital/clinic/lab name)
- `facility_address`
- `date_of_service`
- `diseases` (primary diagnosis)
- `secondary_diagnosis`
- `icd_codes` (mapped via MongoDB ICD-10 database)
- `prescriptions` — array of `{ medication, dosage, timing }`
- `claim_amount`
- `cpt_codes`

---

## API Endpoints

Base URL: `http://localhost:8000`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/process-document` | Upload a PDF/JPG/PNG, returns `job_id` |
| `GET` | `/job-status/{job_id}` | Poll processing status |
| `GET` | `/result/{job_id}` | Retrieve extracted data |
| `POST` | `/cancel/{job_id}` | Cancel an in-progress job |

### Upload a document
```bash
curl -X POST http://localhost:8000/process-document \
  -F "file=@discharge_summary.pdf"
# → { "job_id": "uuid", "status": "processing" }
```

### Poll until done
```bash
curl http://localhost:8000/job-status/{job_id}
# → { "status": "completed" }
```

### Get results
```bash
curl http://localhost:8000/result/{job_id}
```

### Response shape
```json
{
  "job_id": "...",
  "data": {
    "status": "completed",
    "confidence": 87.4,
    "raw_text": "...",
    "entities": {
      "document_type": "discharge_summary",
      "patient_name": "Ramesh Kumar",
      "age": "52",
      "gender": "Male",
      "admission_date": "10-May-2024",
      "discharge_date": "15-May-2024",
      "duration_of_stay": "5",
      "diseases": ["Acute Myocardial Infarction"],
      "vital_signs": { "blood_pressure": "130/85", "pulse": "72", "spo2": "98" },
      "prescriptions": [
        { "medication": "Aspirin", "dosage": "75mg", "timing": "once daily" }
      ],
      "...": "..."
    },
    "icd_codes": { "Acute Myocardial Infarction": "I21.9" }
  }
}
```

---

## Installation

### Prerequisites
- Python 3.12
- PHP 8.2 + Composer
- MongoDB
- Tesseract OCR (`brew install tesseract` on macOS)
- Poppler (`brew install poppler` on macOS — needed for PDF→image)

### AI Service
```bash
cd ai_service
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
uvicorn main:app --reload --port 8000
```

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

---

## OCR Pipeline

| Input | Method | Confidence |
|-------|--------|------------|
| Digitally-generated PDF | pdfplumber (native text extraction) | ~99% |
| Scanned PDF | Poppler → OpenCV preprocess → Tesseract | ~75–90% |
| JPG / PNG image | OpenCV preprocess → Tesseract | ~70–90% |

Image preprocessing steps applied before Tesseract:
1. Convert to grayscale
2. `fastNlMeansDenoising` (h=10)
3. `adaptiveThreshold` — Gaussian, block 15, C=8

Tesseract config: `--oem 3 --psm 6` (LSTM engine, uniform block layout).

---

## Notes

- Dates are normalised to `DD-Mon-YYYY` format. Both Indian (`DD/MM/YYYY`) and US (`MM/DD/YYYY`) formats are handled.
- Currency: supports `₹`, `Rs.`, `INR`, and `$`.
- Combined `Age/Sex` fields (e.g. `45/M`) common in Indian medical documents are parsed in a single pass.
- ICD-10 mapping is a MongoDB regex lookup — seed the `icd_codes` collection using `import_icd_codes.py`.
