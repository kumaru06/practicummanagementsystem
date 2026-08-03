param(
    [Parameter(Mandatory = $true)]
    [string[]]$Files
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$configFile = Join-Path $root 'hostinger.deploy.json'

if (-not (Test-Path $configFile)) {
    throw 'Missing hostinger.deploy.json'
}

$config = Get-Content $configFile -Raw | ConvertFrom-Json
$remoteRoot = ($config.remotePath -replace '^/|/$', '')
$ok = 0
$bad = 0

foreach ($rel in $Files) {
    $rel = $rel -replace '\\', '/'
    $local = Join-Path $root ($rel -replace '/', [IO.Path]::DirectorySeparatorChar)
    if (-not (Test-Path $local)) {
        Write-Host "[SKIP] $rel (missing locally)" -ForegroundColor Yellow
        $bad++
        continue
    }

    $remotePath = if ($remoteRoot) { "$remoteRoot/$rel" } else { $rel }
    $encoded = ($remotePath -split '/' | ForEach-Object { [uri]::EscapeDataString($_) }) -join '/'
    $url = "ftp://$($config.ftpHost)/$encoded"

    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'SilentlyContinue'
    $output = & curl.exe --silent --show-error --no-progress-meter --max-time 180 --ftp-pasv --disable-epsv `
        --user "$($config.ftpUser):$($config.ftpPass)" `
        --ftp-create-dirs `
        --upload-file $local `
        $url 2>&1
    $exitCode = $LASTEXITCODE
    $ErrorActionPreference = $prev

    if ($exitCode -eq 0) {
        Write-Host "[OK] $rel" -ForegroundColor Green
        $ok++
    } else {
        Write-Host "[FAIL] $rel" -ForegroundColor Red
        if ($output) {
            Write-Host "       $output" -ForegroundColor DarkRed
        }
        $bad++
    }
}

Write-Host ""
Write-Host "Retry: $ok ok, $bad failed"
exit $(if ($bad -eq 0) { 0 } else { 1 })
