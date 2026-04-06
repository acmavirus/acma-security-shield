<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

use Acma\WpSecurity\Services\SecurityService;

/**
 * Controller cho cac tinh nang giam sat, scan va canh bao.
 */
class MonitoringController
{
    /**
     * @var SecurityService
     */
    private $security_service;

    public function __construct()
    {
        $this->security_service = new SecurityService();

        add_action('init', [$this, 'maybe_schedule_cron_scan']);
        add_action('wps_monitoring_cron_scan', [$this, 'run_cron_scan']);
        add_action('wps_render_admin_tab_monitoring', [$this, 'render_admin_tab']);
        add_action('wp_ajax_wps_monitoring_scan_integrity', [$this, 'ajax_scan_integrity']);
        add_action('wp_ajax_wps_monitoring_scan_malware', [$this, 'ajax_scan_malware']);
        add_action('wp_ajax_wps_monitoring_scan_vulnerability', [$this, 'ajax_scan_vulnerability']);
        add_action('wp_ajax_wps_monitoring_apply_uploads_protection', [$this, 'ajax_apply_uploads_protection']);
        add_action('wp_ajax_wps_monitoring_quarantine_file', [$this, 'ajax_quarantine_file']);
        add_action('wp_ajax_wps_monitoring_restore_file', [$this, 'ajax_restore_file']);
        add_action('wp_ajax_wps_monitoring_reset_baseline', [$this, 'ajax_reset_baseline']);
    }

    /**
     * Render tab giam sat trong admin.
     */
    public function render_admin_tab(array $main_settings)
    {
        $geo_countries = implode(', ', (array) ($main_settings['geo_block_countries'] ?? []));
        $quarantine_map = get_option('wps_monitoring_quarantine_map', []);
        $quarantine_map = is_array($quarantine_map) ? $quarantine_map : [];
        $last_cron_scan = get_option('wps_monitoring_last_cron_scan', []);
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
            <h2><?php esc_html_e('Giám sát & Phòng thủ nâng cao', 'wp-plugin-security'); ?></h2>
            <p class="description"><?php esc_html_e('Mục này gồm rate limit, geoblocking, chặn uploads PHP, 404 monitoring và các bộ quét.', 'wp-plugin-security'); ?></p>

            <div class="wps-grid two">
                <div class="wps-card">
                    <h4><?php esc_html_e('Rate limit & Geo block', 'wp-plugin-security'); ?></h4>
                    <table class="form-table wps-form-table" role="presentation">
                        <?php $this->render_checkbox_row('monitoring_enabled', 'Bật giám sát', $main_settings, 'Kích hoạt các lớp phòng thủ runtime cho site.'); ?>
                        <?php $this->render_checkbox_row('rate_limit_enabled', 'Bật rate limit', $main_settings, 'Giới hạn request theo IP và theo đường dẫn request.'); ?>
                        <?php $this->render_number_row('rate_limit_window_seconds', 'Cửa sổ rate limit (giây)', $main_settings, 60, 'Khoảng thời gian dùng để đếm request.'); ?>
                        <?php $this->render_number_row('rate_limit_max_requests', 'Số request/IP', $main_settings, 120, 'Vượt ngưỡng này sẽ bị chặn tạm thời.'); ?>
                        <?php $this->render_number_row('rate_limit_path_max_requests', 'Số request/đường dẫn', $main_settings, 30, 'Chặn khi một IP spam cùng đường dẫn.'); ?>
                        <?php $this->render_checkbox_row('geo_block_enabled', 'Bật geoblocking', $main_settings, 'Chặn hoặc cho phép truy cập theo mã quốc gia.'); ?>
                        <tr>
                            <th scope="row"><label for="geo_block_mode"><?php esc_html_e('Chế độ', 'wp-plugin-security'); ?></label></th>
                            <td>
                                <select id="geo_block_mode" name="geo_block_mode">
                                    <option value="deny" <?php selected(($main_settings['geo_block_mode'] ?? 'deny'), 'deny'); ?>><?php esc_html_e('Chặn quốc gia trong danh sách', 'wp-plugin-security'); ?></option>
                                    <option value="allow" <?php selected(($main_settings['geo_block_mode'] ?? 'deny'), 'allow'); ?>><?php esc_html_e('Chỉ cho phép quốc gia trong danh sách', 'wp-plugin-security'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Dùng mã ISO 2 ký tự, ví dụ: VN, CN, RU, US.', 'wp-plugin-security'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="geo_block_countries"><?php esc_html_e('Mã quốc gia', 'wp-plugin-security'); ?></label></th>
                            <td>
                                <textarea id="geo_block_countries" name="geo_block_countries" rows="4" class="large-text code"><?php echo esc_textarea($geo_countries); ?></textarea>
                                <p class="description"><?php esc_html_e('Ngăn cách bằng dấu phẩy hoặc xuống dòng.', 'wp-plugin-security'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="wps-card">
                    <h4><?php esc_html_e('Uploads & 404', 'wp-plugin-security'); ?></h4>
                    <table class="form-table wps-form-table" role="presentation">
                        <?php $this->render_checkbox_row('uploads_php_protection', 'Chặn PHP trong uploads', $main_settings, 'Tự chặn truy cập file PHP trong wp-content/uploads.'); ?>
                        <?php $this->render_checkbox_row('monitor_404_enabled', 'Theo dõi 404', $main_settings, 'Ghi nhận IP quét trang lỗi 404 và chặn tự động nếu vượt ngưỡng.'); ?>
                        <?php $this->render_checkbox_row('monitor_404_auto_block', 'Auto-block 404', $main_settings, 'Tự thêm IP vào blacklist khi vượt ngưỡng 404.'); ?>
                        <?php $this->render_number_row('monitor_404_threshold', 'Ngưỡng 404', $main_settings, 6, 'Số lần 404 trong cửa sổ thời gian.'); ?>
                        <?php $this->render_number_row('monitor_404_window_minutes', 'Cửa sổ 404 (phút)', $main_settings, 10, 'Khoảng thời gian để đếm 404.'); ?>
                    </table>
                    <p class="description"><?php esc_html_e('Uploads protection được áp dụng bằng .htaccess nếu server hỗ trợ.', 'wp-plugin-security'); ?></p>
                    <p>
                        <button type="button" class="button button-secondary" id="wps-apply-uploads-protection" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_monitoring_nonce')); ?>"><?php esc_html_e('Áp dụng uploads protection', 'wp-plugin-security'); ?></button>
                        <span id="wps-apply-uploads-status" class="description" style="margin-left: 12px;"></span>
                    </p>
                </div>
            </div>

            <div class="wps-card" style="margin-top: 20px;">
                <h4><?php esc_html_e('Automation', 'wp-plugin-security'); ?></h4>
                <table class="form-table wps-form-table" role="presentation">
                    <?php $this->render_checkbox_row('monitoring_cron_enabled', 'Bật quét định kỳ', $main_settings, 'Tự động quét theo lịch WP-Cron.'); ?>
                    <?php $this->render_checkbox_row('monitoring_email_alerts', 'Gửi email cảnh báo', $main_settings, 'Gửi báo cáo khi phát hiện thay đổi hoặc file nghi ngờ.'); ?>
                    <tr>
                        <th scope="row"><label for="monitoring_cron_frequency"><?php esc_html_e('Tần suất', 'wp-plugin-security'); ?></label></th>
                        <td>
                            <select id="monitoring_cron_frequency" name="monitoring_cron_frequency">
                                <option value="hourly" <?php selected(($main_settings['monitoring_cron_frequency'] ?? 'daily'), 'hourly'); ?>><?php esc_html_e('Mỗi giờ', 'wp-plugin-security'); ?></option>
                                <option value="twicedaily" <?php selected(($main_settings['monitoring_cron_frequency'] ?? 'daily'), 'twicedaily'); ?>><?php esc_html_e('2 lần/ngày', 'wp-plugin-security'); ?></option>
                                <option value="daily" <?php selected(($main_settings['monitoring_cron_frequency'] ?? 'daily'), 'daily'); ?>><?php esc_html_e('Hàng ngày', 'wp-plugin-security'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="description"><?php esc_html_e('Khi bật, plugin sẽ tự chạy integrity + malware + vulnerability scan theo lịch.', 'wp-plugin-security'); ?></p>
                <?php if (!empty($last_cron_scan['time'])) : ?>
                    <p class="description"><?php echo esc_html(sprintf(__('Lần quét gần nhất: %s', 'wp-plugin-security'), $last_cron_scan['time'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="wps-card" style="margin-top: 20px;">
                <h4><?php esc_html_e('Scanners', 'wp-plugin-security'); ?></h4>
                <table class="form-table wps-form-table" role="presentation">
                    <?php $this->render_checkbox_row('monitoring_scan_integrity', 'Quét integrity', $main_settings, 'So sánh file hiện tại với snapshot baseline đã lưu.'); ?>
                    <?php $this->render_checkbox_row('monitoring_scan_malware', 'Quét malware', $main_settings, 'Tìm pattern nghi ngờ trong file PHP/JS/HTML.'); ?>
                    <?php $this->render_checkbox_row('monitoring_scan_vulnerability', 'Quét lỗ hổng', $main_settings, 'Kiểm tra core/plugin/theme có bản cập nhật hay không.'); ?>
                </table>

                <p>
                    <button type="button" class="button button-primary" id="wps-scan-integrity" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_monitoring_nonce')); ?>"><?php esc_html_e('Quét integrity', 'wp-plugin-security'); ?></button>
                    <button type="button" class="button" id="wps-scan-malware" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_monitoring_nonce')); ?>"><?php esc_html_e('Quét malware', 'wp-plugin-security'); ?></button>
                    <button type="button" class="button" id="wps-scan-vulnerability" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_monitoring_nonce')); ?>"><?php esc_html_e('Quét lỗ hổng', 'wp-plugin-security'); ?></button>
                    <span id="wps-monitoring-scan-status" class="description" style="margin-left: 12px;"></span>
                </p>

                <div style="margin-top: 14px;">
                    <div style="height: 16px; border-radius: 999px; background: #e6edf3; overflow: hidden; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);">
                        <div id="wps-monitoring-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #1167ad 0%, #00a3c4 100%); transition: width 180ms ease;"></div>
                    </div>
                    <div id="wps-monitoring-progress-text" class="description" style="margin-top: 8px;"><?php esc_html_e('Chưa bắt đầu quét.', 'wp-plugin-security'); ?></div>
                </div>

                <div style="margin-top: 18px;">
                    <div class="description" style="margin-bottom: 8px;"><?php esc_html_e('Kết quả sẽ hiển thị tại đây:', 'wp-plugin-security'); ?></div>
                    <ol id="wps-monitoring-results" style="margin: 0; padding-left: 20px; max-height: 320px; overflow: auto; background: #f8fbfe; border: 1px solid #d9e3ef; border-radius: 12px; padding-top: 12px; padding-bottom: 12px;"></ol>
                </div>
                <p style="margin-top: 16px;">
                    <button type="button" class="button" id="wps-reset-baseline" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_monitoring_nonce')); ?>"><?php esc_html_e('Tạo baseline mới', 'wp-plugin-security'); ?></button>
                    <span id="wps-reset-baseline-status" class="description" style="margin-left: 12px;"></span>
                </p>
            </div>

            <div class="wps-card" style="margin-top: 20px;">
                <h4><?php esc_html_e('Quarantine', 'wp-plugin-security'); ?></h4>
                <p class="description"><?php esc_html_e('Các file đã cách ly sẽ xuất hiện ở đây để bạn khôi phục khi cần.', 'wp-plugin-security'); ?></p>
                <ol id="wps-quarantine-list" style="margin: 0; padding-left: 20px; max-height: 260px; overflow: auto; background: #f8fbfe; border: 1px solid #d9e3ef; border-radius: 12px; padding-top: 12px; padding-bottom: 12px;">
                    <?php foreach ($quarantine_map as $original_path => $backup_path) : ?>
                        <li data-path="<?php echo esc_attr($original_path); ?>" style="margin: 0 0 10px 0; padding: 8px 12px; background: #fff; border-left: 4px solid #8b5cf6; border-radius: 8px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                            <span style="font-weight: 600;"><?php echo esc_html($this->relativize_path($original_path)); ?></span>
                            <span class="description"><?php echo esc_html($this->relativize_path($backup_path)); ?></span>
                            <button type="button" class="button button-small wps-restore-file-btn" data-path="<?php echo esc_attr($original_path); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_monitoring_nonce')); ?>"><?php esc_html_e('Phục hồi', 'wp-plugin-security'); ?></button>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <input type="hidden" name="wps_save_settings" value="1">
            <?php submit_button(__('Lưu thiết lập Giám sát', 'wp-plugin-security')); ?>
        </form>
        <script>
        (function(){
            var scanButtons = {
                integrity: document.getElementById('wps-scan-integrity'),
                malware: document.getElementById('wps-scan-malware'),
                vulnerability: document.getElementById('wps-scan-vulnerability')
            };
            var applyUploadsButton = document.getElementById('wps-apply-uploads-protection');
            var resetBaselineButton = document.getElementById('wps-reset-baseline');
            var resetBaselineStatus = document.getElementById('wps-reset-baseline-status');
            var quarantineList = document.getElementById('wps-quarantine-list');
            var status = document.getElementById('wps-monitoring-scan-status');
            var progressBar = document.getElementById('wps-monitoring-progress-bar');
            var progressText = document.getElementById('wps-monitoring-progress-text');
            var results = document.getElementById('wps-monitoring-results');

            function setStatus(text) {
                if (status) {
                    status.textContent = text;
                }
            }

            function setProgress(percent, text) {
                if (progressBar) {
                    progressBar.style.width = Math.max(0, Math.min(100, percent)) + '%';
                }
                if (progressText && text) {
                    progressText.textContent = text;
                }
            }

            function renderResults(items) {
                if (!results) {
                    return;
                }

                results.innerHTML = '';
                (items || []).forEach(function(item){
                    var li = document.createElement('li');
                    li.style.margin = '0 0 10px 0';
                    li.style.padding = '8px 12px';
                    li.style.background = '#ffffff';
                    li.style.borderLeft = '4px solid ' + (item.color || '#d1dbe7');
                    li.style.borderRadius = '8px';
                    li.style.display = 'flex';
                    li.style.justifyContent = 'space-between';
                    li.style.gap = '12px';
                    li.style.flexWrap = 'wrap';

                    var title = document.createElement('span');
                    title.style.fontWeight = '600';
                    title.textContent = item.title || '';

                    var meta = document.createElement('span');
                    meta.className = 'description';
                    meta.textContent = item.meta || '';

                    var metaWrap = document.createElement('span');
                    metaWrap.style.display = 'flex';
                    metaWrap.style.alignItems = 'center';
                    metaWrap.style.gap = '10px';
                    metaWrap.style.flexWrap = 'wrap';

                    metaWrap.appendChild(meta);

                    if (item.path && item.can_quarantine) {
                        var quarantineButton = document.createElement('button');
                        quarantineButton.type = 'button';
                        quarantineButton.className = 'button button-small';
                        quarantineButton.textContent = '<?php echo esc_js(__('Cách ly', 'wp-plugin-security')); ?>';
                        quarantineButton.addEventListener('click', function(event){
                            event.preventDefault();
                            quarantineButton.disabled = true;
                            post('wps_monitoring_quarantine_file', { nonce: scanButtons.integrity ? scanButtons.integrity.getAttribute('data-nonce') : '', path: item.path }).then(function(payload){
                                if (!payload || !payload.success) {
                                    throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Không thể cách ly file.', 'wp-plugin-security')); ?>');
                                }
                                setStatus((payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Đã cách ly file.', 'wp-plugin-security')); ?>');
                            }).catch(function(error){
                                setStatus(error && error.message ? error.message : '<?php echo esc_js(__('Không thể cách ly file.', 'wp-plugin-security')); ?>');
                            }).finally(function(){
                                quarantineButton.disabled = false;
                            });
                        });
                        metaWrap.appendChild(quarantineButton);
                    }

                    li.appendChild(title);
                    li.appendChild(metaWrap);
                    results.appendChild(li);
                });
            }

            function post(action, data) {
                var formData = new FormData();
                formData.append('action', action);
                formData.append('nonce', (data && data.nonce) ? data.nonce : '');
                Object.keys(data || {}).forEach(function(key) {
                    if (key !== 'nonce') {
                        formData.append(key, data[key]);
                    }
                });

                return fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                }).then(function(response){
                    return response.json();
                });
            }

            function runScan(action, button, label) {
                button.disabled = true;
                setStatus(label + '...');
                setProgress(15, '<?php echo esc_js(__('Đang quét...', 'wp-plugin-security')); ?>');

                return post(action, { nonce: button.getAttribute('data-nonce') }).then(function(payload){
                    if (!payload || !payload.success) {
                        throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Quét thất bại.', 'wp-plugin-security')); ?>');
                    }

                    var data = payload.data || {};
                    renderResults(Array.isArray(data.items) ? data.items : []);
                    setStatus(data.message || '<?php echo esc_js(__('Hoàn tất.', 'wp-plugin-security')); ?>');
                    setProgress(100, data.summary || '<?php echo esc_js(__('Đã quét xong.', 'wp-plugin-security')); ?>');
                }).catch(function(error){
                    setStatus(error && error.message ? error.message : '<?php echo esc_js(__('Quét thất bại.', 'wp-plugin-security')); ?>');
                    setProgress(0, '<?php echo esc_js(__('Chưa bắt đầu quét.', 'wp-plugin-security')); ?>');
                }).finally(function(){
                    button.disabled = false;
                });
            }

            Object.keys(scanButtons).forEach(function(key) {
                var button = scanButtons[key];
                if (!button) {
                    return;
                }
                button.addEventListener('click', function(event){
                    event.preventDefault();
                    runScan('wps_monitoring_scan_' + key, button, button.textContent.trim());
                });
            });

            if (applyUploadsButton) {
                applyUploadsButton.addEventListener('click', function(event){
                    event.preventDefault();
                    applyUploadsButton.disabled = true;
                    setStatus('<?php echo esc_js(__('Đang áp dụng uploads protection...', 'wp-plugin-security')); ?>');
                    post('wps_monitoring_apply_uploads_protection', { nonce: applyUploadsButton.getAttribute('data-nonce') }).then(function(payload){
                        if (!payload || !payload.success) {
                            throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Không thể áp dụng uploads protection.', 'wp-plugin-security')); ?>');
                        }

                        setStatus((payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Đã áp dụng uploads protection.', 'wp-plugin-security')); ?>');
                    }).catch(function(error){
                        setStatus(error && error.message ? error.message : '<?php echo esc_js(__('Không thể áp dụng uploads protection.', 'wp-plugin-security')); ?>');
                    }).finally(function(){
                        applyUploadsButton.disabled = false;
                    });
                });
            }

            if (resetBaselineButton) {
                resetBaselineButton.addEventListener('click', function(event){
                    event.preventDefault();
                    resetBaselineButton.disabled = true;
                    if (resetBaselineStatus) {
                        resetBaselineStatus.textContent = '<?php echo esc_js(__('Đang tạo baseline...', 'wp-plugin-security')); ?>';
                    }

                    post('wps_monitoring_reset_baseline', { nonce: resetBaselineButton.getAttribute('data-nonce') }).then(function(payload){
                        if (!payload || !payload.success) {
                            throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Không thể tạo baseline mới.', 'wp-plugin-security')); ?>');
                        }

                        if (resetBaselineStatus) {
                            resetBaselineStatus.textContent = (payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Đã tạo baseline mới.', 'wp-plugin-security')); ?>';
                        }
                    }).catch(function(error){
                        if (resetBaselineStatus) {
                            resetBaselineStatus.textContent = error && error.message ? error.message : '<?php echo esc_js(__('Không thể tạo baseline mới.', 'wp-plugin-security')); ?>';
                        }
                    }).finally(function(){
                        resetBaselineButton.disabled = false;
                    });
                });
            }

            if (quarantineList) {
                quarantineList.addEventListener('click', function(event){
                    var button = event.target.closest('.wps-restore-file-btn');
                    if (!button) {
                        return;
                    }

                    event.preventDefault();
                    button.disabled = true;
                    post('wps_monitoring_restore_file', { nonce: button.getAttribute('data-nonce'), path: button.getAttribute('data-path') }).then(function(payload){
                        if (!payload || !payload.success) {
                            throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Không thể phục hồi file.', 'wp-plugin-security')); ?>');
                        }

                        var item = button.closest('li');
                        if (item) {
                            item.remove();
                        }

                        setStatus((payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Đã phục hồi file.', 'wp-plugin-security')); ?>');
                    }).catch(function(error){
                        setStatus(error && error.message ? error.message : '<?php echo esc_js(__('Không thể phục hồi file.', 'wp-plugin-security')); ?>');
                    }).finally(function(){
                        button.disabled = false;
                    });
                });
            }
        })();
        </script>
        <?php
    }

    public function ajax_scan_integrity()
    {
        $this->require_admin_ajax();
        wp_send_json_success($this->scan_integrity());
    }

    public function ajax_scan_malware()
    {
        $this->require_admin_ajax();
        wp_send_json_success($this->scan_malware());
    }

    public function ajax_scan_vulnerability()
    {
        $this->require_admin_ajax();
        wp_send_json_success($this->scan_vulnerabilities());
    }

    public function ajax_apply_uploads_protection()
    {
        $this->require_admin_ajax();
        $result = $this->apply_uploads_protection();
        if (empty($result['success'])) {
            wp_send_json_error($result);
        }

        wp_send_json_success($result);
    }

    public function ajax_reset_baseline()
    {
        $this->require_admin_ajax();
        $result = $this->generate_integrity_baseline();
        if (empty($result['success'])) {
            wp_send_json_error($result);
        }

        wp_send_json_success($result);
    }

    public function ajax_quarantine_file()
    {
        $this->require_admin_ajax();
        $path = isset($_POST['path']) ? wp_unslash((string) $_POST['path']) : '';
        $result = $this->quarantine_file($path);
        if (empty($result['success'])) {
            wp_send_json_error($result);
        }

        wp_send_json_success($result);
    }

    public function ajax_restore_file()
    {
        $this->require_admin_ajax();
        $path = isset($_POST['path']) ? wp_unslash((string) $_POST['path']) : '';
        $result = $this->restore_file($path);
        if (empty($result['success'])) {
            wp_send_json_error($result);
        }

        wp_send_json_success($result);
    }

    /**
     * Tao baseline integrity moi.
     */
    private function generate_integrity_baseline()
    {
        $files = $this->collect_files('integrity');
        $baseline = [];

        foreach ($files as $file) {
            $hash = $this->hash_file($file['path']);
            if ($hash !== '') {
                $baseline[$file['path']] = $hash;
            }
        }

        update_option('wps_file_integrity_snapshot', $baseline, false);

        return [
            'success' => true,
            'message' => sprintf(__('Đã tạo baseline mới cho %d file.', 'wp-plugin-security'), count($baseline)),
        ];
    }

    /**
     * Lich quet tu dong theo wp-cron.
     */
    public function maybe_schedule_cron_scan()
    {
        if (!$this->security_service->get_setting('monitoring_enabled', false) || !$this->security_service->get_setting('monitoring_cron_enabled', false)) {
            $this->unschedule_cron_scan();
            return;
        }

        $frequency = sanitize_key((string) $this->security_service->get_setting('monitoring_cron_frequency', 'daily'));
        if (!in_array($frequency, ['hourly', 'twicedaily', 'daily'], true)) {
            $frequency = 'daily';
        }

        $scheduled = wp_next_scheduled('wps_monitoring_cron_scan');
        if ($scheduled) {
            $this->unschedule_cron_scan();
        }

        if (!wp_next_scheduled('wps_monitoring_cron_scan')) {
            wp_schedule_event(time() + 300, $frequency, 'wps_monitoring_cron_scan');
        }
    }

    /**
     * Huy lich scan khi khong con can.
     */
    private function unschedule_cron_scan()
    {
        wp_clear_scheduled_hook('wps_monitoring_cron_scan');
    }

    /**
     * Chay scan dinh ky va gui email khi can.
     */
    public function run_cron_scan()
    {
        if (!$this->security_service->get_setting('monitoring_enabled', false)) {
            return;
        }

        $reports = [
            'integrity' => $this->scan_integrity(),
            'malware' => $this->scan_malware(),
            'vulnerability' => $this->scan_vulnerabilities(),
        ];

        $summary_lines = [];
        $has_issue = false;

        foreach ($reports as $name => $report) {
            $summary = is_array($report) ? ($report['summary'] ?? '') : '';
            $message = is_array($report) ? ($report['message'] ?? '') : '';
            $summary_lines[] = strtoupper($name) . ': ' . $summary;

            if (!empty($report['baseline_created'])) {
                if ($message !== '') {
                    $summary_lines[] = $message;
                }
                continue;
            }

            if ($name !== 'vulnerability' && !empty($report['items'])) {
                $has_issue = true;
            }

            if ($name === 'vulnerability' && !empty($report['items'])) {
                foreach ((array) $report['items'] as $item) {
                    if (!empty($item['color']) && $item['color'] !== '#1f7a3f') {
                        $has_issue = true;
                        break;
                    }
                }
            }

            if ($message !== '') {
                $summary_lines[] = $message;
            }
        }

        if ($has_issue && $this->security_service->get_setting('monitoring_email_alerts', true)) {
            $to = get_option('admin_email');
            $subject = sprintf('[%s] %s', get_bloginfo('name'), __('Cảnh báo bảo mật từ WP Plugin Security', 'wp-plugin-security'));
            $body = implode("\n", $summary_lines) . "\n\n" . __('Hãy mở trang quản trị để xem chi tiết.', 'wp-plugin-security');
            wp_mail($to, $subject, $body);
        }

        update_option('wps_monitoring_last_cron_scan', [
            'time' => current_time('mysql'),
            'reports' => $summary_lines,
        ], false);
    }

    /**
     * Cách ly file nghi ngờ vào thư mục quarantine.
     */
    private function quarantine_file($path)
    {
        $path = $this->normalize_path($path);
        if ($path === '' || !file_exists($path) || !is_file($path)) {
            return [
                'success' => false,
                'message' => __('File không hợp lệ.', 'wp-plugin-security'),
            ];
        }

        if (!$this->is_quarantine_allowed_path($path)) {
            return [
                'success' => false,
                'message' => __('Không cho phép cách ly file này.', 'wp-plugin-security'),
            ];
        }

        $quarantine_dir = $this->get_quarantine_dir();
        if (!wp_mkdir_p($quarantine_dir)) {
            return [
                'success' => false,
                'message' => __('Không tạo được thư mục quarantine.', 'wp-plugin-security'),
            ];
        }

        $token = md5($path . '|' . time() . '|' . wp_generate_password(8, false, false));
        $dest = trailingslashit($quarantine_dir) . basename($path) . '.' . $token . '.quarantine';

        if (!@copy($path, $dest)) {
            return [
                'success' => false,
                'message' => __('Không sao lưu được file.', 'wp-plugin-security'),
            ];
        }

        if (!@unlink($path)) {
            return [
                'success' => false,
                'message' => __('Đã sao lưu nhưng không thể xóa file gốc.', 'wp-plugin-security'),
            ];
        }

        $map = get_option('wps_monitoring_quarantine_map', []);
        $map = is_array($map) ? $map : [];
        $map[$path] = $dest;
        update_option('wps_monitoring_quarantine_map', $map, false);

        return [
            'success' => true,
            'message' => sprintf(__('Đã cách ly file: %s', 'wp-plugin-security'), $this->relativize_path($path)),
        ];
    }

    /**
     * Phuc hoi file tu quarantine.
     */
    private function restore_file($path)
    {
        $path = $this->normalize_path($path);
        if ($path === '') {
            return [
                'success' => false,
                'message' => __('File không hợp lệ.', 'wp-plugin-security'),
            ];
        }

        $map = get_option('wps_monitoring_quarantine_map', []);
        $map = is_array($map) ? $map : [];
        $backup = $map[$path] ?? '';

        if ($backup === '' || !file_exists($backup)) {
            return [
                'success' => false,
                'message' => __('Không tìm thấy bản sao trong quarantine.', 'wp-plugin-security'),
            ];
        }

        $target_dir = dirname($path);
        if (!is_dir($target_dir) && !wp_mkdir_p($target_dir)) {
            return [
                'success' => false,
                'message' => __('Không tạo được thư mục gốc để phục hồi.', 'wp-plugin-security'),
            ];
        }

        if (!@copy($backup, $path)) {
            return [
                'success' => false,
                'message' => __('Không thể phục hồi file.', 'wp-plugin-security'),
            ];
        }

        if (@unlink($backup)) {
            unset($map[$path]);
            update_option('wps_monitoring_quarantine_map', $map, false);
        }

        return [
            'success' => true,
            'message' => sprintf(__('Đã phục hồi file: %s', 'wp-plugin-security'), $this->relativize_path($path)),
        ];
    }

    /**
     * Lay thu muc quarantine an toan.
     */
    private function get_quarantine_dir()
    {
        $upload_dir = wp_get_upload_dir();
        $base = $upload_dir['basedir'] ?? ABSPATH;
        return trailingslashit($base) . 'wps-quarantine';
    }

    /**
     * Kiem tra duong dan co nam trong khu vuc duoc phep khong.
     */
    private function is_quarantine_allowed_path($path)
    {
        $path = $this->normalize_path($path);
        $allowed_roots = [
            ABSPATH,
            wp_normalize_path(WP_CONTENT_DIR),
        ];

        foreach ($allowed_roots as $root) {
            $root = wp_normalize_path($root);
            if ($root !== '' && stripos($path, $root) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Chuan hoa path de an toan khi xu ly.
     */
    private function normalize_path($path)
    {
        $path = wp_normalize_path(trim((string) $path));
        if ($path === '') {
            return '';
        }

        $real = realpath($path);
        if ($real !== false) {
            $path = wp_normalize_path($real);
        }

        return $path;
    }

    /**
     * Bao ve ajax cho user co quyen quan tri.
     */
    private function require_admin_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Bạn không có quyền thực hiện thao tác này.', 'wp-plugin-security')], 403);
        }

        check_ajax_referer('wps_monitoring_nonce', 'nonce');
    }

    /**
     * Quet integrity so sanh voi snapshot baseline.
     */
    private function scan_integrity()
    {
        @set_time_limit(0);

        $files = $this->collect_files('integrity');
        $snapshot = get_option('wps_file_integrity_snapshot', []);
        $snapshot = is_array($snapshot) ? $snapshot : [];

        $items = [];
        $summary = [
            'total' => count($files),
            'clean' => 0,
            'changed' => 0,
            'new' => 0,
            'missing' => 0,
        ];

        if (empty($snapshot)) {
            $baseline = [];
            foreach ($files as $file) {
                $hash = $this->hash_file($file['path']);
                if ($hash !== '') {
                    $baseline[$file['path']] = $hash;
                }
            }

            update_option('wps_file_integrity_snapshot', $baseline, false);

            foreach (array_slice($files, 0, 50) as $file) {
                $items[] = [
                    'title' => $file['label'],
                    'meta' => __('Đã lưu baseline ban đầu.', 'wp-plugin-security'),
                    'color' => '#1f7a3f',
                    'path' => $file['path'],
                ];
            }

            $summary['clean'] = count($files);

            return [
                'message' => __('Chưa có baseline trước đó, plugin đã tạo baseline mới.', 'wp-plugin-security'),
                'summary' => sprintf(__('Baseline đã được lưu cho %d file.', 'wp-plugin-security'), count($baseline)),
                'items' => $items,
                'baseline_created' => true,
            ];
        }

        $current_map = [];
        foreach ($files as $file) {
            $current_map[$file['path']] = true;

            $current_hash = $this->hash_file($file['path']);
            $baseline_hash = $snapshot[$file['path']] ?? null;

            if ($baseline_hash === null) {
                $summary['new']++;
                $items[] = [
                    'title' => $file['label'],
                    'meta' => __('File mới xuất hiện', 'wp-plugin-security'),
                    'color' => '#b36b00',
                    'path' => $file['path'],
                    'can_quarantine' => true,
                ];
                continue;
            }

            if ($current_hash === '') {
                $summary['missing']++;
                $items[] = [
                    'title' => $file['label'],
                    'meta' => __('Không đọc được file', 'wp-plugin-security'),
                    'color' => '#b42318',
                    'path' => $file['path'],
                    'can_quarantine' => true,
                ];
                continue;
            }

            if (!hash_equals((string) $baseline_hash, (string) $current_hash)) {
                $summary['changed']++;
                $items[] = [
                    'title' => $file['label'],
                    'meta' => __('Đã thay đổi so với baseline', 'wp-plugin-security'),
                    'color' => '#b42318',
                    'path' => $file['path'],
                    'can_quarantine' => true,
                ];
            } else {
                $summary['clean']++;
            }
        }

        foreach ($snapshot as $missing_path => $hash) {
            if (isset($current_map[$missing_path])) {
                continue;
            }

            $summary['missing']++;
            $items[] = [
                'title' => $this->relativize_path($missing_path),
                'meta' => __('File trong baseline nhưng hiện không còn tồn tại', 'wp-plugin-security'),
                'color' => '#8b5cf6',
                'path' => $missing_path,
            ];
        }

        return [
            'message' => __('Quét integrity hoàn tất.', 'wp-plugin-security'),
            'summary' => sprintf(
                __('Tổng: %1$d | Sạch: %2$d | Đổi: %3$d | Mới: %4$d | Mất: %5$d', 'wp-plugin-security'),
                (int) $summary['total'],
                (int) $summary['clean'],
                (int) $summary['changed'],
                (int) $summary['new'],
                (int) $summary['missing']
            ),
            'items' => array_slice($items, 0, 100),
        ];
    }

    /**
     * Quet pattern malware heuristic.
     */
    private function scan_malware()
    {
        @set_time_limit(0);

        $files = $this->collect_files('malware');
        $items = [];
        $summary = [
            'total' => count($files),
            'clean' => 0,
            'suspicious' => 0,
        ];

        foreach ($files as $file) {
            $result = $this->inspect_file_for_malware($file['path']);
            if ($result['suspicious']) {
                $summary['suspicious']++;
                $items[] = [
                    'title' => $file['label'],
                    'meta' => implode(' | ', $result['reasons']),
                    'color' => '#b42318',
                    'path' => $file['path'],
                    'can_quarantine' => true,
                ];
            } else {
                $summary['clean']++;
            }
        }

        return [
            'message' => __('Quét malware hoàn tất.', 'wp-plugin-security'),
            'summary' => sprintf(
                __('Tổng: %1$d | Sạch: %2$d | Nghi ngờ: %3$d', 'wp-plugin-security'),
                (int) $summary['total'],
                (int) $summary['clean'],
                (int) $summary['suspicious']
            ),
            'items' => array_slice($items, 0, 100),
        ];
    }

    /**
     * Quet inventory de canh bao version/update.
     */
    private function scan_vulnerabilities()
    {
        @set_time_limit(0);

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        wp_update_plugins();
        wp_update_themes();
        wp_version_check();

        $items = [];
        $core_update = get_site_transient('update_core');
        $core_version = get_bloginfo('version');
        $core_latest = '';

        if (is_object($core_update) && !empty($core_update->updates)) {
            foreach ((array) $core_update->updates as $update) {
                if (!empty($update->current)) {
                    $core_latest = (string) $update->current;
                    break;
                }
            }
        }

        if ($core_latest !== '' && version_compare($core_version, $core_latest, '<')) {
            $items[] = [
                'title' => __('WordPress core', 'wp-plugin-security'),
                'meta' => sprintf(__('Đang dùng %1$s, có bản mới %2$s', 'wp-plugin-security'), $core_version, $core_latest),
                'color' => '#b42318',
            ];
        } else {
            $items[] = [
                'title' => __('WordPress core', 'wp-plugin-security'),
                'meta' => sprintf(__('Phiên bản hiện tại: %s', 'wp-plugin-security'), $core_version),
                'color' => '#1f7a3f',
            ];
        }

        $plugin_updates = get_site_transient('update_plugins');
        $active_plugins = get_option('active_plugins', []);
        foreach ((array) $active_plugins as $plugin_file) {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
            if (!file_exists($plugin_path)) {
                continue;
            }

            $plugin_data = get_plugin_data($plugin_path, false, false);
            if (empty($plugin_data['Name'])) {
                continue;
            }

            $has_update = is_object($plugin_updates) && !empty($plugin_updates->response[$plugin_file]);
            $items[] = [
                'title' => $plugin_data['Name'],
                'meta' => $has_update
                    ? sprintf(__('Plugin %1$s có bản cập nhật %2$s', 'wp-plugin-security'), $plugin_data['Version'] ?? '0.0.0', $plugin_updates->response[$plugin_file]->new_version ?? '')
                    : sprintf(__('Plugin version: %s', 'wp-plugin-security'), $plugin_data['Version'] ?? '0.0.0'),
                'color' => $has_update ? '#b36b00' : '#1f7a3f',
            ];
        }

        $theme_updates = get_site_transient('update_themes');
        $theme = wp_get_theme();
        $theme_slug = $theme->get_stylesheet();
        $has_theme_update = is_object($theme_updates) && !empty($theme_updates->response[$theme_slug]);
        $items[] = [
            'title' => $theme->get('Name') ?: __('Theme hiện tại', 'wp-plugin-security'),
            'meta' => $has_theme_update
                ? sprintf(__('Theme %1$s có bản cập nhật %2$s', 'wp-plugin-security'), $theme->get('Version') ?: '0.0.0', $theme_updates->response[$theme_slug]['new_version'] ?? '')
                : sprintf(__('Theme version: %s', 'wp-plugin-security'), $theme->get('Version') ?: '0.0.0'),
            'color' => $has_theme_update ? '#b36b00' : '#1f7a3f',
        ];

        return [
            'message' => __('Quét lỗ hổng hoàn tất.', 'wp-plugin-security'),
            'summary' => __('Đã kiểm tra core, plugin và theme đang hoạt động.', 'wp-plugin-security'),
            'items' => $items,
        ];
    }

    /**
     * Ap dung rule chan PHP trong uploads.
     */
    private function apply_uploads_protection()
    {
        $upload_dir = wp_get_upload_dir();
        $basedir = $upload_dir['basedir'] ?? '';
        if ($basedir === '' || !is_dir($basedir)) {
            return [
                'success' => false,
                'message' => __('Không xác định được thư mục uploads.', 'wp-plugin-security'),
            ];
        }

        $htaccess = trailingslashit($basedir) . '.htaccess';
        $marker = 'WP Plugin Security Uploads Protection';
        $rules = [
            '<FilesMatch "\\.(php|phtml|phar|php[0-9]?)$">',
            '    Require all denied',
            '</FilesMatch>',
            '<IfModule mod_php.c>',
            '    php_flag engine off',
            '</IfModule>',
            '<IfModule mod_mime.c>',
            '    RemoveHandler .php .phtml .phar',
            '</IfModule>',
        ];

        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        if (!is_writable($basedir) && (!file_exists($htaccess) || !is_writable($htaccess))) {
            return [
                'success' => false,
                'message' => __('Thư mục uploads không thể ghi .htaccess.', 'wp-plugin-security'),
            ];
        }

        $result = insert_with_markers($htaccess, $marker, $rules);
        if (!$result) {
            return [
                'success' => false,
                'message' => __('Không thể ghi rule bảo vệ uploads.', 'wp-plugin-security'),
            ];
        }

        return [
            'success' => true,
            'message' => __('Đã áp dụng uploads protection.', 'wp-plugin-security'),
        ];
    }

    /**
     * Lay danh sach file phuc vu scan.
     */
    private function collect_files($mode = 'integrity')
    {
        $directories = $this->get_scan_directories($mode);
        $files = [];
        $seen = [];
        $extensions = $mode === 'malware'
            ? ['php', 'phtml', 'phar', 'php5', 'php7', 'php8', 'inc', 'js', 'html', 'htm', 'css', 'txt', 'json', 'xml', 'twig', 'md']
            : ['php', 'phtml', 'phar', 'php5', 'php7', 'php8', 'inc', 'js', 'html', 'htm', 'css', 'txt', 'json', 'xml', 'twig', 'md'];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
            } catch (\Throwable $exception) {
                continue;
            }

            foreach ($iterator as $item) {
                if (!$item->isFile()) {
                    continue;
                }

                $path = $item->getPathname();
                if (isset($seen[$path]) || $this->should_skip_path($path, $mode)) {
                    continue;
                }

                if (!$this->is_scannable_extension($path, $extensions)) {
                    continue;
                }

                $seen[$path] = true;
                $files[] = [
                    'path' => $path,
                    'label' => $this->relativize_path($path),
                ];
            }
        }

        usort($files, static function ($a, $b) {
            return strcmp($a['path'], $b['path']);
        });

        return $files;
    }

    /**
     * Lay cac thu muc can scan.
     */
    private function get_scan_directories($mode = 'integrity')
    {
        $directories = [
            ABSPATH,
            ABSPATH . 'wp-admin',
            ABSPATH . 'wp-includes',
            get_stylesheet_directory(),
        ];

        $template_directory = get_template_directory();
        if ($template_directory) {
            $directories[] = $template_directory;
        }

        foreach ((array) wp_get_active_and_valid_plugins() as $plugin_file) {
            $directories[] = dirname($plugin_file);
        }

        if ($mode === 'malware') {
            $upload_dir = wp_get_upload_dir();
            if (!empty($upload_dir['basedir'])) {
                $directories[] = $upload_dir['basedir'];
            }
        }

        return array_filter(array_unique($directories));
    }

    /**
     * Bo qua file/thu muc khong can scan.
     */
    private function should_skip_path($path, $mode = 'integrity')
    {
        $normalized = str_replace('\\', '/', strtolower((string) $path));
        $excluded_parts = [
            '/cache/',
            '/tmp/',
            '/temp/',
            '/logs/',
            '/log/',
            '/backup/',
            '/backups/',
            '/vendor/',
            '/node_modules/',
            '/.git/',
            '/.svn/',
        ];

        foreach ($excluded_parts as $part) {
            if (strpos($normalized, $part) !== false) {
                return true;
            }
        }

        if ($mode === 'integrity' && strpos($normalized, '/uploads/') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Kiem tra extension file co can scan hay khong.
     */
    private function is_scannable_extension($path, array $extensions)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, $extensions, true);
    }

    /**
     * Hash file an toan.
     */
    private function hash_file($path)
    {
        if (!is_readable($path) || !is_file($path)) {
            return '';
        }

        $hash = @hash_file('sha256', $path);
        return is_string($hash) ? $hash : '';
    }

    /**
     * Quet file tim pattern nghi ngo.
     */
    private function inspect_file_for_malware($path)
    {
        if (!is_readable($path) || !is_file($path)) {
            return [
                'suspicious' => false,
                'reasons' => [],
            ];
        }

        $size = @filesize($path);
        if ($size !== false && $size > 2097152) {
            return [
                'suspicious' => false,
                'reasons' => [],
            ];
        }

        $content = @file_get_contents($path);
        if (!is_string($content) || $content === '') {
            return [
                'suspicious' => false,
                'reasons' => [],
            ];
        }

        $patterns = [
            'eval(' => '/\beval\s*\(/i',
            'base64_decode(' => '/\bbase64_decode\s*\(/i',
            'gzinflate(' => '/\bgzinflate\s*\(/i',
            'str_rot13(' => '/\bstr_rot13\s*\(/i',
            'shell_exec(' => '/\bshell_exec\s*\(/i',
            'system(' => '/\bsystem\s*\(/i',
            'passthru(' => '/\bpassthru\s*\(/i',
            'proc_open(' => '/\bproc_open\s*\(/i',
            'curl_exec(' => '/\bcurl_exec\s*\(/i',
            'preg_replace /e' => '/preg_replace\s*\(.*\/e[\'"]?/i',
            'long encoded string' => '/[A-Za-z0-9+\/]{200,}={0,2}/',
        ];

        $reasons = [];
        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $content)) {
                $reasons[] = $label;
            }
        }

        if (stripos($path, '/uploads/') !== false && preg_match('/\.(php|phtml|phar)$/i', $path)) {
            $reasons[] = __('PHP trong uploads', 'wp-plugin-security');
        }

        return [
            'suspicious' => !empty($reasons),
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    /**
     * Chuan hoa duong dan de hien thi.
     */
    private function relativize_path($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        $root = str_replace('\\', '/', ABSPATH);

        if (stripos($path, $root) === 0) {
            $path = ltrim(substr($path, strlen($root)), '/');
            return 'ABSPATH/' . $path;
        }

        return $path;
    }

    /**
     * Render checkbox row don gian cho tab monitoring.
     */
    private function render_checkbox_row($key, $label, array $settings, $description)
    {
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html__($label, 'wp-plugin-security'); ?></label></th>
            <td class="wps-inline-setting">
                <input type="checkbox" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($settings[$key] ?? false); ?>>
                <span class="description"><?php echo esc_html__($description, 'wp-plugin-security'); ?></span>
            </td>
        </tr>
        <?php
    }

    /**
     * Render number row don gian cho tab monitoring.
     */
    private function render_number_row($key, $label, array $settings, $default = 0, $description = '')
    {
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html__($label, 'wp-plugin-security'); ?></label></th>
            <td>
                <input type="number" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($settings[$key] ?? $default); ?>" class="small-text">
                <?php if ($description !== '') : ?>
                    <p class="description"><?php echo esc_html__($description, 'wp-plugin-security'); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

// Copyright by AcmaTvirus
