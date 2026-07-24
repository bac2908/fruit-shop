# Thế Giới Trái Cây

Đây là dự án ecommerce bán trái cây mình xây dựng để luyện cách tổ chức một ứng dụng Laravel có nghiệp vụ thực tế. Ngoài phần cửa hàng dành cho khách, dự án còn có khu vực quản trị để xử lý sản phẩm, tồn kho, đơn hàng, voucher, khách hàng và nội dung website.

Điểm mình tập trung nhiều nhất là luồng đặt hàng: dữ liệu được kiểm tra lại ở server, tồn kho được cập nhật trong transaction, trạng thái đơn chỉ được chuyển theo đúng thứ tự và mỗi phương thức thanh toán có điều kiện xác nhận riêng.

## Phạm vi hiện tại

| Khu vực | Chức năng chính |
| --- | --- |
| Cửa hàng | Danh mục, tìm kiếm, chi tiết sản phẩm, quick view, yêu thích, sản phẩm đã xem và gợi ý mua kèm |
| Tài khoản | Đăng ký, xác minh email, đăng nhập thường/Google, quên mật khẩu, hồ sơ và địa chỉ giao hàng |
| Mua hàng | Giỏ hàng, voucher cá nhân, phí vận chuyển, checkout hai bước, COD, chuyển khoản và MoMo sandbox |
| Sau mua | Theo dõi trạng thái, thông báo, email, hủy đơn, đổi trả và hoàn tiền |
| Quản trị | Sản phẩm, tồn kho, đơn hàng, khách hàng, voucher, báo cáo, danh mục, banner, trang nội dung và inbox liên hệ |
| Bảo mật | RBAC, TOTP 2FA, thu hồi phiên, mật khẩu tạm bắt buộc đổi, rate limit và audit log |

Storefront dùng Blade thay vì tách thành một SPA. Cách này phù hợp với mục tiêu của dự án: giữ luồng request rõ ràng, triển khai gọn và dành phần lớn công sức cho nghiệp vụ backend.

## Luồng đơn hàng

Đơn hàng được quản lý bằng state machine:

```text
Chờ xác nhận -> Đã xác nhận -> Đang giao -> Hoàn tất
       |              |
       +--------------+--------> Đã hủy
```

Điều kiện xác nhận phụ thuộc vào phương thức thanh toán:

- COD được xác nhận khi tồn kho và phí giao hàng đã được chốt.
- Chuyển khoản chỉ được xác nhận sau khi admin đối soát giao dịch.
- MoMo chỉ được ghi nhận thanh toán từ callback hợp lệ; callback được kiểm tra chữ ký, số tiền và mã giao dịch.
- Đơn chưa có đơn vị vận chuyển không thể chuyển sang trạng thái đang giao.
- Tổng tiền của đơn đã thanh toán không được sửa lại từ trang quản trị.

Mỗi lần đổi trạng thái đều được lưu lịch sử. Khi hủy đơn, hệ thống hoàn tồn kho và xử lý lại voucher theo quy tắc tương ứng.

## Kiến trúc

```text
Browser
  -> Route
  -> Controller / Form Request
  -> Service nghiệp vụ
  -> Eloquent Model
  -> MySQL
  -> Blade response
```

Một số nguyên tắc đang được áp dụng:

- Controller nhận request và điều phối; nghiệp vụ lớn nằm trong `app/Services`.
- Validation được tách sang Form Request ở những luồng có dữ liệu đầu vào phức tạp.
- Tạo đơn, cập nhật tồn kho và sử dụng voucher chạy trong database transaction.
- Các thao tác nhạy cảm khóa bản ghi trước khi cập nhật để hạn chế xử lý trùng.
- Callback thanh toán có tính idempotent.
- Quyền admin được kiểm tra ở route và service, không chỉ ẩn nút trên giao diện.

## Công nghệ

- PHP 8.2, Laravel 12
- MySQL 8
- Blade, CSS, JavaScript và Vite 8
- Laravel Socialite cho Google OAuth
- PHPUnit cho feature/unit test
- Playwright và axe-core cho E2E, responsive và accessibility
- Docker Compose, Apache, phpMyAdmin và Mailpit

## Cấu trúc thư mục

```text
app/
  Http/Controllers/       Controller storefront và admin
  Http/Requests/          Validation request
  Models/                 Eloquent models
  Services/               Nghiệp vụ đơn hàng, thanh toán, tồn kho...
database/
  migrations/             Lịch sử thay đổi schema
  seeders/                Dữ liệu khởi tạo và tài khoản admin
resources/views/          Blade views của storefront và admin
routes/                   Web routes và scheduler
tests/
  Feature/                Kiểm thử nghiệp vụ
  Unit/                   Kiểm thử đơn vị
  E2E/                    Luồng trình duyệt bằng Playwright
scripts/                  Script audit frontend và chuẩn bị E2E
docker/                   Cấu hình Apache/MySQL cho container
```

## Chạy bằng AMPPS hoặc local PHP

Yêu cầu:

- PHP 8.2 trở lên
- Composer
- MySQL 8
- Node.js 22 và npm

Tạo file cấu hình:

```powershell
Copy-Item .env.example .env
```

Điền kết nối MySQL và tài khoản quản trị trong `.env`:

```dotenv
DB_DATABASE=web_traicay
DB_USERNAME=root
DB_PASSWORD=

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=ThayBangMatKhauManh#123
```

`ADMIN_PASSWORD` phải có ít nhất 12 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt. Seeder không chứa mật khẩu admin mặc định.

Cài dependency và khởi tạo ứng dụng:

```powershell
composer install
npm ci
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Website chạy tại `http://127.0.0.1:8000`. Nếu dùng virtual host của AMPPS/XAMPP, cần đặt `APP_URL` đúng URL đang mở để link xác minh email, reset mật khẩu và OAuth hoạt động.

## Chạy bằng Docker

Docker Compose tạo bốn service: ứng dụng, MySQL, phpMyAdmin và Mailpit.

```powershell
Copy-Item .env.docker.example .env
```

Sau khi đặt `ADMIN_EMAIL` và `ADMIN_PASSWORD` trong `.env`:

```powershell
docker compose up -d --build
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Các địa chỉ mặc định:

| Dịch vụ | URL |
| --- | --- |
| Website | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| Mailpit | http://localhost:8025 |
| MySQL từ máy host | `127.0.0.1:3307` |

Thông tin database Docker dành cho môi trường local:

```text
Database: fruitshop
Username: fruitshop
Password: fruitshop
```

Không sử dụng các giá trị này khi triển khai production.

## Kiểm thử

Chạy toàn bộ backend test:

```powershell
php artisan test
```

Kiểm tra build frontend:

```powershell
npm run build
```

Audit trang desktop/mobile, ảnh hỏng, tràn ngang và accessibility:

```powershell
npm run audit:frontend
```

Chạy checkout E2E trên database riêng có hậu tố `_e2e`:

```powershell
npx playwright install chromium
npm run test:e2e
```

Script E2E từ chối chạy `migrate:fresh` nếu tên database không có hậu tố `_e2e`.

## Công cụ vận hành

Kiểm tra chất lượng catalog:

```powershell
php artisan catalog:audit --json=storage/app/catalog-audit.json
```

Tạo WebP cho ảnh local:

```powershell
php artisan media:optimize --path=images/products_synced --quality=80
```

Kiểm tra cấu hình mail hoặc gửi một email thử:

```powershell
php artisan mail:check
php artisan mail:check --send=nguoi-nhan@example.com
```

Chạy scheduler ở local:

```powershell
php artisan schedule:work
```

Scheduler phụ trách cảnh báo tồn kho thấp và hủy các đơn chuyển khoản/MoMo quá hạn chưa thanh toán.

## Biến môi trường cần chú ý

| Nhóm | Biến |
| --- | --- |
| Ứng dụng | `APP_URL`, `APP_TIMEZONE`, `APP_DISPLAY_TIMEZONE` |
| Database | `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Email | `MAIL_*`, `CONTACT_INBOX_EMAIL` |
| Google OAuth | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |
| MoMo sandbox | `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY` |
| Cửa hàng | `SHOP_*` |
| Analytics | `ANALYTICS_ENABLED`, `ANALYTICS_MEASUREMENT_ID` |

Sau khi sửa `.env` trên hosting, chạy `php artisan config:clear` trước khi kiểm tra lại.

## Giới hạn trước khi dùng thực tế

Dự án hiện phù hợp để demo và trình bày portfolio, chưa nên xem là một hệ thống production hoàn chỉnh. Trước khi vận hành thật cần:

- thay MoMo sandbox bằng tài khoản merchant và quy trình đối soát chính thức;
- kết nối đơn vị vận chuyển hoặc quy trình giao hàng nội bộ thực tế;
- rà soát quyền sử dụng toàn bộ ảnh và dữ liệu catalog;
- chuyển email/tác vụ nặng sang queue worker;
- thiết lập backup, monitoring, HTTPS và CI/CD;
- kiểm thử SMTP, OAuth và callback thanh toán trên tên miền thật.

## Bảo mật khi chia sẻ repository

- Không commit `.env`, database dump, mật khẩu ứng dụng hoặc credential OAuth/SMTP/MoMo.
- Production phải dùng `APP_DEBUG=false`.
- Tài khoản admin được tạo từ biến môi trường, không dùng chung mật khẩu giữa các thành viên.
- Sau khi deploy ổn định, có thể cache cấu hình bằng `php artisan config:cache`.

## Mục đích sử dụng

Dự án được xây dựng cho mục đích học tập và portfolio PHP/Laravel. Các tích hợp thanh toán hiện chỉ phục vụ môi trường thử nghiệm.
