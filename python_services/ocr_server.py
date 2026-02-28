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
print("🟢 [SYSTEM] ULTRA-SMART OCR v4.3 ONLINE!")
print("Loading OCR Model... (This might take a few seconds)")
print("="*50 + "\n")

reader = easyocr.Reader(['en'], gpu=False)

# ==========================================
# HIGH-SPEED IMAGE ENHANCEMENT
# ==========================================
def process_image(file):
    file_bytes = np.frombuffer(file.read(), np.uint8)
    img = cv2.imdecode(file_bytes, cv2.IMREAD_COLOR)
    height, width = img.shape[:2]
    enlarged_img = cv2.resize(img, (int(width * 1.5), int(height * 1.5)), interpolation=cv2.INTER_LINEAR)
    gray = cv2.cvtColor(enlarged_img, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (3, 3), 0)
    kernel = np.array([[0, -1, 0], [-1, 5, -1], [0, -1, 0]])
    sharpened = cv2.filter2D(blurred, -1, kernel)
    high_contrast = cv2.convertScaleAbs(sharpened, alpha=1.5, beta=15)
    return high_contrast

# ==========================================
# ADVANCED TEXT CLEANER
# ==========================================
def clean_alpha_text(text):
    text = str(text).lower().replace('ñ', 'n')
    text = re.sub(r'[^a-z0-9\s]', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text

# ==========================================
# BULLETPROOF CLASSIFIERS
# ==========================================
def check_keywords(raw_text, keywords_list):
    raw_str = str(raw_text).lower()
    norm_text = clean_alpha_text(raw_str)
    
    for kw in keywords_list:
        if kw.lower() in raw_str or kw.lower() in norm_text:
            return True
            
    for kw in keywords_list:
        words = kw.split()
        if len(words) > 1:
            if all(word in norm_text.split() for word in words):
                return True
    return False

def is_report_card(text): return check_keywords(text, ['sf9', 'sf 9', 'sfq', 'sfm', 'report card', 'recort', 'report cau', 'form 9'])
def is_birth_certificate(text): return check_keywords(text, ['birth', 'certificate of live birth', 'psa', 'nso', 'registry number'])
def is_enrollment_form(text): return check_keywords(text, ['enrollment form', 'learner enrollment', 'basic education enrollment'])
def is_als_certificate(text): return check_keywords(text, ['alternative learning system', 'als certificate', 'certificate of rating'])
def is_affidavit(text): return check_keywords(text, ['affidavit', 'sworn statement', 'notary public'])
def is_good_moral(text): return check_keywords(text, ['good moral', 'moral character', 'certification of good'])
def is_form_137(text): return check_keywords(text, ['form 137', 'sf10', 'sf 10', 'learner permanent record'])

def matches_doc_type(raw_text, doc_type):
    if doc_type == 'report_card': return is_report_card(raw_text)
    elif doc_type == 'birth_certificate': return is_birth_certificate(raw_text)
    elif doc_type == 'enrollment_form': return is_enrollment_form(raw_text)
    elif doc_type == 'als_certificate': return is_als_certificate(raw_text)
    elif doc_type == 'affidavit': return is_affidavit(raw_text)
    elif doc_type == 'good_moral': return is_good_moral(raw_text)
    elif doc_type == 'form_137': return is_form_137(raw_text)
    return False

# ==========================================
# THE "LRN SNIPER"
# ==========================================
def extract_smart_lrn(raw_text):
    raw_str = str(raw_text).lower()
    perfect_match = re.search(r'(?<!\d)(?:\d[\W_]*){12}(?!\d)', raw_str)
    if perfect_match:
        return re.sub(r'[^\d]', '', perfect_match.group(0))

    corrections = {'o':'0', 'c':'0', 'l':'1', 'i':'1', '|':'1', 'z':'2', 'a':'4', 's':'5', 'b':'8', 'g':'9', 'q':'9', 't':'7', '?':'7', '+':'7', 'e':'3'}
    translated = "".join([corrections.get(c, c) for c in raw_str])
    
    pattern = r'(?<!\d)(?:\d[\W_]*){12,}(?!\d)'
    for match in re.finditer(pattern, translated):
        chunk = match.group(0)
        digits = re.sub(r'[^\d]', '', chunk)
        if len(digits) >= 12:
            start, end = match.span()
            original_chunk = raw_str[start:end]
            if sum(c.isdigit() for c in original_chunk) >= 5:
                return digits[:12]
                
    keyword_match = re.search(r'(lrn|learner|reference|rn|sfq|sfm|lru|1rn)', raw_str)
    if keyword_match:
        chunk = raw_str[keyword_match.end():keyword_match.end()+50]
        translated_chunk = "".join([corrections.get(c, c) for c in chunk])
        pattern_brute = r'(?:\d[\W_a-z]{0,3}){11}\d'
        for match in re.finditer(pattern_brute, translated_chunk):
            brute_chunk = match.group(0)
            brute_digits = re.sub(r'[^\d]', '', brute_chunk)
            if len(brute_digits) >= 12:
                return brute_digits[:12]

    return None

# ==========================================
# THE "NAME SNIPER" (Maximum Forgiveness)
# ==========================================
def fuzzy_match_name(expected_name, text):
    if not expected_name or expected_name.lower() == 'unknown': return True 
    
    name_corrections = {'0':'o', '1':'l', '2':'z', '3':'e', '4':'a', '5':'s', '6':'g', '7':'t', '8':'b', '9':'g', '@':'a', 'c':'e', 'd':'o', 'rn':'m'}
    raw_str = str(text).lower()
    translated_text = "".join([name_corrections.get(c, c) for c in raw_str])
    
    norm_text = clean_alpha_text(translated_text)
    text_no_spaces = norm_text.replace(" ", "")
    parts = clean_alpha_text(expected_name).split()
    matched_parts = 0
    valid_parts = [p for p in parts if len(p) >= 3]
    if not valid_parts: return True 
    
    for part in valid_parts:
        part_no_spaces = part.replace(" ", "")
        part_found = False
        
        # 1. Look for Exact Matches first
        if part_no_spaces in text_no_spaces:
            matched_parts += 1
            continue
            
        # 2. Check individual words with extreme forgiveness (Allows 40% of the word to be completely wrong)
        words = norm_text.split()
        for word in words:
            if difflib.SequenceMatcher(None, part_no_spaces, word).ratio() >= 0.55:
                part_found = True
                break
                
        # 3. Sliding window fallback
        if not part_found:
            window = len(part_no_spaces)
            for i in range(len(text_no_spaces) - window + 1):
                chunk = text_no_spaces[i:i+window]
                if difflib.SequenceMatcher(None, part_no_spaces, chunk).ratio() >= 0.55:
                    part_found = True
                    break
                    
        if part_found:
            matched_parts += 1
            
    # We only need ONE part of the expected name to be found to confidently pass it.
    return matched_parts >= 1

@app.route('/ocr', methods=['POST'])
def ocr():
    if 'image' not in request.files:
        return jsonify({'error': 'No image uploaded'}), 400
    
    doc_type = request.form.get('doc_type', 'generic')
    expected_first = request.form.get('first_name', '').lower()
    expected_last = request.form.get('last_name', '').lower()
    
    print("\n" + "!"*50)
    print(f"📡 COMMUNICATION RADAR: Laravel requested -> [{doc_type.upper()}]")
    print("!"*50)

    try:
        base_image = process_image(request.files['image'])
        
        orientations = [None, cv2.ROTATE_90_CLOCKWISE, cv2.ROTATE_180, cv2.ROTATE_90_COUNTERCLOCKWISE]
        best_raw_text = ""
        max_letters = 0

        for rot in orientations:
            rotated_img = base_image if rot is None else cv2.rotate(base_image, rot)
            results = reader.readtext(rotated_img, detail=0, text_threshold=0.4, contrast_ths=0.1)
            raw_text = " ".join(results).lower()
            cleaned_text = clean_alpha_text(raw_text)
            
            if doc_type != 'generic_name_check' and matches_doc_type(raw_text, doc_type):
                best_raw_text = raw_text
                break
                
            if len(cleaned_text) > max_letters:
                max_letters = len(cleaned_text)
                best_raw_text = raw_text
                
        raw_text = str(best_raw_text)
        cleaned_text = clean_alpha_text(raw_text)
        
        print("\n" + "="*50)
        # 🚨 REMOVED THE [:120] CUTOFF! NOW YOU CAN SEE EVERYTHING! 🚨
        print(f"📄 RAW OCR   : {raw_text}")
        print(f"✨ CLEAN TEXT: {cleaned_text}")
        print("="*50 + "\n")

        response = {'success': False}

        if doc_type == 'report_card':
            print("[*] STEP 1: Verifying Document Type & Extracting LRN...")
            lrn = extract_smart_lrn(raw_text)
            
            if lrn or is_report_card(raw_text):
                print("✅ SUCCESS: Document identified as SF9/Report Card.")
                if not lrn:
                    response['error'] = "SF9 Form verified! Now scanning for 12-digit LRN..."
                    print("❌ FAILED: No 12 digits matched.")
                else:
                    response['success'] = True
                    response['lrn'] = lrn
                    response['message'] = "SF9 and LRN Verified!"
                    print(f"✅ SUCCESS: Final Clean LRN is -> {lrn}")
            else:
                response['error'] = "Looking for SF9 / Report Card..."
                print("❌ FAILED: Wrong Document Type. Halting.")

        elif doc_type == 'birth_certificate':
            print("[*] STEP 1: Verifying Document Type...")
            if not is_birth_certificate(raw_text):
                response['error'] = "Looking for Birth Certificate / PSA..."
                print("❌ FAILED: Wrong Document Type. Halting.")
            else:
                print("✅ SUCCESS: Document identified as Birth Certificate.")
                print(f"[*] STEP 2: Scanning for First Name ({expected_first})...")
                if not fuzzy_match_name(expected_first, raw_text):
                    response['error'] = f"Birth Certificate verified! Scanning for First Name ({expected_first})..."
                    print("❌ FAILED: First Name missing.")
                else:
                    print("✅ SUCCESS: First Name found.")
                    print(f"[*] STEP 3: Scanning for Last Name ({expected_last})...")
                    if not fuzzy_match_name(expected_last, raw_text):
                        response['error'] = f"First Name found! Scanning for Last Name ({expected_last})..."
                        print("❌ FAILED: Last Name missing.")
                    else:
                        print("✅ SUCCESS: Last Name found.")
                        response['success'] = True
                        response['message'] = "Birth Certificate and Full Name Verified!"

        elif doc_type == 'enrollment_form':
            print("[*] STEP 1: Verifying Document Type...")
            if not is_enrollment_form(raw_text):
                response['error'] = "Looking for Enrollment Form..."
                print("❌ FAILED: Wrong Document Type. Halting.")
            else:
                print("✅ SUCCESS: Document identified as Enrollment Form.")
                print(f"[*] STEP 2: Scanning for First Name ({expected_first})...")
                if not fuzzy_match_name(expected_first, raw_text):
                    response['error'] = f"Enrollment Form verified! Scanning for First Name ({expected_first})..."
                else:
                    print(f"[*] STEP 3: Scanning for Last Name ({expected_last})...")
                    if not fuzzy_match_name(expected_last, raw_text):
                        response['error'] = f"First Name found! Scanning for Last Name ({expected_last})..."
                    else:
                        response['success'] = True
                        response['message'] = "Enrollment Form and Full Name Verified!"

        elif doc_type == 'als_certificate':
            print("[*] STEP 1: Verifying Document Type...")
            if not is_als_certificate(raw_text):
                response['error'] = "Looking for ALS Certificate..."
                print("❌ FAILED: Wrong Document Type. Halting.")
            else:
                print("✅ SUCCESS: Document identified as ALS Certificate.")
                print(f"[*] STEP 2: Scanning for First Name ({expected_first})...")
                if not fuzzy_match_name(expected_first, raw_text):
                    response['error'] = f"ALS Certificate verified! Scanning for First Name ({expected_first})..."
                else:
                    print(f"[*] STEP 3: Scanning for Last Name ({expected_last})...")
                    if not fuzzy_match_name(expected_last, raw_text):
                        response['error'] = f"First Name found! Scanning for Last Name ({expected_last})..."
                    else:
                        response['success'] = True
                        response['message'] = "ALS Certificate and Full Name Verified!"

        elif doc_type == 'affidavit':
            print("[*] STEP 1: Verifying Document Type...")
            if not is_affidavit(raw_text):
                response['error'] = "Looking for Affidavit / Sworn Statement..."
                print("❌ FAILED: Wrong Document Type. Halting.")
            else:
                print("✅ SUCCESS: Document identified as Affidavit.")
                print(f"[*] STEP 2: Scanning for First Name ({expected_first})...")
                if not fuzzy_match_name(expected_first, raw_text):
                    response['error'] = f"Affidavit verified! Scanning for First Name ({expected_first})..."
                else:
                    print(f"[*] STEP 3: Scanning for Last Name ({expected_last})...")
                    if not fuzzy_match_name(expected_last, raw_text):
                        response['error'] = f"First Name found! Scanning for Last Name ({expected_last})..."
                    else:
                        response['success'] = True
                        response['message'] = "Affidavit and Full Name Verified!"

        elif doc_type == 'good_moral':
            print("[*] STEP 1: Verifying Document Type...")
            if not is_good_moral(raw_text):
                response['error'] = "Looking for Good Moral Certificate..."
                print("❌ FAILED: Wrong Document Type. Halting.")
            else:
                print("✅ SUCCESS: Document identified as Good Moral Certificate.")
                print(f"[*] STEP 2: Scanning for First Name ({expected_first})...")
                if not fuzzy_match_name(expected_first, raw_text):
                    response['error'] = f"Good Moral verified! Scanning for First Name ({expected_first})..."
                else:
                    print(f"[*] STEP 3: Scanning for Last Name ({expected_last})...")
                    if not fuzzy_match_name(expected_last, raw_text):
                        response['error'] = f"First Name found! Scanning for Last Name ({expected_last})..."
                    else:
                        response['success'] = True
                        response['message'] = "Good Moral Certificate and Full Name Verified!"

        elif doc_type == 'form_137':
            print("[*] STEP 1: Verifying Document Type...")
            if not is_form_137(raw_text):
                response['error'] = "Looking for Form 137 / SF10..."
                print("❌ FAILED: Wrong Document Type. Halting.")
            else:
                print("✅ SUCCESS: Document identified as Form 137.")
                print(f"[*] STEP 2: Scanning for First Name ({expected_first})...")
                if not fuzzy_match_name(expected_first, raw_text):
                    response['error'] = f"Form 137 verified! Scanning for First Name ({expected_first})..."
                else:
                    print(f"[*] STEP 3: Scanning for Last Name ({expected_last})...")
                    if not fuzzy_match_name(expected_last, raw_text):
                        response['error'] = f"First Name found! Scanning for Last Name ({expected_last})..."
                    else:
                        response['success'] = True
                        response['message'] = "Form 137 and Full Name Verified!"

        else:
            print("[*] STEP 1: Performing Generic Check...")
            response['error'] = "Scanning document..."

        return jsonify(response)
        
    except Exception as e:
        print(f"❌ Error during OCR: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status', methods=['GET'])
def server_status():
    return jsonify({'status': 'online'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9001, debug=True)