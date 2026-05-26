<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

try {
    echo "Iniciando verificacion de CRUDs...\n";
    DB::beginTransaction();

    // 1. Category
    echo "Probando Category...\n";
    $cat = Category::create([
        'name' => 'Categoria Prueba CRUD',
        'is_active' => true
    ]);
    $cat->update(['name' => 'Categoria Prueba Updated']);
    if ($cat->name !== 'Categoria Prueba Updated') throw new Exception("Category update falló");
    $catId = $cat->id;
    $cat->delete();

    // 2. Branch
    echo "Probando Branch...\n";
    $branch = Branch::create([
        'name' => 'Sucursal CRUD',
        'address' => 'Test Address',
        'phone' => '11112222',
        'city' => 'Test City'
    ]);
    $branch->update(['phone' => '33334444']);
    if ($branch->phone !== '33334444') throw new Exception("Branch update falló");
    $branch->delete();

    // 3. Product
    echo "Probando Product...\n";
    $cat = Category::first();
    $branch = Branch::first();
    $prod = Product::create([
        'category_id' => $cat->id,
        'branch_id' => $branch->id,
        'name' => 'Producto CRUD',
        'base_price' => 10.00,
        'time_preparation' => 10
    ]);
    $prod->update(['base_price' => 15.00]);
    if ($prod->base_price != 15.00) throw new Exception("Product update falló");
    $prod->delete();

    // 4. User
    echo "Probando User...\n";
    $user = User::create([
        'name' => 'User CRUD',
        'email' => 'crud@test.com',
        'phone' => '00000000',
        'password' => bcrypt('password')
    ]);
    $user->update(['name' => 'User CRUD Updated']);
    if ($user->name !== 'User CRUD Updated') throw new Exception("User update falló");
    $user->delete();

    echo "CRUDs verificados correctamente. Revirtiendo cambios de prueba...\n";
    DB::rollBack();
    echo "SUCCESS\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
