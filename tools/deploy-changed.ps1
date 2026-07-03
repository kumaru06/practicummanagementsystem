param(
    [string[]]$Files = @()
)

$ErrorActionPreference = "Stop"
$localRoot = Split-Path $PSScriptRoot -Parent
$configFile = Join-Path $localRoot "hostinger.deploy.json"

if (-not (Test-Path $configFile)) {
    throw "Missing hostinger.deploy.json"
}

$json = Get-Content $configFile -Raw | ConvertFrom-Json
$remoteRoot = ($json.remotePath -replace '^/|/$', '')

function Build-FtpRemoteUrl([string]$HostName, [string]$RemotePath) {
    $segments = $RemotePath -split '/'
    $encoded = ($segments | ForEach-Object { [uri]::EscapeDataString($_) }) -join '/'
    return "ftp://$HostName/$encoded"
}

function Invoke-FtpUpload([string]$LocalFile, [string]$RemoteUrl, [string]$User, [string]$Pass) {
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'SilentlyContinue'
    try {
        $output = & curl.exe --silent --show-error --no-progress-meter --max-time 120 --ftp-pasv --disable-epsv `
            --user "${User}:${Pass}" --ftp-create-dirs --upload-file $LocalFile $RemoteUrl 2>&1
        return @{ Ok = ($LASTEXITCODE -eq 0); Output = ($output | Out-String).Trim() }
    } finally {
        $ErrorActionPreference = $prev
    }
}

if ($Files.Count -eq 0) {
    $Files = @(
        "assets/css/login.css",
        "assets/css/style.css",
        "controllers/AdminController.php",
        "controllers/AuthController.php",
        "controllers/CoordinatorController.php",
        "database/migration_2026_07_03_student_registration_requests.sql",
        "database/migration_2026_07_03_middle_name.sql",
        "database/schema.sql",
        "helpers.php",
        "models/StudentRegistrationRequest.php",
        "models/User.php",
        "views/admin/registration_requests.php",
        "views/admin/users.php",
        "views/coordinator/manage.php",
        "views/shared/register.php"
    )
}

$uploaded = 0
$failed = 0

Write-Host ""
Write-Host "Uploading $($Files.Count) files to Hostinger..." -ForegroundColor Cyan
Write-Host ""

foreach ($rel in $Files) {
    $rel = $rel -replace '\\', '/'
    $local = Join-Path $localRoot ($rel -replace '/', [IO.Path]::DirectorySeparatorChar)
    if (-not (Test-Path $local)) {
        Write-Host "[SKIP] $rel (not found)" -ForegroundColor Yellow
        continue
    }

    $remotePath = if ($remoteRoot) { "$remoteRoot/$rel" } else { $rel }
    $url = Build-FtpRemoteUrl $json.ftpHost $remotePath
    $result = Invoke-FtpUpload $local $url $json.ftpUser $json.ftpPass

    if ($result.Ok) {
        $uploaded++
        Write-Host "[OK] $rel" -ForegroundColor Green
    } else {
        $failed++
        Write-Host "[FAIL] $rel" -ForegroundColor Red
        if ($result.Output) {
            Write-Host "       $($result.Output)" -ForegroundColor DarkRed
        }
    }
}

Write-Host ""
Write-Host "Uploaded: $uploaded | Failed: $failed" -ForegroundColor $(if ($failed -eq 0) { 'Green' } else { 'Yellow' })

if ($json.websiteUrl -and $failed -eq 0) {
    Write-Host "Live site: $($json.websiteUrl)" -ForegroundColor Green
}

if ($failed -gt 0) {
    exit 1
}
