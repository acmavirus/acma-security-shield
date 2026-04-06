# Standards cho `WP Plugin Security`

Các quy định dưới đây áp dụng cho mọi thay đổi trong plugin.

## 1. Ngôn ngữ và kiến trúc
- Dùng namespace `Acma\\WpSecurity` theo PSR-4.
- Giữ controller, service, view tách biệt rõ ràng.
- Không quay lại kiến trúc `inc/` kiểu cũ nếu không có yêu cầu migration cụ thể.

## 2. PHP và WordPress
- Mọi input từ `$_GET`, `$_POST`, `$_REQUEST` phải được kiểm tra capability, nonce, và sanitize đúng kiểu dữ liệu.
- Dùng `wp_unslash()` trước khi sanitize chuỗi từ request.
- Ưu tiên `sanitize_text_field`, `sanitize_textarea_field`, `absint`, `sanitize_key`, `esc_url_raw`.
- Khi lưu settings mới, giữ khóa option nhất quán với `wps_main_settings`.
- Hooks phải dùng tên rõ ràng, tránh đụng vào hook không tồn tại.

## 3. Security
- Không thêm luồng ẩn dữ liệu ra ngoài.
- Mọi tích hợp ngoài như Google, SMTP, Telegram, reCAPTCHA, update metadata phải là opt-in hoặc có cấu hình rõ ràng.
- Không bỏ qua capability checks trong trang admin hoặc AJAX.
- Không dùng logic che giấu admin/user ngoài phạm vi tính năng đã được mô tả trong plugin.

## 4. UI và content
- UI admin phải giữ phong cách tabbed dashboard hiện có.
- Tất cả chuỗi giao diện phải là UTF-8 sạch, không mojibake.
- Giữ văn phong tiếng Việt nhất quán trong nhãn và mô tả.

## 5. Option và dữ liệu
- Cấu hình trung tâm: `wps_main_settings`.
- Dữ liệu runtime/nhật ký phải dùng option chuyên biệt phù hợp mục đích.
- Tránh tạo bảng mới nếu option là đủ.

## 6. Kiểm thử và sửa lỗi
- Sau khi chỉnh text hoặc controller, chạy `php -l` cho file đã sửa.
- Khi sửa UI string, kiểm tra lại mã hóa để tránh `Ã`, `�`, hoặc ký tự lỗi.
- Nếu thay đổi hook runtime, xác nhận không phá logic tab admin hoặc AJAX action hiện có.

## 7. Nguyên tắc làm việc
- Code là nguồn sự thật.
- Tài liệu agent phải bám theo source hiện tại, không bám theo changelog tưởng tượng.
- Nếu phát hiện file vừa đúng cú pháp vừa sai encoding, ưu tiên sửa encoding trước khi suy luận lỗi logic.
