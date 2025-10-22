@echo off
REM Backup script voor Kruit & Kramer Ticketportaal
REM Windows versie voor XAMPP

echo ========================================
echo Kruit ^& Kramer Ticketportaal Backup
echo ========================================
echo.

REM Stel datum en tijd in
set TIMESTAMP=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

REM Backup directory
set BACKUP_DIR=backups
if not exist %BACKUP_DIR% mkdir %BACKUP_DIR%

echo [1/3] Database backup maken...
REM Database backup (pas gebruikersnaam en wachtwoord aan)
C:\Users\Damian\XAMPP\mysql\bin\mysqldump.exe -u root ticketportaal > %BACKUP_DIR%\db_%TIMESTAMP%.sql
if %errorlevel% equ 0 (
    echo Database backup succesvol: db_%TIMESTAMP%.sql
) else (
    echo FOUT: Database backup mislukt!
)

echo.
echo [2/3] Bestanden backup maken...
REM Bestanden backup (uploads, config, logs)
tar -czf %BACKUP_DIR%\files_%TIMESTAMP%.tar.gz uploads config logs
if %errorlevel% equ 0 (
    echo Bestanden backup succesvol: files_%TIMESTAMP%.tar.gz
) else (
    echo FOUT: Bestanden backup mislukt!
)

echo.
echo [3/3] Oude backups opruimen (ouder dan 30 dagen)...
forfiles /p %BACKUP_DIR% /s /m *.sql /d -30 /c "cmd /c del @path" 2>nul
forfiles /p %BACKUP_DIR% /s /m *.tar.gz /d -30 /c "cmd /c del @path" 2>nul

echo.
echo ========================================
echo Backup voltooid!
echo Locatie: %BACKUP_DIR%
echo ========================================
echo.
pause
