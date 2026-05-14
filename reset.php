<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AdminUser;

// Si ya existe lo borramos por si acaso
AdminUser::where('email', 'admin@gmail.com')->delete();

AdminUser::create([
    'name' => 'Administrador',
    'email' => 'admin@gmail.com',
    'password' => bcrypt('admin'),
    'role' => 'super_admin',
    'is_active' => true,
]);

echo "Usuario admin@gmail.com creado exitosamente.\n";
