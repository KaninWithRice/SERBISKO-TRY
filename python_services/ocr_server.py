from flask import Flask, request, jsonify
from flask_cors import CORS
import easyocr
import cv2
import numpy as np
import re
import difflib 

app = Flask(__name__)
CORS(app)

print("\n" + "="*50)
print("🟢 [SYSTEM] OCR ENGINE v5.1 ONLINE!")
print("STRICT VERIFICATION: 55% THRESHOLD ENABLED")
print("="*50 + "\n")

# Global reader
reader = easyocr.Reader(['en'], gpu=False)

def clean_text(text):
    text = str(text).lower().replace('ñ', 'n')
    # Strip box lines and symbols common in grids
    text = re.sub(r'[|\[\]_!/\\(){}:;.\-+=—–]', ' ', text)
    return re.sub(r'\s+', ' ', text).strip()

def fuzzy_match(expected, text):
    if not expected or expected.lower() == 'unknown': return True
    
    # 1. Prepare data
    blob = clean_text(text).replace(" ", "")
    parts = clean_text(expected).split()
    
    # We check each part of the name (e.g., "Mary", "Grace")
    for part in parts:
        if len(part) < 3: continue
        p_clean = part.replace(" ", "")
        
        # Exact match in the spaceless blob (very common for grid forms)
        if p_clean in blob: return True
        
        # Fuzzy sliding window (55% threshold as requested)
        window = len(p_clean)
        for i in range(len(blob) - window + 1):
            chunk = blob[i:i+window]
            if difflib.SequenceMatcher(None, p_clean, chunk).ratio() >= 0.55:
                return True
    return False

@app.route('/ocr', methods=['POST'])
def ocr():
    if 'image' not in request.files: return jsonify({'success': False, 'error': 'No image'}), 400
    
    doc_type = request.form.get('doc_type', 'generic')
    first_name = request.form.get('first_name', '').lower()
    last_name = request.form.get('last_name', '').lower()
    
    print(f"📡 Request: {doc_type.upper()} for {first_name} {last_name}")

    try:
        file = request.files['image']
        img = cv2.imdecode(np.frombuffer(file.read(), np.uint8), cv2.IMREAD_COLOR)
        
        # Pre-processing
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        processed = cv2.convertScaleAbs(gray, alpha=1.3, beta=10)

        # Sequential Scanning (Stop early if document type is found)
        best_text = ""
        found_doc = False
        
        for rot in [None, cv2.ROTATE_90_CLOCKWISE, cv2.ROTATE_90_COUNTERCLOCKWISE]:
            rotated = processed if rot is None else cv2.rotate(processed, rot)
            results = reader.readtext(rotated, detail=0, paragraph=True)
            text = " ".join(results).lower()
            
            # Step 1: Identify Document Type
            is_match = False
            if 'report' in doc_type or 'sf9' in doc_type:
                if any(k in text for k in ['sf9', 'report card', 'form 9']): is_match = True
            elif 'birth' in doc_type or 'psa' in doc_type:
                if any(k in text for k in ['birth', 'psa', 'nso', 'registry']): is_match = True
            elif 'enroll' in doc_type:
                if any(k in text for k in ['enrollment', 'basic education', 'learner information', 'beal']): is_match = True
            elif 'als' in doc_type:
                if any(k in text for k in ['als', 'alternative learning', 'rating']): is_match = True
            elif 'moral' in doc_type:
                if any(k in text for k in ['moral', 'character']): is_match = True
            else:
                is_match = True # Generic fallback
            
            if is_match:
                best_text = text
                found_doc = True
                break
            
            if len(text) > len(best_text): best_text = text

        print(f"📄 Found Text: {best_text[:150]}...")

        if not found_doc:
            return jsonify({'success': False, 'error': f"Document mismatch. Please scan your {doc_type.replace('_', ' ').title()}."})

        # Step 2: Verify LRN (For Report Card only)
        if 'report' in doc_type or 'sf9' in doc_type:
            lrn = None
            keyword_match = re.search(r'(lrn|learner|reference|id|no)', best_text)
            if keyword_match:
                after_text = best_text[keyword_match.end():keyword_match.end()+50]
                digits_near = re.sub(r'\D', '', after_text)
                if len(digits_near) >= 12: lrn = digits_near[:12]
            
            if not lrn:
                all_digits = re.findall(r'\d{12}', re.sub(r'\D', '', best_text))
                if all_digits: lrn = all_digits[0]

            if not lrn: return jsonify({'success': False, 'error': "SF9 found, but 12-digit LRN is unreadable."})
            return jsonify({'success': True, 'lrn': lrn, 'message': "SF9 and LRN Verified!"})
        
        # Step 3: Verify First Name (55% Threshold)
        if not fuzzy_match(first_name, best_text):
            return jsonify({'success': False, 'error': f"Document type correct, but First Name ({first_name}) missing."})
        
        # Step 4: Verify Last Name (55% Threshold)
        if not fuzzy_match(last_name, best_text):
            return jsonify({'success': False, 'error': f"First Name found, but Last Name ({last_name}) is missing or unreadable."})
            
        return jsonify({'success': True, 'message': f"{doc_type.replace('_', ' ').title()} Verified!"})

    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status')
def status(): return jsonify({'status': 'online'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, threaded=True)
