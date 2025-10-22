@echo off
REM Batch wrapper for test_ollama_api.ps1

echo ========================================
echo Ollama API Test Script
echo ========================================
echo.

powershell.exe -ExecutionPolicy Bypass -File "%~dp0test_ollama_api.ps1"

echo.
echo Press any key to exit...
pause >nul
