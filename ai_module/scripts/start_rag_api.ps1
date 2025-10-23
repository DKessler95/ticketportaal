# Start RAG API Service (PowerShell version)
# This script activates the virtual environment and starts the FastAPI server

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Starting K&K Ticketportaal RAG API" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Get script directory
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AIModuleDir = Split-Path -Parent $ScriptDir

Write-Host "`nScript directory: $ScriptDir"
Write-Host "AI module directory: $AIModuleDir"

# Activate virtual environment
Write-Host "`nActivating virtual environment..." -ForegroundColor Yellow
$VenvActivate = Join-Path $AIModuleDir "venv\Scripts\Activate.ps1"

if (Test-Path $VenvActivate) {
    & $VenvActivate
    Write-Host "Virtual environment activated" -ForegroundColor Green
} else {
    Write-Host "ERROR: Virtual environment not found at: $VenvActivate" -ForegroundColor Red
    Write-Host "Please create venv first with: python -m venv venv" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Change to scripts directory
Set-Location $ScriptDir

# Start FastAPI server
Write-Host "`nStarting FastAPI server on http://0.0.0.0:5005" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

try {
    python -m uvicorn rag_api:app --host 0.0.0.0 --port 5005 --log-level info
} catch {
    Write-Host "`nERROR: Failed to start server" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}

Write-Host "`nRAG API server stopped" -ForegroundColor Yellow
Read-Host "Press Enter to exit"
