# Fix Ollama Service Paused State
# This script resolves the SERVICE_PAUSED issue

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Fix Ollama Service Paused State" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "[ERROR] This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

$serviceName = "Ollama"
$nssmPath = "$PSScriptRoot\nssm\win64\nssm.exe"

# Check if service exists
$service = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
if (-not $service) {
    Write-Host "[ERROR] Service '$serviceName' not found!" -ForegroundColor Red
    Write-Host "Please run install_ollama_service.ps1 first" -ForegroundColor Yellow
    exit 1
}

Write-Host "Current Service Status: $($service.Status)" -ForegroundColor White
Write-Host ""

# Step 1: Stop all Ollama processes
Write-Host "[STEP 1] Stopping all Ollama processes..." -ForegroundColor Yellow
$ollamaProcesses = Get-Process -Name "ollama" -ErrorAction SilentlyContinue
if ($ollamaProcesses) {
    Write-Host "  Found $($ollamaProcesses.Count) Ollama process(es)" -ForegroundColor Gray
    Stop-Process -Name "ollama" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    Write-Host "  [OK] Processes stopped" -ForegroundColor Green
} else {
    Write-Host "  [OK] No Ollama processes running" -ForegroundColor Green
}
Write-Host ""

# Step 2: Stop the service
Write-Host "[STEP 2] Stopping Ollama service..." -ForegroundColor Yellow
try {
    & $nssmPath stop $serviceName 2>&1 | Out-Null
    Start-Sleep -Seconds 3
    Write-Host "  [OK] Service stop command sent" -ForegroundColor Green
} catch {
    Write-Host "  [WARNING] Service may already be stopped" -ForegroundColor Yellow
}
Write-Host ""

# Step 3: Verify service is stopped
Write-Host "[STEP 3] Verifying service is stopped..." -ForegroundColor Yellow
$service.Refresh()
$maxAttempts = 10
$attempt = 0

while ($service.Status -ne 'Stopped' -and $attempt -lt $maxAttempts) {
    Write-Host "  Waiting for service to stop... (attempt $($attempt + 1)/$maxAttempts)" -ForegroundColor Gray
    Start-Sleep -Seconds 2
    $service.Refresh()
    $attempt++
}

if ($service.Status -eq 'Stopped') {
    Write-Host "  [OK] Service is stopped" -ForegroundColor Green
} else {
    Write-Host "  [WARNING] Service status: $($service.Status)" -ForegroundColor Yellow
    Write-Host "  Attempting force stop..." -ForegroundColor Yellow
    
    # Force kill any remaining processes
    Get-Process -Name "ollama" -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 3
}
Write-Host ""

# Step 4: Check port availability
Write-Host "[STEP 4] Checking port 11434..." -ForegroundColor Yellow
$portInUse = Get-NetTCPConnection -LocalPort 11434 -ErrorAction SilentlyContinue
if ($portInUse) {
    Write-Host "  [WARNING] Port 11434 is still in use" -ForegroundColor Yellow
    foreach ($conn in $portInUse) {
        $process = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
        if ($process) {
            Write-Host "    Killing process: $($process.Name) (PID: $($process.Id))" -ForegroundColor Gray
            Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
        }
    }
    Start-Sleep -Seconds 2
    Write-Host "  [OK] Port cleared" -ForegroundColor Green
} else {
    Write-Host "  [OK] Port 11434 is available" -ForegroundColor Green
}
Write-Host ""

# Step 5: Start the service
Write-Host "[STEP 5] Starting Ollama service..." -ForegroundColor Yellow
& $nssmPath start $serviceName

Start-Sleep -Seconds 5

# Step 6: Monitor service startup
Write-Host "[STEP 6] Monitoring service startup..." -ForegroundColor Yellow
$maxAttempts = 10
$attempt = 0
$serviceStarted = $false

while ($attempt -lt $maxAttempts) {
    $service.Refresh()
    
    Write-Host "  Attempt $($attempt + 1)/$maxAttempts - Status: $($service.Status)" -ForegroundColor Gray
    
    if ($service.Status -eq 'Running') {
        $serviceStarted = $true
        Write-Host "  [OK] Service is running!" -ForegroundColor Green
        break
    } elseif ($service.Status -eq 'Paused') {
        Write-Host "  [WARNING] Service is paused, attempting to continue..." -ForegroundColor Yellow
        & $nssmPath continue $serviceName
        Start-Sleep -Seconds 3
    } elseif ($service.Status -eq 'StartPending') {
        Write-Host "  [INFO] Service is starting..." -ForegroundColor Gray
        Start-Sleep -Seconds 3
    } else {
        Write-Host "  [WARNING] Unexpected status: $($service.Status)" -ForegroundColor Yellow
        Start-Sleep -Seconds 3
    }
    
    $attempt++
}
Write-Host ""

# Step 7: Test API
if ($serviceStarted) {
    Write-Host "[STEP 7] Testing Ollama API..." -ForegroundColor Yellow
    Start-Sleep -Seconds 5
    
    try {
        $response = Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get -TimeoutSec 10
        Write-Host "  [OK] API is responding!" -ForegroundColor Green
        Write-Host "  Available models: $($response.models.Count)" -ForegroundColor White
    } catch {
        Write-Host "  [WARNING] API not responding yet" -ForegroundColor Yellow
        Write-Host "  This is normal - Ollama may need more time to start" -ForegroundColor Gray
        Write-Host "  Wait 30-60 seconds and test manually:" -ForegroundColor Gray
        Write-Host "    Invoke-RestMethod -Uri 'http://localhost:11434/api/tags' -Method Get" -ForegroundColor Gray
    }
} else {
    Write-Host "[STEP 7] Service failed to start" -ForegroundColor Red
    Write-Host ""
    Write-Host "Troubleshooting:" -ForegroundColor Cyan
    Write-Host "1. Check error logs:" -ForegroundColor White
    Write-Host "   Get-Content $PSScriptRoot\..\logs\ollama_stderr.log -Tail 50" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. Try manual start:" -ForegroundColor White
    Write-Host "   $nssmPath start $serviceName" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. Check service configuration:" -ForegroundColor White
    Write-Host "   $nssmPath edit $serviceName" -ForegroundColor Gray
    Write-Host ""
    Write-Host "4. Reinstall service:" -ForegroundColor White
    Write-Host "   .\uninstall_ollama_service.ps1" -ForegroundColor Gray
    Write-Host "   .\install_ollama_service.ps1" -ForegroundColor Gray
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Fix Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Service Status: $($service.Status)" -ForegroundColor White
Write-Host ""
Write-Host "Service Management:" -ForegroundColor Cyan
Write-Host "  Status:  Get-Service $serviceName" -ForegroundColor Gray
Write-Host "  Start:   Start-Service $serviceName" -ForegroundColor Gray
Write-Host "  Stop:    Stop-Service $serviceName" -ForegroundColor Gray
Write-Host "  Restart: Restart-Service $serviceName" -ForegroundColor Gray
