<?php
// Copyright by AcmaTvirus
if (!defined('ABSPATH')) exit;
?>

<form method="post" action="">
    <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
    <input type="hidden" name="wps_save_settings" value="1">

    <div class="wps-card">
        <h2 class="title">Tường lửa & Hardening</h2>
        <table class="form-table">
            <tbody>
                <?php
                $general_options = [
                    'disable_xmlrpc' => ['Vô hiệu hóa XML-RPC', 'Chặn tấn công brute-force qua cổng XML-RPC.'],
                    'disable_rest_api' => ['Hạn chế REST API', 'Chỉ cho phép người dùng đã đăng nhập truy cập.'],
                    'block_author_scan' => ['Chặn Author Scan', 'Ngăn bot dò tìm username quản trị viên.'],
                    'disable_directory_browsing' => ['Chặn Directory Browsing', 'Ngăn duyệt file trong các thư mục.'],
                    'disable_file_editor' => ['Tắt trình chỉnh sửa file', 'Vô hiệu hóa sửa code trực tiếp trong Admin.'],
                ];
                foreach ($general_options as $key => $info) :
                ?>
                    <tr>
                        <th scope="row"><?php echo $info[0]; ?></th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" name="<?php echo $key; ?>" <?php checked($main_settings[$key] ?? false); ?>>
                                <span class="description"><?php echo $info[1]; ?></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="wps-card">
        <h2 class="title">Quyền riêng tư & Nhật ký</h2>
        <table class="form-table">
            <tbody>
                <?php
                $privacy_options = [
                    'hide_wp_version' => ['Ẩn phiên bản WP', 'Xóa bỏ dấu hiệu nhận biết từ mã nguồn.'],
                    'enable_security_headers' => ['Security Headers', 'Kích hoạt HSTS, XSS Protection, nosniff...'],
                    'enable_audit_log' => ['Audit Trail', 'Lưu lại mọi hoạt động của người dùng.'],
                    'enable_email_alerts' => ['Thông báo qua Email', 'Gửi cảnh báo ngay khi có sự cố bảo mật.'],
                ];
                foreach ($privacy_options as $key => $info) :
                ?>
                    <tr>
                        <th scope="row"><?php echo $info[0]; ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $key; ?>" <?php checked($main_settings[$key] ?? false); ?>>
                                <span class="description"><?php echo $info[1]; ?></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="p-6 bg-white rounded border border-gray-100 shadow-sm mt-6" style="padding: 15px; background: #fff; border-left: 4px solid #72aee6;">
        <p><strong>Lời khuyên từ AcmaTvirus:</strong> Bật Security Headers giúp bảo vệ website khỏi các cuộc tấn công phổ biến như clickjacking và XSS một cách chủ động.</p>
    </div>

    <?php submit_button('Lưu thiết lập Hệ thống', 'primary', 'submit', true); ?>
</form>
<?php"submit" class="bg-black text-white px-10 py-4 rounded-2xl font-bold text-sm hover:shadow-2xl transition-all active:scale-95">Lưu thiết lập Hệ thống</button>
    </div>
</form>
