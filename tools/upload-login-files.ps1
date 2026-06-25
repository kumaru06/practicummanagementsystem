$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$config = Get-Content (Join-Path $root 'hostinger.deploy.json') -Raw | ConvertFrom-Json

$targets = @(
    'assets/css/login.css',
    'assets/css/style.css',
    'assets/js/login-portal.js',
    'assets/js/main.js',
    'views/shared/footer.php',
    'views/shared/header.php',
    'views/shared/login.php',
    'assets/image/login/amamain.jpg',
    'assets/image/main/favicon.jpg'
)

$ok = 0
$fail = 0

foreach ($rel in $targets) {
    $local = Join-Path $root ($rel -replace '/', [IO.Path]::DirectorySeparatorChar)
    if (-not (Test-Path $local)) {
        Write-Host "MISSING $rel" -ForegroundColor Red
        $fail++
        continue
    }

    $remote = if ($config.remotePath) { "$($config.remotePath)/$rel" } else { $rel }
    $segments = $remote -split '/'
    $encoded = ($segments | ForEach-Object { [uri]::EscapeDataString($_) }) -join '/'
    $url = "ftp://$($config.ftpHost)/$encoded"

    $null = & curl.exe --silent --show-error --no-progress-meter --max-time 120 --ftp-pasv --disable-epsv `
        --user "$($config.ftpUser):$($config.ftpPass)" `
        --ftp-create-dirs `
        --upload-file $local `
        $url 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "OK $rel" -ForegroundColor Green
        $ok++
    } else {
        Write-Host "FAIL $rel" -ForegroundColor Red
        $fail++
    }
}

Write-Host ""
Write-Host "Done: $ok uploaded, $fail failed"
if ($config.websiteUrl) {
    Write-Host "Live: $($config.websiteUrl)" -ForegroundColor Cyan
}

exit $(if ($fail -gt 0) { 1 } else { 0 })
