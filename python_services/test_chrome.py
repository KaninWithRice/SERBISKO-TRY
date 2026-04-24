from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager

print('Testing Chrome automation...')
try:
    options = Options()
    # Add arguments to make it work with existing Chrome
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    # options.add_argument("--remote-debugging-port=9222") # Can cause conflicts if port is already in use.

    print('Installing ChromeDriver...')
    service = Service(ChromeDriverManager().install())
    print('Creating Chrome driver...')
    driver = webdriver.Chrome(service=service, options=options)
    print('Navigating to Google...')
    driver.get('https://www.google.com')
    print(f'SUCCESS: Page title is "{driver.title}"')
    driver.quit()
    print('Chrome automation test PASSED!')
except Exception as e:
    print(f'ERROR: {str(e)}')
    print('Chrome automation test FAILED!')