# Infrastructure - WP Plugin Security

## 1. Môi trường Thực thi (Runtime Environment)
- **Hệ điều hành:** Windows (Laragon)
- **Web Server:** Apache/Nginx (thông qua Laragon)
- **PHP:** >= 7.4 (Quản lý qua Composer)
- **WordPress:** Phiên bản mới nhất được khuyến nghị.

## 2. Cấu trúc Dự án (Project Structure)
- `wp-plugin-security.php`: File entry point chính của plugin. Định nghĩa phiên bản và khởi tạo class `Plugin`.
- `src/`: Chứa mã nguồn logic theo PSR-4 (Namespace: `Acma\WpSecurity`).
    - `Controllers/`: Xử lý các hooks của WordPress và điều phối dữ liệu.
    - `Services/`: Chứa logic nghiệp vụ cốt lõi (Bảo mật, Audit, Cập nhật).
    - `Views/`: (Nếu có) Chứa các template hiển thị.
- `vendor/`: Thư mục dependencies do Composer quản lý.

## 3. Cơ chế Cập nhật (Update Mechanism)
Plugin sử dụng hệ thống cập nhật tự động tùy chỉnh thông qua GitHub Releases:
- **Service:** `Acma\WpSecurity\Services\UpdateService`
- **Controller:** `Acma\WpSecurity\Controllers\UpdateController`
- **Quy trình hoạt động:**
    1. Hook vào filter `pre_set_site_transient_update_plugins` để kiểm tra bản phát hành mới nhất từ API GitHub: `https://api.github.com/repos/acmavirus/wp-plugin-security/releases/latest`.
    2. So sánh tag `tag_name` (lọc bỏ tiền tố 'v') với phiên bản hiện tại trong file plugin.
    3. Nếu có bản mới, cung cấp link tải `.zip` từ GitHub Assets hoặc Zipball.
    4. Sử dụng filter `upgrader_source_selection` để xử lý việc đổi tên thư mục giải nén từ GitHub (thường có hậu tố hash/version) về đúng slug của plugin.
    5. Cung cấp thông tin plugin cho WordPress qua filter `plugins_api`.

## 4. Công cụ & Lệnh hữu ích
- **Cài đặt dependencies:** `composer install`
- **Cập nhật Autoloader:** `composer dump-autoload`
- **Dải Port đề xuất:** 8900-8999 (theo quy tắc Agent).

---
**Copyright by AcmaTvirus**
