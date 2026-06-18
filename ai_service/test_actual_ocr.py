import sys
from models.ocr import extract_text

img_path = "/Users/mudabbir/medical-doc-engine/backend/storage/app/private/documents/ZiERrS2LM9foTFSMUaF4EDfarzuNnCwHK85gYHTq.jpg"

try:
    from models.ner import extract_entities, get_nlp_model
    get_nlp_model()
    text, conf = extract_text(img_path)
    entities = extract_entities(text)
    print("=== EXTRACTED LAB TESTS ===")
    print(repr(entities.get('lab_test_names')))
    print(repr(entities.get('lab_test_values')))
except Exception as e:
    print(f"Exception: {e}")
