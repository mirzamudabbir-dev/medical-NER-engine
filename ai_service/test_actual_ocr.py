"""
Quick test: extract text + entities from a real document file.
Usage:
    python test_actual_ocr.py /path/to/document.jpg
    python test_actual_ocr.py /path/to/document.pdf
"""
import sys
from models.ner import extract_entities, get_nlp_model
from models.ocr import extract_text

if len(sys.argv) < 2:
    print("Usage: python test_actual_ocr.py /path/to/document.jpg")
    sys.exit(1)

img_path = sys.argv[1]
get_nlp_model()

try:
    text, conf = extract_text(img_path)
    entities = extract_entities(text)
    print(f"Confidence: {conf}")
    print("=== EXTRACTED LAB TESTS ===")
    print(repr(entities.get('lab_test_names')))
    print(repr(entities.get('lab_test_values')))
    print("=== ALL ENTITIES ===")
    for k, v in entities.items():
        if v:
            print(f"  {k}: {v}")
except Exception as e:
    print(f"Exception: {e}")
