# Cau truc du an theo FE / BE / DB

Tai lieu nay chia lai du an Laravel hien tai theo 3 lop de de giai thich va de lap ke hoach phat trien tiep theo.

Luu y quan trong: day van la mot du an Laravel full-stack. FE va BE duoc tach theo vai tro, khong phai 2 ung dung rieng biet nhu React app + API app.

## Tong quan flow

```text
User / Browser
    -> FE Blade hien thi giao dien
    -> Gui request len Laravel route
    -> BE Controller / Service xu ly logic
    -> Model Eloquent doc/ghi MySQL
    -> BE tra du lieu vao Blade
    -> Browser nhan HTML/CSS/JS de hien thi
```

## 1. FE - Frontend

FE la phan nguoi dung nhin thay va thao tac tren trinh duyet.

### Thu muc chinh

```text
resources/views/
public/
resources/css/
resources/js/
```

### Storefront FE

Day la giao dien khach hang.

```text
resources/views/home.blade.php
resources/views/products/index.blade.php
resources/views/products/show.blade.php
resources/views/cart.blade.php
resources/views/checkout.blade.php
resources/views/checkout-success.blade.php
resources/views/pages/
resources/views/layouts/app.blade.php
```

Nhiem vu:

- Hien thi trang chu.
- Hien thi danh sach san pham.
- Hien thi chi tiet san pham.
- Hien thi gio hang.
- Hien thi form checkout.
- Hien thi trang cam on sau khi dat hang.
- Hien thi cac trang gioi thieu, lien he, chinh sach.

Vi du:

```blade
@foreach($products as $product)
    <x-products.card :product="$product" />
@endforeach
```

Doan tren khong tu query database. No chi render bien `$products` da duoc BE truyen sang.

### Admin FE

Day la giao dien quan tri.

```text
resources/views/admin/dashboard.blade.php
resources/views/admin/products.blade.php
resources/views/admin/orders.blade.php
resources/views/admin/customers.blade.php
resources/views/admin/coupons.blade.php
resources/views/admin/reports.blade.php
resources/views/admin/settings.blade.php
```

Tinh trang hien tai:

- Da co layout UI admin.
- Da co route admin va middleware dang nhap/admin.
- Nhieu man hinh admin van dang o muc FE/mock, can noi BE that o buoc tiep theo.

Huong lam tiep:

- Tao `AdminProductController`.
- Tao `AdminOrderController`.
- Tao `AdminCouponController`.
- Truyen data that tu DB vao cac view admin.

## 2. BE - Backend

BE la phan Laravel xu ly request, logic nghiep vu, validation, bao mat va doc/ghi database.

### Route

```text
routes/web.php
routes/api.php
```

`routes/web.php` xu ly cac trang web Blade:

```php
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/collections/all', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/checkout/place-order', [CartController::class, 'placeOrder'])->name('checkout.place');
```

`routes/api.php` xu ly API:

```php
Route::middleware('auth:sanctum')->prefix('apriori')->group(function () {
    Route::get('stats', [AprioriReportController::class, 'stats']);
    Route::get('rules', [AprioriReportController::class, 'rules']);
    Route::get('itemsets', [AprioriReportController::class, 'itemsets']);
});
```

### Controller

```text
app/Http/Controllers/HomeController.php
app/Http/Controllers/ProductController.php
app/Http/Controllers/CategoryController.php
app/Http/Controllers/CartController.php
app/Http/Controllers/AuthController.php
app/Http/Controllers/AprioriReportController.php
```

Nhiem vu:

- Nhan request tu route.
- Validate du lieu nguoi dung gui len.
- Goi service/model de xu ly.
- Tra ve view hoac response.

Vi du product detail:

```php
$product = $this->productService->findBySlug($slug);

return view('products.show', [
    'product' => $product,
    'relatedProducts' => $relatedProducts,
]);
```

### Service

```text
app/Services/HomeService.php
app/Services/ProductService.php
app/Services/AprioriRecommendationService.php
```

Nhiem vu:

- Gom logic query/lap du lieu phuc tap.
- Giu controller gon hon.
- Tai su dung logic cho nhieu noi.

Vi du:

```php
Product::query()
    ->with('images')
    ->where('is_active', true)
    ->paginate(12);
```

### Model

```text
app/Models/Product.php
app/Models/Category.php
app/Models/Order.php
app/Models/OrderItem.php
app/Models/Coupon.php
app/Models/CouponUsage.php
app/Models/InventoryMovement.php
app/Models/User.php
```

Nhiem vu:

- Dai dien cho cac bang trong database.
- Dinh nghia fillable/casts.
- Dinh nghia relationship.

Vi du:

```php
class Product extends Model
{
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
}
```

### Middleware / Auth

```text
app/Http/Middleware/EnsureAdmin.php
app/Http/Middleware/Authenticate.php
app/Http/Kernel.php
```

Nhiem vu:

- Kiem tra user da dang nhap chua.
- Kiem tra user co role admin khong.
- Bao ve route admin/API.

## 3. DB - Database

DB la MySQL database `web_traicay`. phpMyAdmin chi la cong cu de xem va quan ly database, khong phai noi Laravel ket noi vao.

Laravel ket noi MySQL thong qua file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_traicay
DB_USERNAME=root
DB_PASSWORD=...
```

### Migration

```text
database/migrations/
```

Nhiem vu:

- Dinh nghia cau truc bang.
- Them cot moi.
- Them index/foreign key.
- Tao cac bang phuc vu BE.

Migration BE-ready moi:

```text
database/migrations/2026_06_22_000001_prepare_backend_schema.php
```

### Seeder

```text
database/seeders/
```

Nhiem vu:

- Tao du lieu mau.
- Tao admin user.
- Tao category/product/coupon demo neu can.

### Nhom bang chinh

Catalog:

```text
categories
products
product_images
```

Cart:

```text
carts
cart_items
```

Order:

```text
orders
order_items
order_status_histories
```

Coupon:

```text
coupons
coupon_images
coupon_usages
```

Inventory:

```text
inventory_movements
```

User/Auth:

```text
users
password_resets
password_history
personal_access_tokens
failed_login_attempts
secure_sessions
security_audit_log
```

CMS/Settings:

```text
settings
banners
pages
```

System:

```text
migrations
failed_jobs
```

## Vi du xu ly 1 request

### User mo trang danh sach san pham

```text
GET /collections/all
    -> routes/web.php
    -> ProductController@index
    -> ProductService@getCollection
    -> Product model query bang products/product_images/categories
    -> resources/views/products/index.blade.php
    -> Browser hien thi danh sach san pham
```

### User mo chi tiet san pham

```text
GET /products/{slug}
    -> routes/web.php
    -> ProductController@show
    -> ProductService@findBySlug
    -> Product model query products + category + images
    -> resources/views/products/show.blade.php
    -> Browser hien thi ten, gia, anh, mo ta, ton kho
```

### User dat hang

```text
POST /checkout/place-order
    -> routes/web.php
    -> CartController@placeOrder
    -> Validate thong tin khach hang
    -> Kiem tra gio hang, gia, ton kho
    -> Tao orders
    -> Tao order_items
    -> Tru stock trong products
    -> Ghi inventory_movements
    -> Ghi coupon_usages neu co coupon
    -> Tra ve checkout-success
```

## Cach noi voi user/client

Co the giai thich ngan gon:

```text
Du an duoc chia thanh 3 lop. FE la giao dien Blade hien thi cho nguoi dung. BE la Laravel xu ly request, kiem tra du lieu va thuc hien nghiep vu. DB la MySQL luu san pham, danh muc, don hang, user va ton kho. Nguoi dung khong truy cap truc tiep database; moi thao tac deu di qua Laravel truoc khi doc/ghi MySQL.
```

## Roadmap tiep theo

1. Noi admin products voi DB that.
2. Noi admin orders voi DB that.
3. Noi admin coupons voi DB that.
4. Noi dashboard voi thong ke tu orders/products/users.
5. Lam CRUD settings/banners/pages.
6. Don encoding du lieu tieng Viet trong database.
7. Xu ly cac san pham gia 0 theo rule kinh doanh: an san pham, chuyen sang lien he, hoac cap nhat gia.
