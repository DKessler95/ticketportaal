@echo off
REM Configure Ollama Service Models Path
REM This script must be run as Administrator

echo ========================================
echo Configure Ollama Models Path
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

powershell -ExecutionPolicy Bypass -File "%~dp0configure_models_path.ps1"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Configuration failed with error code %ERRORLEVEL%
    pause
    exit /b %ERRORLEVEL%
)

echo.
pause
