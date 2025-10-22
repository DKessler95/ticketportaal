@echo off
REM Batch wrapper for verify_ollama.ps1

echo ========================================
echo Ollama Verification Script
echo ========================================
echo.

powershell.exe -ExecutionPolicy Bypass -File "%~dp0verify_ollama.ps1"

echo.
echo Press any key to exit...
pause >nul
