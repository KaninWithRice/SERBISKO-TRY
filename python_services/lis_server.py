from flask import Flask, request, jsonify
from flask_cors import CORS
import threading, requests, time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import urllib3

# Suppress the local SSL warning in the terminal
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

app = Flask(__name__)
CORS(app)

# CONFIG
LIS_URL = "https://learner-information-system-dashboard-540972607515.us-west1.run.app/"
CREDS = ("depedsample@gmail.com", "deped123")

def run_check(lrn, expected_grade, webhook, scan_id):
    print(f"[*] Verifying LRN: {lrn}. Looking for: {expected_grade} completer.")
    result = "failed_lis"
    driver = None
    
    try:
        options = webdriver.ChromeOptions()
        options.add_experimental_option("detach", True) 
        driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)
        
        driver.get(LIS_URL)
        
        # Explicitly wait up to 15 seconds for the Email field to appear
        wait = WebDriverWait(driver, 15)
        email_input = wait.until(EC.presence_of_element_located((By.XPATH, "//input[@type='email']")))
        
        # Login
        email_input.send_keys(CREDS[0])
        driver.find_element(By.XPATH, "//input[@type='password']").send_keys(CREDS[1])
        driver.find_element(By.XPATH, "//button[@type='submit']").click()
        
        # Navigate & Search
        driver.implicitly_wait(10)
        driver.find_element(By.XPATH, "//*[contains(text(),'Masterlist')]").click()
        driver.find_element(By.XPATH, "//*[contains(text(),'Enrol Learner')]").click()
        driver.find_element(By.XPATH, "//*[contains(text(),'Proceed')]").click()
        
        inp = driver.find_element(By.XPATH, "//input[@placeholder='Search LRN' or contains(@aria-label,'Search')]")
        inp.send_keys(lrn)
        inp.find_element(By.XPATH, "./following-sibling::button").click()
        
        # --- Check Result ---
        try:
            print("[*] Searching for student record...")
            wait_preview = WebDriverWait(driver, 5)
            preview_btn = wait_preview.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(),'Preview')]")))
            
            # Force click using Javascript
            driver.execute_script("arguments[0].click();", preview_btn)
            print("[*] Preview clicked! Waiting for modal to open...")
            
            # Wait for modal text
            wait_modal = WebDriverWait(driver, 10)
            wait_modal.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(),'Most recent enrolment')]")))
            
            time.sleep(1) 
            text = driver.find_element(By.TAG_NAME, "body").text
            
            print("\n=== WHAT SELENIUM SEES ===")
            print(text[:800] + "...") 
            print("==========================\n")
            
            # --- DYNAMIC GRADE CHECK ---
            if expected_grade.lower() in text.lower():
                result = "verified_lis"
                print(f"✅ SUCCESS: {expected_grade} verified successfully in LIS!")
            else:
                result = "failed_lis"
                print(f"❌ FAILED: Grade mismatch. Expected {expected_grade}, but it was not found in the modal.")
                
        except Exception as e:
            result = "failed_lis"
            print("❌ LRN not found or Modal failed to open.")
            
        driver.quit()
    except Exception as e:
        print(f"❌ Selenium Error: {e}")
        if driver:
            try:
                driver.quit()
            except:
                pass

    # --- UPDATED: Actively print Laravel's HTTP Response ---
    print(f"[*] Sending webhook to Laravel at: {webhook}")
    try:
        response = requests.post(webhook, json={'scan_id': scan_id, 'result': result}, verify=False)
        
        if response.status_code == 200:
            print("✅ Webhook accepted! Laravel updated the database successfully.")
        else:
            print(f"❌ Webhook REJECTED by Laravel! Status Code: {response.status_code}")
            print(f"Laravel Response: {response.text[:300]}")
    except Exception as e:
        print(f"❌ Webhook Network Error: {e}")

@app.route('/verify', methods=['POST'])
def verify():
    data = request.json
    lrn = data.get('lrn')
    expected_grade = data.get('expected_grade')
    webhook_url = data.get('webhook_url')
    scan_id = data.get('scan_id')

    threading.Thread(target=run_check, args=(lrn, expected_grade, webhook_url, scan_id)).start()
    return jsonify({'status': 'started'})

@app.route('/status', methods=['GET'])
def server_status():
    return jsonify({'status': 'online'})

if __name__ == '__main__':
    print("Starting LIS Verifier on Port 5001...")
    app.run(host='0.0.0.0', port=5001, debug=True)