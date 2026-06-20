import pytesseract
from PIL import Image
from pdf2image import convert_from_path
from pytesseract import Output
import pdfplumber

# LSTM engine + uniform-block layout — best balance for medical forms/reports
_TESS_CONFIG = "--oem 3 --psm 6"


def get_ocr_model():
    return pytesseract


def _preprocess_image(img: Image.Image) -> Image.Image:
    """Apply OpenCV preprocessing to improve Tesseract accuracy on scanned documents."""
    import cv2
    import numpy as np
    arr = np.array(img.convert("RGB"))
    gray = cv2.cvtColor(arr, cv2.COLOR_RGB2GRAY)
    # Denoise before thresholding to avoid amplifying noise into black speckles
    denoised = cv2.fastNlMeansDenoising(gray, h=10)
    # Adaptive threshold handles uneven illumination and shadows common in scanned docs
    binary = cv2.adaptiveThreshold(
        denoised, 255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY,
        15, 8,
    )
    return Image.fromarray(binary)


def _process_image(img: Image.Image):
    preprocessed = _preprocess_image(img)
    text = pytesseract.image_to_string(preprocessed, config=_TESS_CONFIG)
    data = pytesseract.image_to_data(preprocessed, config=_TESS_CONFIG, output_type=Output.DICT)
    confs = [int(c) for c in data["conf"] if c != "-1" and int(c) != -1]
    return text, confs


def _extract_native_pdf_text(path: str) -> str:
    """Extract embedded text from a digitally-generated PDF without OCR."""
    try:
        with pdfplumber.open(path) as pdf:
            pages = [page.extract_text() or "" for page in pdf.pages]
        return "\n".join(pages).strip()
    except Exception:
        return ""


def extract_text(image_path_or_array: str):
    """
    Extracts text from an image or PDF.
    - Text-native PDFs:  pdfplumber (fast, near-perfect accuracy, no OCR loss)
    - Scanned PDFs/images: OpenCV-preprocessed Tesseract at 300 DPI
    Returns: (extracted_text, average_confidence_score)
    """
    all_text = ""
    all_confs = []

    if isinstance(image_path_or_array, str):
        if image_path_or_array.lower().endswith(".pdf"):
            native_text = _extract_native_pdf_text(image_path_or_array)
            # Use native extraction when there is substantial embedded text
            if len(native_text) > 100:
                return native_text, 99.0
            # Scanned PDF — render at high DPI before OCR
            images = convert_from_path(image_path_or_array, dpi=300)
            for img in images:
                text, confs = _process_image(img)
                all_text += text + "\n"
                all_confs.extend(confs)
        else:
            image = Image.open(image_path_or_array)
            text, confs = _process_image(image)
            all_text = text
            all_confs = confs

    avg_conf = sum(all_confs) / len(all_confs) if all_confs else 0.0
    return all_text.strip(), round(avg_conf, 2)
