# FTP Deploy Script - uploads modified/untracked files to Hostinger
param(
    [string]$FtpHost   = "153.92.10.160",
    [string]$FtpUser   = "u859158056.u859158056",
    [string]$FtpPass   = "M@rkperez201",
    [string]$RemoteBase = ""
)

$LocalBase = "c:\laragon\www\amaccmanagementsystem"

$Files = @(
    "assets/css/style.css",
    "assets/js/main.js",
    "controllers/ChatController.php",
    "controllers/PartnerController.php",
    "controllers/StudentController.php",
    "helpers.php",
    "index.php",
    "models/Company.php",
    "models/Evaluation.php",
    "models/Report.php",
    "models/StudentEvaluation.php",
    "views/partner/_submissions_helpers.php",
    "views/partner/change_password.php",
    "views/partner/dashboard.php",
    "views/partner/evaluate.php",
    "views/partner/no_company.php",
    "views/partner/portal.php",
    "views/partner/profile.php",
    "views/partner/settings.php",
    "views/partner/submissions.php",
    "views/partner/submissions_detail.php",
    "views/shared/header.php",
    "views/partner/evaluations.php",
    "views/partner/reports.php",
    "views/partner/student_evaluation.php",
    "views/partner/timeline.php",
    "tools/k6/partner-smoke.js"
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
