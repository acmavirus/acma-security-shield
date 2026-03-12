<?php
// Copyright by AcmaTvirus
if (!defined('ABSPATH')) exit;
?>

<div class="wps-grid">
    <!-- Kill Sessions -->
    <div class="wps-card">
        <h2 class="title"><span class="dashicons dashicons-exit"></span> Đăng xuất toàn bộ</h2>
        <p class="description">Buộc tất cả người dùng (bao gồm cả bạn) phải đăng nhập lại. Hữu ích khi nghi ngờ có thiết bị lạ xâm nhập.</p>
        <form method="post" action="" style="margin-top: 20px;">
            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
            <input type="hidden" name="wps_tool_action" value="kill_sessions">
            <?php submit_button('Đăng xuất tất cả', 'delete', 'submit', false); ?>
        </form>
    </div>

    <!-- Force PW Reset -->
    <div class="wps-card">
        <h2 class="title"><span class="dashicons dashicons-lock"></span> Đặt lại mật khẩu toàn hệ thống</h2>
        <p class="description">Vô hiệu hóa toàn bộ mật khẩu hiện tại. Tất cả người dùng sẽ nhận được yêu cầu đổi mật khẩu mới qua email.</p>
        <form method="post" action="" style="margin-top: 20px;">
            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
            <input type="hidden" name="wps_tool_action" value="force_pw_reset">
            <?php submit_button('Kích hoạt Force Reset', 'secondary', 'submit', false, ['onclick' => "return confirm('Hành động này không thể hoàn tác. Bạn có chắc chắn?')"]); ?>
        </form>
    </div>

    <!-- Clear Logs -->
    <div class="wps-card">
        <h2 class="title"><span class="dashicons dashicons-trash"></span> Dọn dẹp Nhật ký</h2>
        <p class="description">Xóa sạch toàn bộ Audit Trail và Security Logs để giảm dung lượng cơ sở dữ liệu.</p>
        <form method="post" action="" style="margin-top: 20px;">
            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
            <input type="hidden" name="wps_tool_action" value="clear_logs">
            <?php submit_button('Xóa Logs sạch sẽ', 'secondary', 'submit', false); ?>
        </form>
    </div>
</div>

<div class="notice notice-error inline" style="margin-top: 30px; display: block; padding: 20px;">
    <h3 style="margin-top: 0;">Chế độ Khẩn cấp (Panic Button)</h3>
    <p>Khóa toàn bộ truy cập vào website trừ IP của bạn trong 60 phút.</p>
    <button class="button button-primary button-hero">Kích hoạt Panic Mode</button>
</div>
<?php
