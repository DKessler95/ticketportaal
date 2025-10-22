# Install spaCy and download Dutch NER model
# PowerShell version

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Installing spaCy and Dutch NER Model" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if virtual environment exists
$venvPath = "C:\TicketportaalAI\venv\Scripts\Activate.ps1"
if (-not (Test-Path $venvPath)) {
    Write-Host "ERROR: Virtual environment not found at C:\TicketportaalAI\venv" -ForegroundColor Red
    Write-Host "Please run setup_environment.ps1 first" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Activating virtual environment..." -ForegroundColor Yellow
& $venvPath

Write-Host ""
Write-Host "Installing spaCy..." -ForegroundColor Yellow
python -m pip install "spacy>=3.7.0"

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to install spaCy" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""
Write-Host "Downloading Dutch NER model (nl_core_news_lg)..." -ForegroundColor Yellow
Write-Host "This may take a few minutes (approximately 560MB download)..." -ForegroundColor Yellow
python -m spacy download nl_core_news_lg

if ($LASTEXITCODE -ne 0) {
    Write-Host "WARNING: Failed to download large model, trying smaller model..." -ForegroundColor Yellow
    python -m spacy download nl_core_news_sm
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to download Dutch model" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}

Write-Host ""
Write-Host "Verifying installation..." -ForegroundColor Yellow
python -c "import spacy; nlp = spacy.load('nl_core_news_lg'); print('Model loaded successfully!')"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Trying smaller model..." -ForegroundColor Yellow
    python -c "import spacy; nlp = spacy.load('nl_core_news_sm'); print('Model loaded successfully!')"
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Installation Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "spaCy and Dutch NER model are now installed." -ForegroundColor Green
Write-Host "You can now use the entity extraction functionality." -ForegroundColor Green
Write-Host ""

Read-Host "Press Enter to exit"
