import re
import spacy
from datetime import datetime
import dateutil.parser as dparser

nlp = None


def get_nlp_model():
    global nlp
    if nlp is None:
        print("Initializing SciSpacy NER...")
        nlp = spacy.load("en_ner_bc5cdr_md")
    return nlp


# ── Document type classifier ──────────────────────────────────────────────────

def _classify_document_type(text: str) -> str:
    tl = text.lower()
    scores = {
        "pathology": sum(1 for kw in [
            "hemoglobin", "haemoglobin", "wbc", "rbc", "platelet", "cbc",
            "complete blood", "reference value", "pathology", "lab report",
            "investigation", "serum", "urine", "hematology", "test result",
        ] if kw in tl),
        "discharge_summary": sum(1 for kw in [
            "discharge summary", "date of discharge", "dod", "doa",
            "date of admission", "admitted on", "discharged on",
            "condition at discharge", "final diagnosis", "discharge advice",
            "nature of treatment", "duration of stay", "primary diagnosis",
            "secondary diagnosis", "room rent", "room type", "room category",
        ] if kw in tl),
        "prescription": sum(1 for kw in [
            "prescription", "rx", "tablet", "capsule", "syrup", "dosage",
            "sig:", "once daily", "twice daily", "sos", "prn", "refill",
        ] if kw in tl),
        "radiology": sum(1 for kw in [
            "x-ray", "xray", "mri", "ct scan", "ultrasound", "sonography",
            "impression:", "findings:", "radiologist", "echocardiography",
            "echo report", "scan report", "mammography",
        ] if kw in tl),
        "claim_form": sum(1 for kw in [
            "claim form", "policy number", "insured", "insurance", "tpa",
            "cashless", "pre-authorization", "reimbursement", "claim no",
            "sum insured",
        ] if kw in tl),
        "consultation": sum(1 for kw in [
            "opd", "outpatient", "consultation", "chief complaint", "history of",
            "examination", "bp:", "pulse:", "spo2", "vitals", "clinical notes",
        ] if kw in tl),
    }
    best = max(scores, key=scores.get)
    return best if scores[best] > 0 else "general"


# ── Helpers ───────────────────────────────────────────────────────────────────

def _clean(val: str) -> str:
    """Strip trailing noise (pipe chars, extra punctuation) from a regex match."""
    if not val:
        return val
    val = re.sub(r'\s*[|│]\s*.*$', '', val)
    return val.strip(' \t\r,.;:')


def _parse_date(raw: str) -> str:
    """Normalise common medical date formats including DD/MM/YYYY (Indian standard)."""
    if not raw:
        return raw
    raw = raw.strip()
    # Explicit DD/MM/YYYY check before dateutil, which defaults to MM/DD
    dd_mm_yyyy = re.match(r'^(\d{1,2})[/\-\.](\d{1,2})[/\-\.](\d{2,4})$', raw)
    if dd_mm_yyyy:
        d, m, y = dd_mm_yyyy.groups()
        y = "20" + y if len(y) == 2 else y
        try:
            return datetime(int(y), int(m), int(d)).strftime("%d-%b-%Y")
        except ValueError:
            pass
    try:
        return dparser.parse(raw, dayfirst=True, fuzzy=False).strftime("%d-%b-%Y")
    except Exception:
        return raw


# ── Vital signs ───────────────────────────────────────────────────────────────

def _extract_vital_signs(text: str) -> dict:
    vs = {}
    bp = re.search(r'(?i)(?:BP|Blood\s+Pressure)\s*[:\-]?\s*(\d{2,3}\s*/\s*\d{2,3})', text)
    if bp:
        vs["blood_pressure"] = bp.group(1).replace(' ', '')
    pulse = re.search(r'(?i)(?:Pulse|PR|Heart\s+Rate)\s*[:\-]?\s*(\d{2,3})\s*(?:bpm|/min)?', text)
    if pulse:
        vs["pulse"] = pulse.group(1)
    temp = re.search(r'(?i)(?:Temp(?:erature)?)\s*[:\-]?\s*([\d\.]+)\s*(?:°?[CF])?', text)
    if temp:
        vs["temperature"] = temp.group(1)
    spo2 = re.search(r'(?i)(?:SpO2|Oxygen\s+Saturation|O2\s+Sat)\s*[:\-]?\s*(\d{2,3})\s*%?', text)
    if spo2:
        vs["spo2"] = spo2.group(1)
    weight = re.search(r'(?i)(?:Weight|Wt)\s*[:\-]?\s*([\d\.]+)\s*(?:kg|lbs?)?', text)
    if weight:
        vs["weight"] = weight.group(1)
    height = re.search(r'(?i)(?:Height|Ht)\s*[:\-]?\s*([\d\.]+)\s*(?:cm|ft|m)?', text)
    if height:
        vs["height"] = height.group(1)
    return vs


# ── Main extraction ───────────────────────────────────────────────────────────

def extract_entities(text: str):
    doc = get_nlp_model()(text)

    doc_type = _classify_document_type(text)

    entities = {
        "document_type": doc_type,
        "diseases": [],
        "chemicals": [],
        "patient_name": None,
        "dob": None,
        "age": None,
        "gender": None,
        "doctor": None,
        "dos": None,
        "facility": None,
        "facility_address": None,
        "admission_date": None,
        "discharge_date": None,
        "duration_of_stay": None,
        "secondary_diagnosis": None,
        "nature_of_treatment": None,
        "chief_complaints": None,
        "cpt_codes": None,
        "procedure": None,
        "room_rent_category": None,
        "itemised_bill_totals": None,
        "follow_up_instructions": None,
        "prescriptions": [],
        "lab_test_names": None,
        "lab_test_values": None,
        "lab_test_status": None,
        "lab_test_reference_ranges": None,
        "claim_amount": None,
        "vital_signs": {},
        "policy_number": None,
        "radiology_findings": None,
        "radiology_impression": None,
    }

    # ── SciSpacy medical NER ───────────────────────────────────────────────────
    for ent in doc.ents:
        if ent.label_ == "DISEASE":
            entities["diseases"].append(ent.text)
        elif ent.label_ == "CHEMICAL":
            entities["chemicals"].append(ent.text)

    entities["diseases"] = list(dict.fromkeys(entities["diseases"]))
    entities["chemicals"] = list(dict.fromkeys(entities["chemicals"]))
    _exclusions = {"Sr", "UC", "mg", "ml", "No", "Dr", "kg", "g/dL", "mL", "Tab", "Cap"}
    entities["chemicals"] = [c for c in entities["chemicals"] if c not in _exclusions and len(c) > 2]

    # ── Age + combined Age/Sex field ──────────────────────────────────────────
    # "Age/Sex: 45/M" or "45/F" — extremely common in Indian medical documents
    age_sex = re.search(r'(?i)(?:age[/\s]*sex|age)\s*[:\-\/]?\s*(\d{1,3})\s*/\s*([MF])\b', text)
    if age_sex:
        age_val = int(age_sex.group(1))
        if age_val <= 120:
            entities["age"] = str(age_val)
        entities["gender"] = "Male" if age_sex.group(2).upper() == "M" else "Female"
    else:
        age_m = re.search(r'(?i)(?:age|age\s+of\s+patient|y\.?o\.?)[\s\:\-\/]*(\d{1,3})(?:\s*(?:yrs?|years?|y/o|y))?', text)
        if not age_m:
            age_m = re.search(r'(?i)\b(\d{1,3})\s*(?:yrs?|years?\s+old|y/o|y/m|y/f)\b', text)
        if age_m:
            age_val = int(age_m.group(1))
            if age_val > 120:
                age_val = int(str(age_val)[-2:])
            if age_val <= 120:
                entities["age"] = str(age_val)

    # ── Gender (standalone) ───────────────────────────────────────────────────
    if not entities["gender"]:
        g_m = re.search(r'(?i)(?:gender|sex)\s*[:\-]?\s*([A-Za-z]+)', text)
        if g_m:
            g = g_m.group(1).lower()
            if g in ("male", "m", "mate", "mal"):
                entities["gender"] = "Male"
            elif g in ("female", "f", "femaie", "femate", "femaje", "fem"):
                entities["gender"] = "Female"
        if not entities["gender"]:
            if re.search(r'(?i)\b(?:mate|male)\s+patient\b', text):
                entities["gender"] = "Male"
            elif re.search(r'(?i)\bfemale\s+patient\b', text):
                entities["gender"] = "Female"

    # ── Patient name ──────────────────────────────────────────────────────────
    name_m = re.search(
        r'(?i)(?:patient(?:\s+name)?|name(?:\s+of\s+(?:the\s+)?patient)?)\s*[\:\-]+\s*'
        r'([A-Za-z][A-Za-z\s\.\']+?)(?=,|\s+age|\s+sex|\s+dob|\n|$)',
        text,
    )
    if not name_m:
        name_m = re.search(
            r'(?i)\b(?:mr\.|mrs\.|ms\.|mast\.|master)\s+([A-Za-z\s\.\']+?)(?=,|\s+age|\s+sex|\s+dob|\n|$)',
            text,
        )
    if name_m and len(name_m.group(1).strip()) > 2 and not re.search(r'\d', name_m.group(1)):
        entities["patient_name"] = name_m.group(1).strip()
    else:
        alt = re.search(r'(?i)patient\s+(?:mr\.?|mrs\.?|ms\.?)?\s*([A-Za-z\s\.]+)\s+admitted', text)
        if alt:
            entities["patient_name"] = alt.group(1).strip()

    # Floating-name fallback: capitalised words near age/sex/uhid context
    if not entities.get("patient_name"):
        lines = text.split('\n')[:15]
        for i, line in enumerate(lines):
            nm = re.search(r'^([A-Z][a-z]+(?:\s+[A-Z]\.?\s*)?(?:\s+[A-Z][a-z]+)+)', line.strip())
            if nm:
                ctx = " ".join(lines[i + 1:i + 4]).lower()
                if any(kw in ctx for kw in ("age", "sex", "uhid", "gender", "dob")):
                    raw = nm.group(1).strip()
                    entities["patient_name"] = re.split(r'\s{2,}', raw)[0]
                    break

    # ── Doctor ────────────────────────────────────────────────────────────────
    doc_m = re.search(
        r'(?i)(?:Ref(?:erred)?\s*By|Attending\s+(?:Physician|Doctor)|Consulting\s+(?:Doctor|Physician)|'
        r'Doctor|Physician|Dr\.?|Cardiologist)\s*[\s:]+([A-Za-z][A-Za-z\s\.]+?)(?:\n|$|,|MD|MS|MBBS|DM|DNB)',
        text,
    )
    if doc_m and len(doc_m.group(1).strip()) > 3:
        raw_doc = doc_m.group(1).strip().rstrip('.,')
        entities["doctor"] = raw_doc if re.match(r'(?i)^dr\.?\s', raw_doc) else "Dr. " + raw_doc

    # ── Claim / bill amount ───────────────────────────────────────────────────
    amt_m = re.search(
        r'(?i)(?:total\s+(?:bill|amount|charges?)|grand\s+total|amount\s+(?:payable|claimed?))\s*[:\-]?\s*'
        r'(?:₹|Rs\.?|INR|USD|\$)?\s*([\d,]+(?:\.\d{1,2})?)',
        text,
    )
    if not amt_m:
        amt_m = re.search(r'(?:₹|Rs\.?)\s*([\d,]+(?:\.\d{1,2})?)', text)
    if not amt_m:
        amt_m = re.search(r'(?i)(?:total|amount|cost)\s*[:\-]?\s*[\$]?\s*([\d,\.]+)', text)
    if amt_m:
        entities["claim_amount"] = amt_m.group(1).replace(',', '')

    # ── Date of service ───────────────────────────────────────────────────────
    dos_m = re.search(
        r'(?i)(?:Date\s+of\s+Service|Collection\s+Date|Sample\s+Collected\s+(?:On|Date)|'
        r'Collected\s+On|Report\s+Date|Date\s+Collected)\s*[:\-]?\s*'
        r'([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4}|[A-Za-z]+\s+\d+[,\s]+\d{4})',
        text,
    )
    if dos_m:
        entities["dos"] = _parse_date(dos_m.group(1).strip())

    # ── Facility ──────────────────────────────────────────────────────────────
    fac_m = re.search(
        r'(?i)^(.*?(?:Hospital|Clinic|Medical\s+Cent(?:re|er)|Health(?:care)?|'
        r'Diagnostics?|Pathology|Laboratory|Labs?))(?:[\s\n]|$)',
        text, re.MULTILINE,
    )
    if fac_m:
        entities["facility"] = fac_m.group(1).strip()

    # ── DOB ───────────────────────────────────────────────────────────────────
    dob_m = re.search(
        r'(?i)(?:DOB|Date\s+of\s+Birth)\s*[:\-]?\s*'
        r'([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4}|\d+[- ][A-Za-z]+[- ]\d{2,4})',
        text,
    )
    if dob_m:
        entities["dob"] = _parse_date(dob_m.group(1).strip())

    # ── Facility address ──────────────────────────────────────────────────────
    addr_m = re.search(
        r'(?i)(?:Hospital\s+Address|Facility\s+Address|Address|Location|Add\.)\s*[:\-]+\s*([^\n]+)',
        text,
    )
    if addr_m and len(addr_m.group(1).strip()) > 5:
        entities["facility_address"] = addr_m.group(1).strip()
    else:
        alt_addr = re.search(
            r'(?i)(?:[0-9]+[a-z]?|Plot\s*No|Block\s*No|Flat)[\s\S]{10,150}?'
            r'(?:street|road|rd|avenue|ave|marg|lane)[^\n]*?(?:\d{5,6}|\b(?:NY|CA|TX|IL|FL|WA|PA|M\.S|M\.H)\b)',
            text,
        )
        if alt_addr:
            entities["facility_address"] = alt_addr.group(0).replace('\n', ' ').strip()

    # ── Admission date ────────────────────────────────────────────────────────
    adm_m = re.search(
        r'(?i)(?:DOA|Date\s+of\s+Admission|Admitted\s+(?:On|Date))\s*[:\-]?\s*'
        r'([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4}|\d+[- ][A-Za-z]+[- ]\d{2,4})',
        text,
    )
    if adm_m:
        entities["admission_date"] = _parse_date(adm_m.group(1).strip())

    # ── Discharge date ────────────────────────────────────────────────────────
    dis_m = re.search(
        r'(?i)(?:DOD|Date\s+of\s+Discharge|Discharged\s+(?:On|Date))\s*[:\-]?\s*'
        r'([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4}|\d+[- ][A-Za-z]+[- ]\d{2,4})',
        text,
    )
    if dis_m:
        entities["discharge_date"] = _parse_date(dis_m.group(1).strip())

    # ── Duration of stay ──────────────────────────────────────────────────────
    los_m = re.search(r'(?i)(?:Duration\s+of\s+Stay|Length\s+of\s+Stay|LOS)\s*[:\-]?\s*([^\n]+)', text)
    if los_m:
        entities["duration_of_stay"] = _clean(los_m.group(1))

    # ── Secondary diagnosis ───────────────────────────────────────────────────
    sec_m = re.search(r'(?i)(?:Secondary\s+Diagnosis|Comorbidities|Co-morbidities)\s*[:\-]?\s*([^\n]+)', text)
    if sec_m:
        entities["secondary_diagnosis"] = _clean(sec_m.group(1))

    # ── Nature of treatment ───────────────────────────────────────────────────
    treat_m = re.search(
        r'(?i)(?:Nature\s+of\s+Treatment|Treatment\s+Type|Procedure\s+Type|Management)\s*[:\-]?\s*([^\n]+)',
        text,
    )
    if treat_m:
        entities["nature_of_treatment"] = _clean(treat_m.group(1))

    # ── Procedure ─────────────────────────────────────────────────────────────
    proc_m = re.search(
        r'(?i)(?:Procedure\s+(?:Name|Performed|Done)|Operation\s+Performed|Surgery\s+(?:Name|Done))\s*[:\-]?\s*([^\n]+)',
        text,
    )
    if proc_m:
        entities["procedure"] = _clean(proc_m.group(1))

    # ── Chief complaints ──────────────────────────────────────────────────────
    chief_m = re.search(
        r'(?i)(?:Chief\s+Complaints?|Presenting\s+Complaints?|Complaints?)\s*[:\-]?\s*([^\n]+(?:\n(?!\n).{1,80})?)',
        text,
    )
    if chief_m:
        entities["chief_complaints"] = _clean(chief_m.group(1).replace('\n', '; '))

    # ── CPT codes ─────────────────────────────────────────────────────────────
    cpt_m = re.search(r'(?i)(?:CPT|Procedure\s+Code)\s*[:\-]?\s*([\d\w, ]+)', text)
    if cpt_m:
        entities["cpt_codes"] = cpt_m.group(1).strip()

    # ── Room category ─────────────────────────────────────────────────────────
    room_m = re.search(
        r'(?i)(?:Room\s+(?:Rent\s+)?Category|Room\s+Type|Ward\s+Type|Ward)\s*[:\-]?\s*([^\n]+)',
        text,
    )
    if room_m:
        entities["room_rent_category"] = _clean(room_m.group(1))

    # ── Itemised bill ─────────────────────────────────────────────────────────
    bill_m = re.search(
        r'(?i)(?:Itemis[ae]d\s+Bill|Bill\s+Breakdown|Room\s+Charges|Surgical\s+Charges)\s*[:\-]?\s*([^\n]+)',
        text,
    )
    if bill_m:
        entities["itemised_bill_totals"] = _clean(bill_m.group(1))

    # ── Follow-up instructions ────────────────────────────────────────────────
    fup_m = re.search(
        r'(?i)(?:Follow[\s\-]?up|Discharge\s+Advice|Plan)\s*[:\-]?\s*([^\n]+(?:\n(?!\n).{1,120})?)',
        text,
    )
    if fup_m:
        entities["follow_up_instructions"] = _clean(fup_m.group(1).replace('\n', '; '))

    # ── Prescriptions ─────────────────────────────────────────────────────────
    rx_m = re.search(
        r'(?i)(?:Prescriptions?|Medications?|Rx|Discharge\s+Medications?)\s*:\s*([^\n]+)',
        text,
    )
    if rx_m:
        raw_rx = rx_m.group(1).strip()
        meds = [m.strip() for m in raw_rx.split(',')]
        timing_pat = (
            r'(?i)\s+(every|twice|once|times?|bid|tid|qid|daily|prn|morning|night|hs|am|pm|'
            r'\d+\s+times|\d+\s+puffs?|at\s+bedtime|with\s+meals|sos|od|bd|tds|qds)'
        )
        parsed = []
        for med in meds:
            dose_m2 = re.search(r'(\d+\s*(?:mg|mcg|ml|g|unit))', med, re.IGNORECASE)
            tm = re.search(timing_pat, med)
            parsed.append({
                "medication": med[:tm.start()].strip() if tm else med,
                "dosage": dose_m2.group(1) if dose_m2 else "",
                "timing": med[tm.start():].strip() if tm else "",
            })
        entities["prescriptions"] = parsed
    elif entities["chemicals"]:
        entities["prescriptions"] = [{"medication": c, "dosage": "", "timing": ""} for c in entities["chemicals"]]

    # ── Lab tests: name, value, status, reference range ──────────────────────
    lab_sec = re.search(
        r'(?i)(?:lab\s*(?:tests?|report|results?)|investigations?|cbc\s*(?:report)?|'
        r'complete\s*blood\s*(?:count|test)|blood\s*(?:indices|count|test)|'
        r'haematology|hematology|biochemistry|test\s*results?)[^\n]*\n'
        r'([\s\S]+?)(?=\Z|diagnosis|doctor|interpretation|instruments|thanks|end\s*of\s*report|radiology|discharge)',
        text,
    )
    if lab_sec:
        skip_re = re.compile(
            r'(?i)\b(?:result|reference|unit|sample|blood|electrical|immunoturbidimetry|'
            r'calculated|method|instrument|analysed|reported)\b'
        )
        lab_names, lab_values, lab_statuses, lab_refs = [], [], [], []
        for line in lab_sec.group(1).split('\n'):
            line = line.strip()
            if not line or skip_re.search(line):
                continue
            # Tabular line: Name  Value  [Status]  [RefRange]
            tab = re.match(
                r'^([a-zA-Z0-9\s\(\)\[\]\,\.\-\'\%\/]+?)\s+'
                r'([\d\.]+[a-zA-Z%]*)\s*'
                r'(?:(Normal|High|Low|Positive|Negative|Reactive|Non\s*Reactive)\s*)?'
                r'([\d\.]+ *[-–] *[\d\.]+)?',
                line,
            )
            if tab and len(tab.group(1).strip()) > 2:
                lab_names.append(tab.group(1).strip())
                lab_values.append(tab.group(2).strip())
                lab_statuses.append((tab.group(3) or "").strip())
                lab_refs.append((tab.group(4) or "").strip())
            else:
                parts = re.split(r'\s*[-:]\s*', line, 1)
                if len(parts) == 2 and parts[0] and parts[1]:
                    lab_names.append(parts[0].strip())
                    lab_values.append(parts[1].strip())
                    lab_statuses.append("")
                    lab_refs.append("")

        if lab_names:
            entities["lab_test_names"] = "\n".join(lab_names)
            entities["lab_test_values"] = "\n".join(lab_values)
            entities["lab_test_status"] = "\n".join(lab_statuses)
            entities["lab_test_reference_ranges"] = "\n".join(lab_refs)

    # ── Radiology: findings and impression ────────────────────────────────────
    findings_m = re.search(
        r'(?i)(?:Findings?|Observations?)\s*[:\-]?\s*([\s\S]+?)(?=Impression|Conclusion|Diagnosis|$)',
        text,
    )
    if findings_m:
        entities["radiology_findings"] = findings_m.group(1).strip()[:500]

    impression_m = re.search(
        r'(?i)(?:Impression|Conclusion|Opinion)\s*[:\-]?\s*([\s\S]+?)(?=\n\n|Recommendation|Radiologist|Referring|$)',
        text,
    )
    if impression_m:
        entities["radiology_impression"] = impression_m.group(1).strip()[:300]

    # ── Insurance / claim fields ──────────────────────────────────────────────
    policy_m = re.search(
        r'(?i)(?:Policy\s+(?:No\.?|Number|#)|Policy\s+ID)\s*[:\-]?\s*([\w\-\/]+)',
        text,
    )
    if policy_m:
        entities["policy_number"] = policy_m.group(1).strip()

    # ── Vital signs ───────────────────────────────────────────────────────────
    entities["vital_signs"] = _extract_vital_signs(text)

    # ── Auto-compute duration of stay when both dates are present ─────────────
    if not entities["duration_of_stay"] and entities["admission_date"] and entities["discharge_date"]:
        try:
            ad = dparser.parse(entities["admission_date"], fuzzy=True, dayfirst=True)
            dd = dparser.parse(entities["discharge_date"], fuzzy=True, dayfirst=True)
            days = max(1, abs((dd - ad).days))
            entities["duration_of_stay"] = str(days)
        except Exception:
            pass

    # ── Primary diagnosis ─────────────────────────────────────────────────────
    prim_m = re.search(
        r'(?i)(?:Primary|Final|Main|Provisional)\s+Diagnosis\s*[:\-]?\s*([^\n]+)',
        text,
    )
    if prim_m:
        entities["diseases"] = [_clean(prim_m.group(1))]
    else:
        if entities["diseases"]:
            if len(entities["diseases"]) > 1 and not entities["secondary_diagnosis"]:
                entities["secondary_diagnosis"] = ", ".join(entities["diseases"][1:])
            entities["diseases"] = [entities["diseases"][0]]

    return entities
