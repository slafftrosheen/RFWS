param(
    [string]$Php = 'php',
    [ValidateRange(1, 65535)] [int]$Port = 8080,
    [ValidateRange(1, 65535)] [int]$DatabasePort = 54322,
    [ValidateScript({ $_.AddressFamily -eq 'InterNetwork' -and -not $_.Equals([ipaddress]::Any) })]
    [ipaddress]$ListenAddress = '127.0.0.1'
)

$ErrorActionPreference = 'Stop'
$site = Join-Path $PSScriptRoot '../site'
$env:DATABASE_URL = "postgresql://postgres:postgres@127.0.0.1:${DatabasePort}/postgres"
$endpoint = "${ListenAddress}:${Port}"
$env:RF_SITE_URL = "http://${endpoint}"
$env:RF_NO_MAIL = '1'

& $Php -d upload_max_filesize=25M -d post_max_size=100M -d max_input_vars=10000 `
    -S $endpoint -t $site (Join-Path $site 'index.php')
exit $LASTEXITCODE
