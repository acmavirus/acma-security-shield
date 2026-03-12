<?php
// Copyright by AcmaTvirus
if (!defined('ABSPATH')) exit;
?>

<form method="post" action="">
    <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
    <input type="hidden" name="wps_save_settings" value="1">

    <div class="wps-grid">
        <div class="wps-card">
            <h2 class="title">Danh sách IP Blacklist / Whitelist</h2>
            
            <p><strong>IP Blacklist:</strong> Danh sách IP bị cấm (Mỗi IP một dòng).</p>
            <textarea name="wps_blocked_ips_raw" rows="6" class="large-text code" style="width: 100%;"><?php echo esc_textarea($ips_text); ?></textarea>

            <p style="margin-top: 20px;"><strong>IP Whitelist:</strong> IP tin cậy không bị khóa.</p>
            <textarea name="wps_whitelist_ips_raw" rows="4" class="large-text code" style="width: 100%;"><?php echo esc_textarea($whitelist_text); ?></textarea>
            
            <?php submit_button('Cập nhật danh sách IP', 'primary', 'submit', false); ?>
        </div>

        <div class="wps-card">
            <h2 class="title">Nhật ký bị chặn tự động</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Thời gian</th>
                        <th>Lý do</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $auto_blocked = array_filter($security_logs, fn($l) => in_array($l['type'] ?? '', ['ip_blocked', 'dangerous_request']));
                    if (empty($auto_blocked)) : ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">Hệ thống đang an toàn, chưa có IP nào bị chặn.</td>
                        </tr>
                    <?php else : foreach (array_slice($auto_blocked, 0, 15) as $log) : ?>
                        <tr>
                            <td><code><?php echo $log['ip'] ?? 'Unknown'; ?></code></td>
                            <td><small><?php echo isset($log['time']) ? date('H:i d/m', strtotime($log['time'])) : ''; ?></small></td>
                            <td><small><?php echo $log['message'] ?? ''; ?></small></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
