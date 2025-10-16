@echo off
echo Restarting Apache in XAMPP...
echo.

REM Stop Apache
"C:\xampp\apache\bin\httpd.exe" -k stop

REM Wait 2 seconds
timeout /t 2 /nobreak > nul

REM Start Apache
"C:\xampp\apache\bin\httpd.exe" -k start

echo.
echo Apache restarted!
echo You can now try logging in again.
pause
