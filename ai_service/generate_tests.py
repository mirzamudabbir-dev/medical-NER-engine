"""
Generates a synthetic noisy scan and runs the full extraction pipeline on it.
Usage:
    python generate_tests.py
"""
import os
import random
from PIL import Image, ImageDraw, ImageFont, ImageFilter
from models.ocr import extract_text
from models.ner import extract_entities

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), "test_samples")
os.makedirs(OUTPUT_DIR, exist_ok=True)


def generate_noisy_image():
    img = Image.new('RGB', (800, 1000), color=(240, 240, 240))
    d = ImageDraw.Draw(img)

    try:
        font = ImageFont.truetype("Arial", 20)
        large_font = ImageFont.truetype("Arial", 28)
    except IOError:
        font = ImageFont.load_default()
        large_font = font

    text_lines = [
        "METRO CITY HEALTHCARE CLINIC",
        "Facility Address: 123 Health Ave, Suite 400",
        "Metropolis, NY 10001",
        "",
        "Name of the patient: John Doe",
        "Age/Sex: 45/M   DOB: 01-Jan-1980",
        "DOA : 10-Jan-2023",
        "",
        "Attending Physician: Dr. Sarah Connor MD",
        "",
        "Primary Diagnosis: Acute Bronchitis",
        "Secondary Diagnosis: Hypertension, Type 2 Diabetes",
        "",
        "Chief Complaint: Severe cough and shortness of breath",
        "Treatment Type: Antibiotics and Nebulization",
        "",
        "Total Bill Amount: Rs. 4500",
        "Room Type: Private Suite",
        "",
        "Lab Results: WBC-12000, CRP-45",
        "Medications: Amoxicillin 500mg twice daily, Albuterol 2 puffs twice daily",
    ]

    y = 50
    for line in text_lines:
        f = large_font if "HEALTHCARE" in line else font
        d.text((50, y), line, fill=(0, 0, 0), font=f)
        y += 40

    for _ in range(5000):
        x = random.randint(0, 799)
        y = random.randint(0, 999)
        d.point((x, y), fill=(100, 100, 100))

    img = img.filter(ImageFilter.GaussianBlur(1))
    img_path = os.path.join(OUTPUT_DIR, "noisy_scan.png")
    img.save(img_path)
    return img_path


def run_test(file_path):
    print(f"\n--- Testing {os.path.basename(file_path)} ---")
    text, conf = extract_text(file_path)
    entities = extract_entities(text)
    print(f"Confidence: {conf}")
    for key, val in entities.items():
        if val:
            print(f"  {key.ljust(25)}: {val}")


if __name__ == "__main__":
    print("Generating noisy test image...")
    img_path = generate_noisy_image()
    run_test(img_path)

    print("\nRunning on any PDFs in test_samples/...")
    for file in os.listdir(OUTPUT_DIR):
        if file.endswith(".pdf"):
            run_test(os.path.join(OUTPUT_DIR, file))
