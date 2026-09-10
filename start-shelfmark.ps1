$port = 8000
$php = 'C:\xampp\php\php.exe'
$documentRoot = 'C:\PROJECTS\shelfmark'

$listener = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
if (-not $listener) {
    Start-Process -FilePath $php -ArgumentList @('-S', "localhost:$port", '-t', $documentRoot) -WorkingDirectory $documentRoot -WindowStyle Hidden
}
