@echo off
REM Qdrant Installation Launcher
REM Right-click this file and select "Run as Administrator"

echo ============================================
echo Qdrant Windows Installation
echo ============================================
echo.
echo This will install Qdrant as a Windows Service
echo.
pause

PowerShell -NoProfile -ExecutionPolicy Bypass -Command "& '%~dp0install_qdrant.ps1'"

echo.
echo Press any key to exit...
pause >nul
