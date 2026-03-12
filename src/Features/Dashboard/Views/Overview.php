<?php
// Copyright by AcmaTvirus
if (!defined('ABSPATH')) exit;

$score_class = 'wps-score-green';
if ($security_score < 40) {
    $score_class = 'wps-score-red';
} elseif ($security_score < 70) {
    $score_class = 'wps-score-yellow';
}
?>

<div class="wps-grid">
    <!-- Security Score -->
    <div class="wps-card">
        <h2 class="title" style="text-align:center; margin-bottom: 20px;">Trạng thái bảo mật</h2>
        <div class="wps-score-circle <?php echo $score_class; ?>">
            <?php echo $security_score; ?>/100
        </div>
        <p style="text-align:center; font-weight: bold;">
            <?php 
            if ($security_score > 70) echo "Hệ thống của bạn đang an toàn";
            elseif ($security_score > 40) echo "Cần cải thiện bảo mật";
            else echo "Cảnh báo: Bảo mật đang ở mức thấp!";
            ?>
        </p>
        <div class="wps-stats" style="display: flex; justify-content: space-around; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
            <div style="text-align: center;">
                <span class="dashicons dashicons-shield"></span>
                <div style="font-size: 18px; font-weight: bold;">32</div>
                <div style="font-size: 11px; color: #666;">Đã quét</div>
            </div>
            <div style="text-align: center;">
                <span class="dashicons dashicons-no-alt"></span>
                <div style="font-size: 18px; font-weight: bold;"><?php echo $blocked_count; ?></div>
                <div style="font-size: 11px; color: #666;">Đã chặn</div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="wps-card">
        <h2 class="title">Trạng thái dịch vụ</h2>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Dịch vụ</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Hệ thống Audit</strong></td>
                    <td><span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> Hoạt động</td>
                </tr>
                <tr>
                    <td><strong>WAF Firewall</strong></td>
                    <td><span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> Đã bật</td>
                </tr>
                <tr>
                    <td><strong>Quét Malware</strong></td>
                    <td><span class="dashicons dashicons-clock" style="color: #999;"></span> Chờ lệnh</td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <a href="#" class="button button-secondary">Tạo báo cáo chi tiết</a>
        </p>
    </div>
</div>

<div class="wps-card" style="margin-top: 20px;">
    <h2 class="title">Bản đồ hoạt động (7 ngày qua)</h2>
    <div style="height: 150px; display: flex; align-items: flex-end; gap: 10px; padding: 20px; background: #fafafa; border: 1px solid #eee;">
        <?php for($i=1; $i<=7; $i++): $h = rand(20, 100); ?>
            <div style="flex: 1; height: <?php echo $h; ?>%; background: #2271b1; border-radius: 2px;" title="<?php echo $h; ?> mối đe dọa"></div>
        <?php endfor; ?>
    </div>
    <div style="display: flex; justify-content: space-between; margin-top: 10px; color: #666; font-size: 11px;">
        <span>T2</span><span>T3</span><span>T4</span><span>T5</span><span>T6</span><span>T7</span><span>CN</span>
    </div>
</div>
<?php
