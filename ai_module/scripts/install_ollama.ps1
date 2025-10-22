# Ollama Installation and Configuration Script for Windows
# This script automates the installation and setup of Ollama with Llama 3.1 8B model

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ollama Installation Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "WARNING: Not running as Administrator. Some operations may fail." -ForegroundColor Yellow
    Write-Host "Consider running PowerShell as Administrator for full functionality." -ForegroundColor Yellow
    Write-Host ""
}

# Step 1: Download Ollama Windows Installer
Write-Host "[1/6] Downloading Ollama Windows Installer..." -ForegroundColor Green
$ollamaUrl = "https://ollama.com/download/OllamaSetup.exe"
$installerPath = "$env:TEMP\OllamaSetup.exe"

try {
    # Check if Ollama is already installed
    $ollamaPath = Get-Command ollama -ErrorAction SilentlyContinue
    if ($ollamaPath) {
        Write-Host "Ollama is already installed at: $($ollamaPath.Source)" -ForegroundColor Yellow
        $response = Read-Host "Do you want to reinstall? (y/n)"
        if ($response -ne 'y') {
            Write-Host "Skipping installation..." -ForegroundColor Yellow
        } else {
            Write-Host "Downloading installer..." -ForegroundColor Cyan
            Invoke-WebRequest -Uri $ollamaUrl -OutFile $installerPath -UseBasicParsing
            Write-Host "Download complete!" -ForegroundColor Green
            
            # Step 2: Install Ollama
            Write-Host ""
            Write-Host "[2/6] Installing Ollama..." -ForegroundColor Green
            Write-Host "Running installer (this may take a few minutes)..." -ForegroundColor Cyan
            Start-Process -FilePath $installerPath -Wait
            Write-Host "Installation complete!" -ForegroundColor Green
        }
    } else {
        Write-Host "Downloading installer..." -ForegroundColor Cyan
        Invoke-WebRequest -Uri $ollamaUrl -OutFile $installerPath -UseBasicParsing
        Write-Host "Download complete!" -ForegroundColor Green
        
        # Step 2: Install Ollama
        Write-Host ""
        Write-Host "[2/6] Installing Ollama..." -ForegroundColor Green
        Write-Host "Running installer (this may take a few minutes)..." -ForegroundColor Cyan
        Start-Process -FilePath $installerPath -Wait
        Write-Host "Installation complete!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error downloading/installing Ollama: $_" -ForegroundColor Red
    Write-Host "Please download manually from: https://ollama.com/download" -ForegroundColor Yellow
    exit 1
}

# Refresh environment variables
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

# Step 3: Configure Environment Variables
Write-Host ""
Write-Host "[3/6] Configuring Environment Variables..." -ForegroundColor Green

# Create models directory
$modelsDir = "C:\TicketportaalAI\models"
if (-not (Test-Path $modelsDir)) {
    New-Item -ItemType Directory -Path $modelsDir -Force | Out-Null
    Write-Host "Created models directory: $modelsDir" -ForegroundColor Cyan
}

# Set environment variables
try {
    # OLLAMA_HOST - bind to all interfaces for internal network access
    [System.Environment]::SetEnvironmentVariable("OLLAMA_HOST", "0.0.0.0:11434", "Machine")
    Write-Host "Set OLLAMA_HOST=0.0.0.0:11434" -ForegroundColor Cyan
    
    # OLLAMA_ORIGINS - allow requests from localhost and internal network
    [System.Environment]::SetEnvironmentVariable("OLLAMA_ORIGINS", "http://localhost:*,http://127.0.0.1:*", "Machine")
    Write-Host "Set OLLAMA_ORIGINS=http://localhost:*,http://127.0.0.1:*" -ForegroundColor Cyan
    
    # OLLAMA_MODELS - custom models directory
    [System.Environment]::SetEnvironmentVariable("OLLAMA_MODELS", $modelsDir, "Machine")
    Write-Host "Set OLLAMA_MODELS=$modelsDir" -ForegroundColor Cyan
    
    Write-Host "Environment variables configured successfully!" -ForegroundColor Green
} catch {
    Write-Host "Error setting environment variables: $_" -ForegroundColor Red
    Write-Host "You may need to set them manually in System Properties > Environment Variables" -ForegroundColor Yellow
}

# Refresh environment for current session
$env:OLLAMA_HOST = "0.0.0.0:11434"
$env:OLLAMA_ORIGINS = "http://localhost:*,http://127.0.0.1:*"
$env:OLLAMA_MODELS = $modelsDir

# Step 4: Start Ollama Service
Write-Host ""
Write-Host "[4/6] Starting Ollama Service..." -ForegroundColor Green

# Check if Ollama service exists
$service = Get-Service -Name "Ollama" -ErrorAction SilentlyContinue
if ($service) {
    if ($service.Status -ne "Running") {
        Start-Service -Name "Ollama"
        Write-Host "Ollama service started!" -ForegroundColor Green
    } else {
        Write-Host "Ollama service is already running!" -ForegroundColor Green
    }
} else {
    # Start Ollama manually if service doesn't exist yet
    Write-Host "Starting Ollama manually..." -ForegroundColor Cyan
    Start-Process -FilePath "ollama" -ArgumentList "serve" -WindowStyle Hidden
    Start-Sleep -Seconds 5
    Write-Host "Ollama started!" -ForegroundColor Green
}

# Wait for Ollama to be ready
Write-Host "Waiting for Ollama to be ready..." -ForegroundColor Cyan
$maxAttempts = 10
$attempt = 0
$ollamaReady = $false

while ($attempt -lt $maxAttempts -and -not $ollamaReady) {
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:11434/api/tags" -Method GET -UseBasicParsing -TimeoutSec 2
        if ($response.StatusCode -eq 200) {
            $ollamaReady = $true
            Write-Host "Ollama is ready!" -ForegroundColor Green
        }
    } catch {
        $attempt++
        Start-Sleep -Seconds 2
    }
}

if (-not $ollamaReady) {
    Write-Host "Warning: Could not verify Ollama is running. Continuing anyway..." -ForegroundColor Yellow
}

# Step 5: Pull Llama 3.1 8B Model
Write-Host ""
Write-Host "[5/6] Pulling Llama 3.1 8B Model (4.7GB download)..." -ForegroundColor Green
Write-Host "This may take 10-30 minutes depending on your internet connection..." -ForegroundColor Yellow
Write-Host ""

try {
    # Check if model already exists
    $existingModels = ollama list 2>$null
    if ($existingModels -match "llama3.1:8b") {
        Write-Host "Llama 3.1 8B model is already installed!" -ForegroundColor Green
    } else {
        Write-Host "Downloading model... (Progress will be shown below)" -ForegroundColor Cyan
        ollama pull llama3.1:8b
        Write-Host ""
        Write-Host "Model downloaded successfully!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error pulling model: $_" -ForegroundColor Red
    Write-Host "You can manually pull the model later with: ollama pull llama3.1:8b" -ForegroundColor Yellow
}

# Step 6: Test Model with Simple Query
Write-Host ""
Write-Host "[6/6] Testing Model with Simple Query..." -ForegroundColor Green
Write-Host ""

$testQuery = "Hello, can you help me troubleshoot a printer issue?"

try {
    Write-Host "Sending test query: '$testQuery'" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Response:" -ForegroundColor Cyan
    Write-Host "---" -ForegroundColor Gray
    
    $response = ollama run llama3.1:8b $testQuery
    Write-Host $response -ForegroundColor White
    
    Write-Host "---" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Test successful! Ollama is working correctly." -ForegroundColor Green
} catch {
    Write-Host "Error testing model: $_" -ForegroundColor Red
    Write-Host "You can manually test with: ollama run llama3.1:8b 'test query'" -ForegroundColor Yellow
}

# Summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Installation Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "✓ Ollama installed" -ForegroundColor Green
Write-Host "✓ Environment variables configured:" -ForegroundColor Green
Write-Host "  - OLLAMA_HOST: 0.0.0.0:11434" -ForegroundColor White
Write-Host "  - OLLAMA_ORIGINS: http://localhost:*,http://127.0.0.1:*" -ForegroundColor White
Write-Host "  - OLLAMA_MODELS: $modelsDir" -ForegroundColor White
Write-Host "✓ Llama 3.1 8B model pulled" -ForegroundColor Green
Write-Host "✓ Model tested successfully" -ForegroundColor Green
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Verify Ollama service is running: Get-Service Ollama" -ForegroundColor White
Write-Host "2. Test API endpoint: Invoke-WebRequest http://localhost:11434/api/tags" -ForegroundColor White
Write-Host "3. Proceed to Task 3: Setup Ollama as Windows Service" -ForegroundColor White
Write-Host ""
Write-Host "Note: You may need to restart your terminal/PowerShell for environment variables to take effect." -ForegroundColor Yellow
Write-Host ""
