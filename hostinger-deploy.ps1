param(
    [switch]$ZipOnly,
    [switch]$SkipUpload,
    [switch]$IncludeProductionConfig,
    [switch]$Setup,
    [string]$ConfigPath = ""
)

$ErrorActionPreference = "Stop"
$localRoot = $PSScriptRoot
$configFile = if ($ConfigPath -ne "") { $ConfigPath } else { Join-Path $localRoot "hostinger.deploy.json" }
$exampleFile = Join-Path $localRoot "hostinger.deploy.json.example"
$zipPath = Join-Path (Split-Path $localRoot -Parent) "amaccmanagementsystem_deploy.zip"

$excludeDirNames = @('.git', '.idea', '.vscode')
$excludeFileNames = @(
    'hostinger.deploy.json',
    'hostinger.deploy.json.example',
    'hostinger-deploy.ps1',
    'deploy.ps1',
    'deploy.bat',
    'deploy-zip.bat',
    'setup-hostinger.ps1',
    'upload.ps1',
    'HOSTINGER_DEPLOY.md'
)
$excludeFilePatterns = @('*.zip', 'debug-*.log', 'hostinger_export.sql')

function Write-Step([string]$Message, [string]$Color = 'White') {
    Write-Host $Message -ForegroundColor $Color
}

function Test-DeployExcluded {
    param([string]$RelativePath, [string]$FileName)

    $RelativePath = $RelativePath -replace '\\', '/'

    foreach ($dir in $excludeDirNames) {
        if ($RelativePath -match "(^|[\\/])$([regex]::Escape($dir))([\\/]|$)") {
            return $true
        }
    }

    if ($excludeFileNames -contains $FileName) {
        return $true
    }

    foreach ($pattern in $excludeFilePatterns) {
        if ($FileName -like $pattern) {
            return $true
        }
    }

    if (-not $IncludeProductionConfig -and $RelativePath -eq 'config/database.production.php') {
        return $true
    }

    if ($RelativePath -match '^uploads/' -and $FileName -ne '.htaccess' -and $FileName -ne '.gitkeep') {
        return $true
    }

    return $false
}

function Get-DeployFiles {
    Get-ChildItem -Path $localRoot -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($localRoot.Length + 1)
        if (Test-DeployExcluded -RelativePath $relative -FileName $_.Name) {
            return
        }

        [PSCustomObject]@{
            FullName = $_.FullName
            Relative = ($relative -replace '\\', '/')
        }
    }
}

function Read-DeployConfig {
    if (-not (Test-Path $configFile)) {
        Write-Host ""
        Write-Step "ERROR: Missing hostinger.deploy.json" Red
        Write-Step "Run setup first:" Yellow
        Write-Step "  .\setup-hostinger.ps1" Cyan
        Write-Step "Or:" Yellow
        Write-Step "  copy hostinger.deploy.json.example hostinger.deploy.json" Cyan
        Write-Step "Then edit hostinger.deploy.json with FTP details from hPanel." Yellow
        Write-Host ""
        throw "hostinger.deploy.json not found"
    }

    $json = Get-Content $configFile -Raw | ConvertFrom-Json
    foreach ($key in @('ftpHost', 'ftpUser', 'ftpPass', 'remotePath')) {
        if (-not $json.$key -or $json.$key -match 'YOUR_FTP') {
            throw "hostinger.deploy.json: set a real value for '$key'."
        }
    }
    return $json
}

function Invoke-FtpUpload {
    param(
        [string]$LocalFile,
        [string]$RemoteUrl,
        [string]$User,
        [string]$Pass
    )

    $output = & curl.exe --show-error --max-time 120 --ftp-pasv --disable-epsv `
        --user "${User}:${Pass}" `
        --ftp-create-dirs `
        --upload-file $LocalFile `
        $RemoteUrl 2>&1

    return @{
        Ok = ($LASTEXITCODE -eq 0)
        Output = ($output | Out-String).Trim()
    }
}

if ($Setup) {
    & (Join-Path $localRoot "setup-hostinger.ps1")
    exit $LASTEXITCODE
}

Write-Host ""
Write-Step "==========================================" Cyan
Write-Step "  AMA OJT Portal - Hostinger Deploy" Cyan
Write-Step "==========================================" Cyan
Write-Host ""

if (-not $ZipOnly -and -not $SkipUpload) {
    Write-Step "[0/2] Checking FTP config..." Yellow
    $null = Read-DeployConfig
    Write-Step "  Config OK" Green
    Write-Host ""
}

$files = @(Get-DeployFiles)
Write-Step "Files to deploy: $($files.Count)" Gray
Write-Host ""

Write-Step "[1/2] Building zip (forward-slash paths)..." Yellow
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
foreach ($file in $files) {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $file.Relative) | Out-Null
}
$zip.Dispose()

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
Write-Step "  Created: $zipPath ($sizeMb MB)" Green

if ($ZipOnly -or $SkipUpload) {
    Write-Host ""
    Write-Step "Zip ready. Upload and extract in Hostinger File Manager -> public_html" Yellow
    exit 0
}

Write-Host ""
Write-Step "[2/2] Uploading via FTP..." Yellow
$config = Read-DeployConfig
$remoteRoot = ($config.remotePath -replace '^/|/$', '')
$uploaded = 0
$failed = 0
$lastError = ""

Write-Step "  Testing FTP connection..." Gray
$test = Invoke-FtpUpload -LocalFile $files[0].FullName -RemoteUrl "ftp://$($config.ftpHost)/$remoteRoot/$($files[0].Relative)" -User $config.ftpUser -Pass $config.ftpPass
if (-not $test.Ok) {
    Write-Step "  FTP connection failed on first file." Red
    Write-Step "  $($test.Output)" Red
    Write-Host ""
    Write-Step "Check hostinger.deploy.json:" Yellow
    Write-Step "  ftpHost  = from hPanel -> FTP Accounts (e.g. ftp.ama-ojtportal.com)" White
    Write-Step "  ftpUser  = your FTP username (e.g. u859158056)" White
    Write-Step "  ftpPass  = FTP account password (not always same as hPanel login)" White
    Write-Step "  remotePath = public_html" White
    exit 1
}
$uploaded++
Write-Step "  [OK] $($files[0].Relative)" Green

for ($i = 1; $i -lt $files.Count; $i++) {
    $file = $files[$i]
    $remotePath = "$remoteRoot/$($file.Relative)"
    $url = "ftp://$($config.ftpHost)/$remotePath"

    $result = Invoke-FtpUpload -LocalFile $file.FullName -RemoteUrl $url -User $config.ftpUser -Pass $config.ftpPass
    if ($result.Ok) {
        $uploaded++
        if ($uploaded % 50 -eq 0) {
            Write-Step "  ... $uploaded / $($files.Count)" Gray
        }
    } else {
        $failed++
        $lastError = $result.Output
        Write-Step "  [FAIL] $($file.Relative)" Red
        if ($result.Output) {
            Write-Step "         $($result.Output)" DarkRed
        }
    }
}

Write-Host ""
Write-Step "Uploaded: $uploaded | Failed: $failed" $(if ($failed -eq 0) { 'Green' } else { 'Yellow' })

if ($failed -eq 0 -and $config.websiteUrl) {
    Write-Step "Live site: $($config.websiteUrl)" Green
}

if ($failed -gt 0) {
    if ($lastError) {
        Write-Step "Last error: $lastError" Red
    }
    exit 1
}
