@echo off
REM Batch wrapper for install_ollama.ps1
REM This allows double-clicking to run the PowerShell script

echo ========================================
echo Ollama Installation Script
echo ========================================
echo.
echo This script will install and configure Ollama with Llama 3.1 8B model.
echo.
echo Requirements:
echo - Administrator privileges (recommended)
echo - Internet connection
echo - At least 10GB free disk space
echo.
echo Press any key to continue or Ctrl+C to cancel...
pause >nul

echo.
echo Starting installation...
echo.

REM Run PowerShell script with execution policy bypass
powershell.exe -ExecutionPolicy Bypass -File "%~dp0install_ollama.ps1"

echo.
echo ========================================
echo Installation script completed
echo ========================================
echo.
echo Press any key to exit...
pause >nul
