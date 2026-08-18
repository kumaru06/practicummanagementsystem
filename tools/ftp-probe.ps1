$cred = [System.Net.NetworkCredential]::new('u859158056.u859158056', 'M@rkperez201')
$req = [System.Net.FtpWebRequest]::Create('ftp://153.92.10.160/')
$req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
$req.Credentials = $cred
try {
    $resp = $req.GetResponse()
    $reader = [System.IO.StreamReader]::new($resp.GetResponseStream())
    Write-Host $reader.ReadToEnd()
    $reader.Close()
    $resp.Close()
} catch {
    Write-Host "ERR: $($_.Exception.Message)"
}
