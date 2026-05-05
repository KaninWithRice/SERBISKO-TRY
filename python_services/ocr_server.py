from flask import Flask, request, jsonify
from flask_cors import CORS
import easyocr
import cv2
import numpy as np
import re
import difflib 
import sys

app = Flask(__name__)
CORS(app)

def log(msg):
    print(f"🟢 [OCR-LOG] {msg}", flush=True)

log("OCR ENGINE v8.0 OPTIMIZED STARTING...")

# Global reader - allow auto-detection of GPU for performance
reader = easyocr.Reader(['en'], gpu=False) 

def clean_text(text):
    text = str(text).lower().replace('ñ', 'n')
    text = re.sub(r'[|\[\]_!/\\(){}:;.\-+=—–]', ' ', text)
    return re.sub(r'\s+', ' ', text).strip()

def normalize_digits(text):
    norm = text.upper()
    replacements = {
        'O': '0', 'D': '0', 'Q': '0', 'U': '0',
        'I': '1', 'L': '1', '|': '1', 'J': '1', 'T': '1',
        'Z': '7', 'S': '5', 'G': '6', 'B': '8', 'E': '8',
        'A': '4', 'Y': '4', 'H': '4', 'K': '4'
    }
    for char, digit in replacements.items():
        norm = norm.replace(char, digit)
    return re.sub(r'[^0-9]', '', norm)

def extract_candidates(text):
    candidates = []
    clean_blob = text.replace(" ", "").replace(":", "").replace("-", "")
    candidates.extend(re.findall(r'\d{12}', clean_blob))
    norm_blob = normalize_digits(text)
    candidates.extend(re.findall(r'\d{12}', norm_blob))
    potentials = re.findall(r'\d{10,14}', norm_blob)
    for p in potentials:
        if len(p) != 12: candidates.append(p)
    return list(set(candidates))

def fuzzy_match(expected, text):
    if not expected or expected.lower() == 'unknown': return True
    blob = clean_text(text).replace(" ", "")
    parts = clean_text(expected).split()
    for part in parts:
        if len(part) < 3: continue
        p_clean = part.replace(" ", "")
        if p_clean in blob: return True
        window = len(p_clean)
        for i in range(len(blob) - window + 1):
            if difflib.SequenceMatcher(None, p_clean, blob[i:i+window]).ratio() >= 0.6:
                return True
    return False

@app.route('/ocr', methods=['POST'])
def ocr():
    if 'image' not in request.files: return jsonify({'success': False, 'error': 'No image'}), 400
    
    doc_type = request.form.get('doc_type', 'generic')
    first_name = request.form.get('first_name', '').lower()
    last_name = request.form.get('last_name', '').lower()
    expected_lrn = request.form.get('expected_lrn', '')
    
    log(f"SPEED-OPT REQUEST: {doc_type.upper()} | EXPECTED LRN: {expected_lrn}")

    try:
        file = request.files['image']
        img_bytes = file.read()
        img = cv2.imdecode(np.frombuffer(img_bytes, np.uint8), cv2.IMREAD_COLOR)
        if img is None: return jsonify({'success': False, 'error': 'Invalid image format.'}), 400

        # Resize for speed - 1500px is usually plenty for EasyOCR
        h, w = img.shape[:2]
        if w > 1800:
            scale = 1800 / w
            img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)
        
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Reduced Passes (P1: CLAHE, P2: Dilated Threshold)
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        p1 = clahe.apply(gray)
        
        thresh = cv2.adaptiveThreshold(p1, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 31, 10)
        kernel = np.ones((2,2), np.uint8)
        p2 = cv2.dilate(thresh, kernel, iterations=1)

        best_text = ""
        found_doc = False
        all_lrn_candidates = []
        is_sf9 = 'report' in doc_type or 'sf9' in doc_type

        # Multi-pass loop with aggressive early exits
        for p_idx, proc_img in enumerate([p1, p2]):
            for r_idx, rot in enumerate([None, cv2.ROTATE_90_CLOCKWISE, cv2.ROTATE_90_COUNTERCLOCKWISE]):
                rotated = proc_img if rot is None else cv2.rotate(proc_img, rot)
                
                # Run General OCR
                results = reader.readtext(rotated, detail=0, paragraph=False)
                text = " ".join(results).lower()
                clean_blob = text.replace(" ", "").replace("\n", "")
                
                # Document Keyword Check
                is_match = False
                if is_sf9:
                    keywords = ['sf9', 'reportcard', 'form9', 'deped', 'republic', 'attendance', 'learner', 'schoolform9', 'guardian', 'rating']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                elif 'birth' in doc_type or 'psa' in doc_type:
                    keywords = ['birth', 'certificate', 'psa', 'nso', 'registry', 'born', 'live', 'child']
                    if sum(1 for k in keywords if k in clean_blob) >= 2: is_match = True
                else: is_match = True 

                if is_match:
                    found_doc = True
                    if len(text) > len(best_text): best_text = text
                    candidates = extract_candidates(text)
                    all_lrn_candidates.extend(candidates)
                    
                    # Early Exit: If LRN is found in General Pass with high confidence
                    if expected_lrn:
                        for cand in candidates:
                            if difflib.SequenceMatcher(None, expected_lrn, cand).ratio() >= 0.9:
                                log("SUPER-FAST EXIT: High-confidence match in General Pass.")
                                break

                # Numeric-Only Pass (Only if LRN not already found with high confidence)
                if is_sf9 and not any(len(c) == 12 for c in all_lrn_candidates):
                    num_results = reader.readtext(rotated, detail=0, allowlist='0123456789')
                    all_lrn_candidates.extend(extract_candidates(" ".join(num_results)))

                # FINAL EARLY EXIT CHECK (After each rotation)
                if found_doc:
                    if is_sf9 and any(len(c) == 12 for c in all_lrn_candidates): break
                    elif not is_sf9: break # Name docs only need one good read
            
            if found_doc:
                if is_sf9 and any(len(c) == 12 for c in all_lrn_candidates): break
                elif not is_sf9: break

        if not found_doc:
            return jsonify({'success': False, 'error': f"Document mismatch. Ensure it is a valid {doc_type}."})

        # Name Verification
        name_verified = fuzzy_match(first_name, best_text) and fuzzy_match(last_name, best_text)
        if not name_verified:
            return jsonify({'success': False, 'error': f"Name mismatch. '{first_name} {last_name}' not found."})

        # LRN Selection
        if is_sf9:
            unique_candidates = list(set(all_lrn_candidates))
            best_lrn = None
            if expected_lrn:
                for candidate in unique_candidates:
                    if difflib.SequenceMatcher(None, expected_lrn, candidate).ratio() >= 0.85:
                        best_lrn = expected_lrn
                        break
            if not best_lrn and unique_candidates:
                best_lrn = sorted(unique_candidates, key=lambda x: (abs(len(x)-12), not x.startswith('000')))[0]
            
            if best_lrn:
                return jsonify({'success': True, 'lrn': best_lrn, 'candidates': unique_candidates, 'message': "Verified!"})
            return jsonify({'success': False, 'error': "LRN unreadable."})
            
        return jsonify({'success': True, 'message': "Verified!"})

    except Exception as e:
        log(f"ERROR: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, threaded=True)

@app.route('/status')
def status(): return jsonify({'status': 'online'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, threaded=True)
