<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

use Acma\WpSecurity\Services\SecurityService;

/**
 * Controller quản lý các hook bảo mật.
 */
class SecurityController
{
    /**
     * @var SecurityService
     */
    private $security_service;

    /**
     * @var \Acma\WpSecurity\Services\AuditService
     */
    private $audit_service;

    public function __construct()
    {
        $this->security_service = new SecurityService();
        $this->audit_service = new \Acma\WpSecurity\Services\AuditService();
        $this->init_hooks();
        $this->init_audit_hooks();
    }

    /**
     * Đăng ký các WordPress hooks.
     */
    private function init_hooks()
    {
        add_action('init', [$this, 'handle_security_checks'], 1);
        add_action('init', [$this, 'handle_rename_login']);
        add_filter('site_url', [$this, 'fix_login_urls'], 10, 4);
        add_filter('network_site_url', [$this, 'fix_login_urls'], 10, 4);
        add_action('login_form', [$this, 'render_two_factor_field']);

        add_action('init', [$this, 'handle_idle_logout']);
        add_action('template_redirect', [$this, 'handle_404_monitoring'], 1);
        add_filter('wp_headers', [$this, 'add_security_headers']);

        if ($this->security_service->get_setting('disable_xmlrpc', true)) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_xmlrpc_server_class', '__return_false');
        }

        if ($this->security_service->get_setting('hide_wp_version', true)) {
            add_filter('the_generator', '__return_empty_string');
            remove_action('wp_head', 'wp_generator');
        }

        if ($this->security_service->get_setting('block_author_scan', true)) {
            if (!is_admin() && isset($_GET['author'])) {
                $this->security_service->log_event('author_scan', __('Phát hiện hành vi quét tác giả', 'wp-plugin-security'));
                wp_die(__('Quét tác giả đã bị vô hiệu hóa vì lý do bảo mật.', 'wp-plugin-security'), __('Lỗi bảo mật', 'wp-plugin-security'), ['response' => 403]);
            }
        }

        if ($this->security_service->get_setting('disable_rest_api', true)) {
            add_filter('rest_authentication_errors', function ($result) {
                if (!empty($result)) {
                    return $result;
                }

                if (!is_user_logged_in()) {
                    return new \WP_Error('rest_forbidden', __('REST API chỉ dành cho người dùng đã đăng nhập.', 'wp-plugin-security'), ['status' => 401]);
                }

                return $result;
            });
        }

        add_action('wp_login_failed', [$this, 'handle_failed_login']);
        add_filter('authenticate', [$this, 'check_login_lockout'], 30, 3);
        add_filter('authenticate', [$this, 'check_two_factor_code'], 40, 3);

        if ($this->security_service->get_setting('enforce_strong_password', true)) {
            add_action('user_profile_update_errors', [$this, 'check_strong_password'], 10, 3);
            add_action('validate_password_reset', [$this, 'check_password_reset_strength'], 10, 2);
        }

        if ($this->security_service->get_setting('mask_login_errors', true)) {
            add_filter('login_errors', function () {
                return __('Sai thông tin đăng nhập. Vui lòng thử lại.', 'wp-plugin-security');
            });
        }

        add_action('admin_init', [$this, 'protect_admin_area']);

        if ($this->security_service->get_setting('disable_file_editor', true) && !defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }

    /**
     * Đăng ký các hook theo dõi hoạt động.
     */
    private function init_audit_hooks()
    {
        if (!$this->security_service->get_setting('enable_audit_log', true)) {
            return;
        }

        add_action('wp_login', function ($user_login, $user) {
            $this->audit_service->log('login', sprintf(__('Người dùng %s đã đăng nhập', 'wp-plugin-security'), $user_login), $user->ID);
        }, 10, 2);

        add_action('wp_logout', function () {
            $user_id = get_current_user_id();
            $this->audit_service->log('logout', __('Người dùng đã đăng xuất', 'wp-plugin-security'), $user_id);
        });

        add_action('switch_theme', function ($new_name) {
            $this->audit_service->log('theme_change', sprintf(__('Đổi giao diện sang: %s', 'wp-plugin-security'), $new_name));
        });

        add_action('activated_plugin', function ($plugin) {
            $this->audit_service->log('plugin_activate', sprintf(__('Kích hoạt plugin: %s', 'wp-plugin-security'), $plugin));
        });

        add_action('deactivated_plugin', function ($plugin) {
            $this->audit_service->log('plugin_deactivate', sprintf(__('Hủy kích hoạt plugin: %s', 'wp-plugin-security'), $plugin));
        });

        add_action('save_post', function ($post_id, $post, $update) {
            if ($update) {
                $this->audit_service->log('post_update', sprintf(__('Cập nhật bài viết: %s', 'wp-plugin-security'), get_the_title($post_id)));
            }
        }, 10, 3);
    }

    /**
     * Xử lý Rename Login Page.
     */
    public function handle_rename_login()
    {
        $slug = $this->security_service->get_setting('rename_login_slug', '');
        if (empty($slug)) {
            return;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_login = (strpos($request_uri, 'wp-login.php') !== false || strpos($request_uri, 'wp-admin') !== false);
        $is_custom = (strpos($request_uri, $slug) !== false);

        if ($is_login && !$is_custom && !is_user_logged_in() && !defined('DOING_AJAX')) {
            wp_safe_redirect(home_url());
            exit;
        }

        if ($is_custom && !is_user_logged_in()) {
            include ABSPATH . 'wp-login.php';
            exit;
        }
    }

    /**
     * Sửa URL đăng nhập trong toàn trang.
     */
    public function fix_login_urls($url, $path, $scheme, $blog_id)
    {
        $slug = $this->security_service->get_setting('rename_login_slug', '');
        if (empty($slug)) {
            return $url;
        }

        if (strpos($url, 'wp-login.php') !== false) {
            return str_replace('wp-login.php', $slug, $url);
        }

        return $url;
    }

    /**
     * Xử lý Idle Logout.
     */
    public function handle_idle_logout()
    {
        $this->security_service->check_idle_timeout();
    }

    /**
     * Kiểm tra mật khẩu mạnh khi sửa hồ sơ người dùng.
     */
    public function check_strong_password($errors, $update, $user)
    {
        $password = $_POST['pass1'] ?? '';
        if (empty($password)) {
            return;
        }

        if (!$this->is_strong_password($password)) {
            $errors->add('weak_password', '<strong>' . esc_html__('Lỗi:', 'wp-plugin-security') . '</strong> ' . esc_html__('Mật khẩu phải dài ít nhất 12 ký tự, bao gồm chữ hoa, số và ký tự đặc biệt.', 'wp-plugin-security'));
        }
    }

    /**
     * Kiểm tra mật khẩu mạnh khi reset password.
     */
    public function check_password_reset_strength($errors, $user)
    {
        $password = $_POST['pass1'] ?? '';
        if (empty($password)) {
            return;
        }

        if (!$this->is_strong_password($password)) {
            $errors->add('weak_password', '<strong>' . esc_html__('Lỗi:', 'wp-plugin-security') . '</strong> ' . esc_html__('Mật khẩu phải dài ít nhất 12 ký tự, bao gồm chữ hoa, số và ký tự đặc biệt.', 'wp-plugin-security'));
        }
    }

    /**
     * Thực hiện các kiểm tra bảo mật.
     */
    public function handle_security_checks()
    {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($this->security_service->is_ip_blocked($user_ip)) {
            $this->security_service->log_event('ip_blocked', sprintf(__('IP %s cố gắng truy cập', 'wp-plugin-security'), $user_ip));
            wp_die(__('Truy cập bị chặn bởi WP Plugin Security!', 'wp-plugin-security'), __('Truy cập bị từ chối', 'wp-plugin-security'), ['response' => 403]);
        }

        if ($this->security_service->is_dangerous_request()) {
            $this->security_service->log_event('dangerous_request', sprintf(__('Phát hiện request nguy hiểm từ IP %s', 'wp-plugin-security'), $user_ip));
            wp_die(__('Phát hiện hành vi nguy hiểm!', 'wp-plugin-security'), __('Cảnh báo bảo mật', 'wp-plugin-security'), ['response' => 400]);
        }

        if ($this->security_service->should_block_uploads_php_request()) {
            $this->security_service->log_event('uploads_php_blocked', sprintf(__('Chặn request PHP trong uploads từ IP %s', 'wp-plugin-security'), $user_ip), $user_ip);
            wp_die(__('Truy cập file PHP trong uploads đã bị chặn.', 'wp-plugin-security'), __('Cảnh báo bảo mật', 'wp-plugin-security'), ['response' => 403]);
        }

        if ($this->security_service->is_geo_blocked($user_ip)) {
            $country_code = $this->security_service->get_client_country_code() ?: __('không xác định', 'wp-plugin-security');
            $this->security_service->log_event('geo_blocked', sprintf(__('Chặn truy cập từ quốc gia %1$s, IP %2$s', 'wp-plugin-security'), $country_code, $user_ip), $user_ip);
            wp_die(__('Khu vực của bạn hiện không được phép truy cập.', 'wp-plugin-security'), __('Truy cập bị từ chối', 'wp-plugin-security'), ['response' => 403]);
        }

        if ($this->security_service->is_rate_limited($user_ip, $_SERVER['REQUEST_URI'] ?? '', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            $this->security_service->log_event('rate_limited', sprintf(__('Rate limit kích hoạt cho IP %s', 'wp-plugin-security'), $user_ip), $user_ip);
            wp_die(__('Bạn đang gửi quá nhiều yêu cầu trong thời gian ngắn. Vui lòng thử lại sau.', 'wp-plugin-security'), __('Tạm thời bị giới hạn', 'wp-plugin-security'), ['response' => 429]);
        }
    }

    /**
     * Xử lý đăng nhập thất bại.
     */
    public function handle_failed_login($username)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->security_service->increment_login_attempts($ip);
    }

    /**
     * Ghi nhan cac trang 404 de phat hien do quet va auto-block.
     */
    public function handle_404_monitoring()
    {
        if (!is_404()) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->security_service->track_404($ip, $_SERVER['REQUEST_URI'] ?? '');
    }

    /**
     * Kiểm tra trạng thái khóa đăng nhập.
     */
    public function check_login_lockout($user, $username, $password)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->security_service->check_login_attempts($ip)) {
            return new \WP_Error('locked_out', __('IP của bạn tạm thời bị khóa do thử sai quá nhiều lần.', 'wp-plugin-security'));
        }

        return $user;
    }

    /**
     * Hiển thị ô nhập mã 2FA trên form đăng nhập.
     */
    public function render_two_factor_field()
    {
        if (!$this->security_service->is_two_factor_enabled()) {
            return;
        }
        ?>
        <p>
            <label for="wps_2fa_code"><?php esc_html_e('Mã xác thực 2FA', 'wp-plugin-security'); ?><br>
                <input type="text" name="wps_2fa_code" id="wps_2fa_code" class="input" value="" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6">
            </label>
        </p>
        <?php
    }

    /**
     * Kiểm tra mã xác thực 2FA sau khi username/password đã đúng.
     */
    public function check_two_factor_code($user, $username, $password)
    {
        if (!$user instanceof \WP_User) {
            return $user;
        }

        if (!$this->security_service->requires_two_factor_for_user($user)) {
            return $user;
        }

        $code = sanitize_text_field($_POST['wps_2fa_code'] ?? '');
        if ($code === '') {
            return new \WP_Error('wps_2fa_required', __('Vui lòng nhập mã xác thực 2FA.', 'wp-plugin-security'));
        }

        $secret = $this->security_service->get_two_factor_secret($user->ID);
        if ($secret === '' || !$this->security_service->verify_two_factor_code($secret, $code)) {
            $this->security_service->log_event('two_factor_failed', sprintf(__('Sai mã 2FA của user %s', 'wp-plugin-security'), $user->user_login), $user->ID);
            return new \WP_Error('wps_2fa_invalid', __('Mã xác thực 2FA không đúng.', 'wp-plugin-security'));
        }

        return $user;
    }

    /**
     * Thêm các headers bảo mật vào response.
     */
    public function add_security_headers($headers)
    {
        if ($this->security_service->get_setting('enable_security_headers', true)) {
            $security_headers = $this->security_service->get_security_headers();
            return array_merge($headers, $security_headers);
        }

        return $headers;
    }

    /**
     * Bảo vệ trang cấu hình của plugin trong admin.
     */
    public function protect_admin_area()
    {
        $page = sanitize_key($_GET['page'] ?? '');

        if (is_admin() && $page === 'wp-plugin-security' && !current_user_can('manage_options') && !defined('DOING_AJAX')) {
            wp_redirect(home_url());
            exit;
        }
    }

    /**
     * Kiểm tra password có đạt yêu cầu độ mạnh hay không.
     */
    private function is_strong_password($password)
    {
        return strlen($password) >= 12
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[^a-zA-Z0-9]/', $password);
    }
}

// Copyright by AcmaTvirus
