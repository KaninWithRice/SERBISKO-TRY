import os
import time
import traceback
import random
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# DepEd LIS credentials and URL
LIS_URL = "https://learner-information-system-lis-dashboard-352866309332.us-west1.run.app/"
LIS_EMAIL = "gamboakenleam1015@gmail.com"
LIS_PASSWORD = "password432"


def find_chrome_path():
    possible_paths = [
        r"C:\Program Files\Google\Chrome\Application\chrome.exe",
        r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
        os.path.expandvars(r"%ProgramFiles%\Google\Chrome\Application\chrome.exe"),
        os.path.expandvars(r"%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"),
    ]
    for path in possible_paths:
        if os.path.exists(path):
            print(f"[DEBUG] Found Chrome at: {path}")
            return path
    print("[WARN] Chrome not found in standard locations; using system default")
    return None


def get_chrome_options():
    from selenium.webdriver.chrome.options import Options

    options = Options()
    chrome_path = find_chrome_path()
    if chrome_path:
        options.binary_location = chrome_path
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-infobars")
    options.add_argument("--disable-notifications")
    options.add_argument("--disable-save-password-bubble")
    options.add_argument("--disable-autofill")
    options.add_argument("--disable-sync")
    options.add_experimental_option("excludeSwitches", ["enable-automation", "enable-logging"])
    options.add_experimental_option("useAutomationExtension", False)
    return options


def get_chromedriver_path():
    from webdriver_manager.chrome import ChromeDriverManager

    path = ChromeDriverManager().install()
    if path and not path.lower().endswith('.exe'):
        candidate = os.path.join(os.path.dirname(path), 'chromedriver.exe')
        if os.path.exists(candidate):
            return candidate
    return path


def js_click(driver, xpath):
    script = (
        "const el = document.evaluate(arguments[0], document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;"
        "if (!el) return false; el.scrollIntoView({block:'center'}); el.click(); return true;"
    )
    return driver.execute_script(script, xpath)


def click_element(driver, wait, xpaths, name, timeout=5):
    for xp in xpaths:
        try:
            if js_click(driver, xp):
                print(f"[OK] Clicked {name} via JS XPath: {xp}")
                return True
        except Exception as exc:
            print(f"[DEBUG] JS click failed for {name} xpath {xp}: {exc}")
    raise Exception(f"Could not click {name} using any of the provided XPaths")


def click_masterlist(driver, wait):
    masterlist_xpaths = [
        "//button[normalize-space()='Masterlist']",
        "//a[normalize-space()='Masterlist']",
        "//span[normalize-space()='Masterlist']/ancestor::button",
        "//span[normalize-space()='Masterlist']/ancestor::a"
    ]
    click_element(driver, wait, masterlist_xpaths, 'Masterlist')
    try:
        wait.until(EC.presence_of_element_located((By.XPATH, "//h1[normalize-space()='Masterlist'] | //h2[normalize-space()='Masterlist'] | //div[contains(normalize-space(.),'Masterlist')]")))
        print("[OK] Masterlist page confirmed")
        return True
    except Exception:
        print("[WARN] Could not confirm Masterlist page by header")
        return False


def select_random_section(driver, wait):
    try:
        from selenium.webdriver.support.select import Select

        select_elem = wait.until(EC.presence_of_element_located((By.XPATH,
            "//select[contains(@class,'w-full') or contains(@class,'rounded-sm') or contains(@class,'text-sm') or contains(@class,'select')]"
        )))
        select = Select(select_elem)

        options = [opt for opt in select.options if opt.get_attribute('value') and not opt.get_attribute('disabled') and opt.text.strip()]
        if not options:
            raise Exception('No valid section options found')

        selected = random.choice(options)
        select.select_by_visible_text(selected.text)
        print(f"[OK] Random section selected: {selected.text}")
        return True
    except Exception as exc:
        print(f"[WARN] Could not select a random section: {exc}")
        return False


def enrol_and_search_lrn(driver, wait, lrn):
    print(f"[INFO] Starting enrollment search for LRN: {lrn}")
    try:
        # 1. Click Enrol Learner
        enrol_xpaths = [
            "//button[contains(normalize-space(.), 'Enrol Learner') or contains(normalize-space(.), 'Enroll Learner') or contains(normalize-space(.), 'Enroll')]",
            "//a[contains(normalize-space(.), 'Enrol Learner') or contains(normalize-space(.), 'Enroll Learner') or contains(normalize-space(.), 'Enroll')]",
        ]
        click_element(driver, wait, enrol_xpaths, "Enrol Learner")
        time.sleep(2)

        # 2. Click Proceed Enrollment
        print("[INFO] Attempting to click Proceed Enrollment...")
        proceed_xpaths = [
            "//button[contains(normalize-space(.), 'Proceed') or contains(normalize-space(.), 'Continue') or contains(normalize-space(.), 'Next')]",
            "//a[contains(normalize-space(.), 'Proceed') or contains(normalize-space(.), 'Continue') or contains(normalize-space(.), 'Next')]",
            "//input[@value='Proceed' or @value='Continue' or @value='Next']",
            "//*[contains(@class, 'button') and contains(., 'Proceed')]",
        ]
        click_element(driver, wait, proceed_xpaths, "Proceed Enrollment")
        
        # Wait for the page to transition - the "Proceed Enrollment" text should ideally disappear or a new header appear
        print("[INFO] Waiting for search page to load...")
        time.sleep(4)

        # 3. Search LRN
        print(f"[INFO] Looking for search input field...")
        
        # Try to find an input that is likely the LRN search, avoiding the header search if possible
        search_input_xpaths = [
            "//main//input[contains(@placeholder,'Search') or contains(@placeholder,'LRN')]",
            "//input[contains(@placeholder,'Search') or contains(@aria-label,'Search') or @type='search' or contains(@placeholder,'LRN')]"
        ]
        
        search_input = None
        for xpath in search_input_xpaths:
            inputs = driver.find_elements(By.XPATH, xpath)
            for si in inputs:
                if si.is_displayed():
                    search_input = si
                    break
            if search_input: break
            
        if not search_input:
            # Fallback to any visible input
            inputs = driver.find_elements(By.XPATH, "//input[@type='text' or @type='search']")
            for si in inputs:
                if si.is_displayed():
                    search_input = si
                    break
        
        if not search_input:
            raise Exception("No visible search input found")
            
        # Scroll to ensure visibility
        driver.execute_script("arguments[0].scrollIntoView({block:'center'});", search_input)
        time.sleep(1)
        
        # Focus and Type
        try:
            search_input.click()
        except:
            driver.execute_script("arguments[0].click();", search_input)
        
        search_input.clear()
        time.sleep(0.5)
        
        print(f"[INFO] Typing LRN: {lrn}")
        for char in lrn:
            search_input.send_keys(char)
            time.sleep(0.05)
        
        time.sleep(1)
        
        # Try to click search button or press Enter
        print("[INFO] Triggering search...")
        try:
            # Look for button in the same container or nearby
            search_button_xpath = ".//following-sibling::button | .//ancestor::div[1]//button | .//ancestor::form//button"
            search_buttons = search_input.find_elements(By.XPATH, search_button_xpath)
            clicked = False
            for btn in search_buttons:
                if "search" in btn.text.lower() or "go" in btn.text.lower() or not btn.text.strip():
                    driver.execute_script("arguments[0].click();", btn)
                    print(f"[OK] Search button clicked: {btn.text}")
                    clicked = True
                    break
            if not clicked:
                raise Exception("No obvious search button found near input")
        except Exception as e:
            print(f"[DEBUG] Search button click failed or not found: {e}")
            from selenium.webdriver.common.keys import Keys
            search_input.send_keys(Keys.ENTER)
            print("[OK] Enter pressed for search")

        # Wait for results to load or an alert
        print("[INFO] Waiting for search results...")
        try:
            # Check for alert first
            wait_alert = WebDriverWait(driver, 3)
            alert = wait_alert.until(EC.alert_is_present())
            alert_text = alert.text
            print(f"[WARN] Alert appeared: {alert_text}")
            alert.accept()
            time.sleep(1)
        except:
            # No alert, which is good if search was successful
            time.sleep(4)

        # Verify search success
        page_text = driver.find_element(By.TAG_NAME, "body").text
        
        if lrn in page_text or "Preview" in page_text:
            print("[OK] Search result found (LRN or Preview button visible)")
            print("successful")
            return True
        else:
            # Fallback check in page source
            if lrn in driver.page_source:
                print("[OK] LRN found in page source")
                print("successful")
                return True
            
            # Print title to see where we are
            print(f"[DEBUG] Current Page Title: {driver.title}")
            print(f"[DEBUG] Current URL: {driver.current_url}")
            
            # Check for common "No results" or "Not found"
            if "not found" in page_text.lower() or "no results" in page_text.lower() or "no data" in page_text.lower():
                print(f"[WARN] Search returned 'No results' for LRN: {lrn}")
            else:
                print("[WARN] LRN not found in search results.")
            
            return False

    except Exception as e:
        print(f"[ERROR] Enrollment search failed: {e}")
        return False


def login_and_open_masterlist():
    print("[START] DepEd LIS navigation helper")
    driver = None
    try:
        chrome_path = find_chrome_path()
        options = get_chrome_options()
        driver_path = get_chromedriver_path()
        print(f"[DEBUG] ChromeDriver path: {driver_path}")

        service = Service(driver_path)
        driver = webdriver.Chrome(service=service, options=options)
        driver.maximize_window()

        driver.get(LIS_URL)
        print(f"[INFO] Opened LIS URL: {LIS_URL}")

        wait = WebDriverWait(driver, 12)

        # Fill login form
        email_xpaths = [
            "//input[contains(@type,'email') or contains(@placeholder,'Email') or contains(translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'email')]",
            "//input[contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'email') or contains(translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'email')]"]
        password_xpaths = [
            "//input[@type='password']",
            "//input[contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'password') or contains(translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'password')]"
        ]

        email_field = wait.until(EC.presence_of_element_located((By.XPATH, " | ".join(email_xpaths))))
        email_field.clear()
        email_field.send_keys(LIS_EMAIL)
        print(f"[OK] Entered email")

        password_field = wait.until(EC.presence_of_element_located((By.XPATH, " | ".join(password_xpaths))))
        password_field.clear()
        password_field.send_keys(LIS_PASSWORD)
        print(f"[OK] Entered password")

        login_xpaths = [
            "//button[contains(normalize-space(.),'Sign in') or contains(normalize-space(.),'Sign In') or contains(normalize-space(.),'Login') or contains(normalize-space(.),'Log In')]",
            "//input[@type='submit' and (contains(translate(@value,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'sign in') or contains(translate(@value,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'login'))]"
        ]
        click_element(driver, wait, login_xpaths, 'login button')
        print("[OK] Login button clicked")

        # Wait for navigation to show Dashboard or Masterlist
        try:
            wait.until(EC.any_of(
                EC.presence_of_element_located((By.XPATH, "//button[normalize-space()='Masterlist'] | //a[normalize-space()='Masterlist']")),
                EC.presence_of_element_located((By.XPATH, "//div[contains(.,'Dashboard') or contains(.,'Masterlist')]"))
            ))
            print("[OK] Login succeeded and LIS navigation elements are visible")
        except Exception:
            print("[WARN] Login may have succeeded but main navigation did not appear quickly")

        # Click Masterlist directly
        masterlist_success = click_masterlist(driver, wait)
        if masterlist_success:
            print("[SUCCESS] Masterlist click succeeded")
            section_selected = select_random_section(driver, wait)
            if section_selected:
                print("[SUCCESS] Random section selected successfully")
            else:
                print("[WARN] Random section selection failed")
            
            # New step: Enrol Learner and Search LRN
            search_success = enrol_and_search_lrn(driver, wait, "006346555172")
            
            if search_success:
                print("[SUCCESS] LRN search completed successfully")
            else:
                print("[FAILED] LRN search failed")

            print("[INFO] Waiting 3 seconds so you can see the results")
            time.sleep(3)
        else:
            print("[FAILED] Masterlist click succeeded, but confirmation failed")
            print("[INFO] Waiting 3 seconds before ending so you can inspect the browser")
            time.sleep(3)

        print("[INFO] Browser will remain open for manual inspection")
        print("[INFO] The program will exit automatically once the browser window is manually closed")
        while True:
            try:
                _ = driver.title
            except Exception:
                print("[INFO] Browser window was closed manually")
                break
            time.sleep(1)
        return masterlist_success
    except Exception:
        print("[ERROR] DepEd LIS navigation failed")
        traceback.print_exc()
        return False
    finally:
        if driver:
            print("[INFO] Leaving browser open. Close manually when done.")


if __name__ == '__main__':
    success = login_and_open_masterlist()
    if success:
        print('MASTERLIST_NAVIGATION_RESULT: success')
    else:
        print('MASTERLIST_NAVIGATION_RESULT: failure')
