import os
import xml.etree.ElementTree as ET
from pymongo import MongoClient
from dotenv import load_dotenv

def main():
    # Load .env from the backend directory
    backend_env_path = os.path.join(os.path.dirname(__file__), "..", "backend", ".env")
    load_dotenv(dotenv_path=backend_env_path)

    MONGODB_URI = os.getenv("MONGODB_URI")
    MONGODB_DATABASE = os.getenv("MONGODB_DATABASE", "medical_doc_engine")

    print("Connecting to MongoDB Atlas...")
    # Connect to MongoDB synchronously
    client = MongoClient(MONGODB_URI)
    db = client[MONGODB_DATABASE]
    collection = db["icd_codes"]

    xml_path = "/Users/mudabbir/Downloads/icd10cm-April-1-2026-XML/icd10c-tabular-April-1-2026.xml"
    print(f"Parsing XML file: {xml_path}")
    
    tree = ET.parse(xml_path)
    root = tree.getroot()

    documents = []
    # .iter() recursively finds all tags named 'diag'
    for diag in root.iter("diag"):
        name_node = diag.find("name")
        desc_node = diag.find("desc")
        
        if name_node is not None and desc_node is not None:
            code = name_node.text
            disease = desc_node.text
            
            if code and disease:
                documents.append({
                    "icd_code": code.strip(),
                    "disease": disease.strip()
                })

    if not documents:
        print("No diagnoses found. Please check the XML file format.")
        return

    print(f"Found {len(documents)} ICD codes. Clearing old records and inserting...")
    
    # Clear the collection first to avoid duplicates
    collection.delete_many({})
    
    # Insert all documents in bulk
    collection.insert_many(documents)
    
    print("Creating index on 'disease' field to speed up NLP lookups...")
    collection.create_index("disease")
    
    print("Successfully imported all ICD-10-CM codes to MongoDB!")

if __name__ == "__main__":
    main()
