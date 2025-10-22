@echo off
REM Download NSSM (Non-Sucking Service Manager)

echo ========================================
echo NSSM Download Script
echo ========================================
echo.

powershell -ExecutionPolicy Bypass -File "%~dp0download_nssm.ps1"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Script failed with error code %ERRORLEVEL%
    pause
    exit /b %ERRORLEVEL%
)

echo.
pause
