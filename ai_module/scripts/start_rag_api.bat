@echo off
REM Start RAG API Service
REM This script activates the virtual environment and starts the FastAPI server

echo ========================================
echo Starting K&K Ticketportaal RAG API
echo ========================================

REM Get the directory where this script is located
set SCRIPT_DIR=%~dp0
set AI_MODULE_DIR=%SCRIPT_DIR%..

echo Script directory: %SCRIPT_DIR%
echo AI module directory: %AI_MODULE_DIR%

REM Activate virtual environment
echo.
echo Activating virtual environment...
call "%AI_MODULE_DIR%\venv\Scripts\activate.bat"

if errorlevel 1 (
    echo ERROR: Failed to activate virtual environment
    echo Please ensure venv exists at: %AI_MODULE_DIR%\venv
    pause
    exit /b 1
)

echo Virtual environment activated

REM Change to scripts directory
cd /d "%SCRIPT_DIR%"

REM Start FastAPI server with uvicorn
echo.
echo Starting FastAPI server on http://0.0.0.0:5005
echo Press Ctrl+C to stop the server
echo.

python -m uvicorn rag_api:app --host 0.0.0.0 --port 5005 --log-level info

REM If we get here, the server has stopped
echo.
echo RAG API server stopped
pause
