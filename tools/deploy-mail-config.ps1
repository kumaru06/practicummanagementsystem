$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$config = Get-Content (Join-Path $root 'hostinger.deploy.json') -Raw | ConvertFrom-Json
$files = @('config/mail.php', 'config/mail.local.php', 'models/Email.php', 'helpers.php')

foreach ($f in $files) {
    $local = Join-Path $root ($f -replace '/', '\')
    $url = "ftp://$($config.ftpHost)/$f"
    Write-Host "Uploading $f..."
    curl.exe --silent --show-error --ftp-pasv --disable-epsv --user "$($config.ftpUser):$($config.ftpPass)" --ftp-create-dirs --upload-file $local $url
    if ($LASTEXITCODE -ne 0) { throw "Upload failed: $f" }
}
Write-Host "Mail files deployed to Hostinger."
