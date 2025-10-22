@echo off
REM Install spaCy and download Dutch NER model
REM This script installs spaCy and downloads the nl_core_news_lg model

echo ========================================
echo Installing spaCy and Dutch NER Model
echo ========================================
echo.

REM Check if virtual environment exists
if not exist "C:\TicketportaalAI\venv\Scripts\activate.bat" (
    echo ERROR: Virtual environment not found at C:\TicketportaalAI\venv
    echo Please run setup_environment.bat first
    pause
    exit /b 1
)

echo Activating virtual environment...
call C:\TicketportaalAI\venv\Scripts\activate.bat

echo.
echo Installing spaCy...
python -m pip install spacy>=3.7.0

if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to install spaCy
    pause
    exit /b 1
)

echo.
echo Downloading Dutch NER model (nl_core_news_lg)...
echo This may take a few minutes (approximately 560MB download)...
python -m spacy download nl_core_news_lg

if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Failed to download large model, trying smaller model...
    python -m spacy download nl_core_news_sm
    
    if %ERRORLEVEL% NEQ 0 (
        echo ERROR: Failed to download Dutch model
        pause
        exit /b 1
    )
)

echo.
echo Verifying installation...
python -c "import spacy; nlp = spacy.load('nl_core_news_lg'); print('Model loaded successfully!')"

if %ERRORLEVEL% NEQ 0 (
    echo Trying smaller model...
    python -c "import spacy; nlp = spacy.load('nl_core_news_sm'); print('Model loaded successfully!')"
)

echo.
echo ========================================
echo Installation Complete!
echo ========================================
echo.
echo spaCy and Dutch NER model are now installed.
echo You can now use the entity extraction functionality.
echo.

pause
