import sys
import os

from models.ner import extract_entities, get_nlp_model

get_nlp_model()

mock_ocr_text = """
DRLOGY PATHOLOGY LAB
Accurate | Caring | Instant
105 -108, SMART VISION COMPLEX, HEALTHCARE ROAD, OPPOSITE HEALTHCARE COMPLEX. MUMBAI - 689578

Yashvi M. Patel               Sample Collected At:
Age : 21 Years                125, Shiv complex, S G Road, Mumbai
Sex : Female                  Sample Collected By: Mr Suresh
UHID : 556                    Ref. By: Dr. Hiren Shah

Complete Blood Count (CBC)

Investigation Result Reference Value Unit
Sample Type Blood (2 ml) TAT : 1 day (Normal: 1-3 days)
Hemoglobin (Hb) 13.00 Normal 13.00 - 17.00 g/dL
Total RBC count 5.00 Normal 4.50 - 5.50 mill/cumm
Packed Cell Volume (PCV) 45 Normal 40 - 50 %
Mean Corpuscular Volume (MCV) 100 Normal 83 - 101 fL
Total WBC count 10000 Normal 4000 - 11000 cumm
Neutrophils 60 Normal 50 - 62 %

Interpretation: Further confirm for Anemia

Medical Lab Technician        Dr. Payal Shah          Dr. Vimal Shah
(DMLT, BMLT)                  (MD, Pathologist)       (MD, Pathologist)
"""

entities = extract_entities(mock_ocr_text)

print(f"Patient Name: {entities.get('patient_name')}")
print(f"Age: {entities.get('age')}")
print(f"Gender: {entities.get('gender')}")
print(f"Doctor: {entities.get('doctor')}")
print("--- Lab Tests ---")
print("Names:")
print(entities.get('lab_test_names'))
print("Values:")
print(entities.get('lab_test_values'))
