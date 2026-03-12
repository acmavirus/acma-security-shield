---
description: Quy trình kiểm tra và chuẩn bị phát hành bản cập nhật mới
---

# Workflow: Kiểm tra Cập nhật (Update Check)

Quy trình này giúp Agent và Developer đảm bảo plugin sẵn sàng cho việc phát hành bản mới trên GitHub.

## Các bước thực hiện:

1. **Kiểm tra phiên bản hiện tại:**
   - Mở file `wp-plugin-security.php`.
   - Xác định giá trị `Version:`.

2. **Kiểm tra phiên bản trên GitHub:**
   - Truy vấn API: `https://api.github.com/repos/acmavirus/wp-plugin-security/releases/latest`.
   - Lấy giá trị `tag_name`.

3. **Cập nhật Local (nếu chuẩn bị release):**
   - Nếu cần phát hành bản mới, hãy cập nhật `Version` trong:
     - `wp-plugin-security.php`
     - `README.md` (nếu có phần changelog/version).

4. **Kiểm tra tính tương thích của UpdateService:**
   - Đảm bảo `UpdateService.php` đang trỏ đúng `username` và `repository`.
   - Đảm bảo các hooks trong `UpdateController.php` được khởi tạo.

5. **Test giải nén (Source Selection):**
   - Đảm bảo logic trong `fix_source_selection` có thể xử lý các định dạng thư mục từ GitHub (ví dụ: `wp-plugin-security-master` hoặc `wp-plugin-security-3.0.11`).

// turbo
6. **Xác nhận sạch sẽ:**
   - Chạy `composer install --no-dev` để kiểm tra dependencies.
   - Kiểm tra log WordPress để đảm bảo không có lỗi `WP_Error` khi gọi GitHub API.

---
**Copyright by AcmaTvirus**
