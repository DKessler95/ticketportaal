@echo off
REM K&K Ticketportaal AI - Installatie Wrapper
REM Dubbelklik op dit bestand om de installatie te starten

echo ============================================================
echo K en K Ticketportaal AI - Systeem Installatie
echo ============================================================
echo.
echo Dit script installeert het complete AI systeem.
echo.
echo BELANGRIJK:
echo - Zorg dat je Administrator rechten hebt
echo - Zorg dat Python 3.11+ is geinstalleerd
echo - Zorg dat MySQL/XAMPP draait
echo - Zorg dat je minimaal 20GB vrije schijfruimte hebt
echo.
pause

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo.
    echo ERROR: Dit script moet als Administrator worden uitgevoerd
    echo Klik rechts op install.bat en kies "Run as Administrator"
    echo.
    pause
    exit /b 1
)

REM Run PowerShell script
powershell.exe -ExecutionPolicy Bypass -File "%~dp0install_complete_system.ps1"

pause
