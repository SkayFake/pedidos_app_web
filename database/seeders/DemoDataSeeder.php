<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductExtra;
use App\Models\User;
use App\Models\CustomerAddress;
use App\Models\Deliveryman;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Branches
        $branch1 = Branch::firstOrCreate(
            ['name' => 'Sucursal Centro'],
            [
                'address' => 'Av. Principal #123, Centro',
                'phone' => '555-0100',
                'city' => 'San Salvador',
                'is_active' => true,
                'latitude' => 13.6929,
                'longitude' => -89.2182
            ]
        );

        $branch2 = Branch::firstOrCreate(
            ['name' => 'Sucursal Norte'],
            [
                'address' => 'Plaza Norte, Local 5',
                'phone' => '555-0101',
                'city' => 'San Salvador',
                'is_active' => true,
                'latitude' => 13.7129,
                'longitude' => -89.2082
            ]
        );

        // 2. Create Categories
        $categories = [
            'Pizzas' => 'products/pizza_suprema.jpg',
            'Hamburguesas' => 'products/hamburguesa.jpg',
            'Alitas' => 'products/alitas_con_papas.jpg',
            'Pastas' => 'products/pasta.jpg',
            'Bebidas' => 'products/limonada.jpg',
            'Ensaladas' => 'products/ensalada.jpg',
            'Tacos' => 'products/tacos.jpg',
        ];

        $catModels = [];
        foreach ($categories as $name => $image) {
            $catModels[$name] = Category::firstOrCreate(
                ['name' => $name],
                [
                    'image' => $image,
                    'is_active' => true,
                ]
            );
        }

        // 3. Create Products
        $productsData = [
            [
                'name' => 'Pizza Suprema',
                'category' => 'Pizzas',
                'description' => 'Pizza con pepperoni, champiñones, pimientos y cebolla.',
                'base_price' => 12.99,
                'image' => 'products/pizza_suprema.jpg',
            ],
            [
                'name' => 'Pizza 4 Quesos',
                'category' => 'Pizzas',
                'description' => 'Deliciosa combinación de mozzarella, provolone, parmesano y azul.',
                'base_price' => 14.50,
                'image' => 'products/pizza4.jpg',
            ],
            [
                'name' => 'Hamburguesa Clásica',
                'category' => 'Hamburguesas',
                'description' => 'Carne 100% de res con lechuga, tomate, queso y salsa especial.',
                'base_price' => 6.99,
                'image' => 'products/hamburguesa.jpg',
            ],
            [
                'name' => 'Combo Hamburguesa',
                'category' => 'Hamburguesas',
                'description' => 'Hamburguesa clásica con papas fritas y bebida.',
                'base_price' => 9.99,
                'image' => 'products/combo.jpg',
            ],
            [
                'name' => 'Alitas BBQ',
                'category' => 'Alitas',
                'description' => 'Alitas bañadas en salsa BBQ, perfectas para compartir.',
                'base_price' => 8.50,
                'image' => 'products/alistas.jpg',
            ],
            [
                'name' => 'Alitas con Papas',
                'category' => 'Alitas',
                'description' => 'Porción de alitas acompañada de papas fritas crujientes.',
                'base_price' => 10.50,
                'image' => 'products/alitas_con_papas.jpg',
            ],
            [
                'name' => 'Pasta Tradicional',
                'category' => 'Pastas',
                'description' => 'Pasta al dente con salsa pomodoro y queso parmesano.',
                'base_price' => 7.50,
                'image' => 'products/pasta.jpg',
            ],
            [
                'name' => 'Pasta con Vegetales',
                'category' => 'Pastas',
                'description' => 'Pasta fresca salteada con vegetales de temporada.',
                'base_price' => 8.00,
                'image' => 'products/pasta_con_vegetales.jpg',
            ],
            [
                'name' => 'Pan con Ajo',
                'category' => 'Pastas', // Acompañante
                'description' => 'Pan tostado con mantequilla, ajo y finas hierbas.',
                'base_price' => 3.50,
                'image' => 'products/pan_con_ajo.jpg',
            ],
            [
                'name' => 'Nuditos de Ajo',
                'category' => 'Pizzas', // Acompañante
                'description' => 'Nuditos de masa de pizza horneados con aceite de oliva y ajo.',
                'base_price' => 4.00,
                'image' => 'products/nuditos.jpg',
            ],
            [
                'name' => 'Ensalada César',
                'category' => 'Ensaladas',
                'description' => 'Lechuga romana, crutones, queso parmesano y aderezo César.',
                'base_price' => 6.00,
                'image' => 'products/ensalada.jpg',
            ],
            [
                'name' => 'Tacos al Pastor',
                'category' => 'Tacos',
                'description' => '3 tacos tradicionales al pastor con piña, cilantro y cebolla.',
                'base_price' => 5.50,
                'image' => 'products/tacos.jpg',
            ],
            [
                'name' => 'Limonada Fresca',
                'category' => 'Bebidas',
                'description' => 'Refrescante limonada natural.',
                'base_price' => 2.50,
                'image' => 'products/limonada.jpg',
            ],
            [
                'name' => 'Jugo de Fresa',
                'category' => 'Bebidas',
                'description' => 'Jugo natural de fresa.',
                'base_price' => 3.00,
                'image' => 'products/jugo_fresa.jpg',
            ],
        ];

        foreach ($productsData as $pd) {
            $product = Product::firstOrCreate(
                ['name' => $pd['name']],
                [
                    'category_id' => $catModels[$pd['category']]->id,
                    'branch_id' => $branch1->id, // Default to branch 1
                    'description' => $pd['description'],
                    'base_price' => $pd['base_price'],
                    'image' => $pd['image'],
                    'is_available' => true,
                    'time_preparation' => 15,
                ]
            );

            // Add some variants for Pizzas
            if ($pd['category'] === 'Pizzas') {
                ProductVariant::firstOrCreate(['product_id' => $product->id, 'name' => 'Pequeña'], ['price_modifier' => -($product->base_price * 0.3)]);
                ProductVariant::firstOrCreate(['product_id' => $product->id, 'name' => 'Mediana'], ['price_modifier' => 0]);
                ProductVariant::firstOrCreate(['product_id' => $product->id, 'name' => 'Grande'], ['price_modifier' => ($product->base_price * 0.3)]);
                
                ProductExtra::firstOrCreate(['product_id' => $product->id, 'name' => 'Extra Queso'], ['price' => 1.50]);
                ProductExtra::firstOrCreate(['product_id' => $product->id, 'name' => 'Borde de Queso'], ['price' => 2.00]);
            }
        }

        // 4. Create User
        $user = User::firstOrCreate(
            ['email' => 'cliente@pedidos.com'],
            [
                'name' => 'Cliente de Prueba',
                'password' => Hash::make('password123'),
                'phone' => '12345678',
            ]
        );

        $zone = \App\Models\Zone::firstOrCreate(
            ['name' => 'Zona 1'],
            ['city' => 'San Salvador', 'delivery_fee' => 2.00, 'is_active' => true]
        );

        $address = CustomerAddress::firstOrCreate(
            ['user_id' => $user->id, 'street' => 'Calle Principal #456'],
            [
                'zone_id' => $zone->id,
                'label' => 'Casa',
                'is_default' => true,
                'latitude' => 13.7000,
                'longitude' => -89.2000,
            ]
        );

        // 5. Create Deliveryman
        $deliveryman = Deliveryman::firstOrCreate(
            ['email' => 'repartidor@pedidos.com'],
            [
                'name' => 'Juan Repartidor',
                'password' => Hash::make('password123'),
                'phone' => '87654321',
                'vehicle_type' => 'motorcycle',
                'license_plate' => 'M123456',
                'is_active' => true,
                'is_available' => true,
            ]
        );

        // 6. Create Orders to populate the dashboard
        $statuses = ['pending', 'confirmed', 'preparing', 'assigned', 'on_way', 'delivered'];
        
        $pizza = Product::where('name', 'Pizza Suprema')->first();
        
        // Let's create a few historical orders
        for ($i = 0; $i < 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 24));
            
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'branch_id' => $branch1->id,
                'status' => $status,
                'subtotal' => 12.99,
                'delivery_fee' => 2.00,
                'total' => 14.99,
                'notes' => 'Prueba de pedido',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if (in_array($status, ['assigned', 'on_way', 'delivered'])) {
                $order->deliveryman_id = $deliveryman->id;
                $order->assigned_at = $createdAt->copy()->addMinutes(15);
                if ($status == 'delivered') {
                    $order->delivered_at = $createdAt->copy()->addMinutes(45);
                }
                $order->save();
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $pizza->id,
                'quantity' => 1,
                'unit_price' => 12.99,
                'subtotal' => 12.99,
            ]);
        }
    }
}
