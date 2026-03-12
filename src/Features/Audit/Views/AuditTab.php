<?php
// Copyright by AcmaTvirus
if (!defined('ABSPATH')) exit;
?>

<div class="wps-card">
    <h2 class="title" style="display: flex; justify-content: space-between; align-items: center;">
        Audit Trail
        <span class="update-plugins count-<?php echo count($audit_logs); ?>" style="font-size: 11px;"><?php echo count($audit_logs); ?> mục</span>
    </h2>
    <p class="description">Hành vi hệ thống trong 30 ngày qua.</p>

    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
        <thead>
            <tr>
                <th style="width: 150px;">Thời gian</th>
                <th style="width: 120px;">Người dùng</th>
                <th style="width: 150px;">Hành động</th>
                <th>Chi tiết hoạt động</th>
                <th style="width: 120px;">IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($audit_logs)) : ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Chưa có dữ liệu nhật ký.</td>
                </tr>
            <?php else : foreach (array_reverse(array_slice($audit_logs, -50)) as $log) :
                    $action_type = strtolower($log['action'] ?? '');
                    $color = '#666';
                    if (strpos($action_type, 'login') !== false) {
                        $color = '#46b450';
                    } elseif (strpos($action_type, 'security') !== false) {
                        $color = '#dc3232';
                    }
            ?>
                    <tr>
                        <td>
                            <strong><?php echo date('H:i:s', strtotime($log['time'] ?? 'now')); ?></strong><br>
                            <small><?php echo date('d/m/Y', strtotime($log['time'] ?? 'now')); ?></small>
                        </td>
                        <td><?php echo esc_html($log['user'] ?? 'Guest'); ?></td>
                        <td>
                           <span style="color: <?php echo $color; ?>; font-weight: bold; border: 1px solid <?php echo $color; ?>; padding: 2px 6px; border-radius: 3px; font-size: 10px; text-transform: uppercase;">
                               <?php echo esc_html($log['action'] ?? 'INFO'); ?>
                           </span>
                        </td>
                        <td><?php echo esc_html($log['message'] ?? ''); ?></td>
                        <td><code><?php echo esc_html($log['ip'] ?? '0.0.0.0'); ?></code></td>
                    </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php
