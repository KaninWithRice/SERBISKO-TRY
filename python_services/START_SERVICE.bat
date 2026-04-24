#!/bin/bash
# Enrollment Form Filler Startup Script
# This script properly sets up and runs the enrollment form filler service

echo "========================================"
echo "ENROLLMENT FORM FILLER STARTUP"
echo "========================================"

cd /d "D:\Users\Ryzen 3\Desktop\Serbisko\serbisko-v2\python_services"

echo "[1] Checking Python installation..."
python --version

echo "[2] Installing/updating dependencies..."
python -m pip install --upgrade pip
python -m pip install flask flask-cors selenium webdriver-manager urllib3

echo "[3] Starting enrollment filler service..."
echo "Server will run on: http://localhost:5002"
echo ""

python enrollment_form_filler.py
