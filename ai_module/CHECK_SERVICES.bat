@echo off
REM Check Status of All AI Services

echo ============================================
echo AI Services Status Check
echo ============================================
echo.

echo [1/3] Qdrant Service:
sc query Qdrant | find "STATE" 
echo     Dashboard: http://localhost:6333/dashboard
echo.

echo [2/3] Ollama Service:
sc query Ollama | find "STATE"
echo     API: http://localhost:11434
echo.

echo [3/3] RAG API:
tasklist /FI "WINDOWTITLE eq RAG API Service*" 2>nul | find "python.exe" >nul
if %errorLevel% == 0 (
    echo     STATE: RUNNING
) else (
    echo     STATE: STOPPED
)
echo     API: http://localhost:5005
echo.

echo ============================================
echo Testing Connections...
echo ============================================
echo.

curl -s http://localhost:6333 >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Qdrant is responding on port 6333
) else (
    echo ✗ Qdrant is NOT responding
)

curl -s http://localhost:11434 >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Ollama is responding on port 11434
) else (
    echo ✗ Ollama is NOT responding
)

curl -s http://localhost:5005/health >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ RAG API is responding on port 5005
) else (
    echo ✗ RAG API is NOT responding
)

echo.
echo ============================================
echo.
pause
