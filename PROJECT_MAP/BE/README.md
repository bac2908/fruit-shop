# BE - Backend

BE la phan Laravel xu ly request, validation, logic nghiep vu, auth, cart, order, coupon va inventory.

## Routes

- [routes/web.php](../../routes/web.php): route web Blade, storefront, cart, checkout, admin.
- [routes/api.php](../../routes/api.php): route API, hien co Apriori API va Sanctum auth.

## Controllers

- [HomeController.php](../../app/Http/Controllers/HomeController.php): xu ly trang chu.
- [ProductController.php](../../app/Http/Controllers/ProductController.php): danh sach, chi tiet, search san pham.
- [CategoryController.php](../../app/Http/Controllers/CategoryController.php): trang danh muc.
- [CartController.php](../../app/Http/Controllers/CartController.php): gio hang, coupon, checkout, tao don, tru kho.
- [AuthController.php](../../app/Http/Controllers/AuthController.php): login/logout admin.
- [AprioriReportController.php](../../app/Http/Controllers/AprioriReportController.php): API bao cao/goi y Apriori.

## Services

- [HomeService.php](../../app/Services/HomeService.php): lay data cho trang chu.
- [ProductService.php](../../app/Services/ProductService.php): query/filter/sort/search san pham.
- [AprioriRecommendationService.php](../../app/Services/AprioriRecommendationService.php): logic goi y mua kem.

## Models

- [Product.php](../../app/Models/Product.php): model san pham.
- [Category.php](../../app/Models/Category.php): model danh muc.
- [ProductImage.php](../../app/Models/ProductImage.php): anh san pham.
- [Order.php](../../app/Models/Order.php): don hang.
- [OrderItem.php](../../app/Models/OrderItem.php): chi tiet don hang.
- [OrderStatusHistory.php](../../app/Models/OrderStatusHistory.php): lich su trang thai don.
- [Coupon.php](../../app/Models/Coupon.php): ma giam gia.
- [CouponUsage.php](../../app/Models/CouponUsage.php): luot su dung coupon.
- [CouponImage.php](../../app/Models/CouponImage.php): anh coupon.
- [InventoryMovement.php](../../app/Models/InventoryMovement.php): lich su nhap/xuat/ton kho.
- [User.php](../../app/Models/User.php): nguoi dung/admin.
- [Setting.php](../../app/Models/Setting.php): cau hinh website.
- [Banner.php](../../app/Models/Banner.php): banner.
- [Page.php](../../app/Models/Page.php): trang CMS.

## Middleware / Auth

- [Kernel.php](../../app/Http/Kernel.php): dang ky middleware.
- [EnsureAdmin.php](../../app/Http/Middleware/EnsureAdmin.php): chan route admin neu user khong phai admin.
- [Authenticate.php](../../app/Http/Middleware/Authenticate.php): bat dang nhap.

## Config

- [config/database.php](../../config/database.php): cau hinh ket noi DB.
- [config/mail.php](../../config/mail.php): cau hinh mail.
- [config/apriori.php](../../config/apriori.php): cau hinh Apriori.

## Console commands

- [AnalyzeAprioriCommand.php](../../app/Console/Commands/AnalyzeAprioriCommand.php): chay phan tich Apriori.
- [GenerateAprioriReportCommand.php](../../app/Console/Commands/GenerateAprioriReportCommand.php): tao bao cao Apriori.
- [DebugAprioriCommand.php](../../app/Console/Commands/DebugAprioriCommand.php): debug Apriori.

## Flow BE vi du

```text
POST /checkout/place-order
    -> CartController@placeOrder
    -> validate thong tin khach hang
    -> lock san pham va coupon
    -> tao orders/order_items
    -> tru stock trong products
    -> ghi inventory_movements/coupon_usages
    -> redirect checkout-success
```

