from database import get_icd_code

async def map_to_icd(disease_name: str):
    """Look up the ICD-10 code for a disease name using a MySQL LIKE search."""
    return await get_icd_code(disease_name)
