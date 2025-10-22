@echo off
REM ============================================================================
REM GitHub Push Script voor Ticketportaal
REM Maakt het pushen naar GitHub eenvoudiger met automatische commit messages
REM ============================================================================

echo.
echo ========================================================================
echo   GitHub Push Script - Ticketportaal
echo ========================================================================
echo.

REM Controleer of we in een git repository zitten
git rev-parse --git-dir >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Dit is geen git repository!
    echo Voer eerst 'git init' uit.
    pause
    exit /b 1
)

REM Toon huidige status
echo [STAP 1] Controleren van wijzigingen...
echo.
git status --short
echo.

REM Vraag om bevestiging
set /p confirm="Wil je deze wijzigingen committen en pushen? (j/n): "
if /i not "%confirm%"=="j" (
    echo.
    echo Push geannuleerd.
    pause
    exit /b 0
)

REM Vraag om commit message
echo.
echo [STAP 2] Commit message invoeren
echo.
echo Voorbeelden:
echo   - Task 7: Implemented Knowledge Graph Schema
echo   - Fixed bug in user management
echo   - Added email notification feature
echo.
set /p message="Commit message: "

if "%message%"=="" (
    echo.
    echo [ERROR] Commit message mag niet leeg zijn!
    pause
    exit /b 1
)

REM Voeg alle bestanden toe (inclusief AI module en specs)
echo.
echo [STAP 3] Bestanden toevoegen aan staging...
echo   - AI module bestanden
echo   - RAG AI specs en documentatie
echo   - Database migraties
echo   - Alle andere wijzigingen
echo.
git add .
git add ai_module/
git add .kiro/specs/rag-ai-local-implementation/
git add database/migrations/

REM Commit
echo.
echo [STAP 4] Committen met message: "%message%"
git commit -m "%message%"

if errorlevel 1 (
    echo.
    echo [ERROR] Commit mislukt!
    echo Mogelijk zijn er geen wijzigingen om te committen.
    pause
    exit /b 1
)

REM Push naar GitHub
echo.
echo [STAP 5] Pushen naar GitHub...
git push origin main

if errorlevel 1 (
    echo.
    echo [ERROR] Push mislukt!
    echo Controleer je internet verbinding en GitHub credentials.
    echo.
    echo Probeer handmatig:
    echo   git push origin main
    pause
    exit /b 1
)

REM Success!
echo.
echo ========================================================================
echo   SUCCESS! Code is gepusht naar GitHub
echo ========================================================================
echo.
echo Commit: "%message%"
echo Branch: main
echo.
pause
