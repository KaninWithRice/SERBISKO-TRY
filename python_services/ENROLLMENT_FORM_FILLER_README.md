# Enrollment Form Filler Service

## Overview

The Enrollment Form Filler is a Python service that automates the process of filling the LIS (Learner Information System) enrollment form with student data from Serbisko profiles. 

**Key Features:**
- Opens the LIS enrollment form automatically
- Pre-fills form fields with student information from Serbisko
- Does NOT automatically submit the enrollment
- Allows admin to verify and manually confirm enrollment
- Browser window remains open for review and manual submission

## How It Works

1. Admin views a student profile in Serbisko
2. Admin clicks the **"Use Profile"** button
3. The service opens a Chrome browser with the LIS website
4. Automatically logs in and navigates to the enrollment form
5. Fills in all student information
6. **Stops and waits** - Admin can now:
   - Verify all the filled information
   - Adjust any fields if needed
   - Manually click the "Submit" or "Enroll" button in LIS
7. Browser stays open until admin closes it or the service is stopped

## Installation & Setup

### Prerequisites
- Python 3.8 or higher
- **Google Chrome** browser installed (recommended) OR Firefox
- Windows, Mac, or Linux

### Step 1: Install Python Dependencies

Run this command from the `python_services/` directory:

```bash
pip install flask flask-cors selenium webdriver-manager urllib3
```

### Step 2: Update LIS Credentials (if needed)

Edit `enrollment_form_filler.py` and update these credentials to match your LIS account:

```python
CREDS = ("depedsample@gmail.com", "deped123")
```

Alternatively, you can modify the script to accept credentials from environment variables or a config file.

## Running the Service

### Option A: Using Batch File (Windows)

1. Double-click `start_enrollment_filler.bat` in the `python_services/` folder
2. A terminal window will open showing the service status
3. The service will start on `http://localhost:5002`

### Option B: Using Terminal/Command Line

```bash
cd python_services
python enrollment_form_filler.py
```

You should see:
```
============================================================
Starting Enrollment Form Filler Service on Port 5002...
============================================================
Endpoint: POST http://localhost:5002/fill-enrollment
Status: GET http://localhost:5002/status
============================================================
```

## Usage in Serbisko Admin Panel

1. Navigate to **Admin → Students**
2. Click **View** on a student's row to open their profile
3. Click the **"Use Profile"** button at the top right
4. A status message will appear:
   - ✓ **Success**: A Chrome browser opens with the LIS enrollment form
   - ✗ **Error**: Check that the service is running on port 5002

## What the Service Sends to LIS

The following student information is automatically filled in the LIS enrollment form:

| Field | Source |
|-------|--------|
| LRN | Student LRN |
| First Name | Student's first name |
| Last Name | Student's last name |
| Middle Name | Student's middle name |
| Extension | Suffix (Jr., Sr., etc.) |
| Birthday | Date of birth |
| Sex | Gender |
| Mother Tongue | Primary language |
| Place of Birth | Birthplace |
| Age | Calculated age |
| Contact Number | Phone number |
| Grade Level | Enrolled grade level |
| Track | Academic track (STEM, ASSH, etc.) |
| Cluster | Elective cluster |
| Academic Status | Enrollment status |

## Troubleshooting

### Error: "Service not detected on port 5002"

**Problem**: The Enrollment Form Filler service is not running.

**Solution**:
1. Make sure the service is running: `python enrollment_form_filler.py`
2. Check if port 5002 is available: `netstat -ano | findstr :5002` (Windows)
3. If port 5002 is in use, modify the port in `enrollment_form_filler.py`

### Error: "Failed to fill form"

**Problem**: Form fields could not be located on the LIS website.

**Solution**:
1. The LIS UI may have changed
2. Manually open LIS and check the form structure
3. Update the XPath selectors in the `fill_enrollment_form()` function
4. File an issue with updated form structure details

### Chrome asks to set as default browser

This is normal and will not affect the automation. You can click "Not now" or let it proceed - the automation continues regardless.

### Form not pre-filled with student data

**Possible causes**:
1. LIS website structure has changed
2. Some form fields may not have matching selectors in the script
3. Field names or IDs on LIS have been updated

**Solution**:
- Check the browser console (F12) for any errors
- The script logs which fields were successfully filled
- Update XPath selectors in the Python script to match current LIS form

## API Endpoints

### POST /fill-enrollment

Triggers the form filling process.

**Request:**
```json
{
  "lrn": "123456789012",
  "first_name": "John",
  "last_name": "Doe",
  "middle_name": "M",
  "extension_name": "Jr.",
  "birthday": "2005-06-15",
  "sex": "Male",
  "mother_tongue": "Filipino",
  "place_of_birth": "Manila",
  "age": "20",
  "contact_number": "09123456789",
  "grade_level": "11",
  "track": "Academic",
  "cluster": "STEM",
  "academic_status": "Transferee"
}
```

**Response:**
```json
{
  "status": "started",
  "message": "Enrollment form filler started for LRN 123456789012. Check the browser window.",
  "lrn": "123456789012"
}
```

### GET /status

Check if the service is online.

**Response:**
```json
{
  "status": "online",
  "driver_active": false,
  "message": "Enrollment Form Filler Service"
}
```

## Customization

### Adding New Form Fields

Edit the `fill_enrollment_form()` function in `enrollment_form_filler.py`:

```python
form_data = {
    'LRN': student_data.get('lrn', ''),
    'Last Name': student_data.get('last_name', ''),
    # Add more fields here
    'Your New Field': student_data.get('new_field_key', ''),
}
```

### Changing the Port

Edit the port number in `enrollment_form_filler.py`:

```python
if __name__ == '__main__':
    app.run(host='0.0.0.0', port=YOUR_PORT_HERE, debug=True)
```

And update the port in the Serbisko profile page JavaScript:

```javascript
const response = await fetch('http://localhost:YOUR_PORT_HERE/fill-enrollment', {
    ...
});
```

### Running in Headless Mode

To run Chrome without showing the window (not recommended as admin needs to verify):

Uncomment this line in `enrollment_form_filler.py`:
```python
# options.add_argument("--headless")
```

## Security Considerations

⚠️ **Important**: 

- **Credentials**: The LIS credentials are stored in the Python script. For production, use environment variables:
  ```python
  import os
  CREDS = (os.getenv('LIS_EMAIL'), os.getenv('LIS_PASSWORD'))
  ```

- **Network**: This service runs on localhost (`127.0.0.1:5002`) by default. It's only accessible from your machine.

- **CORS**: CORS is enabled to allow requests from the Serbisko web app. Restrict this in production if needed.

## Logs & Debugging

The service prints detailed logs to the terminal. Look for:

- `[*]` - Information/progress
- `✓` - Success messages
- `✗` - Errors
- `[-]` - Warnings

Example output:
```
[*] Starting Enrollment Form Filler
[*] Student LRN: 123456789012
[*] Navigating to LIS website...
[*] Logging in...
[*] Starting form filling...
  ✓ Filled LRN: 123456789012
  ✓ Filled First Name: John
  ✗ Error filling Contact Number: Element not found
[!] ========================================
[!] FORM FILLED - AWAITING ADMIN VERIFICATION
[!] ========================================
```

## Stopping the Service

- **Terminal**: Press `Ctrl + C` to stop the Python process
- **Browser**: Any open Chrome windows will remain open even after stopping the service

## Common Use Cases

### Scenario 1: Bulk Enrollment
Admin needs to enroll multiple students quickly:
1. Keep the service running
2. Open each student profile sequentially
3. Click "Use Profile" for each
4. Manually submit each LIS form
5. Repeat for next student

### Scenario 2: Verification Process
Admin wants to double-check all information before official enrollment:
1. Click "Use Profile"
2. The form pre-fills in LIS
3. Admin verifies each field carefully
4. Admin corrects any discrepancies manually
5. Admin submits when satisfied

## Frequently Asked Questions

**Q: Why doesn't it automatically submit the enrollment?**  
A: The admin must manually verify that all information filled correctly before submission to prevent enrollment errors.

**Q: Can I run multiple instances?**  
A: Yes, but use different ports for each instance to avoid conflicts.

**Q: What if LIS requires additional fields not in Serbisko?**  
A: Admin can manually fill those fields after the form is pre-populated.

**Q: Does it work on Mac/Linux?**  
A: Yes, the same Python script works on all operating systems. Use the appropriate command to run Python.

## Support & Issues

If you encounter issues:

1. Check that Chrome is installed
2. Verify the service is running on the correct port
3. Inspect the browser console (F12) for JavaScript errors
4. Check the terminal/console where the Python service is running for error messages
5. Verify LIS website is accessible from your browser

For LIS-specific form structure changes, update the XPath selectors in the script.

---

**Service Version**: 1.0  
**Last Updated**: March 16, 2026  
**Compatible With**: Serbisko v2.0+
