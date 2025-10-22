# Uninstall Ollama Windows Service
# This script must be run as Administrator

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ollama Service Uninstallation" -ForegroundColor Cyan
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

# Check if NSSM exists
if (-not (Test-Path $nssmPath)) {
    Write-Host "[ERROR] NSSM not found at: $nssmPath" -ForegroundColor Red
    exit 1
}

# Check if service exists
$service = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
if (-not $service) {
    Write-Host "[INFO] Service '$serviceName' not found. Nothing to uninstall." -ForegroundColor Yellow
    exit 0
}

Write-Host "Current Service Status: $($service.Status)" -ForegroundColor White
Write-Host ""

$response = Read-Host "Are you sure you want to uninstall the Ollama service? (y/n)"
if ($response -ne 'y') {
    Write-Host "[INFO] Uninstallation cancelled" -ForegroundColor Yellow
    exit 0
}

# Stop service if running
if ($service.Status -eq 'Running') {
    Write-Host "[INFO] Stopping service..." -ForegroundColor Yellow
    & $nssmPath stop $serviceName
    Start-Sleep -Seconds 3
    Write-Host "[OK] Service stopped" -ForegroundColor Green
}

# Remove service
Write-Host "[INFO] Removing service..." -ForegroundColor Yellow
& $nssmPath remove $serviceName confirm

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Service removed successfully!" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to remove service" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[SUCCESS] Ollama service uninstalled!" -ForegroundColor Green
Write-Host ""
Write-Host "Note: Ollama application is still installed." -ForegroundColor Gray
Write-Host "You can still run Ollama manually or reinstall the service." -ForegroundColor Gray
