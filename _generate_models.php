<?php

$modelsPath = __DIR__ . '/../app/Models';
if (!is_dir($modelsPath)) {
    mkdir($modelsPath, 0755, true);
}

$models = [
    'Branch' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected \$guarded = ['id'];

    public function adminUsers()
    {
        return \$this->hasMany(AdminUser::class);
    }

    public function orders()
    {
        return \$this->hasMany(Order::class);
    }
}
EOT,

    'AdminUser' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class AdminUser extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected \$guarded = ['id'];
    protected \$hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return \$this->belongsTo(Branch::class);
    }

    public function canAccessPanel(Panel \$panel): bool
    {
        return \$this->is_active;
    }
}
EOT,

    'User' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected \$guarded = ['id'];
    protected \$hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function addresses()
    {
        return \$this->hasMany(CustomerAddress::class);
    }

    public function orders()
    {
        return \$this->hasMany(Order::class);
    }

    public function foodReviews()
    {
        return \$this->hasMany(FoodReview::class);
    }

    public function deliverymanReviews()
    {
        return \$this->hasMany(DeliverymanReview::class);
    }

    public function loyaltyTransactions()
    {
        return \$this->hasMany(LoyaltyTransaction::class);
    }

    public function couponUses()
    {
        return \$this->hasMany(CouponUse::class);
    }
}
EOT,

    'Deliveryman' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Deliveryman extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected \$table = 'deliverymen';
    protected \$guarded = ['id'];
    protected \$hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function orders()
    {
        return \$this->hasMany(Order::class);
    }

    public function reviews()
    {
        return \$this->hasMany(DeliverymanReview::class);
    }
}
EOT,

    'Zone' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_deliverable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function addresses()
    {
        return \$this->hasMany(CustomerAddress::class);
    }
}
EOT,

    'CustomerAddress' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user()
    {
        return \$this->belongsTo(User::class);
    }

    public function zone()
    {
        return \$this->belongsTo(Zone::class);
    }

    public function orders()
    {
        return \$this->hasMany(Order::class);
    }
}
EOT,

    'Category' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products()
    {
        return \$this->hasMany(Product::class);
    }
}
EOT,

    'Product' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_recommended' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }

    public function category()
    {
        return \$this->belongsTo(Category::class);
    }

    public function variants()
    {
        return \$this->hasMany(ProductVariant::class);
    }

    public function extras()
    {
        return \$this->hasMany(ProductExtra::class);
    }

    public function orderItems()
    {
        return \$this->hasMany(OrderItem::class);
    }
}
EOT,

    'ProductVariant' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function product()
    {
        return \$this->belongsTo(Product::class);
    }

    public function orderItems()
    {
        return \$this->hasMany(OrderItem::class);
    }
}
EOT,

    'ProductExtra' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductExtra extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function product()
    {
        return \$this->belongsTo(Product::class);
    }

    public function orderItemExtras()
    {
        return \$this->hasMany(OrderItemExtra::class, 'extra_id');
    }
}
EOT,

    'Coupon' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders()
    {
        return \$this->hasMany(Order::class);
    }

    public function uses()
    {
        return \$this->hasMany(CouponUse::class);
    }
}
EOT,

    'Order' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected \$guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_first_order_promo' => 'boolean',
            'is_free_delivery_promo' => 'boolean',
            'is_loyalty_discount' => 'boolean',
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function user()
    {
        return \$this->belongsTo(User::class);
    }

    public function branch()
    {
        return \$this->belongsTo(Branch::class);
    }

    public function deliveryman()
    {
        return \$this->belongsTo(Deliveryman::class);
    }

    public function address()
    {
        return \$this->belongsTo(CustomerAddress::class, 'address_id');
    }

    public function coupon()
    {
        return \$this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return \$this->hasMany(OrderItem::class);
    }

    public function foodReview()
    {
        return \$this->hasOne(FoodReview::class);
    }

    public function deliverymanReview()
    {
        return \$this->hasOne(DeliverymanReview::class);
    }

    public function loyaltyTransactions()
    {
        return \$this->hasMany(LoyaltyTransaction::class);
    }
}
EOT,

    'CouponUse' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUse extends Model
{
    protected \$guarded = ['id'];
    public \$timestamps = false;

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function coupon()
    {
        return \$this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return \$this->belongsTo(User::class);
    }

    public function order()
    {
        return \$this->belongsTo(Order::class);
    }
}
EOT,

    'OrderItem' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected \$guarded = ['id'];

    public function order()
    {
        return \$this->belongsTo(Order::class);
    }

    public function product()
    {
        return \$this->belongsTo(Product::class);
    }

    public function variant()
    {
        return \$this->belongsTo(ProductVariant::class);
    }

    public function extras()
    {
        return \$this->hasMany(OrderItemExtra::class);
    }
}
EOT,

    'OrderItemExtra' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemExtra extends Model
{
    protected \$guarded = ['id'];
    public \$timestamps = false;

    public function orderItem()
    {
        return \$this->belongsTo(OrderItem::class);
    }

    public function extra()
    {
        return \$this->belongsTo(ProductExtra::class, 'extra_id');
    }
}
EOT,

    'FoodReview' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodReview extends Model
{
    protected \$guarded = ['id'];

    public function order()
    {
        return \$this->belongsTo(Order::class);
    }

    public function user()
    {
        return \$this->belongsTo(User::class);
    }
}
EOT,

    'DeliverymanReview' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverymanReview extends Model
{
    protected \$guarded = ['id'];

    public function order()
    {
        return \$this->belongsTo(Order::class);
    }

    public function user()
    {
        return \$this->belongsTo(User::class);
    }

    public function deliveryman()
    {
        return \$this->belongsTo(Deliveryman::class);
    }
}
EOT,

    'LoyaltyTransaction' => <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    protected \$guarded = ['id'];
    const UPDATED_AT = null;

    public function user()
    {
        return \$this->belongsTo(User::class);
    }

    public function order()
    {
        return \$this->belongsTo(Order::class);
    }
}
EOT,

];

foreach ($models as $name => $content) {
    file_put_contents($modelsPath . '/' . $name . '.php', $content);
}

echo "Created " . count($models) . " models.\n";
