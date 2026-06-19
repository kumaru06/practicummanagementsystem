# Use hostinger-deploy.ps1 instead. This script is kept for compatibility only.

Write-Host "Use .\hostinger-deploy.ps1 — see HOSTINGER_DEPLOY.md" -ForegroundColor Yellow
& "$PSScriptRoot\hostinger-deploy.ps1" @args
