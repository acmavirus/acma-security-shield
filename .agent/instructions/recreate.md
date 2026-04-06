# Hướng dẫn tái lập `WP Plugin Security`

Tài liệu này mô tả cách tái dựng plugin theo đúng mã nguồn hiện tại. Không dựa vào mô tả cũ của một plugin khác.

## 1. Bootstrap
- File entry: `wp-plugin-security.php`.
- Khai báo plugin header chuẩn WordPress.
- Định nghĩa `WPS_PLUGIN_FILE`.
- Load `vendor/autoload.php` nếu tồn tại.
- Khởi chạy `Acma\\WpSecurity\\Plugin::instance()->run()`.

## 2. Kiến trúc lõi
- Namespace chính: `Acma\\WpSecurity`.
- Lõi khởi động: `src/Plugin.php`.
- Controllers nằm trong `src/Controllers`.
- Services nằm trong `src/Services`.
- Giao diện admin nằm trong `src/Views`.

## 3. Nhóm chức năng hiện có
- **Security**: hardening, login protection, blacklist, audit, 404 monitoring.
- **Monitoring**: quét integrity, malware, vulnerability, quarantine, restore, baseline.
- **Performance**: disable emojis, block library CSS, dashicons, preload hints, CDN rewrite, HTML cleanup.
- **SEO**: TOC, auto featured image, SEO AI, SEO content rewrite.
- **Editor/Updates**: disable block editor, TinyMCE, block core/plugin/theme updates.
- **Google**: Indexing API, Google login, reCAPTCHA.
- **Email/Notifications**: SMTP, mail sender, notification bar.
- **Users**: isolation, local avatar, user ID column.
- **WooCommerce**: text overrides, price-zero text, Telegram order alert.
- **Marketing/Tools**: chat bubble, code injection, redirects, search/replace, emergency tools, changelog.

## 4. Admin UI
- Admin page là một shell tabbed interface động tại `src/Views/admin-page.php`.
- Tab hiện có:
  - `general`
  - `login`
  - `blacklist`
  - `audit`
  - `monitoring`
  - `speed`
  - `updates`
  - `seo`
  - `seo_ai`
  - `seo_content_ai`
  - `editor`
  - `google`
  - `email`
  - `users`
  - `woocommerce`
  - `marketing`
  - `tools`
  - `changelog`

## 5. Dữ liệu cấu hình
- Option chính: `wps_main_settings`.
- Option liên quan:
  - `wps_blocked_ips`
  - `wps_audit_logs`
  - `wps_security_logs`
  - `wps_monitoring_quarantine_map`
  - `wps_monitoring_last_cron_scan`
- Mọi input admin phải qua nonce, capability check, và sanitization đúng loại dữ liệu.

## 6. Khi tái tạo hoặc refactor
- Giữ tên hook và option nhất quán để không phá tương thích dữ liệu cũ.
- Không chèn luồng tracking hoặc gửi dữ liệu ra ngoài nếu không có cấu hình rõ ràng từ người dùng.
- Với tính năng gọi dịch vụ ngoài, phải làm rõ nguồn dữ liệu, mục đích, và trạng thái bật/tắt.
- Khi thay đổi admin UI, giữ logic tab và nhóm tab như hiện tại để tránh lệch trải nghiệm.
