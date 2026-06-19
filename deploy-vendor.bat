@echo off
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-vendor-zip.ps1"
echo.
echo Next: upload vendor-only.zip in Hostinger File Manager -^> public_html -^> Extract
pause
