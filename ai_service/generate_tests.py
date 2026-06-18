import os
import random
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import sys
sys.path.append("/Users/mudabbir/medical-doc-engine/ai_service")
from models.ocr import extract_text
from models.ner import extract_entities

OUTPUT_DIR = os.path.expanduser("~/Desktop/testsamples")
os.makedirs(OUTPUT_DIR, exist_ok=True)

def generate_noisy_image():
    # Create an image with a light background
    img = Image.new('RGB', (800, 1000), color=(240, 240, 240))
    d = ImageDraw.Draw(img)
    
    # Try to load a standard font, fallback to default
    try:
        font = ImageFont.truetype("Arial", 20)
        large_font = ImageFont.truetype("Arial", 28)
    except IOError:
        font = ImageFont.load_default()
        large_font = font

    # Draw noisy text
    text_lines = [
        "METRO CITY HEALTHCARE CLINIC",
        "Facility Address: 123 Health Ave, Suite 400",
        "Metropolis, NY 10001",
        "",
        "Name of the patient: John Doe, Age/Sex: 45/M",
        "DOB: 01-Jan-1980",
        "123 Main Street, block 4, Springfield, IL 62701",
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
        "Total Amount: $4500.00",
        "Room Type: Private Suite",
        "",
        "Lab Results: WBC-12000, CRP-45, XRAY-Clear",
        "Rx: Amoxicillin 500mg every 8 hours, Albuterol twice a day"
    ]
    
    y = 50
    for line in text_lines:
        f = large_font if "HEALTHCARE" in line else font
        d.text((50, y), line, fill=(0, 0, 0), font=f)
        y += 40

    # Add artificial noise
    for _ in range(5000):
        x = random.randint(0, 799)
        y = random.randint(0, 999)
        d.point((x, y), fill=(100, 100, 100))
        
    # Add a blur filter to simulate a bad scan
    img = img.filter(ImageFilter.GaussianBlur(1))
    
    img_path = os.path.join(OUTPUT_DIR, "noisy_scan.png")
    img.save(img_path)
    return img_path

def run_test(file_path):
    print(f"\\n--- Testing {os.path.basename(file_path)} ---")
    text, conf = extract_text(file_path)
    entities = extract_entities(text)
    
    for key, val in entities.items():
        print(f"{key.ljust(25)} : {val}")

if __name__ == "__main__":
    print("Generating files...")
    img_path = generate_noisy_image()
    
    print("Running extraction on new noisy image...")
    run_test(img_path)
    
    print("\\nRunning extraction on previous PDFs in testsamples...")
    for file in os.listdir(OUTPUT_DIR):
        if file.endswith(".pdf"):
            run_test(os.path.join(OUTPUT_DIR, file))
