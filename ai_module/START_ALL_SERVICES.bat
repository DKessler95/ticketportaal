@echo off
REM Start All AI Services
REM This script starts Qdrant, Ollama, and RAG API

echo ============================================
echo Starting All AI Services
echo ============================================
echo.

REM Check if running as admin
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as Administrator
) else (
    echo WARNING: Not running as Administrator
    echo Some services may not start correctly
)

echo.
echo [1/3] Checking Qdrant Service...
sc query Qdrant | find "RUNNING" >nul
if %errorLevel% == 0 (
    echo ✓ Qdrant is already running
) else (
    echo Starting Qdrant...
    net start Qdrant
    timeout /t 3 >nul
)

echo.
echo [2/3] Checking Ollama Service...
sc query Ollama | find "RUNNING" >nul
if %errorLevel% == 0 (
    echo ✓ Ollama is already running
) else (
    echo Starting Ollama...
    net start Ollama
    timeout /t 3 >nul
)

echo.
echo [3/3] Starting RAG API...
echo Opening new window for RAG API...
echo.

cd /d "%~dp0scripts"
start "RAG API Service" cmd /k "..\venv\Scripts\python.exe rag_api.py"

timeout /t 3 >nul

echo.
echo ============================================
echo All Services Started!
echo ============================================
echo.
echo Service Status:
echo   Qdrant:  http://localhost:6333/dashboard
echo   Ollama:  http://localhost:11434
echo   RAG API: http://localhost:5005
echo.
echo RAG API is running in a separate window.
echo Close that window to stop the RAG API.
echo.
echo Testing connections...
timeout /t 2 >nul

curl -s http://localhost:6333 >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Qdrant is responding
) else (
    echo ✗ Qdrant is not responding
)

curl -s http://localhost:11434 >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Ollama is responding
) else (
    echo ✗ Ollama is not responding
)

curl -s http://localhost:5005/health >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ RAG API is responding
) else (
    echo ✗ RAG API is not responding yet (may need a few more seconds)
)

echo.
echo Press any key to exit...
pause >nul
