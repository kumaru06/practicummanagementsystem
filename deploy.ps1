# Legacy entry point — use hostinger-deploy.ps1 for Hostinger.
# See HOSTINGER_DEPLOY.md for Git auto-deploy (recommended).

Write-Host "Redirecting to hostinger-deploy.ps1..." -ForegroundColor Cyan
& "$PSScriptRoot\hostinger-deploy.ps1" @args
