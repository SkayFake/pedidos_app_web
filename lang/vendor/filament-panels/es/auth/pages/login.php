<?php

return [
    'title' => 'Iniciar Sesión',
    'heading' => 'Iniciar Sesión',
    'actions' => [
        'register' => [
            'before' => '¿No tienes una cuenta?',
            'label' => 'Regístrate',
        ],
        'request_password_reset' => [
            'label' => '¿Olvidaste tu contraseña?',
        ],
    ],
    'form' => [
        'email' => [
            'label' => 'Correo Electrónico',
        ],
        'password' => [
            'label' => 'Contraseña',
        ],
        'remember' => [
            'label' => 'Recuérdame',
        ],
        'actions' => [
            'authenticate' => [
                'label' => 'Iniciar Sesión',
            ],
        ],
    ],
    'messages' => [
        'failed' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'Demasiados intentos',
            'body' => 'Por favor intenta de nuevo en :seconds segundos.',
        ],
    ],
    'multi_factor' => [
        'heading' => 'Verificación de dos factores',
        'subheading' => 'Por favor ingresa tu código de verificación.',
        'form' => [
            'provider' => [
                'label' => 'Proveedor de autenticación',
            ],
            'actions' => [
                'authenticate' => [
                    'label' => 'Verificar',
                ],
            ],
        ],
    ],
];
