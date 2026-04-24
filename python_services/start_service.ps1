# Auto-restart enrollment filler service with error recovery
$servicePath = "D:\Users\Ryzen 3\Desktop\Serbisko\serbisko-v2\python_services"
$logFile = "$servicePath\service_crashes.log"
$maxAttempts = 999
$attempt = 0

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Enrollment Filler Service Launcher" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Service running on: http://127.0.0.1:5002" -ForegroundColor Green
Write-Host "Logs will be saved to: $logFile" -ForegroundColor Green
Write-Host ""

while ($attempt -lt $maxAttempts) {
    $attempt++
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$timestamp] Starting attempt $attempt..." -ForegroundColor Green

    # Clean up any old enrollment filler processes that may still be bound to port 5002
    Write-Host "[$timestamp] Checking for stale enrollment filler processes..." -ForegroundColor Yellow
    $existingProcs = Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -and $_.CommandLine -match 'enrollment_form_filler\.py' }
    foreach ($proc in $existingProcs) {
        Write-Host "[$timestamp] Terminating stale process PID $($proc.ProcessId)" -ForegroundColor Yellow
        Stop-Process -Id $proc.ProcessId -Force -ErrorAction SilentlyContinue
    }
    
    try {
        Set-Location $servicePath
        & python3 enrollment_form_filler.py 2>&1
    }
    catch {
        $errorMsg = $_.Exception.Message
        Write-Host "[$timestamp] [ERROR] $errorMsg" -ForegroundColor Red
        Add-Content $logFile "[$timestamp] Crash (Attempt $attempt): $errorMsg"
    }
    
    Write-Host ""
    Write-Host "[$timestamp] [WARNING] Service stopped. Restarting in 5 seconds..." -ForegroundColor Yellow
    Add-Content $logFile "[$timestamp] Service restarting (Attempt $attempt -> $($attempt + 1))"
    Start-Sleep -Seconds 5
}

Write-Host "[CRITICAL] Service failed after $maxAttempts attempts." -ForegroundColor Red
Add-Content $logFile "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') CRITICAL: Max attempts reached"
