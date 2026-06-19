# One-time FTP setup for hostinger-deploy.ps1
$root = $PSScriptRoot
$config = Join-Path $root "hostinger.deploy.json"
$example = Join-Path $root "hostinger.deploy.json.example"

Write-Host ""
Write-Host "Hostinger FTP Setup" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Get these from Hostinger hPanel -> Files -> FTP Accounts" -ForegroundColor Yellow
Write-Host ""

if (-not (Test-Path $example)) {
    throw "Missing hostinger.deploy.json.example"
}

if (Test-Path $config) {
    $overwrite = Read-Host "hostinger.deploy.json already exists. Overwrite? (y/N)"
    if ($overwrite -ne 'y' -and $overwrite -ne 'Y') {
        Write-Host "Cancelled." -ForegroundColor Gray
        exit 0
    }
}

Copy-Item $example $config -Force

Write-Host ""
Write-Host "Opening hostinger.deploy.json for editing..." -ForegroundColor Green
Write-Host "Fill in ftpPass with your FTP password, then save and close Notepad." -ForegroundColor Yellow
Write-Host ""

Start-Process notepad.exe $config
Read-Host "Press Enter after you saved hostinger.deploy.json"

try {
    $json = Get-Content $config -Raw | ConvertFrom-Json
    if ($json.ftpPass -match 'YOUR_FTP') {
        Write-Host "Warning: ftpPass still looks like a placeholder." -ForegroundColor Red
        exit 1
    }
    Write-Host "Config saved. Run deploy.bat or .\hostinger-deploy.ps1 to deploy." -ForegroundColor Green
} catch {
    Write-Host "Could not read config: $_" -ForegroundColor Red
    exit 1
}
