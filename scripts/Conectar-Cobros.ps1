param([Parameter(Mandatory=$true)][ValidateSet('Correo', 'WhatsApp')][string]$Canal)

$ErrorActionPreference = 'Stop'
$proyectoCobros = Split-Path -Parent $PSScriptRoot
$archivoEntorno = Join-Path $proyectoCobros '.env'
$textoEntorno = [System.IO.File]::ReadAllText($archivoEntorno)

function Leer-Secreto([string]$Mensaje) {
    $valorSeguro = Read-Host $Mensaje -AsSecureString
    $punteroSeguro = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($valorSeguro)
    try { return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($punteroSeguro) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($punteroSeguro) }
}

function Guardar-Variable([string]$Nombre, [string]$Valor) {
    if ($Valor -match '[\r\n]') { throw 'El valor no debe contener saltos de línea.' }
    $valorEscapado = $Valor.Replace('\', '\\').Replace('"', '\"').Replace('$', '\$')
    $lineaNueva = $Nombre + '="' + $valorEscapado + '"'
    $patron = '(?m)^' + [regex]::Escape($Nombre) + '=.*$'
    if ([regex]::IsMatch($script:textoEntorno, $patron)) {
        $script:textoEntorno = [regex]::Replace($script:textoEntorno, $patron, [System.Text.RegularExpressions.MatchEvaluator]{param($coincidencia) $lineaNueva})
    } else {
        $script:textoEntorno += "`r`n" + $lineaNueva
    }
}

if ($Canal -eq 'Correo') {
    $secretoCobros = Leer-Secreto 'Introduce la contraseña de aplicación de Gmail (no se muestra)'
    if ([string]::IsNullOrWhiteSpace($secretoCobros)) { throw 'No se guardó una contraseña vacía.' }
    Guardar-Variable 'MAIL_MAILER' 'smtp'
    Guardar-Variable 'MAIL_SCHEME' 'smtp'
    Guardar-Variable 'MAIL_HOST' 'smtp.gmail.com'
    Guardar-Variable 'MAIL_PORT' '587'
    Guardar-Variable 'MAIL_USERNAME' 'alfadigital.sas@gmail.com'
    Guardar-Variable 'MAIL_PASSWORD' ($secretoCobros.Replace(' ', ''))
    Guardar-Variable 'MAIL_FROM_ADDRESS' 'alfadigital.sas@gmail.com'
    Guardar-Variable 'MAIL_FROM_NAME' 'Alfa Digital Solutions SAS'
    $variableActiva = 'COBROS_EMAIL_AUTOMATICO'
} else {
    $idNumero = Read-Host 'Identificador del número en Meta (Phone Number ID)'
    $versionApi = Read-Host 'Versión de Graph API de tu aplicación (por ejemplo vXX.0)'
    $plantillaCobros = Read-Host 'Nombre exacto de la plantilla aprobada'
    $idiomaCobros = Read-Host 'Idioma exacto de la plantilla (por ejemplo es_CO)'
    if ($idNumero -notmatch '^\d+$' -or $versionApi -notmatch '^v\d+\.\d+$' -or $plantillaCobros -notmatch '^[a-z0-9_]+$' -or $idiomaCobros -notmatch '^[a-z]{2}(_[A-Z]{2})?$') {
        throw 'Revisa el identificador, versión, nombre e idioma. No se modificó .env.'
    }
    $secretoCobros = Leer-Secreto 'Token de WhatsApp Business Platform (no se muestra)'
    if ([string]::IsNullOrWhiteSpace($secretoCobros)) { throw 'No se guardó un token vacío.' }
    Guardar-Variable 'WHATSAPP_TOKEN' $secretoCobros
    Guardar-Variable 'WHATSAPP_PHONE_ID' $idNumero
    Guardar-Variable 'WHATSAPP_API_VERSION' $versionApi
    Guardar-Variable 'WHATSAPP_TEMPLATE' $plantillaCobros
    Guardar-Variable 'WHATSAPP_TEMPLATE_LANGUAGE' $idiomaCobros
    $variableActiva = 'COBROS_WHATSAPP_AUTOMATICO'
}
$confirmacionCobros = Read-Host 'Activar envíos reales a las cuotas pendientes del sistema: escribe ACTIVAR; cualquier otra respuesta solo guarda la conexión'
Guardar-Variable $variableActiva $(if ($confirmacionCobros -ceq 'ACTIVAR') { 'true' } else { 'false' })
[System.IO.File]::WriteAllText($archivoEntorno, $textoEntorno, (New-Object System.Text.UTF8Encoding($false)))
$secretoCobros = $null
$textoEntorno = $null
& 'C:\xampp\php\php.exe' (Join-Path $proyectoCobros 'artisan') config:clear
Write-Host 'Configuración guardada localmente. Revisa el estado y los resultados en Mensualidades y avisos.'
