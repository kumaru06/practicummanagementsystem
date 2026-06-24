$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$config = Get-Content (Join-Path $root 'hostinger.deploy.json') -Raw | ConvertFrom-Json
$failed = @(
    'lib/phpmailer/DSNConfigurator.php',
    'lib/phpmailer/Exception.php',
    'lib/phpmailer/OAuth.php',
    'lib/phpmailer/OAuthTokenProvider.php',
    'lib/phpmailer/PHPMailer.php',
    'lib/phpmailer/POP3.php',
    'lib/phpmailer/SMTP.php',
    'vendor/dompdf/dompdf/src/Renderer/Block.php'
)

$ok = 0
$bad = 0
foreach ($rel in $failed) {
    $local = Join-Path $root ($rel -replace '/', [IO.Path]::DirectorySeparatorChar)
    $encoded = (($rel -split '/') | ForEach-Object { [uri]::EscapeDataString($_) }) -join '/'
    $url = "ftp://$($config.ftpHost)/$encoded"
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'SilentlyContinue'
    $output = & curl.exe --silent --show-error --ftp-pasv --disable-epsv --max-time 300 `
        --user "$($config.ftpUser):$($config.ftpPass)" `
        --ftp-create-dirs `
        --upload-file $local `
        $url 2>&1
    $exitCode = $LASTEXITCODE
    $ErrorActionPreference = $prev
    if ($exitCode -eq 0) {
        $ok++
        Write-Host "[OK] $rel" -ForegroundColor Green
    } else {
        $bad++
        Write-Host "[FAIL] $rel" -ForegroundColor Red
        if ($output) { Write-Host "       $output" -ForegroundColor DarkRed }
    }
}
Write-Host "Retry done: $ok ok, $bad failed"
exit $(if ($bad -eq 0) { 0 } else { 1 })
