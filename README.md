**Fruitshop — backend Laravel cho cửa hàng trái cây**

Ngắn gọn: dự án `fruitshop` là một backend e‑commerce nhỏ xây bằng Laravel, dùng MySQL làm dữ liệu chính, phù hợp để chạy cục bộ (AMPPS/XAMPP) hoặc deploy lên server PHP.

**Tính năng chính**
- Quản lý sản phẩm, hình ảnh sản phẩm
- Danh mục, giỏ hàng, đơn hàng, trạng thái đơn
- Coupon, voucher, kho và lịch sử thay đổi tồn kho
- Đăng ký/đăng nhập người dùng và địa chỉ giao hàng
- API cơ bản cho frontend và tích hợp view blade cho demo

**Badges (gợi ý)**
- License: MIT

**Yêu cầu**
- PHP >= 8.0
- Composer
- MySQL
- Node.js & npm (để build assets)
- AMPPS/XAMPP hoặc webserver + php-fpm

**Cài đặt nhanh (local)**
1. Clone repository:

	git clone <repo-url> fruitshop
	cd fruitshop

2. Cài phụ thuộc PHP & JS:

	composer install --no-interaction --prefer-dist
	npm install

3. Tạo file môi trường:

	copy .env.example .env
	php artisan key:generate

4. Cấu hình database trong `.env` (DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).

5. Chạy migrate và seed (nếu cần dữ liệu mẫu):

	php artisan migrate --seed

6. Tạo symbolic link cho storage:

	php artisan storage:link

7. Build assets (development):

	npm run dev

8. Chạy server local:

	php artisan serve --host=127.0.0.1 --port=8000

Mở trình duyệt: http://127.0.0.1:8000

Ghi chú: trên môi trường AMPPS, cấu hình virtual host hoặc copy vào `www` và mở qua http://localhost/<folder>.

**Chạy test**

	./vendor/bin/phpunit
	hoặc
	php artisan test

**Các lệnh hữu ích**
- `php artisan migrate:fresh --seed` — reset DB và seed lại
- `php artisan queue:work` — chạy worker queue
- `php artisan config:clear && php artisan cache:clear` — làm mới cache cấu hình

**Môi trường & cấu hình**
File mẫu môi trường: `.env.example`. Những biến quan trọng:
- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_*` — kết nối database
- `MAIL_*` — cấu hình gửi mail

**Triển khai (gợi ý)**
- Sử dụng composer install, migrate, config:cache, storage:link trên server.
- Thiết lập cron cho queue/cronjobs nếu cần.

**Đóng góp**
- Fork → tạo branch tính năng → PR với mô tả rõ ràng.
- Mô tả issue nếu phát hiện bug hoặc đề xuất tính năng.

**Bảo mật**
- Không commit file `.env` chứa credentials.

**License**
This project is licensed under the MIT License.

---

Nếu bạn muốn, tôi có thể: thêm badges (CI, coverage), viết README tiếng Anh, hoặc chuẩn hoá `CONTRIBUTING.md` cùng mẫu PR/Issue. Chọn bước bạn muốn tiếp theo.
