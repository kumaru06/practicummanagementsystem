@echo off
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-mailer-zip.ps1"
echo.
echo Upload mailer-only.zip to public_html and extract (includes lib/phpmailer and bootstrap/).
pause
