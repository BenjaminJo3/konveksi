$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$phpPath = (Get-Command php -ErrorAction Stop).Source
$env:PHP_ROOT = Split-Path $phpPath
$env:PHPRC = $projectRoot

$requiredExtensions = @('mbstring', 'openssl', 'pdo_sqlite')
$loadedExtensions = & $phpPath -m
if ($LASTEXITCODE -ne 0) {
    throw 'PHP gagal dimulai. Periksa instalasi PHP dan konfigurasi php.ini.'
}

$missingExtensions = $requiredExtensions | Where-Object { $_ -notin $loadedExtensions }
if ($missingExtensions) {
    throw "Ekstensi PHP berikut belum tersedia: $($missingExtensions -join ', ')."
}

$manifest = Join-Path $projectRoot 'public\build\manifest.json'
if (-not (Test-Path $manifest)) {
    throw 'Aset Vite belum dibangun. Jalankan npm run build terlebih dahulu.'
}

Push-Location (Join-Path $projectRoot 'public')
try {
    & $phpPath -S 127.0.0.1:8000 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php
}
finally {
    Pop-Location
}
