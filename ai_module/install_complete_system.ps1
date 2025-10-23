# Complete AI System Installer voor K en K Ticketportaal
# Installeert en configureert het volledige AI systeem
# Run als Administrator

param(
    [string]$InstallPath = "C:\TicketportaalAI",
    [string]$PythonVersion = "3.11",
    [switch]$SkipOllama,
    [switch]$SkipServices,
    [switch]$TestMode
)

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "K en K Ticketportaal AI - Complete Systeem Installatie" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Check Administrator
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "ERROR: Dit script moet als Administrator worden uitgevoerd" -ForegroundColor Red
    Write-Host "Klik rechts op het script en kies 'Run as Administrator'" -ForegroundColor Yellow
    Read-Host "Druk op Enter om af te sluiten"
    exit 1
}

$ErrorCount = 0
$WarningCount = 0

function Write-Step {
    param([string]$Message)
    Write-Host "`n[STAP] $Message" -ForegroundColor Yellow
}

function Write-Success {
    param([string]$Message)
    Write-Host "  OK $Message" -ForegroundColor Green
}

function Write-Error {
    param([string]$Message)
    Write-Host "  FOUT $Message" -ForegroundColor Red
    $script:ErrorCount++
}

function Write-Warning {
    param([string]$Message)
    Write-Host "  WAARSCHUWING $Message" -ForegroundColor Yellow
    $script:WarningCount++
}

# Step 1: Check Prerequisites
Write-Step "Controleren van vereisten..."

# Check Python
try {
    $pythonCmd = Get-Command python -ErrorAction Stop
    $pythonVersionOutput = python --version 2>&1
    Write-Success "Python gevonden: $pythonVersionOutput"
} catch {
    Write-Error "Python niet gevonden. Installeer Python $PythonVersion of hoger"
    Write-Host "  Download: https://www.python.org/downloads/" -ForegroundColor Cyan
    $ErrorCount++
}

# Check MySQL
try {
    $mysqlService = Get-Service -Name MySQL* -ErrorAction Stop | Select-Object -First 1
    if ($mysqlService.Status -eq 'Running') {
        Write-Success "MySQL service draait"
    } else {
        Write-Warning "MySQL service is gestopt. Start de service."
    }
} catch {
    Write-Warning "MySQL service niet gevonden. Zorg dat MySQL/XAMPP draait."
}

# Check disk space
$drive = Get-PSDrive C
$freeSpaceGB = [math]::Round($drive.Free / 1GB, 2)
if ($freeSpaceGB -lt 20) {
    Write-Warning "Weinig schijfruimte: ${freeSpaceGB}GB vrij (minimaal 20GB aanbevolen)"
} else {
    Write-Success "Voldoende schijfruimte: ${freeSpaceGB}GB vrij"
}

if ($ErrorCount -gt 0) {
    Write-Host "`nKan niet doorgaan vanwege fouten. Los de bovenstaande problemen op." -ForegroundColor Red
    Read-Host "Druk op Enter om af te sluiten"
    exit 1
}

# Step 2: Create Directory Structure
Write-Step "Aanmaken directory structuur..."

$Directories = @(
    $InstallPath,
    "$InstallPath\scripts",
    "$InstallPath\logs",
    "$InstallPath\chromadb_data",
    "$InstallPath\backups"
)

foreach ($dir in $Directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Success "Aangemaakt: $dir"
    } else {
        Write-Success "Bestaat al: $dir"
    }
}

# Step 3: Copy Scripts
Write-Step "Kopieren van scripts..."

$ScriptSource = Split-Path -Parent $MyInvocation.MyCommand.Path
$ScriptFiles = Get-ChildItem -Path "$ScriptSource\scripts\*.py", "$ScriptSource\scripts\*.ps1", "$ScriptSource\scripts\*.bat" -ErrorAction SilentlyContinue

foreach ($file in $ScriptFiles) {
    Copy-Item -Path $file.FullName -Destination "$InstallPath\scripts\" -Force
    Write-Success "Gekopieerd: $($file.Name)"
}

# Step 4: Create Virtual Environment
Write-Step "Aanmaken Python virtual environment..."

Set-Location $InstallPath

if (Test-Path "venv") {
    Write-Warning "Virtual environment bestaat al. Wordt overgeslagen."
} else {
    python -m venv venv
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Virtual environment aangemaakt"
    } else {
        Write-Error "Fout bij aanmaken virtual environment"
    }
}

# Step 5: Install Python Dependencies
Write-Step "Installeren Python dependencies..."

& "$InstallPath\venv\Scripts\python.exe" -m pip install --upgrade pip --quiet

$dependencies = @(
    "mysql-connector-python",
    "chromadb",
    "sentence-transformers",
    "fastapi",
    "uvicorn",
    "spacy",
    "rank-bm25",
    "networkx",
    "numpy",
    "tqdm",
    "psutil",
    "requests"
)

foreach ($dep in $dependencies) {
    Write-Host "  Installeren: $dep..." -NoNewline
    & "$InstallPath\venv\Scripts\pip.exe" install $dep --quiet
    if ($LASTEXITCODE -eq 0) {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " FOUT" -ForegroundColor Red
        $ErrorCount++
    }
}

# Download spaCy model
Write-Host "  Downloaden spaCy Nederlands model..." -NoNewline
& "$InstallPath\venv\Scripts\python.exe" -m spacy download nl_core_news_lg --quiet
if ($LASTEXITCODE -eq 0) {
    Write-Host " OK" -ForegroundColor Green
} else {
    Write-Host " FOUT" -ForegroundColor Red
    $ErrorCount++
}

# Step 6: Install Ollama (if not skipped)
if (-not $SkipOllama) {
    Write-Step "Installeren Ollama..."
    
    $ollamaInstalled = Get-Command ollama -ErrorAction SilentlyContinue
    if ($ollamaInstalled) {
        Write-Success "Ollama is al geinstalleerd"
    } else {
        Write-Host "  Downloaden Ollama installer..."
        $ollamaUrl = "https://ollama.com/download/OllamaSetup.exe"
        $ollamaInstaller = "$env:TEMP\OllamaSetup.exe"
        
        try {
            Invoke-WebRequest -Uri $ollamaUrl -OutFile $ollamaInstaller
            Write-Success "Ollama gedownload"
            
            Write-Host "  Installeren Ollama (dit kan even duren)..."
            Start-Process -FilePath $ollamaInstaller -ArgumentList "/S" -Wait
            Write-Success "Ollama geinstalleerd"
            
            # Wait for Ollama to start
            Start-Sleep -Seconds 5
            
        } catch {
            Write-Error "Fout bij installeren Ollama: $($_.Exception.Message)"
        }
    }
    
    # Download Llama model
    Write-Host "  Downloaden Llama 3.1 model (4.7GB, dit duurt 10-30 minuten)..."
    Write-Host "  Even geduld, dit is een grote download" -ForegroundColor Cyan
    
    try {
        ollama pull llama3.1:8b
        Write-Success "Llama 3.1 model gedownload"
    } catch {
        Write-Warning "Kon Llama model niet downloaden. Voer later uit: ollama pull llama3.1:8b"
    }
}

# Step 7: Database Setup
Write-Step "Database configuratie..."

Write-Host "  Database migraties moeten handmatig worden uitgevoerd" -ForegroundColor Cyan
Write-Host "  Voer uit in MySQL:" -ForegroundColor Cyan
Write-Host "  SOURCE database/migrations/007_create_knowledge_graph_schema.sql" -ForegroundColor Cyan
Write-Warning "Database migraties overgeslagen - voer handmatig uit"

# Step 8: Install Services (if not skipped)
if (-not $SkipServices) {
    Write-Step "Installeren Windows Services..."
    
    # Check NSSM
    $nssm = Get-Command nssm -ErrorAction SilentlyContinue
    if (-not $nssm) {
        Write-Warning "NSSM niet gevonden. Download van https://nssm.cc/download"
        Write-Host "  Services moeten handmatig worden geinstalleerd" -ForegroundColor Cyan
    } else {
        # Install RAG API Service
        Write-Host "  Installeren TicketportaalRAG service..."
        try {
            & "$InstallPath\scripts\install_rag_service.ps1" -ServiceName "TicketportaalRAG"
            Write-Success "RAG API service geinstalleerd"
        } catch {
            Write-Warning "Service installatie overgeslagen"
        }
    }
}

# Step 9: Setup Scheduled Tasks
Write-Step "Configureren Scheduled Tasks..."

try {
    & "$InstallPath\scripts\setup_scheduled_tasks.ps1"
    Write-Success "Scheduled tasks geconfigureerd"
} catch {
    Write-Warning "Fout bij configureren scheduled tasks: $($_.Exception.Message)"
}

# Step 10: Test Installation
if (-not $TestMode) {
    Write-Step "Testen installatie..."
    
    Write-Host "  Starten test sync met 10 tickets..."
    & "$InstallPath\venv\Scripts\python.exe" "$InstallPath\scripts\sync_tickets_to_vector_db.py" --limit 10
    
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Test sync succesvol"
    } else {
        Write-Warning "Test sync gefaald - controleer logs"
    }
}

# Summary
Write-Host "`n============================================================" -ForegroundColor Cyan
Write-Host "INSTALLATIE VOLTOOID" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

if ($ErrorCount -eq 0 -and $WarningCount -eq 0) {
    Write-Host "OK Installatie succesvol zonder fouten!" -ForegroundColor Green
} elseif ($ErrorCount -eq 0) {
    Write-Host "OK Installatie voltooid met $WarningCount waarschuwing(en)" -ForegroundColor Yellow
} else {
    Write-Host "WAARSCHUWING Installatie voltooid met $ErrorCount fout(en) en $WarningCount waarschuwing(en)" -ForegroundColor Yellow
}

Write-Host "`nVolgende stappen:" -ForegroundColor Yellow
Write-Host "1. Voer database migraties uit (zie database/migrations/)" -ForegroundColor White
Write-Host "2. Start RAG API service: Start-Service TicketportaalRAG" -ForegroundColor White
Write-Host "3. Bekijk AI Dashboard: http://localhost/admin/ai_dashboard.php" -ForegroundColor White
Write-Host "4. Test een ticket met AI suggesties" -ForegroundColor White

Write-Host "`nNuttige commandos:" -ForegroundColor Yellow
Write-Host "  Status checken:  Get-Service TicketportaalRAG" -ForegroundColor White
Write-Host "  Logs bekijken:   Get-Content $InstallPath\logs\rag_api_*.log -Tail 50" -ForegroundColor White
Write-Host "  Sync uitvoeren:  cd $InstallPath\scripts; python sync_tickets_to_vector_db.py" -ForegroundColor White

Read-Host "`nDruk op Enter om af te sluiten"
