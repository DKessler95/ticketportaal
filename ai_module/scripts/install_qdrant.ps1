# Qdrant Windows Installation Script
# Installs Qdrant as a native Windows service

$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Qdrant Windows Installation" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$QDRANT_VERSION = "v1.7.4"
$INSTALL_DIR = "C:\qdrant"
$DATA_DIR = "$INSTALL_DIR\storage"
$DOWNLOAD_URL = "https://github.com/qdrant/qdrant/releases/download/$QDRANT_VERSION/qdrant-x86_64-pc-windows-msvc.zip"
$ZIP_FILE = "$env:TEMP\qdrant.zip"

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/6] Creating installation directory..." -ForegroundColor Green
if (-not (Test-Path $INSTALL_DIR)) {
    New-Item -ItemType Directory -Path $INSTALL_DIR -Force | Out-Null
    Write-Host "      Created: $INSTALL_DIR" -ForegroundColor Gray
} else {
    Write-Host "      Directory already exists" -ForegroundColor Gray
}

if (-not (Test-Path $DATA_DIR)) {
    New-Item -ItemType Directory -Path $DATA_DIR -Force | Out-Null
    Write-Host "      Created: $DATA_DIR" -ForegroundColor Gray
}

Write-Host ""
Write-Host "[2/6] Downloading Qdrant $QDRANT_VERSION..." -ForegroundColor Green
Write-Host "      URL: $DOWNLOAD_URL" -ForegroundColor Gray

try {
    # Download with progress
    $ProgressPreference = 'SilentlyContinue'
    Invoke-WebRequest -Uri $DOWNLOAD_URL -OutFile $ZIP_FILE -UseBasicParsing
    Write-Host "      Downloaded successfully" -ForegroundColor Gray
} catch {
    Write-Host "      ERROR: Failed to download Qdrant" -ForegroundColor Red
    Write-Host "      $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[3/6] Extracting Qdrant..." -ForegroundColor Green
try {
    Expand-Archive -Path $ZIP_FILE -DestinationPath $INSTALL_DIR -Force
    Write-Host "      Extracted to: $INSTALL_DIR" -ForegroundColor Gray
} catch {
    Write-Host "      ERROR: Failed to extract Qdrant" -ForegroundColor Red
    Write-Host "      $_" -ForegroundColor Red
    exit 1
}

# Clean up
Remove-Item $ZIP_FILE -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "[4/6] Creating Qdrant configuration..." -ForegroundColor Green

$configContent = @"
storage:
  storage_path: $DATA_DIR

service:
  host: 0.0.0.0
  http_port: 6333
  grpc_port: 6334

log_level: INFO
"@

$configPath = "$INSTALL_DIR\config.yaml"
$configContent | Out-File -FilePath $configPath -Encoding UTF8
Write-Host "      Config created: $configPath" -ForegroundColor Gray

Write-Host ""
Write-Host "[5/6] Installing Qdrant as Windows Service..." -ForegroundColor Green

# Check if NSSM is available
$nssmPath = "$PSScriptRoot\nssm\nssm.exe"
if (-not (Test-Path $nssmPath)) {
    Write-Host "      NSSM not found, downloading..." -ForegroundColor Yellow
    
    $nssmDir = "$PSScriptRoot\nssm"
    if (-not (Test-Path $nssmDir)) {
        New-Item -ItemType Directory -Path $nssmDir -Force | Out-Null
    }
    
    $nssmUrl = "https://nssm.cc/release/nssm-2.24.zip"
    $nssmZip = "$env:TEMP\nssm.zip"
    
    Invoke-WebRequest -Uri $nssmUrl -OutFile $nssmZip -UseBasicParsing
    Expand-Archive -Path $nssmZip -DestinationPath $env:TEMP -Force
    
    Copy-Item "$env:TEMP\nssm-2.24\win64\nssm.exe" $nssmPath -Force
    Remove-Item $nssmZip -Force -ErrorAction SilentlyContinue
    Remove-Item "$env:TEMP\nssm-2.24" -Recurse -Force -ErrorAction SilentlyContinue
    
    Write-Host "      NSSM downloaded" -ForegroundColor Gray
}

# Stop existing service if running
$existingService = Get-Service -Name "Qdrant" -ErrorAction SilentlyContinue
if ($existingService) {
    Write-Host "      Stopping existing Qdrant service..." -ForegroundColor Yellow
    Stop-Service -Name "Qdrant" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
    
    Write-Host "      Removing existing service..." -ForegroundColor Yellow
    & $nssmPath remove Qdrant confirm
    Start-Sleep -Seconds 2
}

# Install service
$qdrantExe = "$INSTALL_DIR\qdrant.exe"
Write-Host "      Installing service with NSSM..." -ForegroundColor Gray
& $nssmPath install Qdrant $qdrantExe
& $nssmPath set Qdrant AppDirectory $INSTALL_DIR
& $nssmPath set Qdrant AppParameters "--config-path $configPath"
& $nssmPath set Qdrant DisplayName "Qdrant Vector Database"
& $nssmPath set Qdrant Description "High-performance vector similarity search engine"
& $nssmPath set Qdrant Start SERVICE_AUTO_START
& $nssmPath set Qdrant AppStdout "$INSTALL_DIR\logs\qdrant-stdout.log"
& $nssmPath set Qdrant AppStderr "$INSTALL_DIR\logs\qdrant-stderr.log"
& $nssmPath set Qdrant AppRotateFiles 1
& $nssmPath set Qdrant AppRotateBytes 10485760

# Create logs directory
$logsDir = "$INSTALL_DIR\logs"
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
}

Write-Host "      Service installed successfully" -ForegroundColor Gray

Write-Host ""
Write-Host "[6/6] Starting Qdrant service..." -ForegroundColor Green
Start-Service -Name "Qdrant"
Start-Sleep -Seconds 3

# Check if service is running
$service = Get-Service -Name "Qdrant"
if ($service.Status -eq "Running") {
    Write-Host "      Service started successfully" -ForegroundColor Gray
} else {
    Write-Host "      WARNING: Service status is $($service.Status)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Installation Complete!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Qdrant Details:" -ForegroundColor White
Write-Host "  Installation: $INSTALL_DIR" -ForegroundColor Gray
Write-Host "  Data Storage: $DATA_DIR" -ForegroundColor Gray
Write-Host "  HTTP API:     http://localhost:6333" -ForegroundColor Gray
Write-Host "  gRPC API:     http://localhost:6334" -ForegroundColor Gray
Write-Host "  Dashboard:    http://localhost:6333/dashboard" -ForegroundColor Gray
Write-Host ""
Write-Host "Service Management:" -ForegroundColor White
Write-Host "  Start:   Start-Service Qdrant" -ForegroundColor Gray
Write-Host "  Stop:    Stop-Service Qdrant" -ForegroundColor Gray
Write-Host "  Status:  Get-Service Qdrant" -ForegroundColor Gray
Write-Host "  Logs:    $INSTALL_DIR\logs\" -ForegroundColor Gray
Write-Host ""
Write-Host "Testing connection..." -ForegroundColor Yellow

Start-Sleep -Seconds 2

try {
    $response = Invoke-RestMethod -Uri "http://localhost:6333/health" -Method Get -TimeoutSec 5
    Write-Host "SUCCESS: Qdrant is running!" -ForegroundColor Green
    Write-Host "Health Status: $($response | ConvertTo-Json)" -ForegroundColor Gray
} catch {
    Write-Host "WARNING: Could not connect to Qdrant yet" -ForegroundColor Yellow
    Write-Host "Wait a few seconds and try: http://localhost:6333/dashboard" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Open browser: http://localhost:6333/dashboard" -ForegroundColor White
Write-Host "2. Run sync script to populate data" -ForegroundColor White
Write-Host "3. Configure RAG API to use Qdrant" -ForegroundColor White
Write-Host ""
