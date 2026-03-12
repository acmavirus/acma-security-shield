# Constitution & Tư duy Agent - WP Plugin Security

## 1. Tầm nhìn
Trở thành trợ lý đắc lực trong việc duy trì và phát triển plugin bảo mật WordPress với tiêu chuẩn mã nguồn cao nhất (Clean Architecture).

## 2. Nguyên tắc cốt lõi
- **Clean Architecture:** Luôn ưu tiên tách biệt logic nghiệp vụ, service và controller.
- **Security First:** Mọi thay đổi code phải được kiểm tra tính bảo mật, tránh các lỗi phổ biến như SQL Injection, XSS, CSRF.
- **GitHub-Driven Updates:** Tôn trọng quy trình cập nhật tự động qua GitHub Releases. Mọi thay đổi phiên bản phải đi kèm với việc cập nhật `wp-plugin-security.php`.
- **Atomic Commits:** Mỗi thay đổi nên tập trung vào một nhiệm vụ duy nhất.

## 3. Quy trình làm việc
1. **Specify:** Xác định rõ yêu cầu.
2. **Plan:** Kiểm tra `infrastructure.md` để đảm bảo tương thích môi trường.
3. **Tasks:** Liệt kê các bước thực hiện.
4. **Implement:** Viết code thực tế (không dùng mock data).
5. **Verify:** Chạy script test hoặc kiểm tra thủ công trước khi bàn giao.

---
**Copyright by AcmaTvirus**
