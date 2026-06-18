"""
One-time script to bulk-import ICD-10-CM codes from the official XML into MySQL.
Usage:
    python import_icd_codes.py /path/to/icd10c-tabular.xml
"""
import os
import sys
import xml.etree.ElementTree as ET
from sqlalchemy import text
from database import sync_engine


def main():
    xml_path = sys.argv[1] if len(sys.argv) > 1 else None
    if not xml_path or not os.path.exists(xml_path):
        print("Usage: python import_icd_codes.py /path/to/icd10c-tabular.xml")
        sys.exit(1)

    print(f"Parsing {xml_path} ...")
    tree = ET.parse(xml_path)
    root = tree.getroot()

    documents = []
    for diag in root.iter("diag"):
        name_node = diag.find("name")
        desc_node = diag.find("desc")
        if name_node is not None and desc_node is not None:
            code = (name_node.text or "").strip()
            disease = (desc_node.text or "").strip()
            if code and disease:
                documents.append({"icd_code": code, "disease": disease})

    if not documents:
        print("No diagnoses found — check the XML format.")
        return

    print(f"Found {len(documents)} codes. Importing into MySQL...")
    with sync_engine.begin() as conn:
        conn.execute(text("TRUNCATE TABLE icd_codes"))
        conn.execute(
            text("INSERT INTO icd_codes (icd_code, disease) VALUES (:icd_code, :disease)"),
            documents,
        )

    print("Done.")


if __name__ == "__main__":
    main()
