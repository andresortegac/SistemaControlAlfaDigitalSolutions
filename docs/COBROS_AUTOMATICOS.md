# Recordatorios de mensualidades

El sistema revisa las cuotas todos los días a las 09:00 (America/Bogota) con `php artisan cobros:procesar --enviar`.
Genera un registro por cuota y canal desde cinco días antes, incluyendo cuotas atrasadas
si el equipo estuvo apagado. No envía a cuotas pagadas ni a licencias suspendidas.
No hay envíos repetidos diarios: un recordatorio aceptado por canal y cuota.

## Conectar los canales

Los números de contacto no son credenciales de envío. Las variables de ejemplo están
en `.env.cobros.example`; los valores privados se guardan únicamente en `.env`.
No enviar claves por chat, no subir `.env` al repositorio.

### Gmail

Configurar SMTP del negocio con `MAIL_HOST=smtp.gmail.com`, puerto `587`,
`MAIL_SCHEME=smtp` y usuario/remitente `alfadigital.sas@gmail.com`.
La contraseña debe autorizar al servidor de correo; si se usa una contraseña de
aplicación de Google, requiere verificación en dos pasos y disponibilidad para la cuenta.
Usar `scripts/Conectar-Cobros.ps1 -Canal Correo` para introducirla sin mostrarla.
El script pide confirmación de activación y no envía pruebas a clientes.

### WhatsApp

Se necesita un número incorporado a WhatsApp Business Platform, un token con permiso
de mensajería, el identificador del número (no el teléfono) y una plantilla aprobada.
El alta de la cuenta y la aprobación de Meta se realizan fuera del sistema.
Si se quiere mantener el número en el celular, el alta debe admitir coexistencia.

Crear la plantilla `recordatorio_mensualidad`, idioma `es_CO`, con seis variables
posicionales en el cuerpo y sin encabezados ni botones con variables:

> Hola, {{1}}. Tu mensualidad de {{2}} por {{3}} tiene fecha de pago {{4}}.
> Puedes pagar por Nequi al {{5}}. Envía la captura del comprobante al WhatsApp {{6}}.
> Si ya pagaste, envíanos el comprobante para verificarlo. Alfa Digital Solutions SAS.

Orden: cliente, producto, valor, vencimiento, Nequi y WhatsApp de comprobantes.
Meta debe aprobar una plantilla con exactamente esta estructura antes de activar.
Ejecutar `scripts/Conectar-Cobros.ps1 -Canal WhatsApp` e introducir los datos localmente.
La versión de Graph API se toma de la configuración de la aplicación de Meta.
Activar solo con clientes reales que hayan aceptado recibir los recordatorios;
los datos demostrativos no deben usarse para probar envíos.

Después de cambiar configuración, ejecutar `php artisan config:clear`.
La pantalla Mensualidades y avisos muestra la última revisión, los canales y el historial.
Configurar no significa verificar la conexión: el primer resultado real aparece en el historial.

## Programación Windows

`scripts/Instalar-Tarea-Cobros.ps1` registra una tarea de Windows que ejecuta el comando de cobros
diariamente a las 09:00 de Colombia. Tiene nombre `AlfaDigital-CobrosMensuales`, corre oculta con el usuario actual
y exige que ese usuario tenga sesión iniciada y el equipo encendido, con internet.
No depende de tener abierta la página ni del servidor HTTP de desarrollo.
Si se pierde una ejecución por estar apagado, Windows la recupera cuando el equipo y la sesión estén disponibles.
Para operar con el computador apagado, instalar el proyecto en un servidor permanente
y programar `php artisan schedule:run` cada minuto allí. No ejecutar dos instalaciones
con bases de datos independientes para los mismos clientes.

## Errores y pagos

SMTP y WhatsApp registran aceptación del proveedor, no entrega ni lectura.
Los rechazos HTTP explícitos se reintentan cada hora, hasta tres intentos por canal.
Un timeout, una excepción SMTP o una ejecución interrumpida se marca para revisión;
no se reenvía automáticamente porque el proveedor pudo haber aceptado el mensaje.
Los registros aceptados no se reenvían. Los bloqueados por falta de configuración o
contacto se vuelven a comprobar sin consumir intentos.
Para permitir otro intento, comprobar primero el resultado con el proveedor y ejecutar
`php artisan cobros:reintentar ID --confirmar`. El siguiente ciclo lo procesará.

`php artisan cobros:procesar` sin `--enviar` prepara registros y no contacta proveedores.
Los logs técnicos están en `storage/logs/cobros-scheduler.log` y `tarea-cobros.log`.
Las credenciales y respuestas privadas del proveedor no se guardan en el historial.

La captura del comprobante no verifica un movimiento bancario: los pagos Nequi siguen
requiriendo confirmación del administrador. La verificación automática del dinero exigiría
una integración de pagos con confirmaciones verificables; no se simula con capturas.

Referencias: [Programación Laravel](https://laravel.com/docs/12.x/scheduling),
[SMTP Gmail](https://developers.google.com/workspace/gmail/imap/imap-smtp),
[Contraseñas de aplicación Google](https://support.google.com/accounts/answer/185833?hl=es),
[Plantillas WhatsApp](https://whatsappbusiness.com/blog/manage-message-templates-whatsapp-business-api/).
