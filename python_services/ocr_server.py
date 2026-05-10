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
    print(f"[OCR-LOG] {msg}", flush=True)

log("OCR ENGINE v9.10 FUZZY-DOC HYBRID STARTING...")

# Global reader
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

def fuzzy_match(expected, text, threshold=0.6):
    if not expected or expected.lower() == 'unknown': return True
    blob = clean_text(text).replace(" ", "")
    expected_clean = clean_text(expected).replace(" ", "")
    if expected_clean in blob: return True
    parts = clean_text(expected).split()
    for part in parts:
        if len(part) < 3: continue
        p_clean = part.replace(" ", "")
        if p_clean in blob: return True
        window = len(p_clean)
        for i in range(len(blob) - window + 1):
            if difflib.SequenceMatcher(None, p_clean, blob[i:i+window]).ratio() >= threshold:
                return True
    return False

def fuzzy_keyword_match(keywords, text, threshold=0.75):
    """Allows document recognition even if keywords are slightly misread."""
    matches = 0
    for k in keywords:
        if k in text:
            matches += 1
            continue
        if len(k) < 4: continue
        window = len(k)
        for i in range(len(text) - window + 1):
            if difflib.SequenceMatcher(None, k, text[i:i+window]).ratio() >= threshold:
                matches += 1
                break
    return matches

def extract_candidates(text):
    norm_blob = normalize_digits(text)
    return re.findall(r'\d{6,15}', norm_blob)

@app.route('/ocr', methods=['POST'])
def ocr():
    if 'image' not in request.files: return jsonify({'success': False, 'error': 'No image'}), 400
    doc_type = request.form.get('doc_type', 'generic')
    first_name = request.form.get('first_name', '').lower()
    last_name = request.form.get('last_name', '').lower()
    expected_lrn = request.form.get('expected_lrn', '')
    log(f"REQUEST: {doc_type.upper()} | NAME: {first_name} {last_name}")
    try:
        img_bytes = request.files['image'].read()
        img = cv2.imdecode(np.frombuffer(img_bytes, np.uint8), cv2.IMREAD_COLOR)
        if img is None: return jsonify({'success': False, 'error': 'Invalid image format.'}), 400
        h, w = img.shape[:2]
        target_w = 2200
        if w > target_w:
            scale = target_w / w
            img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_LANCZOS4)
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        clahe = cv2.createCLAHE(clipLimit=4.0, tileGridSize=(8,8))
        p1 = clahe.apply(gray)
        _, p2 = cv2.threshold(p1, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        kernel = np.ones((2,2), np.uint8)
        p3 = cv2.morphologyEx(p1, cv2.MORPH_OPEN, kernel)
        p3 = cv2.addWeighted(p1, 1.5, p3, -0.5, 0)
        p4 = cv2.erode(p1, kernel, iterations=1)
        p5 = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY_INV, 31, 15)
        
        best_text, found_doc, all_lrn_candidates, name_verified = "", False, [], False
        doc_config = {
            'report_card': {
                'strong': ['sf9', 'schoolform9', 'reportcard', 'form9'], 
                'weak': ['report', 'card', 'deped', 'attendance', 'learner', 'progress', 'rating', 'completer', 'elementary', 'secondary', 'individual', 'development', 'department', 'education', 'republic', 'philippines'],
                'block': ['psa', 'nso', 'registry', 'birth', 'affidavit']
            },
            'birth_certificate': {
                'strong': ['psa', 'nso', 'registry', 'birth'], 
                'weak': ['certificate', 'live', 'born', 'child'],
                'block': ['enrollment', 'sf9', 'form9', 'rating']
            }
        }
        config = doc_config.get(doc_type, {'strong': [], 'weak': [], 'block': []})
        
        for p_idx, proc_img in enumerate([p1, p2, p3, p4, p5]):
            if name_verified: break
            for rot in [None, cv2.ROTATE_90_CLOCKWISE, cv2.ROTATE_90_COUNTERCLOCKWISE]:
                rotated = proc_img if rot is None else cv2.rotate(proc_img, rot)
                results = reader.readtext(rotated, detail=0, allowlist='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789. ')
                text = " ".join(results).lower()
                clean_blob = text.replace(" ", "")
                log(f"--- EXTRACTED TEXT (Pass {p_idx+1}, Rot {rot}) ---")
                log(text[:300] + "...")
                log("-----------------------------------------")

                block_matches = [b for b in config['block'] if b in clean_blob]
                if block_matches:
                    log(f"BLOCK WORD FOUND: {block_matches}. Skipping pass.")
                    continue
                
                # v9.10: Fuzzy Keyword Matching
                s_m = fuzzy_keyword_match(config['strong'], clean_blob)
                w_m = fuzzy_keyword_match(config['weak'], clean_blob)
                log(f'Doc Analysis: Strong={s_m}, Weak={w_m}')
                
                # Accept just 1 weak fuzzy match if no block words are found. This is extremely forgiving.
                if (s_m >= 1) or (w_m >= 1) or (not config['strong'] and not config['weak']):
                    found_doc = True
                    if len(text) > len(best_text): best_text = text
                    all_lrn_candidates.extend(extract_candidates(text))
                    
                    # Strict match
                    if fuzzy_match(first_name, text) and fuzzy_match(last_name, text):
                        name_verified = True
                    # Lenient match (v9.10 Speed Optimization)
                    elif (fuzzy_match(first_name, text, 0.4) and fuzzy_match(last_name, text, 0.1)) or \
                         (fuzzy_match(last_name, text, 0.4) and fuzzy_match(first_name, text, 0.1)):
                        name_verified = True

                    if name_verified:
                        log(f"SUCCESS: Name verified early in Pass {p_idx+1}, Rot {rot}")
                        break
            if name_verified: break
            
        if not found_doc:
            return jsonify({'success': False, 'error': f"Document mismatch. Please scan the correct {doc_type.replace('_', ' ')}."})
            
        if not name_verified:
            if fuzzy_match(first_name, best_text) and fuzzy_match(last_name, best_text):
                name_verified = True
            elif (fuzzy_match(first_name, best_text, 0.4) and fuzzy_match(last_name, best_text, 0.1)) or \
                 (fuzzy_match(last_name, best_text, 0.4) and fuzzy_match(first_name, best_text, 0.1)):
                name_verified = True
                
        if not name_verified:
            return jsonify({'success': False, 'error': f"Name mismatch. Student name not found on document."})
            
        return jsonify({
            'success': True, 
            'name_verified': True, 
            'lrn': expected_lrn, 
            'candidates': list(set(all_lrn_candidates)),
            'message': "Verified via Name Rescue!"
        })
    except Exception as e:
        log(f"ERROR: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status')
def status(): return jsonify({'status': 'online'})

if __name__ == '__main__':
    log("Starting OCR Hybrid v9.10 on Port 9002...")
    app.run(host='0.0.0.0', port=9002, threaded=True)
