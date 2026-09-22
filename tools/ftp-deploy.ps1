# FTP Deploy Script - uploads modified/untracked files to Hostinger
param(
    [string]$FtpHost   = "153.92.10.160",
    [string]$FtpUser   = "u859158056.u859158056",
    [string]$FtpPass   = "M@rkperez201",
    [string]$RemoteBase = ""
)

$LocalBase = "c:\laragon\www\amaccmanagementsystem"

$Files = @(
    ".htaccess",
    "helpers.php",
    "controllers/StudentController.php",
    "models/FileAccess.php",
    "models/Student.php",
    "views/admin/coordinators.php"
)

$cred = [System.Net.NetworkCredential]::new($FtpUser, $FtpPass)
$ok = 0
$fail = 0

foreach ($rel in $Files) {
    $localPath = Join-Path $LocalBase ($rel -replace '/', '\')
    if (-not (Test-Path $localPath)) {
        Write-Host "SKIP (not found): $rel" -ForegroundColor Yellow
        continue
    }

    # Ensure remote directory exists by uploading via WebClient
    $remotePath = "$RemoteBase/$rel"
    $uri = "ftp://$FtpHost$remotePath"

    # Create parent dirs via separate MKD if needed (best-effort via WC)
    try {
        $wc = [System.Net.WebClient]::new()
        $wc.Credentials = $cred
        $wc.UploadFile($uri, $localPath)
        $wc.Dispose()
        Write-Host "OK  $rel" -ForegroundColor Green
        $ok++
    } catch {
        Write-Host "ERR $rel  =>  $($_.Exception.Message)" -ForegroundColor Red
        $fail++
    }
}

Write-Host ""
Write-Host "Done. Uploaded: $ok   Failed: $fail" -ForegroundColor Cyan
