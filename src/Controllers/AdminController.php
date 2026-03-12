<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

/**
 * Controller xử lý các thiết lập trong trang quản trị
 */
class AdminController
{

    public function __construct()
    {
        // Đăng ký menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Đăng ký settings
        add_action('admin_init', [$this, 'register_settings']);

        // Đăng ký assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Đăng ký action links trực tiếp trong constructor (vì plugin đã load)
        $plugin_base = plugin_basename(WPS_PLUGIN_FILE);
        add_filter("plugin_action_links_{$plugin_base}", [$this, 'add_plugin_action_links']);
    }

    /**
     * Enqueue Bootstrap và CSS/JS tùy chỉnh
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'wp-plugin-security') === false) {
            return;
        }

        // Enqueue Bootstrap 5 từ CDN
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', [], '5.3.2');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.2', true);

        // Custom tweaks để tương thích với WP Admin
        wp_add_inline_style('bootstrap-css', '
            .wps-admin-wrap { padding: 20px; background: #f0f2f5; min-height: calc(100vh - 32px); margin-left: -20px; }
            .wps-admin-wrap * { box-sizing: border-box; }
            .wps-admin-wrap .nav-pills .nav-link.active { background-color: #0d6efd; box-shadow: 0 4px 6px -1px rgba(13, 110, 253, 0.3); }
            .wps-admin-wrap .nav-pills .nav-link { color: #64748b; font-weight: 500; border-radius: 8px; }
            .wps-admin-wrap .card { border: none; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; }
            .wps-admin-wrap h1, .wps-admin-wrap h2, .wps-admin-wrap h3 { color: #1e293b; }
            .wps-admin-wrap .form-switch .form-check-input { width: 3em; height: 1.5em; cursor: pointer; }
            .wps-admin-wrap .btn-primary { background-color: #0d6efd; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; }
            .wps-admin-wrap .btn-primary:hover { background-color: #0b5ed7; transform: translateY(-1px); }
            .wps-admin-wrap .table thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.025em; border-top: none; }
            .wps-admin-wrap .badge { padding: 0.5em 0.8em; border-radius: 6px; }
            #wpfooter { display: none; }
        ');
    }

    /**
     * Thêm liên kết Settings và Check Update vào danh sách plugin
     */
    public function add_plugin_action_links($links)
    {
        $settings_url = admin_url('admin.php?page=wp-plugin-security');
        $update_url = wp_nonce_url(admin_url('update-core.php?force-check=1'), 'upgrade-core');

        $custom_links = [
            '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'wp-plugin-security') . '</a>',
            '<a href="' . esc_url($update_url) . '" style="color: #d63638; font-weight: bold;">' . __('Check Update', 'wp-plugin-security') . '</a>'
        ];

        return array_merge($custom_links, (array)$links);
    }

    /**
     * Tạo menu trong admin
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'WP Security',
            'WP Security',
            'manage_options',
            'wp-plugin-security',
            [$this, 'render_admin_page'],
            'dashicons-shield-alt',
            80
        );
    }

    /**
     * Đăng ký settings
     */
    public function register_settings()
    {
        register_setting('wps_settings_group', 'wps_blocked_ips');
        register_setting('wps_settings_group', 'wps_whitelist_ips');
        register_setting('wps_settings_group', 'wps_main_settings');
    }

    /**
     * Render trang cấu hình
     */
    public function render_admin_page()
    {
        $current_tab = $_GET['tab'] ?? 'general';

        // Xử lý các hành động Tools (Post-Hack)
        if (isset($_POST['wps_tool_action']) && current_user_can('manage_options')) {
            check_admin_referer('wps_tool_nonce_action', 'wps_tool_nonce');
            $action = $_POST['wps_tool_action'];

            if ($action === 'kill_sessions') {
                $sessions = \WP_Session_Tokens::get_instance(get_current_user_id());
                $sessions->destroy_all();
                echo '<div class="updated"><p>Tất cả phiên làm việc đã được đăng xuất (bao gồm cả bạn).</p></div>';
            } elseif ($action === 'force_pw_reset') {
                global $wpdb;
                $wpdb->query("UPDATE $wpdb->users SET user_pass = 'RE-SET-ME' WHERE 1=1;");
                echo '<div class="updated"><p>Đã yêu cầu tất cả người dùng đổi mật khẩu (Mật khẩu cũ sẽ bị vô hiệu hóa).</p></div>';
            } elseif ($action === 'clear_logs') {
                update_option('wps_audit_logs', []);
                update_option('wps_security_logs', []);
                echo '<div class="updated"><p>Tất cả nhật ký đã được dọn dẹp.</p></div>';
            }
        }

        // Xử lý lưu thiết lập
        if (isset($_POST['wps_save_settings']) && current_user_can('manage_options')) {
            check_admin_referer('wps_settings_action', 'wps_settings_nonce');

            $main_settings = get_option('wps_main_settings', []);

            if ($current_tab === 'general') {
                $main_settings = array_merge($main_settings, [
                    'disable_xmlrpc'          => isset($_POST['disable_xmlrpc']),
                    'disable_rest_api'        => isset($_POST['disable_rest_api']),
                    'block_author_scan'       => isset($_POST['block_author_scan']),
                    'disable_file_editor'     => isset($_POST['disable_file_editor']),
                    'disable_directory_browsing' => isset($_POST['disable_directory_browsing']),
                    'hide_wp_version'         => isset($_POST['hide_wp_version']),
                    'enable_security_headers' => isset($_POST['enable_security_headers']),
                    'enable_audit_log'        => isset($_POST['enable_audit_log']),
                    'enable_email_alerts'     => isset($_POST['enable_email_alerts']),
                ]);
            } elseif ($current_tab === 'login') {
                $main_settings = array_merge($main_settings, [
                    'limit_login_attempts'    => isset($_POST['limit_login_attempts']),
                    'max_login_attempts'      => (int)($_POST['max_login_attempts'] ?? 5),
                    'lockout_duration'        => (int)($_POST['lockout_duration'] ?? 60),
                    'rename_login_slug'       => sanitize_title($_POST['rename_login_slug'] ?? ''),
                    'idle_logout_time'        => (int)($_POST['idle_logout_time'] ?? 0),
                    'enforce_strong_password' => isset($_POST['enforce_strong_password']),
                    'mask_login_errors'       => isset($_POST['mask_login_errors']),
                    'enable_2fa'              => isset($_POST['enable_2fa']),
                    'recaptcha_site_key'      => sanitize_text_field($_POST['recaptcha_site_key'] ?? ''),
                    'recaptcha_secret_key'    => sanitize_text_field($_POST['recaptcha_secret_key'] ?? ''),
                ]);
            }

            update_option('wps_main_settings', $main_settings);

            if ($current_tab === 'blacklist') {
                // Handle Blacklist
                $raw_ips = explode("\n", str_replace("\r", "", $_POST['wps_blocked_ips_raw'] ?? ''));
                $clean_ips = array_unique(array_filter(array_map('trim', $raw_ips)));
                update_option('wps_blocked_ips', $clean_ips);

                // Handle Whitelist
                $raw_white = explode("\n", str_replace("\r", "", $_POST['wps_whitelist_ips_raw'] ?? ''));
                $clean_white = array_unique(array_filter(array_map('trim', $raw_white)));
                update_option('wps_whitelist_ips', $clean_white);
            }

            echo '<div class="updated"><p>Cấu hình đã được lưu thành công.</p></div>';
        }

        $main_settings = get_option('wps_main_settings', [
            'limit_login_attempts'    => true,
            'max_login_attempts'      => 5,
            'lockout_duration'        => 60,
            'disable_xmlrpc'          => true,
            'disable_rest_api'        => true,
            'block_author_scan'       => true,
            'mask_login_errors'       => true,
            'hide_wp_version'         => true,
            'disable_file_editor'     => true,
            'enable_security_headers' => true,
            'enable_audit_log'        => true,
            'enforce_strong_password' => true,
            'enable_email_alerts'     => false,
            'enable_2fa'              => false,
        ]);

        $security_service = new \Acma\WpSecurity\Services\SecurityService();
        $security_score = $security_service->calculate_security_score();

        $blocked_ips = get_option('wps_blocked_ips', []);
        $ips_text = is_array($blocked_ips) ? implode("\n", $blocked_ips) : '';

        $whitelist_ips = get_option('wps_whitelist_ips', []);
        $whitelist_text = is_array($whitelist_ips) ? implode("\n", $whitelist_ips) : '';

        $audit_logs = get_option('wps_audit_logs', []);
        $security_logs = get_option('wps_security_logs', []);
?>
        <div class="wrap wps-admin-wrap container-fluid py-4">
            <!-- Header section with Brand and Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="card bg-primary text-white p-4 shadow-sm h-100 border-0 overflow-hidden position-relative">
                        <div class="position-absolute end-0 top-0 p-3 opacity-25">
                            <span class="dashicons dashicons-shield-alt" style="font-size: 120px; width: 120px; height: 120px;"></span>
                        </div>
                        <div class="d-flex align-items-center gap-4 position-relative">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 70px; height: 70px;">
                                <span class="dashicons dashicons-shield-alt text-primary" style="font-size: 36px; width: 36px; height: 36px;"></span>
                            </div>
                            <div>
                                <h1 class="h2 fw-bold mb-1 text-white">WP Plugin Security</h1>
                                <p class="mb-0 opacity-75 fs-5">Giải pháp bảo mật toàn diện cho WordPress</p>
                            </div>
                            <div class="ms-auto align-self-start">
                                <span class="badge bg-light text-primary py-2 px-3 fw-bold rounded-pill shadow-sm">v1.1.3</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 shadow-sm h-100 border-0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted fw-bold text-uppercase small">Điểm bảo mật</span>
                            <div class="p-2 <?php echo $security_score > 70 ? 'bg-success' : ($security_score > 40 ? 'bg-warning' : 'bg-danger'); ?> bg-opacity-10 rounded">
                                <span class="dashicons dashicons-performance text-<?php echo $security_score > 70 ? 'success' : ($security_score > 40 ? 'warning' : 'danger'); ?>"></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-end gap-2 mb-3">
                            <h2 class="display-4 fw-bold mb-0"><?php echo $security_score; ?></h2>
                            <span class="text-muted mb-2">/100</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?php echo $security_score > 70 ? 'bg-success' : ($security_score > 40 ? 'bg-warning' : 'bg-danger'); ?>" role="progressbar" style="width: <?php echo $security_score; ?>%"></div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            <?php 
                            if ($security_score >= 90) echo "Tuyệt vời! Website cực kỳ an toàn.";
                            elseif ($security_score >= 70) echo "Khá tốt. Hãy bật nốt các tính năng còn lại.";
                            else echo "Cảnh báo: Cần cấu hình thêm để bảo vệ website.";
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 p-0">
                    <ul class="nav nav-pills p-2 bg-light bg-opacity-50 m-2 rounded shadow-sm gap-2">
                        <li class="nav-item">
                            <a href="?page=wp-plugin-security&tab=general" class="nav-link <?php echo $current_tab === 'general' ? 'active shadow' : ''; ?>">
                                <span class="dashicons dashicons-admin-settings me-1"></span> Hệ thống & WAF
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?page=wp-plugin-security&tab=login" class="nav-link <?php echo $current_tab === 'login' ? 'active shadow' : ''; ?>">
                                <span class="dashicons dashicons-lock me-1"></span> Bảo mật Đăng nhập
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?page=wp-plugin-security&tab=blacklist" class="nav-link <?php echo $current_tab === 'blacklist' ? 'active shadow' : ''; ?>">
                                <span class="dashicons dashicons-no-alt me-1"></span> IP Blacklist
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?page=wp-plugin-security&tab=audit" class="nav-link <?php echo $current_tab === 'audit' ? 'active shadow' : ''; ?>">
                                <span class="dashicons dashicons-list-view me-1"></span> Audit Trail
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?page=wp-plugin-security&tab=monitoring" class="nav-link <?php echo $current_tab === 'monitoring' ? 'active shadow' : ''; ?>">
                                <span class="dashicons dashicons-visibility me-1"></span> Giám sát & Quét
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?page=wp-plugin-security&tab=tools" class="nav-link <?php echo $current_tab === 'tools' ? 'active shadow' : ''; ?>">
                                <span class="dashicons dashicons-hammer me-1"></span> Công cụ
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="card-body p-4 p-md-5 bg-white min-vh-50">
                    <?php if ($current_tab === 'general') : ?>
                        <form method="post" action="">
                            <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <h3 class="h5 fw-bold mb-4 border-bottom pb-3">
                                        <span class="dashicons dashicons-shield text-primary me-2"></span> Tường lửa & Hardening
                                    </h3>

                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Vô hiệu hóa XML-RPC</h4>
                                                <p class="text-muted small mb-0">Ngăn chặn tấn công brute-force qua cổng XML-RPC.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="disable_xmlrpc" <?php checked($main_settings['disable_xmlrpc'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Hạn chế REST API</h4>
                                                <p class="text-muted small mb-0">Chỉ cho phép người dùng đã đăng nhập truy cập API.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="disable_rest_api" <?php checked($main_settings['disable_rest_api'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Chặn Author Scan</h4>
                                                <p class="text-muted small mb-0">Ngăn bot dò tìm username quản trị viên.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="block_author_scan" <?php checked($main_settings['block_author_scan'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Chặn Directory Browsing</h4>
                                                <p class="text-muted small mb-0">Ngăn người lạ duyệt file trong thư mục.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="disable_directory_browsing" <?php checked($main_settings['disable_directory_browsing'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Tắt trình chỉnh sửa file</h4>
                                                <p class="text-muted small mb-0">Vô hiệu hóa chỉnh sửa Code trực tiếp trong Admin.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="disable_file_editor" <?php checked($main_settings['disable_file_editor'] ?? false); ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <h3 class="h5 fw-bold mb-4 border-bottom pb-3">
                                        <span class="dashicons dashicons-visibility text-primary me-2"></span> Quyền riêng tư & Nhật ký
                                    </h3>

                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Ẩn phiên bản WP</h4>
                                                <p class="text-muted small mb-0">Xóa bỏ dấu hiệu nhận biết phiên bản từ mã nguồn.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="hide_wp_version" <?php checked($main_settings['hide_wp_version'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Security Headers</h4>
                                                <p class="text-muted small mb-0">Kích hoạt HSTS, XSS Protection, nosniff...</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="enable_security_headers" <?php checked($main_settings['enable_security_headers'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Audit Trail</h4>
                                                <p class="text-muted small mb-0">Lưu lại mọi hoạt động của người dùng vào nhật ký.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="enable_audit_log" <?php checked($main_settings['enable_audit_log'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Thông báo qua Email</h4>
                                                <p class="text-muted small mb-0">Gửi cảnh báo ngay khi có sự cố bảo mật.</p>
                                            </div>
                                            <div class="form-check form-switch p-0 ms-3">
                                                <input class="form-check-input ms-0" type="checkbox" name="enable_email_alerts" <?php checked($main_settings['enable_email_alerts'] ?? false); ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-4 rounded-3 border-0 shadow-sm">
                                        <div class="d-flex align-items-center">
                                            <span class="dashicons dashicons-info me-2 text-primary"></span>
                                            <strong>Mẹo:</strong> Bật Security Headers giúp bảo vệ website khỏi các cuộc tấn công phổ biến như clickjacking và XSS.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="wps_save_settings" value="1">
                            <div class="mt-5 border-top pt-4 text-end">
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow">Lưu thiết lập Hệ thống</button>
                            </div>
                        </form>

                    <?php elseif ($current_tab === 'login') : ?>
                        <form method="post" action="">
                            <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <div class="card bg-light border-0 rounded-4 mb-4">
                                        <div class="card-body p-4">
                                            <h3 class="h5 fw-bold mb-4">
                                                <span class="dashicons dashicons-admin-links text-primary me-2"></span> Đổi đường dẫn đăng nhập
                                            </h3>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Đường dẫn đăng nhập mới</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white text-muted small"><?php echo home_url('/'); ?></span>
                                                    <input type="text" class="form-control" name="rename_login_slug" value="<?php echo esc_attr($main_settings['rename_login_slug'] ?? ''); ?>" placeholder="vi-du: secret-login">
                                                </div>
                                                <div class="form-text mt-2">Nếu để trống, plugin sẽ dùng <code>wp-login.php</code> mặc định.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <h3 class="h5 fw-bold mb-4 border-bottom pb-3 mt-4">
                                        <span class="dashicons dashicons-shield-alt text-primary me-2"></span> Brute Force Protection
                                    </h3>
                                    <div class="list-group list-group-flush mb-4">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Giới hạn đăng nhập</h4>
                                                <p class="text-muted small mb-0">Khóa IP nếu đăng nhập sai nhiều lần.</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="limit_login_attempts" <?php checked($main_settings['limit_login_attempts'] ?? false); ?>>
                                            </div>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Ẩn lỗi đăng nhập</h4>
                                                <p class="text-muted small mb-0">Không cho biết chi tiết lỗi (username hay password sai).</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="mask_login_errors" <?php checked($main_settings['mask_login_errors'] ?? false); ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 p-3 bg-light rounded-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-muted">Thử tối đa (lần)</label>
                                            <input type="number" class="form-control" name="max_login_attempts" value="<?php echo esc_attr($main_settings['max_login_attempts'] ?? 5); ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-muted">Thời gian khóa (phút)</label>
                                            <input type="number" class="form-control" name="lockout_duration" value="<?php echo esc_attr($main_settings['lockout_duration'] ?? 60); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <h3 class="h5 fw-bold mb-4 border-bottom pb-3">
                                        <span class="dashicons dashicons-admin-users text-primary me-2"></span> Chính sách người dùng
                                    </h3>
                                    
                                    <div class="list-group list-group-flush mb-4">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Bắt buộc mật khẩu mạnh</h4>
                                                <p class="text-muted small mb-0">Tất cả người dùng phải sử dụng 12 ký tự + ký tự đặc biệt.</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enforce_strong_password" <?php checked($main_settings['enforce_strong_password'] ?? false); ?>>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 px-0">
                                            <div>
                                                <h4 class="h6 fw-bold mb-1">Xác thực 2 lớp (2FA)</h4>
                                                <p class="text-muted small mb-0">Gửi mã xác thực qua email sau khi đăng nhập.</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_2fa" <?php checked($main_settings['enable_2fa'] ?? false); ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card bg-light border-0 rounded-4 p-4 shadow-sm">
                                        <h3 class="h6 fw-bold mb-3">Google reCAPTCHA v3</h3>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Site Key</label>
                                            <input type="text" class="form-control" name="recaptcha_site_key" value="<?php echo esc_attr($main_settings['recaptcha_site_key'] ?? ''); ?>" placeholder="6Ld...">
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label small fw-bold">Secret Key</label>
                                            <input type="password" class="form-control" name="recaptcha_secret_key" value="<?php echo esc_attr($main_settings['recaptcha_secret_key'] ?? ''); ?>" placeholder="***">
                                        </div>
                                    </div>
                                </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Tự động đăng xuất khi nhàn rỗi (phút)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="idle_logout_time" value="<?php echo esc_attr($main_settings['idle_logout_time'] ?? 0); ?>">
                                            <span class="input-group-text">phút</span>
                                        </div>
                                        <div class="form-text mt-2">Nhập <code>0</code> để tắt chức năng tự động đăng xuất.</div>
                                    </div>

                                    <div class="alert alert-warning border-0 rounded-4 p-4 shadow-sm mt-5">
                                        <h5 class="alert-heading h6 fw-bold"><span class="dashicons dashicons-warning me-2 text-warning"></span> Chú ý bảo mật</h5>
                                        <p class="small mb-0 opacity-75">Sử dụng tính năng <strong>Đổi đường dẫn đăng nhập</strong> là cách hiệu quả nhất để giảm 99% các cuộc tấn công brute-force tự động từ bot.</p>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="wps_save_settings" value="1">
                            <div class="mt-5 border-top pt-4 text-end">
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow">Lưu thiết lập Đăng nhập</button>
                            </div>
                        </form>

                    <?php elseif ($current_tab === 'blacklist') : ?>
                        <form method="post" action="">
                            <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                            <div class="row g-5">
                                    <h3 class="h5 fw-bold mb-3">Quản lý IP Blacklist</h3>
                                    <p class="text-muted small mb-4">Danh sách IP bị cấm truy cập hoàn toàn.</p>
                                    <div class="form-floating position-relative mb-4">
                                        <textarea class="form-control bg-light font-monospace border-0 rounded-4 p-4" name="wps_blocked_ips_raw" style="height: 200px" placeholder="0.0.0.0"><?php echo esc_textarea($ips_text); ?></textarea>
                                        <label>IP Blacklist</label>
                                    </div>

                                    <h3 class="h5 fw-bold mb-3">Quản lý IP Whitelist</h3>
                                    <p class="text-muted small mb-4">Danh sách IP "tin cậy" sẽ không bao giờ bị chặn.</p>
                                    <div class="form-floating position-relative">
                                        <textarea class="form-control bg-light font-monospace border-0 rounded-4 p-4" name="wps_whitelist_ips_raw" style="height: 150px" placeholder="0.0.0.0"><?php echo esc_textarea($whitelist_text); ?></textarea>
                                        <label>IP Whitelist</label>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary px-4 py-2 shadow">Cập nhật danh sách IP</button>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="card h-100 bg-light border-0 rounded-4">
                                        <div class="card-body p-4">
                                            <h3 class="h6 fw-bold mb-4 text-uppercase text-muted">Nhật ký chặn tự động</h3>
                                            <div class="overflow-auto" style="max-height: 400px;">
                                                <?php
                                                $auto_blocked = array_filter($security_logs, fn($l) => in_array($l['type'], ['ip_blocked', 'dangerous_request']));
                                                if (empty($auto_blocked)) : ?>
                                                    <div class="text-center py-5 opacity-50">
                                                        <span class="dashicons dashicons-shield-alt display-4"></span>
                                                        <p class="mt-3">Chưa có IP bị chặn tự động.</p>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="list-group list-group-flush bg-transparent">
                                                        <?php foreach (array_slice($auto_blocked, 0, 20) as $log) : ?>
                                                            <div class="list-group-item bg-transparent px-0 py-3 border-bottom border-light">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <code class="fw-bold fs-6 text-primary"><?php echo $log['ip']; ?></code>
                                                                    <span class="text-muted opacity-75 small"><?php echo date('H:i d/m', strtotime($log['time'])); ?></span>
                                                                </div>
                                                                <p class="small mb-0 text-dark opacity-75"><?php echo $log['message']; ?></p>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="wps_save_settings" value="1">
                        </form>

                    <?php elseif ($current_tab === 'audit') : ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="h5 fw-bold mb-0">Nhật ký hoạt động (Audit Trail)</h3>
                            <div class="badge bg-primary rounded-pill px-3 py-2"><?php echo count($audit_logs); ?> bản ghi</div>
                        </div>
                        
                        <div class="table-responsive rounded-4 shadow-sm border overflow-hidden">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" width="150">Thời gian</th>
                                        <th width="180">Người dùng</th>
                                        <th width="140">Hành động</th>
                                        <th>Thông tin chi tiết</th>
                                        <th class="pe-4" width="150">Địa chỉ IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($audit_logs)) : ?>
                                        <tr>
                                            <td colspan="5" class="py-5 text-center opacity-50">
                                                <span class="dashicons dashicons-database display-4"></span>
                                                <p class="mt-3 fs-5">Chưa có hoạt động nào được ghi lại.</p>
                                            </td>
                                        </tr>
                                    <?php else : foreach ($audit_logs as $log) : 
                                        $action_icon = 'info-outline';
                                        $bg_class = 'bg-secondary';
                                        $action_type = strtolower($log['action'] ?? '');
                                        if (strpos($action_type, 'login') !== false) { $action_icon = 'lock'; $bg_class = 'bg-success'; }
                                        elseif (strpos($action_type, 'update') !== false) { $action_icon = 'update'; $bg_class = 'bg-primary'; }
                                        elseif (strpos($action_type, 'security') !== false) { $action_icon = 'shield'; $bg_class = 'bg-danger'; }
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="text-dark fw-bold"><?php echo date('H:i:s', strtotime($log['time'] ?? 'now')); ?></div>
                                                <div class="text-muted small"><?php echo date('d/m/Y', strtotime($log['time'] ?? 'now')); ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <span class="dashicons dashicons-admin-users text-muted" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                                    </div>
                                                    <span class="fw-bold"><?php echo esc_html($log['user'] ?? 'Guest'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $bg_class; ?> bg-opacity-75 text-white fw-bold text-uppercase w-100 py-2" style="font-size: 0.65rem;">
                                                    <?php echo esc_html($log['action'] ?? 'INFO'); ?>
                                                </span>
                                            </td>
                                            <td class="text-dark opacity-75 py-3"><?php echo esc_html($log['message'] ?? ''); ?></td>
                                            <td class="pe-4">
                                                <code class="px-2 py-1 bg-light rounded text-primary small fw-bold"><?php echo esc_html($log['ip'] ?? '0.0.0.0'); ?></code>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($current_tab === 'monitoring') : 
                        $malware_files = $security_service->scan_for_malware();
                        $integrity_changes = $security_service->check_file_integrity();
                        $sessions = $security_service->get_active_sessions(get_current_user_id());
                    ?>
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-body p-4">
                                        <h3 class="h5 fw-bold mb-4"><span class="dashicons dashicons-search text-primary me-2"></span> Malware Scanner (Uploads)</h3>
                                        <?php if (empty($malware_files)) : ?>
                                            <div class="alert alert-success border-0 rounded-4">
                                                <span class="dashicons dashicons-yes me-2"></span> Không tìm thấy script nguy hiểm trong thư mục uploads.
                                            </div>
                                        <?php else : ?>
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($malware_files as $file) : ?>
                                                    <div class="list-group-item px-0 py-3 border-bottom">
                                                        <div class="d-flex justify-content-between">
                                                            <code class="text-danger fw-bold small"><?php echo esc_html($file['path']); ?></code>
                                                            <span class="badge bg-danger rounded-pill"><?php echo $file['size']; ?></span>
                                                        </div>
                                                        <div class="text-muted small mt-1">Phát hiện lúc: <?php echo $file['time']; ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body p-4">
                                        <h3 class="h5 fw-bold mb-4"><span class="dashicons dashicons-media-text text-primary me-2"></span> File Integrity (24h qua)</h3>
                                        <?php if (empty($integrity_changes)) : ?>
                                            <div class="alert alert-info border-0 rounded-4">
                                                <span class="dashicons dashicons-info me-2"></span> Các file hệ thống không có thay đổi trong 24h qua.
                                            </div>
                                        <?php else : ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead><tr><th>Tên file</th><th>Thay đổi cuối</th></tr></thead>
                                                    <tbody>
                                                        <?php foreach ($integrity_changes as $change) : ?>
                                                            <tr>
                                                                <td><strong class="text-warning"><?php echo esc_html($change['file']); ?></strong></td>
                                                                <td class="small"><?php echo $change['time']; ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body p-4">
                                        <h3 class="h5 fw-bold mb-4"><span class="dashicons dashicons-admin-users text-primary me-2"></span> Phiên đăng nhập của bạn</h3>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($sessions as $verifier => $session) : 
                                                $is_current = (wp_get_session_token() === $verifier);
                                            ?>
                                                <div class="list-group-item px-0 py-3 <?php echo $is_current ? 'bg-light rounded-3 px-3' : ''; ?>">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold"><?php echo esc_html($session['ua']); ?></div>
                                                            <div class="small text-muted"><?php echo $session['ip']; ?> — <?php echo date('H:i d/m/Y', $session['login']); ?></div>
                                                        </div>
                                                        <?php if ($is_current) : ?>
                                                            <span class="badge bg-success">Hiện tại</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($current_tab === 'tools') : ?>
                        <h3 class="h5 fw-bold mb-4">Công cụ ứng phó sự cố</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-top border-5 border-warning">
                                    <div class="card-body p-4 text-center">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                                            <span class="dashicons dashicons-warning" style="font-size: 40px; width: 40px; height: 40px;"></span>
                                        </div>
                                        <h4 class="h5 fw-bold mb-3">Ngắt toàn bộ phiên làm việc</h4>
                                        <p class="text-muted small mb-4">Đăng xuất tất cả người dùng ngay lập tức (bao gồm cả bạn). Sử dụng nếu nghi ngờ website đang bị tấn công bởi hacker đã lấy được session.</p>
                                        <form method="post" action="" onsubmit="return confirm('Bạn sẽ bị đăng xuất ngay lập tức và phải đăng nhập lại. Tiếp tục?');">
                                            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
                                            <input type="hidden" name="wps_tool_action" value="kill_sessions">
                                            <button type="submit" class="btn btn-outline-warning fw-bold w-100 py-3 rounded-3 mt-auto">Kích hoạt Logout All</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-top border-5 border-danger">
                                    <div class="card-body p-4 text-center">
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                                            <span class="dashicons dashicons-update" style="font-size: 40px; width: 40px; height: 40px;"></span>
                                        </div>
                                        <h4 class="h5 fw-bold mb-3 text-danger">Reset mật khẩu toàn website</h4>
                                        <p class="text-muted small mb-4"><strong>Hành động cực kỳ nghiêm trọng:</strong> Vô hiệu hóa toàn bộ mật khẩu hiện tại. Người dùng sẽ phải thực hiện "Quên mật khẩu" để truy cập lại.</p>
                                        <form method="post" action="" onsubmit="return confirm('HƯ HẠI NẶNG: Toàn bộ mật khẩu người dùng sẽ bị xóa. Đây là biện pháp cuối cùng khi database bị lộ. Tiếp tục?');">
                                            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
                                            <input type="hidden" name="wps_tool_action" value="force_pw_reset">
                                            <button type="submit" class="btn btn-danger fw-bold w-100 py-3 rounded-3 mt-auto shadow">Kích hoạt Reset Pass Toàn diện</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="card border-0 shadow-sm rounded-4 bg-white">
                                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="h5 fw-bold mb-1">Dọn dẹp nhật ký hệ thống</h4>
                                            <p class="text-muted small mb-0">Xóa toàn bộ Audit Trail và Security Logs để giải phóng dung lượng Database.</p>
                                        </div>
                                        <form method="post" action="" onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ nhật ký?');">
                                            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
                                            <input type="hidden" name="wps_tool_action" value="clear_logs">
                                            <button type="submit" class="btn btn-outline-secondary px-4 fw-bold">Dọn dẹp Log</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-dark border-0 rounded-4 p-4 shadow-sm mt-5">
                            <div class="d-flex align-items-start gap-3">
                                <span class="dashicons dashicons-shield-alt text-primary mt-1"></span>
                                <div>
                                    <h5 class="h6 fw-bold mb-2">Quy trình ứng phó được AcmaTvirus khuyến nghị:</h5>
                                    <ol class="small mb-0 opacity-75 ps-3">
                                        <li>Kích hoạt "Ngắt toàn bộ phiên làm việc" để đẩy hacker ra ngoài.</li>
                                        <li>Đổi đường dẫn đăng nhập trong tab "Bảo mật Đăng nhập".</li>
                                        <li>Nếu nghi ngờ Database bị rò rỉ, hãy thực hiện "Reset mật khẩu toàn website".</li>
                                        <li>Kiểm tra file <code>wp-config.php</code> để xem có code lạ không.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center text-muted small mt-5">
                <p>Copyright © <?php echo date('Y'); ?> by <strong>AcmaTvirus</strong> — WP Plugin Security v1.1.3</p>
            </div>
        </div>
<?php
    }
}

// Copyright by AcmaTvirus
