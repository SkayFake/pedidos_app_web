<?php

return [
    'title' => 'Restablecer Contraseña',
    'heading' => 'Restablecer tu contraseña',
    'form' => [
        'email' => [
            'label' => 'Correo Electrónico',
        ],
        'password' => [
            'label' => 'Contraseña',
        ],
        'password_confirmation' => [
            'label' => 'Confirmar Contraseña',
        ],
        'actions' => [
            'reset' => [
                'label' => 'Restablecer Contraseña',
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
