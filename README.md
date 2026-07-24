# Beauty Core – WordPress theme

Đây là custom theme WordPress thuần PHP, giữ lại bố cục, màu sắc, hình ảnh, nội dung blog, bảng giá, FAQ, floating contact và trợ lý AI của project gốc.

## Cài đặt

1. Chép `src/wp-content/themes/beautycore` vào `wp-content/themes/beautycore` trong WordPress.
2. Kích hoạt theme **Beauty Core** tại `Appearance → Themes`.
3. Vào `Settings → Permalinks` và bấm **Save Changes** nếu các URL blog chưa hoạt động.

Khi kích hoạt lần đầu, theme tự tạo các trang chính sách, trang giới thiệu/liên hệ/FAQ và import 13 bài viết từ `content/blog` vào Custom Post Type `Bài viết`. Bảng giá và nội dung trang chủ được quản lý bằng code trong `inc/site-data.php`.

## Chạy bằng Docker và mang sang máy khác

Yêu cầu Docker Desktop đang chạy. Toàn bộ theme, plugin, uploads và database seed được đặt trong repository tại `src/wp-content` và `seed/database`, nên một máy mới có thể khởi tạo lại đúng website.

Tạo file môi trường khi cần đổi URL, port hoặc thông tin đăng nhập:

```bash
cp .env.example .env
```

Sau đó chạy:

```bash
docker compose up -d --build
```

Mở `http://localhost:8081`. Container `wordpress-init` tự import database seed khi volume trống, cài WordPress nếu cần, kích hoạt theme **Beauty Core**, cập nhật URL và flush permalink.

Xuất database hiện tại thành seed để chuyển sang máy khác:

```bash
scripts/export-database.sh
```

Tạo backup có timestamp:

```bash
scripts/backup.sh
```

Database seed có dữ liệu khách hàng, lịch hẹn và tài khoản. Chỉ đưa repository lên nơi lưu trữ private hoặc chuyển bằng kênh bảo mật.

Dừng container nhưng giữ dữ liệu:

```bash
docker compose down
```

Xóa cả database và dữ liệu WordPress:

```bash
docker compose down -v
```

## Trợ lý AI

Thêm API key vào `.env`:

```env
BEAUTYCORE_GEMINI_API_KEY=your-api-key
```

Nếu không cấu hình key, các nút liên hệ và đặt lịch vẫn hoạt động; phần trả lời AI sẽ báo chưa được cấu hình.

## Cấu trúc chính

- `src/wp-content/themes/beautycore/front-page.php`: trang chủ và các section giao diện.
- `Dockerfile`: image WordPress có bật Apache rewrite để URL `/blog/` hoạt động.
- `src/wp-content/themes/beautycore/page-*.php`: giới thiệu, dịch vụ, liên hệ và FAQ.
- `src/wp-content/themes/beautycore/inc/`: nghiệp vụ website và wp-admin.
- `src/wp-content/uploads/`: media do WordPress tạo, được mang sang máy khác.
- `seed/database/001-wordpress.sql`: dữ liệu WordPress được import khi database volume còn trống.
- `scripts/`: export/import database và backup/restore.

## Import nội dung vào wp-admin

Khi kích hoạt theme, Beauty Core tự động đồng bộ 13 bài viết, toàn bộ ảnh và video trong `public` vào WordPress. Bài viết xuất hiện trong menu **Bài viết**, còn media nằm trong **Media → Library**.

Muốn chạy lại import, vào **Tools → Beauty Core Import** trong wp-admin.

## Quản lý lịch hẹn

Vào **Beauty Core → Lịch hẹn** để xem danh sách hoặc calendar ngày/tuần/tháng,
tạo lịch tại quầy, đổi giờ/nhân viên, check-in, hoàn tất, hủy và theo dõi lịch
sử thao tác. Bộ lọc hỗ trợ mã lịch, số điện thoại, ngày, trạng thái, nhân viên
và chi nhánh.

Giờ làm việc, khoảng đệm chống trùng, thời điểm nhắc lịch và ngày nghỉ nhân
viên được cấu hình tại **Beauty Core → Cấu hình**. Cron WordPress gửi nhắc
trước theo cấu hình và bỏ qua lịch đã hủy.

Để thêm form đặt lịch vào một trang website, dùng shortcode:

~~~text
[beautycore_booking_form]
~~~
