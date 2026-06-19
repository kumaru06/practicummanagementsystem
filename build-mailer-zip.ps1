# Tiny mailer bundle (~150 KB) — upload lib/ folder only, no vendor zip needed.
$ErrorActionPreference = "Stop"
$root = $PSScriptRoot
$libDir = Join-Path $root "lib"
$zipPath = Join-Path $root "mailer-only.zip"

if (-not (Test-Path (Join-Path $libDir "phpmailer\PHPMailer.php"))) {
    $src = Join-Path $root "vendor\phpmailer\phpmailer\src"
    $dst = Join-Path $libDir "phpmailer"
    if (-not (Test-Path $src)) {
        Write-Host "ERROR: lib/phpmailer missing. Run composer install first." -ForegroundColor Red
        exit 1
    }
    New-Item -ItemType Directory -Force -Path $dst | Out-Null
    Copy-Item "$src\*" $dst -Force
}

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
Get-ChildItem -Path $libDir -Recurse -File | ForEach-Object {
    $relative = "lib/" + ($_.FullName.Substring($libDir.Length + 1) -replace '\\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $relative) | Out-Null
}
$zip.Dispose()

$kb = [math]::Round((Get-Item $zipPath).Length / 1KB, 0)
Write-Host "Created $zipPath ($kb KB)" -ForegroundColor Green
Write-Host "Upload to public_html and extract (includes lib/phpmailer/)." -ForegroundColor Yellow
