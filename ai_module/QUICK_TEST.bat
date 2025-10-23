@echo off
REM Quick Test Script - Test het systeem zonder volledige installatie
REM Gebruik dit om te zien of de basis werkt

echo ============================================================
echo K&K Ticketportaal AI - Snelle Test
echo ============================================================
echo.

REM Check Python
echo [1/5] Checken of Python werkt...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo   X Python niet gevonden!
    echo   Installeer Python van https://www.python.org/downloads/
    pause
    exit /b 1
)
echo   OK Python gevonden

REM Check if we're in the right directory
echo.
echo [2/5] Checken of we in de juiste map zitten...
if not exist "scripts\rag_api.py" (
    echo   X Kan scripts niet vinden!
    echo   Zorg dat je in de ai_module map zit
    pause
    exit /b 1
)
echo   OK Scripts gevonden

REM Check MySQL
echo.
echo [3/5] Checken of MySQL draait...
netstat -an | findstr ":3306" >nul 2>&1
if %errorlevel% neq 0 (
    echo   ! MySQL lijkt niet te draaien
    echo   Start XAMPP/MySQL eerst
    pause
)
echo   OK MySQL draait

REM Create venv if not exists
echo.
echo [4/5] Checken Python virtual environment...
if not exist "venv" (
    echo   Aanmaken virtual environment...
    python -m venv venv
    echo   OK Virtual environment aangemaakt
) else (
    echo   OK Virtual environment bestaat al
)

REM Install minimal dependencies
echo.
echo [5/5] Installeren minimale dependencies...
echo   Dit kan 2-5 minuten duren...
call venv\Scripts\activate.bat
pip install --quiet requests mysql-connector-python
if %errorlevel% neq 0 (
    echo   X Fout bij installeren packages
    pause
    exit /b 1
)
echo   OK Dependencies geinstalleerd

REM Run verification
echo.
echo ============================================================
echo Test Resultaten
echo ============================================================
echo.

REM Test 1: Python imports
echo Test 1: Python modules...
python -c "import sys; import mysql.connector; print('  OK Basis modules werken')" 2>nul
if %errorlevel% neq 0 (
    echo   X Python modules niet gevonden
)

REM Test 2: Database
echo Test 2: Database connectie...
python -c "import mysql.connector; conn = mysql.connector.connect(host='localhost', user='root', password='', database='ticketportaal'); print('  OK Database bereikbaar'); conn.close()" 2>nul
if %errorlevel% neq 0 (
    echo   X Kan niet verbinden met database
    echo   Check of MySQL draait en database 'ticketportaal' bestaat
)

REM Test 3: Ollama
echo Test 3: Ollama service...
curl -s http://localhost:11434/api/tags >nul 2>&1
if %errorlevel% neq 0 (
    echo   X Ollama niet bereikbaar
    echo   Installeer Ollama van https://ollama.com
) else (
    echo   OK Ollama draait
)

REM Test 4: RAG API
echo Test 4: RAG API service...
curl -s http://localhost:5005/health >nul 2>&1
if %errorlevel% neq 0 (
    echo   ! RAG API draait niet
    echo   Dit is normaal als je nog niet hebt geinstalleerd
) else (
    echo   OK RAG API draait
)

echo.
echo ============================================================
echo Samenvatting
echo ============================================================
echo.
echo Als alle tests OK zijn, kun je de volledige installatie doen:
echo   1. Run: install.bat (als Administrator)
echo   2. Wacht tot het klaar is (30-60 minuten)
echo   3. Test met: python verify_installation.py
echo.
echo Als er X'en zijn, los die eerst op voordat je installeert.
echo.
echo Hulp nodig? Lees: START_HIER.md
echo.
pause
