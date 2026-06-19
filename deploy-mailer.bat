@echo off
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-mailer-zip.ps1"
echo.
echo EASIEST: upload these 3 files to public_html, then open in browser:
echo   install-mailer.php
echo   init.php
echo   bootstrap\mailer.php
echo URL: https://ama-ojtportal.com/install-mailer.php?key=ama-ojt-mailer-2026
pause
