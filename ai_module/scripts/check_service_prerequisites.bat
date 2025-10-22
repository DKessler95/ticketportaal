@echo off
REM Check prerequisites for Ollama service installation

echo ========================================
echo Ollama Service Prerequisites Check
echo ========================================
echo.

powershell -ExecutionPolicy Bypass -File "%~dp0check_service_prerequisites.ps1"

echo.
pause
