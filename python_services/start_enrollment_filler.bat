@echo off
REM Enrollment Form Filler Service Launcher for Windows
REM This batch file starts the Python enrollment form filler service on port 5002

REM Optionally set PYTHON_EXE to a specific python.exe before running this script.
REM Example: set "PYTHON_EXE=C:\Users\Ryzen 3\AppData\Local\Python\pythoncore-3.14-64\python.exe"

REM Default to a known working Python install path used in this workspace.
if "%PYTHON_EXE%"=="" (
    set "PYTHON_EXE=C:\Users\Ryzen 3\AppData\Local\Python\pythoncore-3.14-64\python.exe"
)

REM If the explicit interpreter does not exist, fall back to whatever is on PATH.
if not exist "%PYTHON_EXE%" (
    echo [WARN] %PYTHON_EXE% not found.
    echo [WARN] Falling back to python on PATH.
    set "PYTHON_EXE=python"
)

cd /d "%~dp0"

echo [*] Using python: %PYTHON_EXE%
"%PYTHON_EXE%" -c "import sys, encodings; print('python:', sys.executable)" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python failed to start or missing standard library (encodings).
    echo [ERROR] Please ensure you are using a proper Python installation.
    pause
    exit /b 1
)

echo [*] Installing/updating dependencies...
"%PYTHON_EXE%" -m pip install -r requirements.txt
if errorlevel 1 (
    echo [ERROR] Failed to install dependencies
    pause
    exit /b 1
)

echo.
echo ========================================
echo Starting Enrollment Form Filler...
echo ========================================
echo Port: 5002
echo Endpoint: http://localhost:5002/fill-enrollment
echo Status: http://localhost:5002/status
echo ========================================
echo.
echo [*] A Chrome browser window may open for testing
echo [*] This is normal - it means the service is working
echo.

"%PYTHON_EXE%" enrollment_form_filler.py

pause
