# Download NSSM (Non-Sucking Service Manager)
# This script downloads NSSM for managing Ollama as a Windows Service

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NSSM Download Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$nssmVersion = "2.24"
$nssmUrl = "https://nssm.cc/release/nssm-$nssmVersion.zip"
$downloadPath = "$PSScriptRoot\nssm-$nssmVersion.zip"
$extractPath = "$PSScriptRoot\nssm"

# Check if NSSM already exists
if (Test-Path "$extractPath\win64\nssm.exe") {
    Write-Host "[OK] NSSM already downloaded at: $extractPath\win64\nssm.exe" -ForegroundColor Green
    Write-Host ""
    Write-Host "NSSM Path: $extractPath\win64\nssm.exe" -ForegroundColor Yellow
    exit 0
}

Write-Host "[INFO] Downloading NSSM version $nssmVersion..." -ForegroundColor Yellow
Write-Host "URL: $nssmUrl" -ForegroundColor Gray

try {
    # Download NSSM
    Invoke-WebRequest -Uri $nssmUrl -OutFile $downloadPath -UseBasicParsing
    Write-Host "[OK] Downloaded NSSM to: $downloadPath" -ForegroundColor Green
    
    # Extract NSSM
    Write-Host "[INFO] Extracting NSSM..." -ForegroundColor Yellow
    Expand-Archive -Path $downloadPath -DestinationPath $extractPath -Force
    
    # Move files to correct location
    $extractedFolder = "$extractPath\nssm-$nssmVersion"
    if (Test-Path $extractedFolder) {
        Move-Item -Path "$extractedFolder\*" -Destination $extractPath -Force
        Remove-Item -Path $extractedFolder -Recurse -Force
    }
    
    # Clean up zip file
    Remove-Item -Path $downloadPath -Force
    
    Write-Host "[OK] NSSM extracted successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "NSSM Location:" -ForegroundColor Cyan
    Write-Host "  64-bit: $extractPath\win64\nssm.exe" -ForegroundColor White
    Write-Host "  32-bit: $extractPath\win32\nssm.exe" -ForegroundColor White
    Write-Host ""
    Write-Host "[SUCCESS] NSSM download complete!" -ForegroundColor Green
    
} catch {
    Write-Host "[ERROR] Failed to download or extract NSSM: $_" -ForegroundColor Red
    exit 1
}
