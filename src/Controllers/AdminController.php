<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

/**
 * Controller chính cho trang quản trị
 * Nhiệm vụ chính: Điều hướng (Routing) và Ủy quyền (Delegation) cho các Feature Controllers
 */
class AdminController
{
    /**
     * Khởi tạo các hooks cho admin
     */
    public function init_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        $plugin_file = 'wp-plugin-security/wp-plugin-security.php';
        add_filter("plugin_action_links_$plugin_file", [$this, 'add_action_links']);
    }

    /**
     * Enqueue CSS/JS cho trang quản trị
     */
    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_wp-plugin-security') {
            return;
        }

        // Chỉ giữ lại Dashicons (đã có sẵn trong WP)
        wp_enqueue_style('dashicons');

        // Thêm một chút CSS tinh chỉnh để phù hợp với WP hơn
        $custom_css = "
            .wps-card { background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; margin-top: 20px; }
            .wps-score-circle { width: 100px; height: 100px; border-radius: 50%; border: 8px solid #f0f0f1; display: flex; items-center; justify-content: center; font-size: 24px; font-weight: bold; margin: 0 auto 10px; }
            .wps-score-green { border-color: #46b450; color: #46b450; }
            .wps-score-yellow { border-color: #ffb900; color: #ffb900; }
            .wps-score-red { border-color: #dc3232; color: #dc3232; }
            .wps-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
            .nav-tab-wrapper { margin-bottom: 20px; }
        ";
        wp_add_inline_style('wp-admin', $custom_css);
    }

    /**
     * Thêm link 'Settings' trong trang Plugins
     */
    public function add_action_links($links)
    {
        $custom_links = [
            '<a href="' . admin_url('admin.php?page=wp-plugin-security') . '">Cài đặt</a>',
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
     * Khởi tạo các Feature Controllers
     */
    private function get_feature_controllers()
    {
        return [
            'dashboard'  => new \Acma\WpSecurity\Features\Dashboard\DashboardController(),
            'firewall'   => new \Acma\WpSecurity\Features\Firewall\FirewallController(),
            'auth'       => new \Acma\WpSecurity\Features\Auth\AuthController(),
            'audit'      => new \Acma\WpSecurity\Features\Audit\AuditController(),
            'monitoring' => new \Acma\WpSecurity\Features\Monitoring\MonitoringController(),
            'tools'      => new \Acma\WpSecurity\Features\Tools\ToolsController(),
        ];
    }

    /**
     * Render trang chính
     */
    public function render_admin_page()
    {
        $current_tab = $_GET['tab'] ?? 'dashboard';
        $features = $this->get_feature_controllers();

        // Xử lý POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['wps_tool_action'])) {
                $features['tools']->handle_actions();
            }

            if (isset($_POST['wps_save_settings'])) {
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
                    $features['firewall']->handle_save_ips();
                }

                echo '<div class="updated"><p>Cấu hình đã được lưu thành công.</p></div>';
            }
        }

        $main_settings = get_option('wps_main_settings', []);
        $security_logs = get_option('wps_security_logs', []);
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p class="description">Được cung cấp bởi AcmaTvirus Intelligence</p>
            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper">
                <a href="?page=wp-plugin-security&tab=dashboard" class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">Bảng điều khiển</a>
                <a href="?page=wp-plugin-security&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">Hệ thống</a>
                <a href="?page=wp-plugin-security&tab=login" class="nav-tab <?php echo $current_tab === 'login' ? 'nav-tab-active' : ''; ?>">Đăng nhập</a>
                <a href="?page=wp-plugin-security&tab=blacklist" class="nav-tab <?php echo $current_tab === 'blacklist' ? 'nav-tab-active' : ''; ?>">Firewall IP</a>
                <a href="?page=wp-plugin-security&tab=audit" class="nav-tab <?php echo $current_tab === 'audit' ? 'nav-tab-active' : ''; ?>">Audit Log</a>
                <a href="?page=wp-plugin-security&tab=monitoring" class="nav-tab <?php echo $current_tab === 'monitoring' ? 'nav-tab-active' : ''; ?>">Theo dõi</a>
                <a href="?page=wp-plugin-security&tab=tools" class="nav-tab <?php echo $current_tab === 'tools' ? 'nav-tab-active' : ''; ?>">Công cụ</a>
            </nav>

            <div class="wps-content">
                <?php 
                switch ($current_tab) {
                    case 'general':
                        $features['firewall']->render_general_tab($main_settings);
                        break;
                    case 'login':
                        $features['auth']->render_tab($main_settings);
                        break;
                    case 'blacklist':
                        $features['firewall']->render_blacklist_tab($security_logs);
                        break;
                    case 'audit':
                        $features['audit']->render_tab();
                        break;
                    case 'monitoring':
                        $features['monitoring']->render_tab();
                        break;
                    case 'tools':
                        $features['tools']->render_tab();
                        break;
                    case 'dashboard':
                    default:
                        $features['dashboard']->render_overview();
                        break;
                }
                ?>
            </div>

            <div style="margin-top: 50px; border-top: 1px solid #ccd0d4; padding-top: 20px; color: #646970; font-size: 11px;">
                <p>WP Plugin Security &bull; Phiên bản 2.1.0 &bull; Copyright by AcmaTvirus</p>
            </div>
        </div>
<?php
    }
}
