# DB - Database

DB la MySQL database `web_traicay`. phpMyAdmin chi la cong cu de xem database, Laravel ket noi truc tiep toi MySQL qua `.env`.

## Ket noi database

- [.env](../../.env): cau hinh database local that. Khong commit file nay len public.
- [.env.example](../../.env.example): mau cau hinh database.
- [config/database.php](../../config/database.php): Laravel doc bien `.env` de ket noi MySQL.

Vi du:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_traicay
DB_USERNAME=root
DB_PASSWORD=...
```

## Migrations

- [database/migrations](../../database/migrations): tat ca migration.
- [prepare_backend_schema.php](../../database/migrations/2026_06_22_000001_prepare_backend_schema.php): migration bo sung schema BE-ready.

## Seeders

- [DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php): seeder tong.
- [AdminUserSeeder.php](../../database/seeders/AdminUserSeeder.php): tao admin.
- [CategorySeeder.php](../../database/seeders/CategorySeeder.php): seed danh muc.
- [ProductSeeder.php](../../database/seeders/ProductSeeder.php): seed san pham.
- [CouponSeeder.php](../../database/seeders/CouponSeeder.php): seed coupon.
- [AprioriDemoOrderSeeder.php](../../database/seeders/AprioriDemoOrderSeeder.php): seed don mau Apriori.

## Nhom bang MySQL

### Catalog

```text
categories
products
product_images
```

### Cart

```text
carts
cart_items
```

### Order

```text
orders
order_items
order_status_histories
```

### Coupon

```text
coupons
coupon_images
coupon_usages
```

### Inventory

```text
inventory_movements
```

### User / Auth / Security

```text
users
password_resets
password_history
personal_access_tokens
failed_login_attempts
secure_sessions
security_audit_log
```

### CMS / Settings

```text
settings
banners
pages
```

### System

```text
migrations
failed_jobs
```

## Lenh hay dung

```bash
php artisan migrate:status
php artisan migrate
php artisan db:seed
```

## Can nho

- Khong sua truc tiep DB production neu chua backup.
- Nen them/sua cau truc bang bang migration.
- Du lieu tieng Viet trong dump cu dang bi loi encoding, can don truoc khi chot data san pham.
- Cac san pham gia `0` nen duoc xu ly theo rule kinh doanh: an, lien he bao gia, hoac cap nhat gia.

