#!/bin/bash

# Kill existing instances
echo "[*] Cleaning up existing processes..."
pkill -9 -f ocr_server.py
pkill -9 -f "node.*sync.js"

sleep 2

# Start OCR Server
echo "[+] Starting OCR Server..."
nohup python3 -u python_services/ocr_server.py > ocr_real_v6.log 2>&1 &

# Start Sync Bridge
echo "[+] Starting Sync Bridge..."
cd bridge && nohup node sync.js > sync.log 2>&1 &

echo "[!] Services are running in the background."
echo "    - OCR Log: ocr_real_v6.log"
echo "    - Sync Log: bridge/sync.log"
