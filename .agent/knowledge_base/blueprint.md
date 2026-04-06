# Blueprint kiến trúc `WP Plugin Security`

Tài liệu này phản ánh cấu trúc thực tế của plugin ở thời điểm hiện tại.

## 1. Entry point
- `wp-plugin-security.php`
- Nhiệm vụ:
  - chặn truy cập trực tiếp,
  - khai báo hằng plugin,
  - nạp autoloader Composer,
  - bootstrap lớp `Acma\\WpSecurity\\Plugin`.

## 2. Lõi khởi động
- `src/Plugin.php`
- Nhiệm vụ:
  - nạp textdomain,
  - khởi tạo các controller chính,
  - bảo đảm plugin hoạt động theo mô hình singleton.

## 3. Controllers
- `src/Controllers/AdminController.php`
  - tạo trang quản trị,
  - đăng ký settings,
  - render shell UI và các tab.
- `src/Controllers/SecurityController.php`
  - hardening, login protection, headers, audit hooks.
- `src/Controllers/FeatureController.php`
  - tối ưu frontend, TOC, preload, CDN rewrite, featured image, editor/update controls.
- `src/Controllers/IntegrationController.php`
  - SMTP, notification bar, code injection, redirects, login integrations, Google, WooCommerce.
- `src/Controllers/MonitoringController.php`
  - cron scan, integrity/malware/vulnerability scan, quarantine, restore.
- `src/Controllers/SeoAiController.php`
  - metabox SEO AI, Rank Math sync, Gemini/Bulk scan.
- `src/Controllers/SeoContentController.php`
  - rewrite nội dung theo AI khi lưu bài và bulk queue/process.
- `src/Controllers/UpdateController.php`
  - kiểm tra cập nhật plugin và metadata của release.
- `src/Controllers/UserController.php`
  - user isolation, local avatar, user ID column, 2FA/profile helpers.

## 4. Services
- `src/Services/SecurityService.php`
  - đọc option, log sự kiện, hỗ trợ logic security runtime.
- `src/Services/AuditService.php`
  - ghi và truy xuất audit logs.
- `src/Services/UpdateService.php`
  - truy vấn version từ nguồn remote và hỗ trợ update flow.

## 5. Views
- `src/Views/admin-page.php`
  - layout chính của admin dashboard.
- `src/Views/admin/tabs/*.php`
  - mỗi tab là một view riêng, render theo tên tab.

## 6. Tab map hiện tại
- `general`: hệ thống & WAF.
- `login`: đăng nhập.
- `blacklist`: danh sách chặn IP.
- `audit`: nhật ký kiểm tra.
- `monitoring`: giám sát.
- `speed`: tốc độ.
- `updates`: cập nhật.
- `seo`: SEO & mục lục.
- `seo_ai`: SEO AI.
- `seo_content_ai`: SEO Content AI.
- `editor`: trình soạn thảo.
- `google`: Google.
- `email`: Email.
- `users`: người dùng.
- `woocommerce`: WooCommerce.
- `marketing`: marketing và helper.
- `tools`: công cụ khẩn cấp.
- `changelog`: nhật ký thay đổi.

## 7. Storage model
- Cấu hình chính nằm trong `wps_main_settings`.
- Dữ liệu runtime bổ sung nằm trong các option chuyên biệt như `wps_blocked_ips`, `wps_audit_logs`, `wps_security_logs`, `wps_monitoring_quarantine_map`.
- Dữ liệu lớn nên được serialize bằng option thay vì tạo bảng mới trừ khi có yêu cầu thiết kế rõ ràng.

## 8. Ghi chú kiến trúc
- Đây là plugin có phạm vi rộng: security, performance, SEO, integrations, user management, WooCommerce.
- Không còn cấu trúc `inc/`, `main/`, `modal/`, `link/` như tài liệu cũ.
- Không nên giữ các tên hook hoặc option giả không tồn tại trong code hiện tại.
