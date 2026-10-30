$ErrorActionPreference = 'Stop'
$proyectoCobros = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $proyectoCobros
& 'C:\xampp\php\php.exe' (Join-Path $proyectoCobros 'artisan') cobros:procesar --enviar --no-interaction *>> (Join-Path $proyectoCobros 'storage\logs\tarea-cobros.log')
exit $LASTEXITCODE
