from database import icd_collection

async def map_to_icd(disease_name: str):
    """
    Queries the MongoDB database to find a matching ICD-10 code.
    Since we don't have a dedicated text index setup in this MVP, 
    we use a case-insensitive regex match on the disease name.
    """
    # Look for a document where the 'disease' field contains the disease_name
    doc = await icd_collection.find_one({"disease": {"$regex": disease_name, "$options": "i"}})
    
    if doc:
        return doc.get("icd_code") or doc.get("code")
    
    return None
