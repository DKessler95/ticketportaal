@echo off
REM Simpele Installatie - Stap voor Stap
REM Voor K en K Ticketportaal AI

echo ============================================================
echo Ticketportaal AI - Simpele Installatie
echo ============================================================
echo.

REM Check Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo FOUT: Run dit als Administrator!
    echo Klik rechts op het bestand en kies "Run as Administrator"
    pause
    exit /b 1
)

echo Stap 1: Python Virtual Environment aanmaken...
echo.

if exist venv (
    echo Virtual environment bestaat al, wordt overgeslagen
) else (
    python -m venv venv
    if %errorLevel% neq 0 (
        echo FOUT: Kan virtual environment niet aanmaken
        echo Check of Python correct is geinstalleerd
        pause
        exit /b 1
    )
    echo OK - Virtual environment aangemaakt
)

echo.
echo Stap 2: Python packages installeren...
echo Dit duurt 5-10 minuten, even geduld...
echo.

call venv\Scripts\activate.bat

pip install --upgrade pip >nul 2>&1

echo Installeren: mysql-connector-python
pip install mysql-connector-python

echo Installeren: chromadb
pip install chromadb

echo Installeren: sentence-transformers
pip install sentence-transformers

echo Installeren: fastapi en uvicorn
pip install fastapi uvicorn

echo Installeren: spacy
pip install spacy

echo Installeren: rank-bm25
pip install rank-bm25

echo Installeren: networkx
pip install networkx

echo Installeren: numpy, tqdm, psutil, requests
pip install numpy tqdm psutil requests

echo.
echo Stap 3: spaCy Nederlands model downloaden...
echo Dit duurt 2-5 minuten...
echo.

python -m spacy download nl_core_news_lg

echo.
echo ============================================================
echo Basis Installatie Voltooid!
echo ============================================================
echo.
echo Volgende stappen:
echo.
echo 1. Installeer Ollama handmatig:
echo    - Download van: https://ollama.com/download
echo    - Installeer Ollama
echo    - Open Command Prompt en typ: ollama pull llama3.1:8b
echo    - Dit downloadt het AI model (4.7GB, duurt 10-30 min)
echo.
echo 2. Importeer database schema:
echo    - Open phpMyAdmin
echo    - Selecteer database 'ticketportaal'
echo    - Importeer: database/migrations/007_create_knowledge_graph_schema.sql
echo.
echo 3. Test de installatie:
echo    - python verify_installation.py
echo.
echo 4. Start de RAG API:
echo    - cd scripts
echo    - python rag_api.py
echo.
echo 5. In een andere Command Prompt, test de sync:
echo    - cd scripts
echo    - python sync_tickets_to_vector_db.py --limit 10
echo.
echo Hulp nodig? Lees: START_HIER.md
echo.
pause
