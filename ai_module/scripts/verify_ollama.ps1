# Ollama Verification Script
# Verifies that Ollama is properly installed and configured

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ollama Verification Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$allChecks = @()

# Check 1: Ollama Command Available
Write-Host "[1/7] Checking if Ollama command is available..." -ForegroundColor Yellow
try {
    $ollamaPath = Get-Command ollama -ErrorAction Stop
    Write-Host "OK Ollama found at: $($ollamaPath.Source)" -ForegroundColor Green
    $allChecks += $true
}
catch {
    Write-Host "X Ollama command not found in PATH" -ForegroundColor Red
    Write-Host "  Please ensure Ollama is installed and added to PATH" -ForegroundColor Yellow
    $allChecks += $false
}

# Check 2: Environment Variables
Write-Host ""
Write-Host "[2/7] Checking environment variables..." -ForegroundColor Yellow

$ollamaHost = [System.Environment]::GetEnvironmentVariable("OLLAMA_HOST", "Machine")
$ollamaOrigins = [System.Environment]::GetEnvironmentVariable("OLLAMA_ORIGINS", "Machine")
$ollamaModels = [System.Environment]::GetEnvironmentVariable("OLLAMA_MODELS", "Machine")

if ($ollamaHost) {
    Write-Host "OK OLLAMA_HOST: $ollamaHost" -ForegroundColor Green
    $allChecks += $true
}
else {
    Write-Host "X OLLAMA_HOST not set" -ForegroundColor Red
    $allChecks += $false
}

if ($ollamaOrigins) {
    Write-Host "OK OLLAMA_ORIGINS: $ollamaOrigins" -ForegroundColor Green
    $allChecks += $true
}
else {
    Write-Host "X OLLAMA_ORIGINS not set" -ForegroundColor Red
    $allChecks += $false
}

if ($ollamaModels) {
    Write-Host "OK OLLAMA_MODELS: $ollamaModels" -ForegroundColor Green
    $allChecks += $true
}
else {
    Write-Host "X OLLAMA_MODELS not set" -ForegroundColor Red
    $allChecks += $false
}

# Check 3: Models Directory
Write-Host ""
Write-Host "[3/7] Checking models directory..." -ForegroundColor Yellow
$modelsDir = if ($ollamaModels) { $ollamaModels } else { "C:\TicketportaalAI\models" }

if (Test-Path $modelsDir) {
    Write-Host "OK Models directory exists: $modelsDir" -ForegroundColor Green
    $allChecks += $true
}
else {
    Write-Host "X Models directory not found: $modelsDir" -ForegroundColor Red
    $allChecks += $false
}

# Check 4: Ollama Service Status
Write-Host ""
Write-Host "[4/7] Checking Ollama service status..." -ForegroundColor Yellow
$service = Get-Service -Name "Ollama" -ErrorAction SilentlyContinue

if ($service) {
    if ($service.Status -eq "Running") {
        Write-Host "OK Ollama service is running" -ForegroundColor Green
        $allChecks += $true
    }
    else {
        Write-Host "X Ollama service exists but is not running (Status: $($service.Status))" -ForegroundColor Red
        Write-Host "  Try: Start-Service Ollama" -ForegroundColor Yellow
        $allChecks += $false
    }
}
else {
    Write-Host "! Ollama service not found (may be running manually)" -ForegroundColor Yellow
    $allChecks += $true
}

# Check 5: API Endpoint Accessibility
Write-Host ""
Write-Host "[5/7] Checking API endpoint..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:11434/api/tags" -Method GET -UseBasicParsing -TimeoutSec 5
    if ($response.StatusCode -eq 200) {
        Write-Host "OK Ollama API is accessible at http://localhost:11434" -ForegroundColor Green
        $allChecks += $true
    }
}
catch {
    Write-Host "X Cannot reach Ollama API at http://localhost:11434" -ForegroundColor Red
    Write-Host "  Error: $_" -ForegroundColor Yellow
    Write-Host "  Ensure Ollama service is running" -ForegroundColor Yellow
    $allChecks += $false
}

# Check 6: Llama 3.1 8B Model
Write-Host ""
Write-Host "[6/7] Checking for Llama 3.1 8B model..." -ForegroundColor Yellow
try {
    $models = ollama list 2>$null
    if ($models -match "llama3.1:8b") {
        Write-Host "OK Llama 3.1 8B model is installed" -ForegroundColor Green
        $allChecks += $true
    }
    else {
        Write-Host "X Llama 3.1 8B model not found" -ForegroundColor Red
        Write-Host "  Available models:" -ForegroundColor Yellow
        Write-Host $models -ForegroundColor White
        Write-Host "  Run: ollama pull llama3.1:8b" -ForegroundColor Yellow
        $allChecks += $false
    }
}
catch {
    Write-Host "X Error checking models: $_" -ForegroundColor Red
    $allChecks += $false
}

# Check 7: Test Query
Write-Host ""
Write-Host "[7/7] Testing model with simple query..." -ForegroundColor Yellow
try {
    Write-Host "Sending test query..." -ForegroundColor Cyan
    $testQuery = "Say OK if you can read this."
    $response = ollama run llama3.1:8b $testQuery --verbose 2>&1
    
    if ($response) {
        Write-Host "OK Model responded successfully" -ForegroundColor Green
        $responseStr = $response.ToString()
        $previewLength = [Math]::Min(100, $responseStr.Length)
        Write-Host "  Response preview: $($responseStr.Substring(0, $previewLength))..." -ForegroundColor White
        $allChecks += $true
    }
    else {
        Write-Host "X Model did not respond" -ForegroundColor Red
        $allChecks += $false
    }
}
catch {
    Write-Host "X Error testing model: $_" -ForegroundColor Red
    $allChecks += $false
}

# Summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Verification Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$passedChecks = ($allChecks | Where-Object { $_ -eq $true }).Count
$totalChecks = $allChecks.Count

if ($passedChecks -eq $totalChecks) {
    $summaryColor = "Green"
}
else {
    $summaryColor = "Yellow"
}

Write-Host "Passed: $passedChecks / $totalChecks checks" -ForegroundColor $summaryColor
Write-Host ""

if ($passedChecks -eq $totalChecks) {
    Write-Host "OK All checks passed! Ollama is properly configured." -ForegroundColor Green
    Write-Host ""
    Write-Host "You can now proceed to Task 3: Setup Ollama as Windows Service" -ForegroundColor Cyan
}
else {
    Write-Host "! Some checks failed. Please review the errors above." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Common fixes:" -ForegroundColor Cyan
    Write-Host "- Restart PowerShell to refresh environment variables" -ForegroundColor White
    Write-Host "- Run: Start-Service Ollama (if service exists)" -ForegroundColor White
    Write-Host "- Run: ollama serve (to start manually)" -ForegroundColor White
    Write-Host "- Run: ollama pull llama3.1:8b (to download model)" -ForegroundColor White
}

Write-Host ""
