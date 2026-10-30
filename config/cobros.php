<?php

return [
    'whatsapp' => '573132560420',
    'nequi' => '3238080851',
    'correo' => 'alfadigital.sas@gmail.com',
    'dias_aviso' => 5,
    'email_automatico' => env('COBROS_EMAIL_AUTOMATICO', false),
    'whatsapp_api' => [
        'activo' => env('COBROS_WHATSAPP_AUTOMATICO', false),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_id' => env('WHATSAPP_PHONE_ID'),
        'version' => env('WHATSAPP_API_VERSION'),
        'plantilla' => env('WHATSAPP_TEMPLATE', 'recordatorio_mensualidad'),
        'idioma' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_CO'),
    ],
];
