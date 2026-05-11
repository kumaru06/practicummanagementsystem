param(
    [switch]$ZipOnly,                  # Just build zip, no FTP upload
    [switch]$SkipGit,                  # Skip git commit & push
    [switch]$SkipFTP,                  # Skip FTP upload (git only)
    [string]$Message = ""              # Git commit message (auto-generated if blank)
)

$ftpUser   = "if0_41872185"
$ftpPass   = "0twdhr9utirPSQ"
$ftpHost   = "ftpupload.net"
$localRoot = "C:\xampp\htdocs\amaccmanagementsystem"
$remoteRoot= "htdocs"
$zipPath   = "C:\xampp\htdocs\amaccmanagementsystem_deploy.zip"

$excludeDirs  = @('.git', 'database')
$excludeFiles = @('deploy.ps1', 'upload.ps1', 'amaccmanagementsystem.zip', 'amaccmanagementsystem_fixed.zip', 'amaccmanagementsystem_deploy.zip')

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  AMA Practicum System - Deploy Script" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# ── Step 0: Git commit & push ────────────────────────────────────────────────
if (-not $SkipGit) {
    Write-Host "[0/3] Pushing to GitHub..." -ForegroundColor Yellow
    Set-Location $localRoot

    $status = & git status --porcelain 2>&1
    if ($status) {
        if ($Message -eq "") {
            $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm"
            $Message = "Deploy update - $timestamp"
        }
        & git add -A
        & git commit -m $Message
        $pushResult = & git push origin main 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  Pushed to GitHub!" -ForegroundColor Green
        } else {
            Write-Host "  [!] Git push failed:" -ForegroundColor Red
            Write-Host "  $pushResult" -ForegroundColor Red
        }
    } else {
        Write-Host "  Nothing to commit, repo is up to date." -ForegroundColor Gray
    }
    Write-Host ""
}

if ($SkipFTP) {
    Write-Host "SkipFTP set — done." -ForegroundColor Green
    exit 0
}

# ── Step 1: Build zip ───────────────────────────────────────────────────────
Write-Host "[1/3] Building deployment zip..." -ForegroundColor Yellow
Remove-Item $zipPath -ErrorAction SilentlyContinue

Add-Type -Assembly System.IO.Compression.FileSystem
$zip = [IO.Compression.ZipFile]::Open($zipPath, 'Create')

Get-ChildItem $localRoot -Recurse -File | ForEach-Object {
    $rel   = $_.FullName.Substring($localRoot.Length + 1)
    $parts = $rel -split '\\'
    $skip  = $false

    foreach ($part in $parts[0..($parts.Count - 2)]) {
        if ($excludeDirs -contains $part) { $skip = $true; break }
    }
    if ($excludeFiles -contains $_.Name) { $skip = $true }
    if ($skip) { return }

    $relForward = $rel.Replace('\', '/')
    [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $relForward) | Out-Null
}
$zip.Dispose()

$sizeMB = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
Write-Host "  Done! ($sizeMB MB, $zipPath)" -ForegroundColor Green

if ($ZipOnly) {
    Write-Host ""
    Write-Host "Zip built. Upload manually via File Manager." -ForegroundColor Yellow
    exit 0
}

# ── Step 3: Upload via FTP ──────────────────────────────────────────────────
Write-Host ""
Write-Host "[3/3] Uploading files via FTP..." -ForegroundColor Yellow
Write-Host "  Testing FTP connection..." -ForegroundColor Gray

$testResult = & curl.exe --silent --show-error --max-time 10 --ftp-pasv --disable-epsv --user "${ftpUser}:${ftpPass}" "ftp://${ftpHost}/${remoteRoot}/" 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "  [!] FTP connection failed. Your current network blocks port 21." -ForegroundColor Red
    Write-Host "      Switch to mobile HOTSPOT and run deploy.ps1 again." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  Alternatively, upload manually:" -ForegroundColor Cyan
    Write-Host "  1. Open File Manager: https://dash.infinityfree.com/accounts/if0_41872185" -ForegroundColor White
    Write-Host "  2. Go inside htdocs/" -ForegroundColor White
    Write-Host "  3. Upload & Unzip: $zipPath" -ForegroundColor White
    Write-Host ""
    Start-Process "https://dash.infinityfree.com/accounts/if0_41872185"
    exit 1
}

Write-Host "  FTP connected!" -ForegroundColor Green

$allFiles = Get-ChildItem $localRoot -Recurse -File
$uploaded = 0; $failed = 0; $skipped = 0

foreach ($file in $allFiles) {
    $rel   = $file.FullName.Substring($localRoot.Length + 1)
    $parts = $rel -split '\\'
    $skip  = $false

    foreach ($part in $parts[0..($parts.Count - 2)]) {
        if ($excludeDirs -contains $part) { $skip = $true; break }
    }
    if ($excludeFiles -contains $file.Name) { $skip = $true }

    if ($skip) { $skipped++; continue }

    $relForward = $rel.Replace('\', '/')
    $url = "ftp://${ftpHost}/${remoteRoot}/${relForward}"

    $success = $false
    for ($i = 1; $i -le 3; $i++) {
        $result = & curl.exe --silent --show-error --max-time 30 --ftp-pasv --disable-epsv --user "${ftpUser}:${ftpPass}" --ftp-create-dirs --upload-file $file.FullName $url 2>&1
        if ($LASTEXITCODE -eq 0) { $success = $true; break }
        Start-Sleep -Seconds 1
    }

    if ($success) {
        $uploaded++
        Write-Host "  [OK] $relForward" -ForegroundColor Green
    } else {
        $failed++
        Write-Host "  [FAIL] $relForward" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Yellow
Write-Host "  Uploaded: $uploaded  |  Failed: $failed  |  Skipped: $skipped" -ForegroundColor Yellow
Write-Host "==========================================" -ForegroundColor Yellow
Write-Host ""

if ($failed -eq 0) {
    Write-Host "Deploy complete! Visit: https://practicummanagementsystem.xo.je/" -ForegroundColor Green
    Start-Process "https://practicummanagementsystem.xo.je/"
}
