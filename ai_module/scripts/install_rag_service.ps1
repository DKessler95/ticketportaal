# Install RAG API as Windows Service using NSSM
# Run this script as Administrator

param(
    [string]$ServiceName = "TicketportaalRAG",
    [string]$DisplayName = "K&K Ticketportaal RAG API",
    [string]$Description = "AI-powered ticket assistance using RAG (Retrieval-Augmented Generation)"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Installing RAG API as Windows Service" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Check if running as Administrator
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "ERROR: This script must be run as Administrator" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Get paths
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AIModuleDir = Split-Path -Parent $ScriptDir
$VenvPython = Join-Path $AIModuleDir "venv\Scripts\python.exe"
$RagApiScript = Join-Path $ScriptDir "rag_api.py"
$LogDir = Join-Path $AIModuleDir "logs"

Write-Host "`nConfiguration:" -ForegroundColor Yellow
Write-Host "  Service Name: $ServiceName"
Write-Host "  Display Name: $DisplayName"
Write-Host "  Python: $VenvPython"
Write-Host "  Script: $RagApiScript"
Write-Host "  Working Dir: $ScriptDir"
Write-Host "  Log Dir: $LogDir"

# Verify paths exist
if (-not (Test-Path $VenvPython)) {
    Write-Host "`nERROR: Python not found at: $VenvPython" -ForegroundColor Red
    Write-Host "Please create virtual environment first" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

if (-not (Test-Path $RagApiScript)) {
    Write-Host "`nERROR: RAG API script not found at: $RagApiScript" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Create log directory if it doesn't exist
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir | Out-Null
    Write-Host "`nCreated log directory: $LogDir" -ForegroundColor Green
}

# Check if NSSM is available
$NssmPath = "nssm.exe"
try {
    $null = Get-Command $NssmPath -ErrorAction Stop
    Write-Host "`nNSSM found" -ForegroundColor Green
} catch {
    Write-Host "`nERROR: NSSM not found in PATH" -ForegroundColor Red
    Write-Host "Please download NSSM from: https://nssm.cc/download" -ForegroundColor Yellow
    Write-Host "Extract nssm.exe to a directory in your PATH (e.g., C:\Windows\System32)" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Check if service already exists
$ExistingService = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($ExistingService) {
    Write-Host "`nService '$ServiceName' already exists" -ForegroundColor Yellow
    $Response = Read-Host "Do you want to remove and reinstall? (y/n)"
    if ($Response -eq 'y') {
        Write-Host "Stopping service..." -ForegroundColor Yellow
        Stop-Service -Name $ServiceName -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 2
        
        Write-Host "Removing service..." -ForegroundColor Yellow
        & $NssmPath remove $ServiceName confirm
        Start-Sleep -Seconds 2
        Write-Host "Service removed" -ForegroundColor Green
    } else {
        Write-Host "Installation cancelled" -ForegroundColor Yellow
        Read-Host "Press Enter to exit"
        exit 0
    }
}

# Install service
Write-Host "`nInstalling service..." -ForegroundColor Yellow

# Install with NSSM
& $NssmPath install $ServiceName $VenvPython "-m" "uvicorn" "rag_api:app" "--host" "0.0.0.0" "--port" "5005"

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to install service" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Configure service
Write-Host "Configuring service..." -ForegroundColor Yellow

# Set display name and description
& $NssmPath set $ServiceName DisplayName $DisplayName
& $NssmPath set $ServiceName Description $Description

# Set working directory
& $NssmPath set $ServiceName AppDirectory $ScriptDir

# Set startup type to Automatic
& $NssmPath set $ServiceName Start SERVICE_AUTO_START

# Configure logging
$StdoutLog = Join-Path $LogDir "rag_api_stdout.log"
$StderrLog = Join-Path $LogDir "rag_api_stderr.log"
& $NssmPath set $ServiceName AppStdout $StdoutLog
& $NssmPath set $ServiceName AppStderr $StderrLog

# Configure log rotation (10MB max, keep 5 files)
& $NssmPath set $ServiceName AppRotateFiles 1
& $NssmPath set $ServiceName AppRotateBytes 10485760
& $NssmPath set $ServiceName AppRotateOnline 1

# Configure service recovery
& $NssmPath set $ServiceName AppExit Default Restart
& $NssmPath set $ServiceName AppRestartDelay 5000

Write-Host "`nService installed successfully!" -ForegroundColor Green

# Ask if user wants to start the service now
$Response = Read-Host "`nDo you want to start the service now? (y/n)"
if ($Response -eq 'y') {
    Write-Host "Starting service..." -ForegroundColor Yellow
    Start-Service -Name $ServiceName
    Start-Sleep -Seconds 3
    
    $Service = Get-Service -Name $ServiceName
    if ($Service.Status -eq 'Running') {
        Write-Host "Service started successfully!" -ForegroundColor Green
        Write-Host "`nRAG API is now running at: http://localhost:5005" -ForegroundColor Cyan
        Write-Host "Health check: http://localhost:5005/health" -ForegroundColor Cyan
        Write-Host "Stats: http://localhost:5005/stats" -ForegroundColor Cyan
    } else {
        Write-Host "WARNING: Service failed to start" -ForegroundColor Yellow
        Write-Host "Check logs at: $LogDir" -ForegroundColor Yellow
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Installation Complete" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "`nService Management Commands:" -ForegroundColor Yellow
Write-Host "  Start:   Start-Service $ServiceName" -ForegroundColor White
Write-Host "  Stop:    Stop-Service $ServiceName" -ForegroundColor White
Write-Host "  Restart: Restart-Service $ServiceName" -ForegroundColor White
Write-Host "  Status:  Get-Service $ServiceName" -ForegroundColor White
Write-Host "  Remove:  nssm remove $ServiceName confirm" -ForegroundColor White

Read-Host "`nPress Enter to exit"
