<?php

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Enviando correo de prueba vía Laravel Mail...\n";

try {
    Illuminate\Support\Facades\Mail::raw('Este es un correo de prueba de configuración SMTP.', function ($message) {
        $message->to('steven19denoviembre@gmail.com')
                ->subject('Prueba SMTP Laravel');
    });

    echo "✅ Correo enviado correctamente!\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
