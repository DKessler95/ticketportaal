# Configure Ollama Service to use existing models directory
# This script must be run as Administrator

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Configure Ollama Models Path" -ForegroundColor Cyan
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

# Find existing models
Write-Host "[INFO] Searching for existing Ollama models..." -ForegroundColor Yellow
Write-Host ""

$possiblePaths = @(
    "$env:USERPROFILE\.ollama\models",
    "C:\TicketportaalAI\models",
    "$PSScriptRoot\..\models"
)

$foundModels = @()

foreach ($path in $possiblePaths) {
    if (Test-Path $path) {
        $files = Get-ChildItem -Path $path -Recurse -File -ErrorAction SilentlyContinue
        if ($files) {
            $totalSize = ($files | Measure-Object -Property Length -Sum).Sum
            $sizeGB = [math]::Round($totalSize/1GB, 2)
            
            $foundModels += [PSCustomObject]@{
                Path = $path
                Files = $files.Count
                SizeGB = $sizeGB
            }
            
            Write-Host "  Found: $path" -ForegroundColor Green
            Write-Host "    Files: $($files.Count)" -ForegroundColor Gray
            Write-Host "    Size: $sizeGB GB" -ForegroundColor Gray
            Write-Host ""
        }
    }
}

if ($foundModels.Count -eq 0) {
    Write-Host "[WARNING] No existing models found in common locations" -ForegroundColor Yellow
    Write-Host "Models will be downloaded to the default location when needed" -ForegroundColor Gray
    exit 0
}

# Select the path with the most data
$selectedPath = ($foundModels | Sort-Object -Property SizeGB -Descending | Select-Object -First 1).Path

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Selected Models Path:" -ForegroundColor Cyan
Write-Host "$selectedPath" -ForegroundColor White
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Stop service
Write-Host "[INFO] Stopping Ollama service..." -ForegroundColor Yellow
& $nssmPath stop $serviceName
Start-Sleep -Seconds 3
Write-Host "[OK] Service stopped" -ForegroundColor Green
Write-Host ""

# Update environment variables
Write-Host "[INFO] Updating service configuration..." -ForegroundColor Yellow
& $nssmPath set $serviceName AppEnvironmentExtra "OLLAMA_HOST=0.0.0.0:11434" "OLLAMA_ORIGINS=*" "OLLAMA_MODELS=$selectedPath"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Configuration updated" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to update configuration" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Start service
Write-Host "[INFO] Starting Ollama service..." -ForegroundColor Yellow
& $nssmPath start $serviceName
Start-Sleep -Seconds 5

$service.Refresh()
if ($service.Status -eq 'Running') {
    Write-Host "[OK] Service started successfully!" -ForegroundColor Green
} else {
    Write-Host "[WARNING] Service status: $($service.Status)" -ForegroundColor Yellow
}
Write-Host ""

# Test API and list models
Write-Host "[INFO] Testing API and listing models..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

try {
    $response = Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get -TimeoutSec 10
    Write-Host "[OK] API is responding!" -ForegroundColor Green
    Write-Host ""
    
    if ($response.models.Count -gt 0) {
        Write-Host "Available Models:" -ForegroundColor Cyan
        foreach ($model in $response.models) {
            $sizeGB = [math]::Round($model.size/1GB, 2)
            Write-Host "  - $($model.name)" -ForegroundColor White
            Write-Host "    Size: $sizeGB GB" -ForegroundColor Gray
            Write-Host "    Modified: $($model.modified_at)" -ForegroundColor Gray
        }
    } else {
        Write-Host "[WARNING] No models found via API" -ForegroundColor Yellow
        Write-Host "The service may need more time to scan the models directory" -ForegroundColor Gray
    }
} catch {
    Write-Host "[WARNING] API not responding yet" -ForegroundColor Yellow
    Write-Host "Wait 30-60 seconds and test manually:" -ForegroundColor Gray
    Write-Host "  ollama list" -ForegroundColor Gray
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Configuration Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Models Path: $selectedPath" -ForegroundColor White
Write-Host "Service Status: $($service.Status)" -ForegroundColor White
Write-Host ""
Write-Host "Test your models:" -ForegroundColor Cyan
Write-Host "  ollama list" -ForegroundColor Gray
Write-Host "  ollama run llama3.1:8b" -ForegroundColor Gray
