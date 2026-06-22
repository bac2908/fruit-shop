# FE - Frontend

FE la phan nguoi dung nhin thay tren trinh duyet. Trong du an nay FE dung Laravel Blade, nam chu yeu trong `resources/views`.

## Layout chinh

- [layouts/app.blade.php](../../resources/views/layouts/app.blade.php): layout tong, header, menu, footer, asset chung.

## Storefront

- [home.blade.php](../../resources/views/home.blade.php): trang chu.
- [products/index.blade.php](../../resources/views/products/index.blade.php): danh sach san pham, filter, sort, pagination.
- [products/show.blade.php](../../resources/views/products/show.blade.php): chi tiet san pham, anh, gia, ton kho, nut mua.
- [cart.blade.php](../../resources/views/cart.blade.php): gio hang.
- [checkout.blade.php](../../resources/views/checkout.blade.php): form dat hang.
- [checkout-success.blade.php](../../resources/views/checkout-success.blade.php): trang cam on sau khi dat hang.

## Static pages

- [pages/about.blade.php](../../resources/views/pages/about.blade.php): gioi thieu.
- [pages/contact.blade.php](../../resources/views/pages/contact.blade.php): lien he.

## Admin UI

- [admin/dashboard.blade.php](../../resources/views/admin/dashboard.blade.php): dashboard admin.
- [admin/products.blade.php](../../resources/views/admin/products.blade.php): UI quan ly san pham.
- [admin/orders.blade.php](../../resources/views/admin/orders.blade.php): UI quan ly don hang.
- [admin/customers.blade.php](../../resources/views/admin/customers.blade.php): UI quan ly khach hang.
- [admin/coupons.blade.php](../../resources/views/admin/coupons.blade.php): UI quan ly ma giam gia.
- [admin/reports.blade.php](../../resources/views/admin/reports.blade.php): UI bao cao.
- [admin/settings.blade.php](../../resources/views/admin/settings.blade.php): UI cau hinh.

## Component

- [views/components](../../resources/views/components): cac component Blade dung lai, vi du card san pham.

## Public assets

- [public](../../public): hinh anh, asset public.
- [resources/js](../../resources/js): JavaScript source neu build bang Laravel Mix.
- [resources/css](../../resources/css): CSS source neu build bang Laravel Mix.

## Can nho

FE khong ket noi truc tiep MySQL. FE chi nhan bien tu controller, vi du `$products`, `$product`, `$sections`, roi render ra HTML.

