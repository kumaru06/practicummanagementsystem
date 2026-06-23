@echo off
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0hostinger-deploy.ps1" -ZipOnly %*
pause
