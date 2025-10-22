# Install Ollama as Windows Service using NSSM
# This script must be run as Administrator

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ollama Windows Service Installation" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "[ERROR] This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

# Configuration
$serviceName = "Ollama"
$nssmPath = "$PSScriptRoot\nssm\win64\nssm.exe"
$ollamaExe = "$env:LOCALAPPDATA\Programs\Ollama\ollama.exe"
$logPath = "$PSScriptRoot\..\logs"

# Verify NSSM exists
if (-not (Test-Path $nssmPath)) {
    Write-Host "[ERROR] NSSM not found at: $nssmPath" -ForegroundColor Red
    Write-Host "Please run download_nssm.ps1 first" -ForegroundColor Yellow
    exit 1
}

# Verify Ollama exists
if (-not (Test-Path $ollamaExe)) {
    Write-Host "[ERROR] Ollama not found at: $ollamaExe" -ForegroundColor Red
    Write-Host "Please install Ollama first using install_ollama.ps1" -ForegroundColor Yellow
    exit 1
}

# Check if Ollama is already running as a process
$ollamaProcess = Get-Process -Name "ollama" -ErrorAction SilentlyContinue
if ($ollamaProcess) {
    Write-Host "[WARNING] Ollama is currently running as a process" -ForegroundColor Yellow
    Write-Host "The service installation requires stopping all Ollama processes" -ForegroundColor Yellow
    $response = Read-Host "Stop all Ollama processes and continue? (y/n)"
    if ($response -eq 'y') {
        Write-Host "[INFO] Stopping Ollama processes..." -ForegroundColor Yellow
        Stop-Process -Name "ollama" -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 3
        Write-Host "[OK] Ollama processes stopped" -ForegroundColor Green
    } else {
        Write-Host "[INFO] Installation cancelled" -ForegroundColor Yellow
        Write-Host "Please stop Ollama manually and run this script again" -ForegroundColor Gray
        exit 0
    }
}

# Create logs directory if it doesn't exist
if (-not (Test-Path $logPath)) {
    New-Item -ItemType Directory -Path $logPath -Force | Out-Null
}

# Check if service already exists
$existingService = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
if ($existingService) {
    Write-Host "[INFO] Service '$serviceName' already exists" -ForegroundColor Yellow
    Write-Host "Current Status: $($existingService.Status)" -ForegroundColor Gray
    
    $response = Read-Host "Do you want to remove and reinstall? (y/n)"
    if ($response -eq 'y') {
        Write-Host "[INFO] Stopping service..." -ForegroundColor Yellow
        & $nssmPath stop $serviceName
        Start-Sleep -Seconds 2
        
        Write-Host "[INFO] Removing existing service..." -ForegroundColor Yellow
        & $nssmPath remove $serviceName confirm
        Start-Sleep -Seconds 2
    } else {
        Write-Host "[INFO] Installation cancelled" -ForegroundColor Yellow
        exit 0
    }
}

Write-Host "[INFO] Installing Ollama as Windows Service..." -ForegroundColor Yellow

# Install service
& $nssmPath install $serviceName $ollamaExe "serve"

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to install service" -ForegroundColor Red
    exit 1
}

Write-Host "[OK] Service installed" -ForegroundColor Green

# Configure service parameters
Write-Host "[INFO] Configuring service parameters..." -ForegroundColor Yellow

# Set display name and description
& $nssmPath set $serviceName DisplayName "Ollama AI Service"
& $nssmPath set $serviceName Description "Local LLM service for K&K Ticketportaal RAG AI"

# Set startup type to Automatic
& $nssmPath set $serviceName Start SERVICE_AUTO_START

# Configure logging
& $nssmPath set $serviceName AppStdout "$logPath\ollama_stdout.log"
& $nssmPath set $serviceName AppStderr "$logPath\ollama_stderr.log"

# Configure service recovery options (restart on failure)
& $nssmPath set $serviceName AppExit Default Restart
& $nssmPath set $serviceName AppRestartDelay 60000  # 60 seconds

# Set environment variables
& $nssmPath set $serviceName AppEnvironmentExtra "OLLAMA_HOST=0.0.0.0:11434" "OLLAMA_ORIGINS=*" "OLLAMA_MODELS=$PSScriptRoot\..\models"

Write-Host "[OK] Service configured" -ForegroundColor Green

# Start the service
Write-Host "[INFO] Starting Ollama service..." -ForegroundColor Yellow
& $nssmPath start $serviceName

# Wait for service to initialize
Start-Sleep -Seconds 3

# Check service status with retry logic
$maxAttempts = 5
$attempt = 0
$serviceStarted = $false

while ($attempt -lt $maxAttempts) {
    $service = Get-Service -Name $serviceName
    
    if ($service.Status -eq 'Running') {
        $serviceStarted = $true
        Write-Host "[OK] Service started successfully!" -ForegroundColor Green
        break
    } elseif ($service.Status -eq 'StartPending') {
        Write-Host "[INFO] Service is starting... (attempt $($attempt + 1)/$maxAttempts)" -ForegroundColor Gray
        Start-Sleep -Seconds 3
        $attempt++
    } elseif ($service.Status -eq 'Paused') {
        Write-Host "[WARNING] Service is paused, attempting to resume..." -ForegroundColor Yellow
        & $nssmPath continue $serviceName
        Start-Sleep -Seconds 3
        $attempt++
    } else {
        Write-Host "[WARNING] Service status: $($service.Status)" -ForegroundColor Yellow
        Start-Sleep -Seconds 3
        $attempt++
    }
}

if (-not $serviceStarted) {
    $service = Get-Service -Name $serviceName
    if ($service.Status -ne 'Running') {
        Write-Host "[ERROR] Service failed to start. Status: $($service.Status)" -ForegroundColor Red
        Write-Host "Check logs at: $logPath\ollama_stderr.log" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Troubleshooting steps:" -ForegroundColor Cyan
        Write-Host "1. Ensure no other Ollama processes are running" -ForegroundColor Gray
        Write-Host "2. Check if port 11434 is available: netstat -ano | findstr :11434" -ForegroundColor Gray
        Write-Host "3. Try starting manually: $nssmPath start $serviceName" -ForegroundColor Gray
        Write-Host "4. View logs: Get-Content $logPath\ollama_stderr.log -Tail 50" -ForegroundColor Gray
        exit 1
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Installation Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Service Name: $serviceName" -ForegroundColor White
Write-Host "Status: $($service.Status)" -ForegroundColor White
Write-Host "Startup Type: Automatic" -ForegroundColor White
Write-Host "Logs: $logPath" -ForegroundColor White
Write-Host ""
Write-Host "Service Management Commands:" -ForegroundColor Cyan
Write-Host "  Start:   $nssmPath start $serviceName" -ForegroundColor Gray
Write-Host "  Stop:    $nssmPath stop $serviceName" -ForegroundColor Gray
Write-Host "  Restart: $nssmPath restart $serviceName" -ForegroundColor Gray
Write-Host "  Status:  Get-Service $serviceName" -ForegroundColor Gray
Write-Host ""
Write-Host "Testing Ollama API in 10 seconds..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Test Ollama API
try {
    $response = Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get -TimeoutSec 5
    Write-Host "[OK] Ollama API is responding!" -ForegroundColor Green
    Write-Host "Available models: $($response.models.Count)" -ForegroundColor White
} catch {
    Write-Host "[WARNING] Ollama API not responding yet. It may need more time to start." -ForegroundColor Yellow
    Write-Host "Check logs at: $logPath" -ForegroundColor Gray
}
