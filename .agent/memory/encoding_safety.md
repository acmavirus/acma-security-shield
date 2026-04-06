# Encoding Safety Note

## Vấn đề đã thấy trong repo
- Nhiều file tài liệu và một số chuỗi giao diện có dấu hiệu mojibake do UTF-8 bị đọc/ghi qua encoding không phù hợp.

## Nguyên tắc sửa
1. Luôn giữ file source và tài liệu ở UTF-8.
2. Sửa text bằng `apply_patch` hoặc editor UTF-8-safe.
3. Không round-trip nội dung PHP/Markdown qua ANSI/CP1252.
4. Nếu chỉ là tài liệu, sửa trực tiếp phần bị lỗi thay vì thay toàn bộ file bằng cách đoán lại encoding.

## Dấu hiệu cần kiểm tra
- `Ã`, `�`, `Â`, `Ä`, `Å`, `Ã©`, `Ãª`, `Ã¹`.
- Chuỗi tiếng Việt bị biến thành ký tự rời hoặc dấu hỏi.

## Checklist sau khi sửa
- Mở lại file để xác nhận văn bản đọc tự nhiên.
- Nếu sửa PHP, chạy `php -l`.
- Nếu thay UI string, kiểm tra thêm opcache hoặc cache trình duyệt nếu nội dung vẫn hiển thị cũ.

## Ghi nhớ
- Không normalize Unicode không liên quan.
- Không sửa lan ra toàn bộ repo khi chỉ một file bị lỗi.
- Ưu tiên phục hồi đúng nội dung gốc của plugin hơn là “viết lại cho đẹp” mà làm lệch ý nghĩa.
