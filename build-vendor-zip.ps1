# Builds vendor-only.zip with forward-slash paths for Hostinger File Manager extract.
$ErrorActionPreference = "Stop"
$root = $PSScriptRoot
$vendorDir = Join-Path $root "vendor"
$zipPath = Join-Path $root "vendor-only.zip"

if (-not (Test-Path (Join-Path $vendorDir "phpmailer\phpmailer\src\PHPMailer.php"))) {
    Write-Host "ERROR: Run 'composer install' first." -ForegroundColor Red
    exit 1
}

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
Get-ChildItem -Path $vendorDir -Recurse -File | ForEach-Object {
    $relative = "vendor/" + ($_.FullName.Substring($vendorDir.Length + 1) -replace '\\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $relative) | Out-Null
}
$zip.Dispose()

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
Write-Host "Created $zipPath ($sizeMb MB)" -ForegroundColor Green
Write-Host "Upload to public_html in Hostinger File Manager, then Extract (overwrite vendor/)." -ForegroundColor Yellow
