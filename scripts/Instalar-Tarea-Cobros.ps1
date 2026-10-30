$ErrorActionPreference = 'Stop'
$proyectoCobros = Split-Path -Parent $PSScriptRoot
$nombreTarea = 'AlfaDigital-CobrosMensuales'
$scriptCobros = Join-Path $PSScriptRoot 'Ejecutar-Cobros.ps1'
$accionCobros = New-ScheduledTaskAction -Execute "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe" -Argument ('-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + $scriptCobros + '"') -WorkingDirectory $proyectoCobros
$zonaCobros = [TimeZoneInfo]::FindSystemTimeZoneById('SA Pacific Standard Time')
$fechaColombia = [TimeZoneInfo]::ConvertTimeFromUtc([DateTime]::UtcNow, $zonaCobros).Date.AddHours(9)
$horaLocalCobros = [TimeZoneInfo]::ConvertTime($fechaColombia, $zonaCobros, [TimeZoneInfo]::Local)
$disparadorCobros = New-ScheduledTaskTrigger -Daily -At $horaLocalCobros
$usuarioCobros = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$principalCobros = New-ScheduledTaskPrincipal -UserId $usuarioCobros -LogonType Interactive -RunLevel Limited
$ajustesCobros = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 10) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$existenteCobros = Get-ScheduledTask -TaskName $nombreTarea -ErrorAction SilentlyContinue
if ($existenteCobros -and $existenteCobros.Actions.Arguments -notlike ('*' + $scriptCobros + '*')) {
    throw 'Existe una tarea con ese nombre para otro proyecto; no se modificó.'
}
Register-ScheduledTask -TaskName $nombreTarea -Action $accionCobros -Trigger $disparadorCobros -Principal $principalCobros -Settings $ajustesCobros -Description 'Revisa las mensualidades diariamente a las 09:00 hora de Colombia mientras el usuario tiene sesión iniciada.' -Force | Out-Null
Get-ScheduledTask -TaskName $nombreTarea | Select-Object TaskName,State
