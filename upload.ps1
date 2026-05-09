$ftpUser = "if0_41872185"
$ftpPass = "0twdhr9utirPSQ"
$ftpHost = "ftpupload.net"
$localRoot = "C:\xampp\htdocs\amaccmanagementsystem"
$remoteRoot = "htdocs"
$uploaded = 0
$failed = 0

$excludeDirs  = @('.git')
$excludeFiles = @('amaccmanagementsystem.zip', 'upload.ps1', '.htaccess')

$allFiles = Get-ChildItem -Path $localRoot -Recurse -File

foreach ($file in $allFiles) {
    $relativePath = $file.FullName.Substring($localRoot.Length + 1).Replace('\', '/')
    $parts = $relativePath -split '/'
    $skip = $false

    foreach ($part in $parts[0..($parts.Count - 2)]) {
        if ($excludeDirs -contains $part) { $skip = $true; break }
    }
    if ($excludeFiles -contains $file.Name) { $skip = $true }
    if ($skip) { Write-Host "  [SKIP] $relativePath" -ForegroundColor DarkGray; continue }

    $url = "ftp://$ftpHost/$remoteRoot/$relativePath"

    # Retry up to 3 times
    $success = $false
    for ($i = 1; $i -le 3; $i++) {
        $result = & curl.exe --silent --show-error --ftp-pasv --disable-epsv --user "${ftpUser}:${ftpPass}" --ftp-create-dirs --upload-file $file.FullName $url 2>&1
        if ($LASTEXITCODE -eq 0) { $success = $true; break }
        Start-Sleep -Seconds 1
    }

    if ($success) {
        $uploaded++
        Write-Host "  [OK] $relativePath" -ForegroundColor Green
    } else {
        $failed++
        Write-Host "  [FAIL] $relativePath - $result" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Yellow
Write-Host "Done! Uploaded: $uploaded | Failed: $failed" -ForegroundColor Yellow
Write-Host "========================================"
