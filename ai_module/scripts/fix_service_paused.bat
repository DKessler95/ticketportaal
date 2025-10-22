@echo off
REM Fix Ollama Service Paused State
REM This script must be run as Administrator

echo ========================================
echo Fix Ollama Service Paused State
echo ========================================
echo.
echo This script must be run as Administrator!
echo.

net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Please run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

powershell -ExecutionPolicy Bypass -File "%~dp0fix_service_paused.ps1"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Fix failed with error code %ERRORLEVEL%
    pause
    exit /b %ERRORLEVEL%
)

echo.
pause
