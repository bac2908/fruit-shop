# Thế Giới Trái Cây - Laravel Ecommerce

Dự án website bán trái cây theo hướng ecommerce, xây dựng bằng Laravel, MySQL và Blade. Project tập trung vào các luồng thật của một website bán hàng: xem sản phẩm, tìm kiếm, giỏ hàng, checkout, thanh toán, quản lý tài khoản, quản lý đơn hàng và khu vực admin.

## Mục Tiêu Dự Án

Project này được xây dựng để đưa vào portfolio/CV intern PHP. Điểm mạnh không chỉ nằm ở giao diện storefront, mà còn ở việc xử lý backend ecommerce: authentication, database schema, order workflow, inventory, voucher, email, thanh toán sandbox và admin dashboard.

## Tech Stack

- Backend: PHP 8.2, Laravel 12
- Frontend: Blade, CSS, JavaScript, Vite 8
- Database: MySQL 8
- Authentication: Laravel session auth, Google OAuth qua Socialite
- Payment: COD, chuyển khoản ngân hàng, MoMo sandbox
- Mail: SMTP/Mailpit cho local Docker
- DevOps: Docker, Docker Compose, Apache, phpMyAdmin
- Testing: PHPUnit 11, Playwright, axe-core

## Chức Năng Đã Xây Dựng

### Khách Hàng

- Đăng ký, đăng nhập, đăng xuất.
- Xác minh email bằng liên kết ký số có thời hạn và giới hạn gửi lại.
- Đăng nhập bằng Google OAuth.
- Quên mật khẩu và đặt lại mật khẩu qua email.
- Validation đăng ký/đăng nhập theo hướng ecommerce.
- Băm mật khẩu bằng cơ chế hash của Laravel.
- Bật/tắt hiển thị mật khẩu đúng logic icon mắt mở/mắt đóng.
- Hồ sơ khách hàng: họ tên, email, số điện thoại, ngày sinh, giới tính, avatar.
- Quản lý địa chỉ giao hàng.
- Địa chỉ Việt Nam theo tỉnh/thành và phường/xã/đặc khu.
- Sản phẩm yêu thích, sản phẩm đã xem, voucher cá nhân.
- Lịch sử đơn hàng, hủy đơn, yêu cầu đổi trả/hoàn tiền.

### Storefront

- Trang chủ với slider/banner, danh mục trái cây và block sản phẩm.
- Danh mục sản phẩm: trái cây Việt Nam, nhập khẩu, Thái Lan, giỏ quà/set quà, quả cưới/mâm cúng, hàng vào mùa, bestseller.
- Trang chi tiết sản phẩm.
- Quick view khi bấm icon mắt trên card sản phẩm.
- Tìm kiếm sản phẩm, gợi ý từ khóa phổ biến và lịch sử tìm kiếm cho user đăng nhập.
- Card sản phẩm có giá, giá khuyến mãi, trạng thái tồn kho, nút thêm vào giỏ.
- Mini cart popup sau khi thêm sản phẩm vào giỏ.
- Sitemap XML, robots.txt, canonical URL và JSON-LD cho tổ chức/sản phẩm/breadcrumb.
- Google Analytics chỉ được tải sau khi người dùng đồng ý cookie.
- Ảnh WebP, lazy loading, kích thước ảnh ổn định và giao diện mobile không tràn ngang.
- Modal hỗ trợ bàn phím, focus trap và các kiểm tra accessibility bằng axe-core.

### Liên Hệ Và Chống Spam

- Form liên hệ có honeypot, token thời gian và rate limit theo IP/email.
- Phát hiện nội dung gửi trùng và chấm điểm spam trước khi vào inbox.
- Admin có inbox để lọc, đọc, ghi chú và trả lời khách hàng.
- Lỗi SMTP không làm mất nội dung liên hệ đã lưu trong database.

### Giỏ Hàng Và Checkout

- Thêm sản phẩm vào giỏ hàng.
- Cập nhật số lượng.
- Xóa sản phẩm khỏi giỏ.
- Áp mã giảm giá/voucher.
- Checkout theo 2 bước: kiểm tra thông tin giao hàng và chọn phương thức thanh toán.
- Tính phí giao hàng theo khu vực.
- Đặt hàng COD.
- Đặt hàng chuyển khoản ngân hàng.
- Tích hợp MoMo sandbox.
- Tạo đơn hàng, tạo order items, trừ tồn kho, ghi inventory movement.
- Trang cảm ơn sau đặt hàng.

### Đơn Hàng, Tự Động Hóa Và Email

- Trạng thái đơn hàng: chờ xác nhận, đã xác nhận, đang giao, hoàn tất, đã hủy.
- Khách hàng có thể hủy đơn theo điều kiện.
- Admin duyệt/từ chối yêu cầu hủy đơn.
- Gửi email khi đơn hàng được xác nhận.
- Gửi email khi đơn hàng bị hủy/hủy thành công.
- Cảnh báo tồn kho thấp.
- Cấu hình tự động hủy đơn chuyển khoản quá hạn.
- Ghi log/audit cho các thay đổi quan trọng.

### Admin

- Dashboard tổng quan đơn hàng, doanh thu, khách hàng, tồn kho thấp.
- Quản lý sản phẩm đầy đủ: tạo, sửa, xóa mềm, khôi phục, ảnh, giá, tồn kho và lịch sử điều chỉnh.
- Quản lý đơn hàng theo state machine: xác minh thanh toán, phí giao hàng, vận chuyển, hủy, đổi trả và hoàn tiền.
- Quản lý khách hàng: hồ sơ, trạng thái tài khoản, phiên đăng nhập, xác thực email và cấp voucher.
- Quản lý coupon/voucher: quy tắc tài chính, quà tặng, phân phối, lịch sử sử dụng và tồn kho quà.
- Báo cáo lấy từ dữ liệu thật: doanh thu, tăng trưởng, AOV, trạng thái, phương thức thanh toán, khung giờ đặt hàng và top sản phẩm; hỗ trợ xuất CSV.
- Cài đặt vận hành từ database: thông tin cửa hàng, phí giao hàng, phương thức thanh toán, email đơn hàng và cảnh báo tồn kho.
- Quản trị danh mục, banner và trang nội dung; nội dung HTML được làm sạch trước khi hiển thị.
- Tìm kiếm toàn hệ thống và trung tâm tác vụ theo đúng phạm vi quyền của nhân viên.
- RBAC gồm `super_admin`, `manager`, `catalog`, `fulfillment`, `support`, `analyst`.
- Bảo mật quản trị: mật khẩu tạm bắt buộc đổi, thu hồi phiên, TOTP 2FA, mã khôi phục dùng một lần và audit log.

## Cấu Trúc Thư Mục

```text
fruitshop/
├── app/                    # Controllers, Models, Services, Notifications
├── config/                 # Cấu hình Laravel và shop
├── database/               # Migrations, seeders
├── public/                 # Entry public/index.php, assets, uploads public
├── resources/views/        # Blade templates FE/Admin
├── routes/web.php          # Web routes
├── scripts/                # Audit frontend và chuẩn bị database E2E
├── tests/                  # PHPUnit feature/unit và Playwright E2E
├── docker/                 # Apache/PHP/MySQL helper files
├── playwright.config.mjs
├── vite.config.js
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Yêu Cầu Khi Chạy Local Không Docker

- PHP 8.2+
- Composer
- MySQL 8
- Node.js 22 và npm
- AMPPS/XAMPP hoặc webserver có document root trỏ vào `public/`

## Cài Đặt Local Không Docker

```powershell
cd fruitshop
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Mở trình duyệt:

```text
http://127.0.0.1:8000
```

Nếu chạy bằng AMPPS/XAMPP, hãy đảm bảo `APP_URL` trong `.env` đúng với đường dẫn đang mở trên trình duyệt.

## Chạy Bằng Docker

Trước khi chạy lệnh Docker, hãy mở Docker Desktop và chờ Docker Engine chạy xong. Nếu gặp lỗi kiểu `failed to connect to the docker API at npipe... dockerDesktopLinuxEngine`, nghĩa là Docker Desktop đang tắt hoặc chưa khởi động xong.

### 1. Tạo file môi trường Docker

```powershell
copy .env.docker.example .env
```

Nếu đang dùng `.env` cho AMPPS, hãy backup lại trước:

```powershell
copy .env .env.backup
copy .env.docker.example .env
```

### 2. Build và start container

```powershell
docker compose up -d --build
```

### 3. Chạy migration và seed admin

```powershell
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

### 4. Mở ứng dụng

- Website: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- Mailpit: http://localhost:8025
- MySQL host từ máy thật: `127.0.0.1:3307`

Thông tin MySQL mặc định trong Docker:

```text
Database: fruitshop
Username: fruitshop
Password: fruitshop
Root password: root
```

Trước khi chạy seeder, bắt buộc cấu hình tài khoản admin trong `.env`:

```dotenv
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=MatKhauManh#DaiHon12KyTu
```

Seeder không còn mật khẩu mặc định và sẽ dừng nếu mật khẩu thiếu chữ hoa, chữ thường, số, ký tự đặc biệt hoặc ngắn hơn 12 ký tự. Không commit hai giá trị này lên Git.

### 5. Import database hiện tại từ phpMyAdmin/AMPPS vào Docker

Nếu muốn Docker có đầy đủ sản phẩm/ảnh/danh mục giống máy hiện tại, export database `web_traicay` từ phpMyAdmin thành file `.sql`, sau đó import vào MySQL container.

PowerShell:

```powershell
Get-Content .\database\backup.sql | docker compose exec -T mysql mysql -ufruitshop -pfruitshop fruitshop
```

Bash:

```bash
docker compose exec -T mysql mysql -ufruitshop -pfruitshop fruitshop < database/backup.sql
```

Nếu chỉ chạy `migrate --seed`, database sẽ có schema và admin user, nhưng dữ liệu sản phẩm mẫu có thể không đầy đủ như database đang làm trên AMPPS.

### 6. Build asset trong Docker

Chạy Vite development server:

```powershell
docker compose --profile assets run --rm node npm run dev
```

Bản production:

```powershell
docker compose --profile assets run --rm node npm run build
```

## Lệnh Docker Hay Dùng

```powershell
docker compose ps
docker compose logs -f app
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:list
docker compose exec app php artisan test
docker compose down
```

Xóa cả volume database Docker:

```powershell
docker compose down -v
```

## Kiểm Thử

### Backend

```powershell
& 'C:\Program Files\Ampps\php82\php.exe' artisan test
```

Hoặc trong Docker:

```powershell
docker compose exec app php artisan test
```

### Frontend, hiệu năng và accessibility

```powershell
npm run build
npm run audit:frontend
```

Audit mở trang desktop/mobile bằng Chromium, kiểm tra request lỗi, ảnh hỏng, tràn ngang và vi phạm accessibility bằng axe-core.

### Audit catalog và tối ưu ảnh

Kiểm tra ảnh, giá, tồn kho, đơn vị bán, mô tả và metadata của toàn bộ catalog:

```powershell
php artisan catalog:audit --json=storage/app/catalog-audit.json
```

Tạo ảnh WebP từ ảnh JPEG/PNG thuộc quyền kiểm soát của dự án, sau đó ưu tiên ảnh local làm ảnh đại diện sản phẩm:

```powershell
php artisan media:optimize --path=images/products_synced --quality=80
php artisan catalog:audit --fix-safe --json=storage/app/catalog-audit-after.json
```

`--fix-safe` chỉ chuẩn hóa dữ liệu xác định được như SKU, metadata và đường dẫn ảnh local. Lệnh không tự đổi giá, tồn kho, danh mục hoặc trạng thái hiển thị. Các cảnh báo `price_outlier`, `active_out_of_stock` và ảnh gallery bên thứ ba phải được người quản trị duyệt theo nghiệp vụ.

### Kiểm tra email trên hosting

Sau khi điền SMTP thật trong `.env` của hosting, xóa cache cấu hình và kiểm tra trước khi gửi:

```powershell
php artisan config:clear
php artisan mail:check
php artisan mail:check --send=dia-chi-nhan-thu@example.com
```

Lệnh đầu kiểm tra mailer, địa chỉ/tên người gửi, SMTP và `APP_URL` mà không hiển thị mật khẩu. Lệnh có `--send` mới gửi một email thật; cần xác nhận thư trong cả Inbox và Spam. Trên production phải dùng `APP_URL` HTTPS công khai, email theo tên miền của cửa hàng và cấu hình SPF, DKIM, DMARC tại nhà cung cấp DNS/mail.

### Checkout end-to-end

Lần đầu cài Chromium cho Playwinset-inline-end:

```powershell
npx playwright install chromium
```

Chạy luồng thật từ đăng nhập đến đặt đơn COD:

```powershell
npm run test:e2e
```

Test tự tạo database `<DB_DATABASE>_e2e`, chạy mới toàn bộ migration, seed user/sản phẩm/địa chỉ riêng, rồi kiểm tra đăng nhập, thêm giỏ, checkout, tạo đơn, trang cảm ơn và giỏ hàng rỗng. Script từ chối chạy nếu tên database không có hậu tố `_e2e`, xác nhận kết nối trước `migrate:fresh` và kiểm tra database chính không thay đổi trước/sau test.

## Biến Môi Trường Quan Trọng

- `APP_URL`: URL ứng dụng, cần đúng để link email reset password/chia sẻ ảnh hoạt động.
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: kết nối MySQL.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`: gửi email.
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`: Google OAuth.
- `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY`: MoMo sandbox.
- `SHOP_MOMO_EXPIRE_MINUTES`: thời gian chờ thanh toán trước khi tự hủy đơn MoMo, mặc định 30 phút.
- `SHOP_*`: cấu hình shop, shipping, email, return/refund, auto confirm.
- `ANALYTICS_ENABLED`, `GOOGLE_ANALYTICS_ID`: bật analytics theo consent.
- `E2E_DB_DATABASE`: tên database kiểm thử tùy chọn, bắt buộc kết thúc bằng `_e2e`.

## Scheduler

Các tác vụ tự hủy đơn và cảnh báo tồn kho chỉ chạy khi Laravel scheduler hoạt động. Ở local có thể chạy:

```powershell
php artisan schedule:work
```

Khi deploy Linux, cấu hình cron gọi `php artisan schedule:run` mỗi phút. Lệnh `shop:cancel-expired-momo-orders` chạy mỗi 5 phút, dùng khóa database và chỉ hủy đơn MoMo còn chưa thanh toán.

## Việc Cần Làm Trước Production Thật

- Ảnh đại diện đã có biến thể WebP local cho phần lớn catalog; tiếp tục chuyển toàn bộ ảnh gallery còn hotlink sang storage/CDN do cửa hàng sở hữu sau khi xác nhận quyền sử dụng.
- Duyệt nghiệp vụ 4 sản phẩm đang hết tồn để nhập thêm hoặc chủ động ẩn khỏi storefront.
- Chuyển email nặng sang queue worker khi deploy thật.
- Thiết lập CI chạy PHPUnit, build frontend, audit và Playwright trên mỗi pull request.
- Chuẩn hóa backup/restore tự động, log tập trung và giám sát lỗi production.

## Bảo Mật

- Không commit file `.env`.
- Seeder admin bắt buộc nhận `ADMIN_EMAIL` và `ADMIN_PASSWORD` mạnh từ môi trường; dự án không chứa mật khẩu admin mặc định.
- Không đưa Google/MoMo/Mail credentials thật lên GitHub.
- Dùng `APP_DEBUG=false` trên production.
- Chạy `php artisan config:cache` và `php artisan route:cache` khi deploy ổn định.

## License

Project được xây dựng cho mục đích học tập, portfolio và phỏng vấn intern PHP.
