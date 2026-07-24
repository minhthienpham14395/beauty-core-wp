# Beauty Core – WordPress theme

Đây là custom theme WordPress thuần PHP, giữ lại bố cục, màu sắc, hình ảnh, nội dung blog, bảng giá, FAQ, floating contact và trợ lý AI của project gốc.

## Cài đặt

1. Chép project này vào `wp-content/themes/beautycore` trong WordPress.
2. Kích hoạt theme **Beauty Core** tại `Appearance → Themes`.
3. Vào `Settings → Permalinks` và bấm **Save Changes** nếu các URL blog chưa hoạt động.

Khi kích hoạt lần đầu, theme tự tạo các trang chính sách, trang giới thiệu/liên hệ/FAQ và import 13 bài viết từ `content/blog` vào Custom Post Type `Bài viết`. Bảng giá và nội dung trang chủ được quản lý bằng code trong `inc/site-data.php`.

## Chạy bằng Docker

Yêu cầu Docker Desktop đang chạy. Có thể tạo file `.env` từ `.env.example` để đổi port, thông tin database hoặc API key, sau đó chạy:

```bash
docker compose up -d
```

Sau khi thay đổi `Dockerfile` hoặc cấu hình Apache, chạy `docker compose up -d --build`.

Mở `http://localhost:8080`, hoàn tất cài đặt WordPress, kích hoạt theme **Beauty Core**, rồi vào `Settings → Permalinks → Save Changes`.

Dừng container nhưng giữ dữ liệu:

```bash
docker compose down
```

Xóa cả database và dữ liệu WordPress:

```bash
docker compose down -v
```

## Trợ lý AI

Thêm API key vào `wp-config.php`:

```php
define('BEAUTYCORE_GEMINI_API_KEY', 'your-api-key');
```

Nếu không cấu hình key, các nút liên hệ và đặt lịch vẫn hoạt động; phần trả lời AI sẽ báo chưa được cấu hình.

## Cấu trúc chính

- `front-page.php`: trang chủ và các section giao diện.
- `Dockerfile`: image WordPress có bật Apache rewrite để URL `/blog/` hoạt động.
- `page-*.php`: giới thiệu, dịch vụ, liên hệ và FAQ.
- `archive-beautycore_blog.php`, `single-beautycore_blog.php`, `taxonomy-beautycore_category.php`: blog và danh mục.
- `inc/content.php`: Custom Post Type, seed nội dung và render Markdown.
- `assets/css/site.css`, `assets/js/theme.js`: style và hành vi giao diện.
- `public/images`, `public/videos`: tài nguyên hình ảnh/video gốc.

## Import nội dung vào wp-admin

Khi kích hoạt theme, Beauty Core tự động đồng bộ 13 bài viết, toàn bộ ảnh và video trong `public` vào WordPress. Bài viết xuất hiện trong menu **Bài viết**, còn media nằm trong **Media → Library**.

Muốn chạy lại import, vào **Tools → Beauty Core Import** trong wp-admin.
