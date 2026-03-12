<?php
// Copyright by AcmaTvirus
if (!defined('ABSPATH')) exit;
?>

<div class="wps-grid">
    <div class="wps-card">
        <h2 class="title">Malware Scanner (uploads)</h2>
        <?php if (empty($malware_files)) : ?>
            <div class="notice notice-success inline">
                <p><strong>Hệ thống sạch:</strong> Không tìm thấy mã độc trong thư mục uploads.</p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Đường dẫn file</th>
                        <th>Thời gian</th>
                        <th>Kích thước</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($malware_files as $file) : ?>
                        <tr>
                            <td><code><?php echo $file['path']; ?></code></td>
                            <td><?php echo $file['time']; ?></td>
                            <td><?php echo $file['size']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p class="submit">
            <button class="button button-secondary">Chạy quét sâu (Root Scan)</button>
        </p>

        <h2 class="title" style="margin-top: 30px;">File Integrity (24h qua)</h2>
        <?php if (empty($integrity_changes)) : ?>
            <p class="description">Không có thay đổi nào được ghi nhận.</p>
        <?php else : ?>
            <ul class="ul-disc">
                <?php foreach ($integrity_changes as $change) : ?>
                    <li><strong><?php echo esc_html($change['file']); ?></strong> - <small><?php echo $change['time']; ?></small></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="wps-card">
        <h2 class="title">Phiên làm việc hiện tại (Active Sessions)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Thiết bị / Trình duyệt</th>
                    <th>IP / Thời gian</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $verifier => $session) : 
                    $is_current = (wp_get_session_token() === $verifier);
                ?>
                    <tr style="<?php echo $is_current ? 'background-color: #f0f6fb;' : ''; ?>">
                        <td>
                            <span class="dashicons <?php echo strpos(strtolower($session['ua'] ?? ''), 'mobile') !== false ? 'dashicons-smartphone' : 'dashicons-desktop'; ?>"></span>
                            <strong><?php echo esc_html(substr($session['ua'] ?? 'Unknown', 0, 40)); ?>...</strong>
                            <?php if ($is_current) echo ' <span class="badge" style="background: #2271b1; color: #fff; padding: 2px 5px; border-radius: 3px; font-size: 9px;">BẠN</span>'; ?>
                        </td>
                        <td>
                            <code><?php echo $session['ip']; ?></code><br>
                            <small><?php echo date('H:i d/m/Y', $session['login']); ?></small>
                        </td>
                        <td>
                            <?php if (!$is_current) : ?>
                                <button class="button button-link-delete">Đăng xuất</button>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="notice notice-info inline" style="margin-top: 20px;">
            <p><strong>Lời khuyên:</strong> Kiểm tra danh sách phiên làm việc thường xuyên để đảm bảo tài khoản của bạn an toàn.</p>
        </div>
    </div>
</div>
