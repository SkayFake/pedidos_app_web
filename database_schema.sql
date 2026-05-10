-- ============================================================
-- DELIVERY APP — Esquema de Base de Datos
-- Laravel 11 + Sanctum + Filament
-- 19 tablas | MySQL 8.0+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- 1. branches — Sucursales
-- ============================================================
CREATE TABLE `branches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `first_order_discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Configurable desde Filament. 0 = desactivado.',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. admin_users — Staff del panel Filament
-- ============================================================
CREATE TABLE `admin_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = superadmin (todas las sucursales)',
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'branch_admin', 'operator') NOT NULL DEFAULT 'operator',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_email_unique` (`email`),
  KEY `admin_users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `admin_users_branch_id_foreign`
    FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. users — Clientes (app Flutter)
-- ============================================================
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `profile_photo` VARCHAR(255) NULL DEFAULT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `loyalty_points` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_completed_orders` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Para promo pedido #11. Se incrementa solo al entregar.',
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. deliverymen — Repartidores (cuenta separada, crea el admin)
-- ============================================================
CREATE TABLE `deliverymen` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `vehicle_type` ENUM('motorcycle', 'bicycle', 'car') NOT NULL DEFAULT 'motorcycle',
  `license_plate` VARCHAR(20) NULL DEFAULT NULL,
  `is_available` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Disponible para recibir pedidos',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Habilitado por el admin',
  `active_orders_count` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Máximo 3 simultáneos',
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deliverymen_email_unique` (`email`),
  UNIQUE KEY `deliverymen_phone_unique` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. zones — Zonas de cobertura por ciudad
-- ============================================================
CREATE TABLE `zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Ej: Ciudad Capital, Mixco, Villa Nueva',
  `city` VARCHAR(100) NOT NULL,
  `delivery_fee` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Tarifa fija de envío',
  `is_deliverable` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'FALSE = cantones sin cobertura',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. customer_addresses — Direcciones guardadas de clientes
-- ============================================================
CREATE TABLE `customer_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `zone_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(50) NOT NULL COMMENT 'Casa, Trabajo, Casa mamá',
  `street` VARCHAR(255) NOT NULL,
  `references` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Referencias adicionales',
  `latitude` DECIMAL(10,8) NULL DEFAULT NULL COMMENT 'Coordenada GPS',
  `longitude` DECIMAL(11,8) NULL DEFAULT NULL COMMENT 'Coordenada GPS',
  `is_default` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_addresses_user_id_foreign` (`user_id`),
  KEY `customer_addresses_zone_id_foreign` (`zone_id`),
  CONSTRAINT `customer_addresses_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_addresses_zone_id_foreign`
    FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. categories — Categorías de productos
-- ============================================================
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Pizzas, Hamburguesas, Bebidas...',
  `image` VARCHAR(255) NULL DEFAULT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. products — Productos del menú
-- ============================================================
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `base_price` DECIMAL(8,2) NOT NULL COMMENT 'Precio base sin variantes ni extras',
  `image` VARCHAR(255) NULL DEFAULT NULL,
  `is_available` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'FALSE = agotado temporalmente',
  `is_recommended` BOOLEAN NOT NULL DEFAULT FALSE,
  `is_popular` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_category_id_foreign` (`category_id`),
  CONSTRAINT `products_category_id_foreign`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. product_variants — Variantes por ingredientes
-- ============================================================
CREATE TABLE `product_variants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL COMMENT 'Sin cebolla, Tamaño grande, Extra jalapeño',
  `price_modifier` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Positivo suma, negativo resta al base_price',
  `is_default` BOOLEAN NOT NULL DEFAULT FALSE,
  `is_available` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variants_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. product_extras — Extras opcionales
-- ============================================================
CREATE TABLE `product_extras` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL COMMENT 'Queso extra, Doble carne, Salsa especial',
  `price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `is_available` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_extras_product_id_foreign` (`product_id`),
  CONSTRAINT `product_extras_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. coupons — Cupones de descuento
-- ============================================================
CREATE TABLE `coupons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `type` ENUM('percent', 'fixed', 'free_delivery') NOT NULL,
  `value` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '% o monto fijo. 0 si es free_delivery.',
  `min_order_amount` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `max_uses_total` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = sin límite global',
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. orders — Pedidos
-- ============================================================
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NOT NULL,
  `deliveryman_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `address_id` BIGINT UNSIGNED NOT NULL,
  `coupon_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `otp` VARCHAR(4) NULL DEFAULT NULL COMMENT 'Código de 4 dígitos para validar la entrega',
  `status` ENUM('pending','confirmed','preparing','assigned','on_way','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal` DECIMAL(8,2) NOT NULL,
  `delivery_fee` DECIMAL(8,2) NOT NULL,
  `discount_amount` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(8,2) NOT NULL,
  `is_first_order_promo` BOOLEAN NOT NULL DEFAULT FALSE,
  `is_free_delivery_promo` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Pedido #11 delivery gratis',
  `is_loyalty_discount` BOOLEAN NOT NULL DEFAULT FALSE COMMENT '5% off delivery con puntos',
  `cancellation_reason` VARCHAR(255) NULL DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `assigned_at` TIMESTAMP NULL DEFAULT NULL,
  `delivered_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_branch_id_foreign` (`branch_id`),
  KEY `orders_deliveryman_id_foreign` (`deliveryman_id`),
  KEY `orders_address_id_foreign` (`address_id`),
  KEY `orders_coupon_id_foreign` (`coupon_id`),
  KEY `orders_status_index` (`status`),
  CONSTRAINT `orders_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_branch_id_foreign`
    FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_deliveryman_id_foreign`
    FOREIGN KEY (`deliveryman_id`) REFERENCES `deliverymen` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_address_id_foreign`
    FOREIGN KEY (`address_id`) REFERENCES `customer_addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_coupon_id_foreign`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. coupon_uses — 1 uso por cliente (garantizado a nivel BD)
-- ============================================================
CREATE TABLE `coupon_uses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `used_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupon_uses_coupon_user_unique` (`coupon_id`, `user_id`),
  KEY `coupon_uses_order_id_foreign` (`order_id`),
  CONSTRAINT `coupon_uses_coupon_id_foreign`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_uses_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_uses_order_id_foreign`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. order_items — Ítems del pedido
-- ============================================================
CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variant_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(8,2) NOT NULL COMMENT 'Snapshot del precio al momento de compra',
  `subtotal` DECIMAL(8,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `order_items_order_id_foreign`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_items_variant_id_foreign`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. order_item_extras — Extras seleccionados por ítem
-- ============================================================
CREATE TABLE `order_item_extras` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_item_id` BIGINT UNSIGNED NOT NULL,
  `extra_id` BIGINT UNSIGNED NOT NULL,
  `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(8,2) NOT NULL COMMENT 'Snapshot del precio del extra',
  PRIMARY KEY (`id`),
  KEY `order_item_extras_order_item_id_foreign` (`order_item_id`),
  KEY `order_item_extras_extra_id_foreign` (`extra_id`),
  CONSTRAINT `order_item_extras_order_item_id_foreign`
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_extras_extra_id_foreign`
    FOREIGN KEY (`extra_id`) REFERENCES `product_extras` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. food_reviews — Reseña de la comida (interna)
-- ============================================================
CREATE TABLE `food_reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL COMMENT '1 a 5 estrellas',
  `comment` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `food_reviews_order_unique` (`order_id`),
  KEY `food_reviews_user_id_foreign` (`user_id`),
  CONSTRAINT `food_reviews_order_id_foreign`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `food_reviews_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 17. deliveryman_reviews — Reseña del repartidor (interna)
-- ============================================================
CREATE TABLE `deliveryman_reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `deliveryman_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL COMMENT '1 a 5 estrellas',
  `comment` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deliveryman_reviews_order_unique` (`order_id`),
  KEY `deliveryman_reviews_user_id_foreign` (`user_id`),
  KEY `deliveryman_reviews_deliveryman_id_foreign` (`deliveryman_id`),
  CONSTRAINT `deliveryman_reviews_order_id_foreign`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deliveryman_reviews_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deliveryman_reviews_deliveryman_id_foreign`
    FOREIGN KEY (`deliveryman_id`) REFERENCES `deliverymen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18. loyalty_transactions — Movimientos de puntos de fidelidad
-- Regla: 1 punto por $1 gastado en subtotal (floor)
-- Canje:  5% off delivery_fee, mínimo 5 puntos para canjear
-- ============================================================
CREATE TABLE `loyalty_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `type` ENUM('earned', 'redeemed') NOT NULL,
  `points` INT NOT NULL COMMENT 'Positivo al ganar, negativo al canjear',
  `description` VARCHAR(255) NOT NULL COMMENT 'Ej: Compra #12345, Canje 5% envío',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_transactions_user_id_foreign` (`user_id`),
  KEY `loyalty_transactions_order_id_foreign` (`order_id`),
  CONSTRAINT `loyalty_transactions_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loyalty_transactions_order_id_foreign`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 19. personal_access_tokens — Sanctum (clientes + repartidores)
-- ============================================================
CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL DEFAULT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
