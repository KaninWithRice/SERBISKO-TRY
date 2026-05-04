import easyocr
import cv2
import numpy as np
import re

reader = easyocr.Reader(['en'], gpu=False)

def analyze(image_path):
    print(f"Analyzing {image_path}...")
    img = cv2.imread(image_path)
    if img is None:
        print("Error: Could not read image.")
        return

    # Multi-pass OCR similar to ocr_server.py but with more debug output
    results = reader.readtext(image_path, detail=1)
    
    print("\n--- ALL DETECTED TEXT ---")
    for (bbox, text, prob) in results:
        print(f"[{prob:.2f}] {text}")
        if any(c.isdigit() for c in text):
            # Check for LRN-like patterns
            clean = re.sub(r'[^0-9]', '', text)
            if len(clean) >= 10:
                print(f"  >>> LRN Candidate: {clean} (from '{text}')")

    # Try specifically for the LRN label
    for i, (bbox, text, prob) in enumerate(results):
        if 'lrn' in text.lower() or 'reference' in text.lower() or 'number' in text.lower():
            print(f"\nPotential LRN label found: '{text}' at {bbox}")
            # Look at nearby text (next 3 results)
            for j in range(i+1, min(i+4, len(results))):
                print(f"  Nearby ({j-i}): '{results[j][1]}' [{results[j][2]:.2f}]")

if __name__ == "__main__":
    analyze("storage/app/public/scans/scan_56_1777877290.jpeg")
