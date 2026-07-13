# Thế Giới Trái Cây - Laravel Ecommerce

Dự án website bán trái cây theo hướng ecommerce, xây dựng bằng Laravel, MySQL và Blade. Project tập trung vào các luồng thật của một website bán hàng: xem sản phẩm, tìm kiếm, giỏ hàng, checkout, thanh toán, quản lý tài khoản, quản lý đơn hàng và khu vực admin.

## Mục Tiêu Dự Án

Project này được xây dựng để đưa vào portfolio/CV intern PHP. Điểm mạnh không chỉ nằm ở giao diện storefront, mà còn ở việc xử lý backend ecommerce: authentication, database schema, order workflow, inventory, voucher, email, thanh toán sandbox và admin dashboard.

## Tech Stack

- Backend: PHP, Laravel 8
- Frontend: Blade, CSS, JavaScript, Laravel Mix
- Database: MySQL
- Authentication: Laravel session auth, Google OAuth qua Socialite
- Payment: COD, chuyển khoản ngân hàng, MoMo sandbox
- Mail: SMTP/Mailpit cho local Docker
- DevOps: Docker, Docker Compose, Apache, phpMyAdmin
- Testing: PHPUnit / `php artisan test`

## Chức Năng Đã Xây Dựng

### Khách Hàng

- Đăng ký, đăng nhập, đăng xuất.
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
- Quản lý đơn hàng: cập nhật trạng thái, phí ship, duyệt hủy đơn.
- Quản lý coupon/voucher: tạo, sửa, kích hoạt, gán voucher.
- Quản lý sản phẩm đã nối DB thật:
  - Đọc sản phẩm từ MySQL.
  - Lọc theo tên/SKU/slug, danh mục, trạng thái, tồn kho.
  - Thống kê tổng sản phẩm, đang hiển thị, tạm ẩn, sắp hết hàng, hết hàng.
  - Phân trang.
  - Ẩn/hiện sản phẩm trên storefront.

## Cấu Trúc Thư Mục

```text
fruitshop/
├── app/                    # Controllers, Models, Services, Notifications
├── config/                 # Cấu hình Laravel và shop
├── database/               # Migrations, seeders
├── public/                 # Entry public/index.php, assets, uploads public
├── resources/views/        # Blade templates FE/Admin
├── routes/web.php          # Web routes
├── tests/                  # PHPUnit tests
├── docker/                 # Apache/PHP/MySQL helper files
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Yêu Cầu Khi Chạy Local Không Docker

- PHP 8.0+
- Composer
- MySQL
- Node.js và npm nếu cần build asset
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
npm run dev
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

Nếu cần build CSS/JS bằng Laravel Mix:

```powershell
docker compose --profile assets run --rm node npm run dev
```

Bản production:

```powershell
docker compose --profile assets run --rm node npm run prod
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

```powershell
php artisan test
```

Hoặc trong Docker:

```powershell
docker compose exec app php artisan test
```

Hiện tại bộ test mẫu đã pass. Các test cần bổ sung tiếp để project đạt điểm cao hơn:

- Auth/register/login/reset password.
- Cart/add/update/remove coupon.
- Checkout/place order.
- Admin product filter/visibility.
- Order cancellation/return workflow.

## Biến Môi Trường Quan Trọng

- `APP_URL`: URL ứng dụng, cần đúng để link email reset password/chia sẻ ảnh hoạt động.
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: kết nối MySQL.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`: gửi email.
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`: Google OAuth.
- `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY`: MoMo sandbox.
- `SHOP_MOMO_EXPIRE_MINUTES`: thời gian chờ thanh toán trước khi tự hủy đơn MoMo, mặc định 30 phút.
- `SHOP_*`: cấu hình shop, shipping, email, return/refund, auto confirm.

## Scheduler

Các tác vụ tự hủy đơn và cảnh báo tồn kho chỉ chạy khi Laravel scheduler hoạt động. Ở local có thể chạy:

```powershell
php artisan schedule:work
```

Khi deploy Linux, cấu hình cron gọi `php artisan schedule:run` mỗi phút. Lệnh `shop:cancel-expired-momo-orders` chạy mỗi 5 phút, dùng khóa database và chỉ hủy đơn MoMo còn chưa thanh toán.

## Roadmap Để Đạt Mức 9/10

- Hoàn thiện CRUD sản phẩm admin: thêm/sửa/xóa mềm, upload ảnh, sửa giá, sửa tồn kho.
- Admin customers: danh sách khách hàng, tổng chi tiêu, đơn gần nhất, khóa/mở tài khoản.
- Admin reports: doanh thu, top sản phẩm, coupon usage, tồn kho thấp, tỷ lệ hủy đơn.
- Admin settings: thông tin shop, phí ship, ngân hàng, cấu hình thanh toán.
- Viết feature tests cho các luồng ecommerce chính.
- Chuyển email nặng sang queue worker khi deploy thật.
- Thêm sitemap/robots/SEO metadata cho product/category.
- Chuẩn hóa backup/restore database và tài liệu deploy production.

## Bảo Mật

- Không commit file `.env`.
- Seeder admin bắt buộc nhận `ADMIN_EMAIL` và `ADMIN_PASSWORD` mạnh từ môi trường; dự án không chứa mật khẩu admin mặc định.
- Không đưa Google/MoMo/Mail credentials thật lên GitHub.
- Dùng `APP_DEBUG=false` trên production.
- Chạy `php artisan config:cache` và `php artisan route:cache` khi deploy ổn định.

## License

Project được xây dựng cho mục đích học tập, portfolio và phỏng vấn intern PHP.
