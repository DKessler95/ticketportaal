@echo off
REM Validation Metrics Calculator
REM Calculates precision, recall, and generates threshold recommendations

echo ========================================
echo AI Validation Metrics Report
echo ========================================
echo.

REM Check if virtual environment exists
if not exist "..\..\venv\Scripts\activate.bat" (
    echo ERROR: Virtual environment not found!
    pause
    exit /b 1
)

REM Activate virtual environment
call ..\..\venv\Scripts\activate.bat

echo Checking validation progress...
echo.
python validation_metrics.py --progress
echo.

echo ========================================
echo Generating Full Validation Report...
echo ========================================
echo.

REM Generate report and save to file
python validation_metrics.py --report > validation_report_%date:~-4,4%%date:~-10,2%%date:~-7,2%.json

if errorlevel 1 (
    echo ERROR: Failed to generate validation report
    pause
    exit /b 1
)

echo.
echo Report saved to: validation_report_%date:~-4,4%%date:~-10,2%%date:~-7,2%.json
echo.

echo ========================================
echo Entity Extraction Metrics
echo ========================================
echo.
python validation_metrics.py --entity-metrics
echo.

echo ========================================
echo Relationship Extraction Metrics
echo ========================================
echo.
python validation_metrics.py --relationship-metrics
echo.

echo ========================================
echo Confidence Threshold Analysis
echo ========================================
echo.
python validation_metrics.py --threshold-analysis
echo.

echo ========================================
echo Validation Complete!
echo ========================================
echo.
echo Review the metrics above and:
echo 1. Check precision and recall scores
echo 2. Review recommended confidence threshold
echo 3. Update thresholds in entity_extractor.py and relationship_extractor.py
echo 4. Re-run knowledge extraction with new thresholds
echo.
echo To re-run extraction:
echo   python knowledge_extraction_pipeline.py --all
echo.
pause
