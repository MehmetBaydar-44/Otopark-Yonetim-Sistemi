import cv2
import numpy as np
import base64
import easyocr
import re
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import uvicorn
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()
reader = easyocr.Reader(['tr', 'en'])

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class ImageData(BaseModel):
    base64_image: str

@app.post("/api/plaka-oku")
async def process_image(data: ImageData):
    try:
        encoded_data = data.base64_image.split(',')[1] if ',' in data.base64_image else data.base64_image
        nparr = np.frombuffer(base64.b64decode(encoded_data), np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        results = reader.readtext(gray, detail=0)
        
        if not results:
            return {"status": "error", "message": "Plaka tespit edilemedi."}
            
        ham_metin = "".join(results)
        # 1. Önce tüm gereksiz karakterleri temizle ve metni büyüt
        temiz_metin = re.sub(r'[^A-Za-z0-9]', '', ham_metin).upper()
        
        # 2. Türkiye plaka formatını (2 rakam + 1-3 harf + 2-4 rakam) bul
        plaka_eslesme = re.search(r'(\d{2}[A-Z]{1,3}\d{2,4})', temiz_metin)
        
        if plaka_eslesme:
            # Sadece eşleşen plaka kısmını al (TR kısmını dışarıda bırakır)
            temiz_plaka = plaka_eslesme.group(1)
        else:
            # Eğer standart dışı bir plaka ise (resmi araç vb.) hepsini döndür
            temiz_plaka = temiz_metin
        
        return {"status": "success", "plaka": temiz_plaka}

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8000)
