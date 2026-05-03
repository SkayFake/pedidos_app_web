<?php

return [
    'title' => 'Restablecer Contraseña',
    'heading' => '¿Olvidaste tu contraseña?',
    'actions' => [
        'login' => [
            'label' => 'Volver al inicio de sesión',
        ],
    ],
    'form' => [
        'email' => [
            'label' => 'Correo Electrónico',
        ],
        'actions' => [
            'request' => [
                'label' => 'Enviar enlace de restablecimiento',
            ],
        ],
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'Demasiados intentos',
            'body' => 'Por favor intenta de nuevo en :seconds segundos.',
        ],
    ],
];
