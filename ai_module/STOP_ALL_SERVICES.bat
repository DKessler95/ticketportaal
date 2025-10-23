@echo off
REM Stop All AI Services

echo ============================================
echo Stopping All AI Services
echo ============================================
echo.

echo [1/3] Stopping RAG API...
taskkill /FI "WINDOWTITLE eq RAG API Service*" /F >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ RAG API stopped
) else (
    echo ℹ RAG API was not running
)

echo.
echo [2/3] Stopping Ollama Service...
net stop Ollama >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Ollama stopped
) else (
    echo ℹ Ollama was not running
)

echo.
echo [3/3] Stopping Qdrant Service...
net stop Qdrant >nul 2>&1
if %errorLevel% == 0 (
    echo ✓ Qdrant stopped
) else (
    echo ℹ Qdrant was not running
)

echo.
echo ============================================
echo All Services Stopped
echo ============================================
echo.
pause
