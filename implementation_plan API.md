# Mega Plan de Implementación del Sistema Final (PedidosApp Backend)

Este documento detalla exhaustivamente todos los cambios arquitectónicos, adiciones a la base de datos, actualizaciones de lógica en la API y modificaciones del panel administrativo (Filament) requeridos para llegar a la **Versión Final y Definitiva** del sistema, garantizando estabilidad, profesionalismo y compatibilidad total con la aplicación Flutter existente.

---

## 1. Auditoría y Actualización de Estética (Filament v3)
**Objetivo:** Eliminar el uso de emojis en todo el panel administrativo y reemplazarlos por íconos vectoriales profesionales, además de activar actualizaciones en tiempo real.
- **Vectores en lugar de Emojis:** Sustitución global en los `NavigationGroup`, `NavigationIcon` y estados de tablas de todos los recursos (usando el paquete oficial de Heroicons ya incluido en Filament).
- **Actualización en Tiempo Real:** Activación de Polling automático en Filament (`->poll('5s')`) en las tablas de Pedidos Activos y Cocina para que los registros aparezcan al instante sin recargar la página web.
- **Cierre de sesión automático:** Configurar el tiempo de expiración de sesión en `config/session.php` a 120 minutos de inactividad estricta para la seguridad del panel.

---

## 2. Implementación de Soft Deletes (Ocultar en lugar de Eliminar)
**Objetivo:** Proteger la integridad referencial. Si un usuario, administrador o repartidor tiene historial, eliminarlo rompería las relaciones.
- **Modelos a afectar:** `User`, `Deliveryman`, `AdminUser`, `Branch`, `Product`, `Category`.
- **Migraciones:** Crear migraciones `add_deleted_at_to_[table]_table` para añadir la columna `deleted_at`.
- **Lógica:** Implementar el Trait `Illuminate\Database\Eloquent\SoftDeletes` en los modelos. Filament detectará automáticamente esto y proporcionará filtros nativos de "Solo Activos" / "Archivados" (Trashed).
- **Impacto en API:** La API automáticamente dejará de listar a los usuarios o productos eliminados suavemente en los endpoints estándar.

---

## 3. Seguridad de Modificación en Panel Admin
**Objetivo:** Exigir la contraseña del administrador en sesión para aplicar cambios críticos a otros usuarios.
- **Filament Resources (`UserResource`, `DeliverymanResource`):**
  - Eliminar los campos de contraseña obligatoria del usuario final en la vista de edición.
  - Crear una **Acción Personalizada (Action)** llamada "Cambiar Contraseña / Actualizar Datos Sensibles".
  - Esta acción levantará un modal que requerirá un campo `admin_password`. El sistema validará usando `Hash::check` contra el administrador autenticado antes de aplicar el cambio al cliente/repartidor.

---

## 4. Nuevo Rol: Usuario de Cocina
**Objetivo:** Restringir acceso. Un usuario de cocina solo debe ver los pedidos en preparación.
- **Modelo `AdminUser`:** Agregar un campo Enum o String `role` (valores: `admin`, `kitchen`).
- **Middleware / Filament Panel:**
  - Modificar el archivo de configuración del Panel (`AdminPanelProvider.php`).
  - Crear un recurso dedicado `KitchenOrderResource` o una vista personalizada en Filament.
  - Usar la función `canViewAny()` y `canView()` de las Policies en Laravel para asegurar que si `auth()->user()->role === 'kitchen'`, todos los demás recursos (Usuarios, Repartidores, Ganancias) devuelvan `false` y queden completamente ocultos.

---

## 5. Validaciones Globales y Estrictas
**Objetivo:** Limpieza y formato perfecto en la Base de Datos.
- **Teléfonos (El Salvador):**
  - Aplicar regla de validación Regex en Laravel (API y Filament): `/^\+503\s?[267]\d{7}$/`. Obliga al prefijo `+503` seguido de 8 dígitos comenzando con 2, 6 o 7.
- **Precios:**
  - Regla `numeric|min:0` y validación de 2 decimales. Limpieza del _input_ (ignorar comas, aceptar puntos).
- **Fechas:**
  - Regla estricta `date|date_format:Y-m-d H:i:s` donde aplique.
- **Correos:**
  - Usar la regla nativa de Laravel `email:rfc,dns` para evitar correos falsos sin dominio válido.
- **Contraseñas:**
  - Uso de inputs `password` en Filament (puntos ocultos) y exclusión de las contraseñas en todas las consultas API (`$hidden = ['password']`).

---

## 6. Lógica de Zonas y Sucursales (Enfoque por Municipios/Distritos)
**Objetivo:** Cálculos de entrega precisos y fáciles de administrar.
- **El Reto de los Polígonos:** Dibujar polígonos en un mapa es preciso, pero muy propenso a errores humanos (zonas superpuestas, huecos sin cobertura) y requiere cálculos matemáticos complejos en el servidor para saber si una coordenada cae dentro del polígono.
- **La Solución Viable (Tu sugerencia):** Usaremos **Zonas por Distritos/Municipios**. 
- **Modelo `Zone`:**
  - El administrador simplemente creará Zonas escribiendo el nombre oficial (ej. "San Salvador", "Soyapango", "Antiguo Cuscatlán") y le asignará una tarifa base y/o una tarifa extra por Kilómetro.
- **Modelo `Branch` (Sucursal):**
  - Cada sucursal se enlazará a las zonas que puede cubrir.
- **Validación Automática:** 
  - Cuando el cliente elige su ubicación en el mapa, la app (o el backend) hará una consulta de **Geocodificación Inversa (Reverse Geocoding) de Google Maps** para traducir esa coordenada en un nombre de ciudad/municipio.
  - El sistema buscará si ese municipio coincide con alguna de las Zonas registradas. Si existe, calculará el costo de envío exacto. Si no existe, indicará "Fuera de área de cobertura". ¡Es la forma más robusta, a prueba de fallas y fácil de gestionar!

---

## 7. Módulo Completo de Cupones
**Objetivo:** Permitir descuentos programados con límites lógicos.
- **Modelo y Migración (`coupons`):**
  - Campos a validar/agregar: `code` (Unique), `discount_type` (percent/fixed), `discount`, `min_purchase` (Gasto mínimo), `max_discount` (Tope de descuento), `start_date`, `expire_date`, `usage_limit` (Límite total de veces a usar en el sistema), `used_count` (Veces usado).
- **Rutas API:**
  - `POST /api/v1/coupons/apply`: Recibe `code` y `order_amount`.
  - **Validaciones:** Retorna error si está vencido, si no alcanza el `min_purchase`, o si `used_count >= usage_limit`. Si es válido, retorna el total a descontar.

---

## 8. Lógica Inteligente de Productos
**Objetivo:** Estrellas, Populares y Recomendados dinámicos.
- **Estrellas (`stars`):**
  - El endpoint de Reseñas que ya implementamos calcula el promedio y actualiza el campo `stars` del producto automáticamente. Esto ya está activo y se probará.
- **Productos Populares:**
  - Modificar el Query del endpoint `GET /api/v1/products?popular=true`.
  - En lugar de basarse en un booleano estático, se hará un `withCount('orderItems')` (basado en la relación de ítems comprados) o `sum('quantity')`, ordenando de mayor a menor (`orderByDesc`).
- **Productos Recomendados:**
  - Endpoint `GET /api/v1/products?recommended=true`.
  - Si el usuario está autenticado, buscar sus últimas compras e inferir categorías preferidas, listando productos de esas categorías que aún no ha comprado. Si es invitado, mostrar los mejor calificados (mayor a 4.5 estrellas).

---

## 9. Sistema Dual de Archivado de Pedidos (El Gran Cambio)
**Objetivo:** Separar las órdenes en curso de las ya procesadas para evitar cuellos de botella en la tabla de pedidos a medida que crece el sistema.
- **Estructura Arquitectónica:**
  - Tabla 1: `orders` (Pedidos Activos). Almacenará exclusivamente estados: `pending`, `accepted`, `processing`, `on_the_way`.
  - Tabla 2: `archived_orders` (Pedidos Históricos). Misma estructura exacta que `orders`. Almacenará estados: `delivered`, `cancelled`, `failed`.
- **Transición Automática:**
  - Usar los _Observers_ de Eloquent o _Listeners_. Cuando el estado de una orden cambie a `delivered` o `cancelled`, el sistema copiará el registro exacto a `archived_orders`, incluyendo todas sus relaciones (detalles del pedido, historial de estados) y luego eliminará físicamente (`delete`) el registro de la tabla `orders`.
- **Compatibilidad API (Crucial):**
  - **No romper la App:** El endpoint `GET /api/v1/orders` del usuario ejecutará internamente una unificación. Laravel hará un llamado a los pedidos activos y a los archivados correspondientes a ese usuario, los combinará, los ordenará por fecha y devolverá una lista unificada en el JSON. La App en Flutter nunca notará que provienen de dos tablas distintas.
  - El panel de Filament tendrá dos páginas distintas: "Pedidos Activos" (Consultando la tabla `orders`) y "Archivo de Pedidos" (Consultando `archived_orders`), permitiendo al servidor manejar volúmenes masivos de datos eficientemente.

---

## 10. Migración de Base de Datos a PostgreSQL
**Objetivo:** Transición de MariaDB/MySQL a PostgreSQL para tener una base de datos más robusta, estricta y con mejor soporte para futuros desarrollos a gran escala.
- **Cambios en Infraestructura (Docker):**
  - Se modificará el archivo `docker-compose.yml` para sustituir el contenedor de MariaDB por la imagen oficial de `postgres` (versión 16+).
  - Se cambiará el puerto de exposición de `3306` a `5432`.
- **Cambios en el Contenedor de la API (Dockerfile):**
  - El sistema base (Ubuntu/PHP) deberá instalar las librerías `libpq-dev` y habilitar la extensión `pdo_pgsql` en lugar de `pdo_mysql` para que Laravel pueda comunicarse con Postgres.
- **Consideraciones de Código y Laravel (`.env`):**
  - Cambiar `DB_CONNECTION=pgsql`.
  - Postgres es mucho más estricto con los tipos de datos (Booleanos, Integers). Revisaremos todas las migraciones para garantizar que Laravel genere correctamente el esquema estricto (por ejemplo, Postgres no tolera guardar `1` o `0` directamente en campos booleanos si no pasan por la capa de abstracción de Eloquent adecuadamente).
- **Manejo de Datos Actuales:**
  - Puesto que esto es un cambio de motor completo, se purgará la base de datos actual y se correrán las migraciones desde cero (`php artisan migrate:fresh`). (Si existieran datos reales de producción que salvar, requeriría un script ETL complejo, pero asumo que estamos en fase de preparación final).

---

## Resumen de Aprobación
Este Mega Plan toca cada rincón del sistema. Asegura las buenas prácticas de validación, escalabilidad arquitectónica (tablas de pedidos divididas), seguridad de sesión, un Frontend administrativo impecable y profesional, y una base de datos sólida y moderna con PostgreSQL. Todo construido sobre Laravel 11 y Filament v3 usando PHP 8.2+.

> [!CAUTION]
> **Revisión del Usuario Requerida**
> Separar la tabla de pedidos en 2 y migrar el motor completo a PostgreSQL son cambios estructurales mayores. Está diseñado para que Flutter NO se entere del cambio (la respuesta JSON será la misma), pero la base de datos se recreará por completo bajo PostgreSQL.
