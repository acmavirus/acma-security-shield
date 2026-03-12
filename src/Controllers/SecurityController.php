<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

use Acma\WpSecurity\Services\SecurityService;

/**
 * Controller quản lý các hook bảo mật
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
     * Đăng ký các WordPress hooks
     */
    private function init_hooks()
    {
        // Kiểm tra truy cập sớm nhất có thể
        add_action('init', [$this, 'handle_security_checks'], 1);

        // Rename Login Page
        add_action('init', [$this, 'handle_rename_login']);
        add_filter('site_url', [$this, 'fix_login_urls'], 10, 4);
        add_filter('network_site_url', [$this, 'fix_login_urls'], 10, 4);

        // Idle Logout
        add_action('init', [$this, 'handle_idle_logout']);

        // Thêm các header bảo mật
        add_filter('wp_headers', [$this, 'add_security_headers']);

        // Privacy Hardening: Xóa các link rác trong head
        add_action('init', [$this, 'privacy_hardening']);

        // Vô hiệu hóa XML-RPC
        if ($this->security_service->get_setting('disable_xmlrpc', true)) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_xmlrpc_server_class', '__return_false');
        }

        // Ẩn phiên bản WordPress
        if ($this->security_service->get_setting('hide_wp_version', true)) {
            add_filter('the_generator', '__return_empty_string');
            remove_action('wp_head', 'wp_generator');
        }

        // Chặn Author Scans
        if ($this->security_service->get_setting('block_author_scan', true)) {
            if (!is_admin() && isset($_GET['author'])) {
                $this->security_service->log_event('author_scan', 'Phát hiện hành vi quét tác giả');
                wp_die('Author scanning is disabled for security reasons.', 'Security Error', ['response' => 403]);
            }
        }

        // Chặn REST API cho người dùng không đăng nhập
        if ($this->security_service->get_setting('disable_rest_api', true)) {
            add_filter('rest_authentication_errors', function ($result) {
                if (!empty($result)) return $result;
                if (!is_user_logged_in()) {
                    return new \WP_Error('rest_forbidden', 'REST API is restricted.', ['status' => 401]);
                }
                return $result;
            });
        }

        // Giới hạn đăng nhập
        add_action('wp_login_failed', [$this, 'handle_failed_login']);
        add_filter('authenticate', [$this, 'check_login_lockout'], 30, 3);

        // 2FA - Xác thực 2 lớp
        if ($this->security_service->get_setting('enable_2fa', false)) {
            add_action('wp_login', [$this, 'init_2fa_verification'], 10, 2);
            add_action('init', [$this, 'handle_2fa_submission']);
        }

        // Thông báo đăng nhập thành công
        add_action('wp_login', [$this, 'alert_on_login'], 20, 2);

        // Google reCAPTCHA v3
        $site_key = $this->security_service->get_setting('recaptcha_site_key', '');
        if (!empty($site_key)) {
            add_action('login_enqueue_scripts', [$this, 'enqueue_recaptcha']);
            add_action('login_form', [$this, 'add_recaptcha_field']);
            add_filter('wp_authenticate_user', [$this, 'verify_recaptcha'], 10, 2);
        }

        // Bắt buộc mật khẩu mạnh
        if ($this->security_service->get_setting('enforce_strong_password', true)) {
            add_action('user_profile_update_errors', [$this, 'check_strong_password'], 10, 3);
            add_action('validate_password_reset', [$this, 'check_strong_password'], 10, 3);
        }

        // Ẩn thông báo lỗi đăng nhập chi tiết
        if ($this->security_service->get_setting('mask_login_errors', true)) {
            add_filter('login_errors', function () {
                return 'Sai thông tin đăng nhập. Vui lòng thử lại.';
            });
        }

        // Bảo vệ admin area
        add_action('admin_init', [$this, 'protect_admin_area']);

        // Vô hiệu hóa File Editor
        if ($this->security_service->get_setting('disable_file_editor', true)) {
            if (!defined('DISALLOW_FILE_EDIT')) {
                define('DISALLOW_FILE_EDIT', true);
            }
        }
    }

    /**
     * Dọn dẹp các link không cần thiết trong head (Privacy Hardening)
     */
    public function privacy_hardening()
    {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'rest_output_link_wp_head');
    }

    /**
     * Cảnh báo khi có người đăng nhập thành công
     */
    public function alert_on_login($user_login, $user)
    {
        if ($this->security_service->get_setting('enable_email_alerts', false)) {
            $this->security_service->send_security_alert(
                'Thông báo: Đăng nhập thành công',
                "Tài khoản $user_login vừa đăng nhập thành công vào website."
            );
        }
    }

    /**
     * Bắt đầu xác thực 2FA sau khi đăng nhập đúng pass
     */
    public function init_2fa_verification($user_login, $user)
    {
        if (is_admin()) return; // Tránh loop trong admin

        // Đánh dấu user đang chờ 2FA
        set_transient('wps_pending_2fa_' . $user->ID, true, 15 * MINUTE_IN_SECONDS);
        $this->security_service->handle_2fa_email($user->ID);

        // Logout ngay để bắt xác thực
        wp_logout();

        // Redirect sang trang nhập mã (dùng luôn wp-login hoặc trang custom)
        wp_redirect(home_url('?wps_2fa_verify=1&uid=' . $user->ID));
        exit;
    }

    /**
     * Xử lý khi user submit mã 2FA
     */
    public function handle_2fa_submission()
    {
        if (isset($_GET['wps_2fa_verify']) && isset($_POST['wps_2fa_code'])) {
            $user_id = (int)$_GET['uid'];
            $code = sanitize_text_field($_POST['wps_2fa_code']);

            if ($this->security_service->verify_2fa($user_id, $code)) {
                delete_transient('wps_pending_2fa_' . $user_id);
                wp_set_auth_cookie($user_id);
                wp_redirect(admin_url());
                exit;
            } else {
                wp_die('Mã xác thực không đúng hoặc đã hết hạn.', '2FA Error', ['back_link' => true]);
            }
        }

        // Hiển thị form 2FA nếu đang trong luồng
        if (isset($_GET['wps_2fa_verify'])) {
            $this->render_2fa_form();
            exit;
        }
    }

    /**
     * Render Form 2FA đơn giản (Bootstrap)
     */
    private function render_2fa_form()
    {
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Xác thực 2 lớp - WP Security</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
                .card { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
            </style>
        </head>
        <body>
            <div class="card p-4" style="width: 100%; max-width: 400px;">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold">Xác thực 2 lớp</h1>
                    <p class="text-muted small">Vui lòng nhập mã bảo mật đã được gửi vào email của bạn.</p>
                </div>
                <form method="post" action="">
                    <div class="mb-3">
                        <input type="text" name="wps_2fa_code" class="form-control form-control-lg text-center fw-bold" placeholder="000000" maxlength="6" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Xác nhận</button>
                </form>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Enqueue Google reCAPTCHA v3 script
     */
    public function enqueue_recaptcha()
    {
        $site_key = $this->security_service->get_setting('recaptcha_site_key', '');
        wp_enqueue_script('google-recaptcha', "https://www.google.com/recaptcha/api.js?render={$site_key}", [], null, true);
    }

    /**
     * Thêm hidden field cho reCAPTCHA token vào login form
     */
    public function add_recaptcha_field()
    {
        $site_key = $this->security_service->get_setting('recaptcha_site_key', '');
        ?>
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
        <script>
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo $site_key; ?>', {action: 'login'}).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                });
            });
        </script>
        <?php
    }

    /**
     * Xác minh reCAPTCHA token khi submit login
     */
    public function verify_recaptcha($user, $password)
    {
        if (isset($_POST['g-recaptcha-response'])) {
            $token = $_POST['g-recaptcha-response'];
            $secret = $this->security_service->get_setting('recaptcha_secret_key', '');
            
            $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ]
            ]);

            $result = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($result['success']) || $result['score'] < 0.5) {
                return new \WP_Error('recaptcha_failed', '<strong>Lỗi:</strong> Phát hiện hành vi nghi ngờ (Bot). Vui lòng thử lại.');
            }
        }
        return $user;
    }

    /**
     * Đăng ký các hook theo dõi hoạt động (Audit Trail)
     */
    private function init_audit_hooks()
    {
        if (!$this->security_service->get_setting('enable_audit_log', true)) return;

        add_action('wp_login', function ($user_login, $user) {
            $this->audit_service->log('login', "Người dùng $user_login đã đăng nhập", $user->ID);
        }, 10, 2);

        add_action('wp_logout', function () {
            $user_id = get_current_user_id();
            $this->audit_service->log('logout', "Người dùng đã đăng xuất", $user_id);
        });

        add_action('switch_theme', function ($new_name) {
            $this->audit_service->log('theme_change', "Đổi giao diện sang: $new_name");
        });

        add_action('activated_plugin', function ($plugin) {
            $this->audit_service->log('plugin_activate', "Kích hoạt plugin: $plugin");
        });

        add_action('deactivated_plugin', function ($plugin) {
            $this->audit_service->log('plugin_deactivate', "Hủy kích hoạt plugin: $plugin");
        });

        add_action('save_post', function ($post_id, $post, $update) {
            if ($update) {
                $this->audit_service->log('post_update', "Cập nhật bài viết: " . get_the_title($post_id));
            }
        }, 10, 3);
    }

    /**
     * Xử lý Rename Login Page
     */
    public function handle_rename_login()
    {
        $slug = $this->security_service->get_setting('rename_login_slug', '');
        if (empty($slug)) return;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_login = (strpos($request_uri, 'wp-login.php') !== false || strpos($request_uri, 'wp-admin') !== false);
        $is_custom = (strpos($request_uri, $slug) !== false);

        if ($is_login && !$is_custom && !is_user_logged_in() && !defined('DOING_AJAX')) {
            wp_safe_redirect(home_url());
            exit;
        }

        if ($is_custom && !is_user_logged_in()) {
            // Load wp-login.php environment but keep the URL
            include ABSPATH . 'wp-login.php';
            exit;
        }
    }

    /**
     * Sửa URL đăng nhập trong toàn trang
     */
    public function fix_login_urls($url, $path, $scheme, $blog_id)
    {
        $slug = $this->security_service->get_setting('rename_login_slug', '');
        if (empty($slug)) return $url;

        if (strpos($url, 'wp-login.php') !== false) {
            return str_replace('wp-login.php', $slug, $url);
        }
        return $url;
    }

    /**
     * Xử lý Idle Logout
     */
    public function handle_idle_logout()
    {
        $this->security_service->check_idle_timeout();
    }

    /**
     * Kiểm tra mật khẩu mạnh
     */
    public function check_strong_password($errors, $update, $user)
    {
        $password = $_POST['pass1'] ?? '';
        if (empty($password)) return;

        if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors->add('weak_password', '<strong>Lỗi:</strong> Mật khẩu phải dài ít nhất 12 ký tự, bao gồm chữ hoa, số và ký tự đặc biệt.');
        }
    }

    /**
     * Thực hiện các kiểm tra bảo mật
     */
    public function handle_security_checks()
    {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Bỏ qua nếu là IP Whitelist
        if ($this->security_service->is_ip_whitelisted($user_ip)) {
            return;
        }

        // 1. Kiểm tra IP Blocked
        if ($this->security_service->is_ip_blocked($user_ip)) {
            $this->security_service->log_event('ip_blocked', "IP $user_ip cố gắng truy cập");
            wp_die('Truy cập bị chặn bởi WP Plugin Security!', 'Access Denied', ['response' => 403]);
        }

        // 2. Lọc các request nguy hiểm (SQLi, XSS)
        if ($this->security_service->is_dangerous_request()) {
            $this->security_service->log_event('dangerous_request', "Phát hiện request nguy hiểm từ IP $user_ip");
            wp_die('Phát hiện hành vi nguy hiểm!', 'Security Warning', ['response' => 400]);
        }
    }

    /**
     * Xử lý đăng nhập thất bại
     */
    public function handle_failed_login($username)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Không đếm số lần thử của IP whitelist
        if ($this->security_service->is_ip_whitelisted($ip)) {
            return;
        }

        $this->security_service->increment_login_attempts($ip);
    }

    /**
     * Kiểm tra trạng thái khóa đăng nhập
     */
    public function check_login_lockout($user, $username, $password)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Bỏ qua nếu là IP Whitelist
        if ($this->security_service->is_ip_whitelisted($ip)) {
            return $user;
        }

        if (!$this->security_service->check_login_attempts($ip)) {
            return new \WP_Error('locked_out', 'IP của bạn tạm thời bị khóa do thử sai quá nhiều lần.');
        }
        return $user;
    }

    /**
     * Thêm các headers bảo mật vào response
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
     * Bảo vệ khu vực admin
     */
    public function protect_admin_area()
    {
        if (is_admin() && !current_user_can('manage_options') && !defined('DOING_AJAX')) {
            // Có thể redirect về trang chủ
            wp_redirect(home_url());
            exit;
        }
    }
}

// Copyright by AcmaTvirus
