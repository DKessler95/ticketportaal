@echo off
REM Human-in-the-Loop Validation Workflow
REM Quick start script for running the complete validation process

echo ========================================
echo AI Extraction Validation Workflow
echo ========================================
echo.

REM Check if virtual environment exists
if not exist "..\..\venv\Scripts\activate.bat" (
    echo ERROR: Virtual environment not found!
    echo Please run setup first.
    pause
    exit /b 1
)

REM Activate virtual environment
call ..\..\venv\Scripts\activate.bat

echo Step 1: Creating validation tables...
echo.
python validation_sampler.py --create-tables
if errorlevel 1 (
    echo ERROR: Failed to create validation tables
    pause
    exit /b 1
)

echo.
echo Step 2: Generating 100 validation samples...
echo This will sample tickets using stratified sampling.
echo.
python validation_sampler.py --samples 100
if errorlevel 1 (
    echo ERROR: Failed to generate validation samples
    pause
    exit /b 1
)

echo.
echo ========================================
echo Validation samples generated successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Open your web browser
echo 2. Navigate to: http://localhost/admin/ai_validation.php
echo 3. Log in as admin
echo 4. Review and validate each sample
echo 5. After completing validations, run: run_validation_metrics.bat
echo.
echo Press any key to open the validation UI in your browser...
pause > nul

REM Try to open browser
start http://localhost/admin/ai_validation.php

echo.
echo Browser opened. Complete the validations, then run:
echo   run_validation_metrics.bat
echo.
pause
