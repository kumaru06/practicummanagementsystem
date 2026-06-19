param(
    [switch]$ZipOnly,
    [switch]$SkipUpload,
    [switch]$IncludeProductionConfig,
    [string]$ConfigPath = ""
)

$ErrorActionPreference = "Stop"
$localRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$configFile = if ($ConfigPath -ne "") { $ConfigPath } else { Join-Path $localRoot "hostinger.deploy.json" }
$zipPath = Join-Path (Split-Path $localRoot -Parent) "amaccmanagementsystem_deploy.zip"

$excludeDirNames = @('.git', '.idea', '.vscode')
$excludeFileNames = @(
    'hostinger.deploy.json',
    'hostinger.deploy.json.example',
    'hostinger-deploy.ps1',
    'deploy.ps1',
    'upload.ps1',
    'HOSTINGER_DEPLOY.md'
)
$excludeFilePatterns = @('*.zip', 'debug-*.log', 'hostinger_export.sql')

function Test-DeployExcluded {
    param([string]$RelativePath, [string]$FileName)

    $RelativePath = $RelativePath -replace '\\', '/'

    foreach ($dir in $excludeDirNames) {
        if ($RelativePath -match "(^|[\\/])$([regex]::Escape($dir))([\\/]|$)") {
            return $true
        }
    }

    if ($excludeFileNames -contains $FileName) {
        return $true
    }

    foreach ($pattern in $excludeFilePatterns) {
        if ($FileName -like $pattern) {
            return $true
        }
    }

    if (-not $IncludeProductionConfig -and $RelativePath -eq 'config/database.production.php') {
        return $true
    }

    if ($RelativePath -match '^uploads[\\/]' -and $FileName -ne '.htaccess' -and $FileName -ne '.gitkeep') {
        return $true
    }

    return $false
}

function Get-DeployFiles {
    Get-ChildItem -Path $localRoot -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($localRoot.Length + 1)
        if (Test-DeployExcluded -RelativePath $relative -FileName $_.Name) {
            return
        }

        [PSCustomObject]@{
            FullName = $_.FullName
            Relative = ($relative -replace '\\', '/')
        }
    }
}

function Read-DeployConfig {
    if (-not (Test-Path $configFile)) {
        throw "Missing $configFile. Copy hostinger.deploy.json.example to hostinger.deploy.json and fill in FTP details from hPanel → Files → FTP Accounts."
    }

    $json = Get-Content $configFile -Raw | ConvertFrom-Json
    foreach ($key in @('ftpHost', 'ftpUser', 'ftpPass', 'remotePath')) {
        if (-not $json.$key) {
            throw "hostinger.deploy.json is missing '$key'."
        }
    }
    return $json
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  AMA OJT Portal - Hostinger Deploy" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$files = @(Get-DeployFiles)
Write-Host "Files to deploy: $($files.Count)" -ForegroundColor Gray
Write-Host ""

Write-Host "[1/2] Building zip (forward-slash paths)..." -ForegroundColor Yellow
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
foreach ($file in $files) {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $file.Relative) | Out-Null
}
$zip.Dispose()

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
Write-Host "  Created: $zipPath ($sizeMb MB)" -ForegroundColor Green

if ($ZipOnly -or $SkipUpload) {
    Write-Host ""
    Write-Host "Zip ready. Upload via File Manager or run without -ZipOnly after configuring FTP." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "[2/2] Uploading via FTP..." -ForegroundColor Yellow
$config = Read-DeployConfig
$remoteRoot = ($config.remotePath -replace '^/|/$', '')
$uploaded = 0
$failed = 0

foreach ($file in $files) {
    $remotePath = "$remoteRoot/$($file.Relative)"
    $url = "ftp://$($config.ftpHost)/$remotePath"

    $ok = $false
    for ($attempt = 1; $attempt -le 3; $attempt++) {
        $null = & curl.exe --silent --show-error --max-time 60 --ftp-pasv --disable-epsv `
            --user "$($config.ftpUser):$($config.ftpPass)" `
            --ftp-create-dirs `
            --upload-file $file.FullName `
            $url 2>&1
        if ($LASTEXITCODE -eq 0) {
            $ok = $true
            break
        }
        Start-Sleep -Seconds 1
    }

    if ($ok) {
        $uploaded++
        Write-Host "  [OK] $($file.Relative)" -ForegroundColor Green
    } else {
        $failed++
        Write-Host "  [FAIL] $($file.Relative)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Uploaded: $uploaded | Failed: $failed" -ForegroundColor $(if ($failed -eq 0) { 'Green' } else { 'Yellow' })

if ($failed -eq 0 -and $config.websiteUrl) {
    Write-Host "Live site: $($config.websiteUrl)" -ForegroundColor Green
}

if ($failed -gt 0) {
    Write-Host "Some files failed. Check FTP password in hostinger.deploy.json (hPanel → FTP Accounts)." -ForegroundColor Yellow
    exit 1
}
