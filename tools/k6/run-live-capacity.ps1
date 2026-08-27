# Phased live capacity: Soft -> Medium -> Stress (stop if Soft or Medium fails).
# Usage:
#   .\tools\k6\run-live-capacity.ps1
#   .\tools\k6\run-live-capacity.ps1 -BaseUrl "https://ama-ojtportal.com"
#   .\tools\k6\run-live-capacity.ps1 -StartFrom soft
#   .\tools\k6\run-live-capacity.ps1 -SkipDashboard
#   .\tools\k6\run-live-capacity.ps1 -CooldownSeconds 90

param(
    [string]$BaseUrl = "https://ama-ojtportal.com",
    [ValidateSet("soft", "medium", "stress")]
    [string]$StartFrom = "soft",
    [switch]$SkipDashboard,
    [int]$DashboardPort = 5665,
    [int]$CooldownSeconds = 90
)

$ErrorActionPreference = "Continue"

$root = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$scriptPath = Join-Path $root "tools\k6\live-capacity.js"
$resultsDir = Join-Path $root "tools\k6\results"
$k6Cmd = (Get-Command k6 -ErrorAction SilentlyContinue)

if (-not $k6Cmd) {
    Write-Host "k6 not found in PATH. Install k6 first." -ForegroundColor Red
    exit 1
}
if (-not (Test-Path $scriptPath)) {
    Write-Host "Missing script: $scriptPath" -ForegroundColor Red
    exit 1
}

New-Item -ItemType Directory -Force -Path $resultsDir | Out-Null
Set-Location $root

$stagesAll = @("soft", "medium", "stress")
$startIdx = [array]::IndexOf($stagesAll, $StartFrom)
if ($startIdx -lt 0) { $startIdx = 0 }
$stages = $stagesAll[$startIdx..($stagesAll.Length - 1)]

$peakVu = @{ soft = 25; medium = 100; stress = 200 }

Write-Host ""
Write-Host "=== AMA OJT Portal live capacity (phased) ===" -ForegroundColor Cyan
Write-Host "Target: $BaseUrl"
Write-Host ("Stages: {0}" -f ($stages -join " -> "))
Write-Host "Pass Soft/Medium: failed <5%, p95 <3s, checks >95%"
Write-Host "Pass Stress:      failed <5%, p95 <5s, checks >95%"
Write-Host "Medium FAIL => stop (no Stress)."
Write-Host ("Cooldown between stages: {0}s" -f $CooldownSeconds)
Write-Host ""

if (-not $SkipDashboard) {
    $env:K6_WEB_DASHBOARD = "true"
    $env:K6_WEB_DASHBOARD_PORT = "$DashboardPort"
    $env:K6_WEB_DASHBOARD_OPEN = "true"
    Write-Host "Web dashboard: http://127.0.0.1:$DashboardPort" -ForegroundColor Gray
} else {
    Remove-Item Env:K6_WEB_DASHBOARD -ErrorAction SilentlyContinue
}

$lastPassedPeak = 0
$failedStage = $null
$reportLines = @()
$reportLines += ("live-capacity run {0}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"))
$reportLines += "base_url=$BaseUrl"

function Test-SiteReady {
    param([string]$Url, [int]$Tries = 8)
    for ($i = 1; $i -le $Tries; $i++) {
        try {
            $tmp = Join-Path $env:TEMP ("k6-probe-{0}.html" -f $PID)
            $code = & curl.exe -sL -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36" `
                -o $tmp -w "%{http_code}" --max-time 25 "$Url/auth.php?portal=student"
            $body = ""
            if (Test-Path $tmp) { $body = Get-Content $tmp -Raw -ErrorAction SilentlyContinue; Remove-Item $tmp -Force -ErrorAction SilentlyContinue }
            if ("$code" -eq "200" -and $body -match 'csrf_token') {
                Write-Host ("Site ready (HTTP {0})" -f $code) -ForegroundColor Green
                return $true
            }
            Write-Host ("Wait site ready: HTTP {0} (try {1}/{2})" -f $code, $i, $Tries) -ForegroundColor Yellow
        } catch {
            Write-Host ("Wait site ready: {0} (try {1}/{2})" -f $_.Exception.Message, $i, $Tries) -ForegroundColor Yellow
        }
        Start-Sleep -Seconds 15
    }
    return $false
}

function Invoke-CapacityStage {
    param([string]$StageName)

    Write-Host ("---------- STAGE: {0} (peak ~{1} VUs) ----------" -f $StageName, $peakVu[$StageName]) -ForegroundColor Yellow
    $summaryJson = Join-Path $resultsDir ("{0}-summary.json" -f $StageName)
    if (Test-Path $summaryJson) { Remove-Item $summaryJson -Force }

    # Reliable exit code (PowerShell LASTEXITCODE is flaky with native stderr)
    $argList = @(
        "run",
        "-e", ("STAGE={0}" -f $StageName),
        "-e", ("BASE_URL={0}" -f $BaseUrl),
        $scriptPath
    )
    $proc = Start-Process -FilePath $k6Cmd.Source -ArgumentList $argList -WorkingDirectory $root -NoNewWindow -Wait -PassThru
    $exitCode = $proc.ExitCode

    $passedFromSummary = $null
    if (Test-Path $summaryJson) {
        try {
            $summary = Get-Content $summaryJson -Raw | ConvertFrom-Json
            $passedFromSummary = [System.Convert]::ToBoolean($summary.passed)
            $reportLinesLocal = ("stage={0} exit={1} summary_passed={2} p95={3} fail_rate={4} checks={5}" -f `
                $StageName, $exitCode, $passedFromSummary, $summary.http_req_duration_p95_ms, $summary.http_req_failed_rate, $summary.checks_rate)
            $script:reportLines += $reportLinesLocal
        } catch {
            $script:reportLines += ("stage={0} exit={1} summary_parse_error" -f $StageName, $exitCode)
        }
    } else {
        $script:reportLines += ("stage={0} exit={1} no_summary_file" -f $StageName, $exitCode)
    }

    # Pass only when process exit is 0 AND summary (if present) agrees
    if ($exitCode -ne 0) {
        return $false
    }
    if ($null -ne $passedFromSummary -and -not $passedFromSummary) {
        return $false
    }
    return $true
}

if (-not (Test-SiteReady -Url $BaseUrl)) {
    Write-Host "Site not ready. Aborting capacity run." -ForegroundColor Red
    exit 1
}

$stageIndex = 0
foreach ($stageName in $stages) {
    if ($stageIndex -gt 0 -and $CooldownSeconds -gt 0) {
        Write-Host ("Cooldown {0}s before next stage..." -f $CooldownSeconds) -ForegroundColor Gray
        Start-Sleep -Seconds $CooldownSeconds
        if (-not (Test-SiteReady -Url $BaseUrl -Tries 6)) {
            Write-Host "Site did not recover after cooldown. Stopping." -ForegroundColor Red
            $failedStage = $stageName
            break
        }
    }

    $ok = Invoke-CapacityStage -StageName $stageName
    if ($ok) {
        $lastPassedPeak = $peakVu[$stageName]
        Write-Host ("STAGE {0}: PASS (peak ~{1} VUs)" -f $stageName, $lastPassedPeak) -ForegroundColor Green
        if ($stageName -eq "medium") {
            Write-Host "Medium passed - proceeding to Stress." -ForegroundColor Cyan
        }
    } else {
        $failedStage = $stageName
        Write-Host ("STAGE {0}: FAIL" -f $stageName) -ForegroundColor Red
        if ($stageName -eq "soft") {
            Write-Host "Stopping. Soft failed - no Medium/Stress." -ForegroundColor Red
            break
        }
        if ($stageName -eq "medium") {
            Write-Host "Stopping. Medium failed - Stress will NOT run." -ForegroundColor Red
            break
        }
        Write-Host "Stress failed - Soft+Medium still count as last good capacity." -ForegroundColor Yellow
        break
    }
    $stageIndex++
}

Write-Host ""
Write-Host "=== CAPACITY RESULT ===" -ForegroundColor Cyan
if ($lastPassedPeak -gt 0) {
    Write-Host ("Approx concurrent public-browse capacity: ~{0} VUs (last passed peak)." -f $lastPassedPeak) -ForegroundColor Green
    $reportLines += "approx_capacity_vus=$lastPassedPeak"
} else {
    Write-Host "No stage passed. Site struggled under Soft load (~25 VUs)." -ForegroundColor Red
    $reportLines += "approx_capacity_vus=0"
}

if ($failedStage) {
    Write-Host ("First failed stage: {0}" -f $failedStage) -ForegroundColor Yellow
    $reportLines += "first_failed_stage=$failedStage"
} else {
    Write-Host "All requested stages passed." -ForegroundColor Green
    $reportLines += "first_failed_stage="
}

$reportPath = Join-Path $resultsDir "run-report.txt"
$reportLines | Set-Content -Path $reportPath -Encoding UTF8
Write-Host "Report: $reportPath"
Write-Host ""

if ($failedStage -and $failedStage -ne "stress") {
    exit 1
}
exit 0
