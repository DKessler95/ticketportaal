# Test Ollama Windows Service
# Tests service start, stop, restart operations

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ollama Service Test Script" -ForegroundColor Cyan
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

Write-Host "Initial Service Status: $($service.Status)" -ForegroundColor White
Write-Host ""

# Function to test API
function Test-OllamaAPI {
    try {
        $response = Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get -TimeoutSec 5
        return $true
    } catch {
        return $false
    }
}

# Test 1: Stop Service
Write-Host "[TEST 1] Stopping service..." -ForegroundColor Yellow
& $nssmPath stop $serviceName
Start-Sleep -Seconds 3

$service.Refresh()
if ($service.Status -eq 'Stopped') {
    Write-Host "[OK] Service stopped successfully" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Service status: $($service.Status)" -ForegroundColor Red
    exit 1
}

# Verify API is not responding
if (-not (Test-OllamaAPI)) {
    Write-Host "[OK] API not responding (as expected)" -ForegroundColor Green
} else {
    Write-Host "[WARNING] API still responding after stop" -ForegroundColor Yellow
}

Write-Host ""

# Test 2: Start Service
Write-Host "[TEST 2] Starting service..." -ForegroundColor Yellow
& $nssmPath start $serviceName
Start-Sleep -Seconds 5

$service.Refresh()
if ($service.Status -eq 'Running') {
    Write-Host "[OK] Service started successfully" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Service status: $($service.Status)" -ForegroundColor Red
    exit 1
}

# Wait for API to be ready
Write-Host "[INFO] Waiting for API to be ready..." -ForegroundColor Gray
$maxAttempts = 10
$attempt = 0
$apiReady = $false

while ($attempt -lt $maxAttempts) {
    if (Test-OllamaAPI) {
        $apiReady = $true
        break
    }
    Start-Sleep -Seconds 2
    $attempt++
}

if ($apiReady) {
    Write-Host "[OK] API is responding" -ForegroundColor Green
} else {
    Write-Host "[WARNING] API not responding after $maxAttempts attempts" -ForegroundColor Yellow
}

Write-Host ""

# Test 3: Restart Service
Write-Host "[TEST 3] Restarting service..." -ForegroundColor Yellow
& $nssmPath restart $serviceName
Start-Sleep -Seconds 5

$service.Refresh()
if ($service.Status -eq 'Running') {
    Write-Host "[OK] Service restarted successfully" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Service status: $($service.Status)" -ForegroundColor Red
    exit 1
}

# Verify API after restart
Write-Host "[INFO] Waiting for API after restart..." -ForegroundColor Gray
Start-Sleep -Seconds 5

if (Test-OllamaAPI) {
    Write-Host "[OK] API responding after restart" -ForegroundColor Green
} else {
    Write-Host "[WARNING] API not responding after restart" -ForegroundColor Yellow
}

Write-Host ""

# Test 4: Check Service Configuration
Write-Host "[TEST 4] Verifying service configuration..." -ForegroundColor Yellow

$startType = (Get-Service -Name $serviceName).StartType
if ($startType -eq 'Automatic') {
    Write-Host "[OK] Startup type is Automatic" -ForegroundColor Green
} else {
    Write-Host "[WARNING] Startup type is $startType (expected Automatic)" -ForegroundColor Yellow
}

Write-Host ""

# Display final status
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Test Results Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Service Name: $serviceName" -ForegroundColor White
Write-Host "Current Status: $($service.Status)" -ForegroundColor White
Write-Host "Startup Type: $startType" -ForegroundColor White
Write-Host ""
Write-Host "[SUCCESS] All service tests completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Service Management:" -ForegroundColor Cyan
Write-Host "  View status:  Get-Service $serviceName" -ForegroundColor Gray
Write-Host "  Start:        $nssmPath start $serviceName" -ForegroundColor Gray
Write-Host "  Stop:         $nssmPath stop $serviceName" -ForegroundColor Gray
Write-Host "  Restart:      $nssmPath restart $serviceName" -ForegroundColor Gray
Write-Host "  View logs:    Get-Content $PSScriptRoot\..\logs\ollama_stdout.log -Tail 50" -ForegroundColor Gray
