<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Services;

/**
 * Service xử lý các logic bảo mật nang cao
 */
class SecurityService
{
    /**
     * Danh sách các bot tìm kiếm phổ biến để cho phép truy cập
     */
    private $allowed_bots = [
        'Googlebot',
        'Bingbot',
        'Slurp',
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        'facebookexternalhit',
        'twitterbot',
        'rogerbot',
        'linkedinbot',
        'embedly',
        'quora link preview',
        'showyoubot',
        'outbrain',
        'pinterest',
        'slackbot',
        'vkShare',
        'W3C_Validator',
    ];

    /**
     * Kiểm tra xem User Agent có phải là bot tìm kiếm không
     * 
     * @return bool
     */
    public function is_search_bot()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($user_agent)) return false;

        foreach ($this->allowed_bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Kiểm tra xem IP có nằm trong danh sách đen không
     */
    public function is_ip_blocked($ip)
    {
        if ($this->is_search_bot()) {
            return false;
        }

        $blocked_ips = get_option('wps_blocked_ips', []);
        return in_array($ip, $blocked_ips);
    }

    /**
     * Lấy cấu hình bảo mật
     */
    public function get_setting($key, $default = false)
    {
        $settings = get_option('wps_main_settings', []);
        return $settings[$key] ?? $default;
    }

    /**
     * Bảo mật Header HTTP
     */
    public function get_security_headers()
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];
    }

    /**
     * Kiểm tra xem request có phải là trang login ẩn không
     */
    public function is_hidden_login_page()
    {
        $slug = $this->get_setting('rename_login_slug', '');
        if (empty($slug)) return false;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return (strpos($request_uri, $slug) !== false);
    }

    /**
     * Kiểm tra thời gian nhàn rỗi (Idle Timeout)
     */
    public function check_idle_timeout()
    {
        if (!is_user_logged_in()) return;

        $timeout = (int)$this->get_setting('idle_logout_time', 0);
        if ($timeout <= 0) return;

        $last_action = get_user_meta(get_current_user_id(), 'wps_last_action', true);
        $current_time = time();

        if ($last_action && ($current_time - $last_action) > ($timeout * 60)) {
            wp_logout();
            wp_redirect(home_url('?wps_event=idle_logout'));
            exit;
        }

        update_user_meta(get_current_user_id(), 'wps_last_action', $current_time);
    }

    /**
     * Chặn các request nguy hiểm (WAF Nâng cao)
     */
    public function is_dangerous_request()
    {
        if ($this->is_search_bot()) return false;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        $post_data = file_get_contents('php://input');
        $data_to_check = $request_uri . ' ' . $query_string . ' ' . $post_data;

        $dangerous_patterns = [
            'union select',
            'concat(',
            '<script>',
            'alert(',
            'eval(',
            'base64_',
            '/etc/passwd',
            '../',
            'proc/self/environ',
            'wp-config.php',
            '.htaccess',
            'global $wpdb',
            'order by',
            'group_concat',
            'sysadmin',
            'xp_cmdshell',
            'javascript:',
            'onerror=',
            'onload=',
            'prompt(',
            'confirm('
        ];

        foreach ($dangerous_patterns as $pattern) {
            if (stripos($data_to_check, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ghi lại nhật ký bảo mật
     */
    public function log_event($type, $message, $ip = null)
    {
        if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? __('Không xác định', 'wp-plugin-security');
        }

        $logs = get_option('wps_security_logs', []);
        $new_log = [
            'time' => current_time('mysql'),
            'type' => $type,
            'ip' => $ip,
            'message' => $message,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? __('Không xác định', 'wp-plugin-security')
        ];

        array_unshift($logs, $new_log);
        $logs = array_slice($logs, 0, 100); // Giữ lại 100 log gần nhất
        update_option('wps_security_logs', $logs);
    }

    /**
     * Kiểm tra và giới hạn đăng nhập
     */
    public function check_login_attempts($ip)
    {
        if (!$this->get_setting('limit_login_attempts', true)) {
            return true;
        }

        $attempts = get_transient('wps_login_attempts_' . md5($ip));
        $max_attempts = (int)$this->get_setting('max_login_attempts', 5);

        if ($attempts && $attempts >= $max_attempts) {
            return false;
        }

        return true;
    }

    /**
     * Tăng số lần thử đăng nhập thất bại
     */
    public function increment_login_attempts($ip)
    {
        $key = 'wps_login_attempts_' . md5($ip);
        $attempts = (int)get_transient($key);
        $attempts++;

        $lockout_time = (int)$this->get_setting('lockout_duration', 60) * MINUTE_IN_SECONDS;
        set_transient($key, $attempts, $lockout_time);

        $this->log_event('login_failed', "Đăng nhập thất bại lần $attempts từ IP $ip", $ip);
    }

    /**
     * Kiểm tra 2FA có được bật toàn site không.
     */
    public function is_two_factor_enabled()
    {
        return (bool) $this->get_setting('enable_two_factor', false);
    }

    /**
     * Danh sách role yêu cầu 2FA.
     */
    public function get_two_factor_roles()
    {
        $roles = $this->get_setting('two_factor_required_roles', ['administrator']);
        return is_array($roles) ? array_values(array_filter(array_map('sanitize_key', $roles))) : ['administrator'];
    }

    /**
     * Kiểm tra user có phải dùng 2FA không.
     */
    public function requires_two_factor_for_user($user)
    {
        if (!$user instanceof \WP_User) {
            return false;
        }

        if (!$this->is_two_factor_enabled()) {
            return false;
        }

        $roles = (array) $this->get_two_factor_roles();
        $user_roles = (array) $user->roles;

        if (!empty(array_intersect($roles, $user_roles))) {
            return true;
        }

        return (bool) get_user_meta($user->ID, 'wps_2fa_enabled', true);
    }

    /**
     * Lấy secret 2FA của user.
     */
    public function get_two_factor_secret($user_id)
    {
        return trim((string) get_user_meta($user_id, 'wps_2fa_secret', true));
    }

    /**
     * Tạo secret mới cho 2FA.
     */
    public function generate_two_factor_secret($length = 20)
    {
        $length = max(16, (int) $length);
        $bytes = function_exists('random_bytes') ? random_bytes($length) : openssl_random_pseudo_bytes($length);

        return $this->base32_encode($bytes);
    }

    /**
     * Tạo provisioning URI cho app authenticator.
     */
    public function get_two_factor_provisioning_uri($issuer, $account_name, $secret)
    {
        $issuer = rawurlencode((string) $issuer);
        $label = rawurlencode($issuer . ':' . $account_name);

        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s&period=30&digits=6', $label, rawurlencode($secret), $issuer);
    }

    /**
     * Xác thực mã TOTP.
     */
    public function verify_two_factor_code($secret, $code, $window = 1)
    {
        $secret = trim((string) $secret);
        $code = preg_replace('/\s+/', '', (string) $code);
        if ($secret === '' || $code === '') {
            return false;
        }

        $time_slice = (int) floor(time() / 30);
        for ($i = -abs((int) $window); $i <= abs((int) $window); $i++) {
            if (hash_equals($this->totp_code($secret, $time_slice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private function totp_code($secret, $time_slice)
    {
        $key = $this->base32_decode($secret);
        if ($key === '') {
            return '';
        }

        $time = pack('N*', 0) . pack('N*', $time_slice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $value % 1000000;
        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32_encode($data)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binary, 5);
        $output = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= $alphabet[bindec($chunk)];
        }

        return $output;
    }

    private function base32_decode($encoded)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string) $encoded));
        if ($encoded === '') {
            return '';
        }

        $binary = '';
        $length = strlen($encoded);
        for ($i = 0; $i < $length; $i++) {
            $position = strpos($alphabet, $encoded[$i]);
            if ($position === false) {
                return '';
            }
            $binary .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }

    /**
     * Kiem tra xem request co nen bo qua cac logic runtime hay khong.
     */
    public function should_bypass_runtime_protection()
    {
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return true;
        }

        if (wp_doing_cron()) {
            return true;
        }

        if (wp_doing_ajax()) {
            return !empty($_POST['action']) && in_array(
                sanitize_key((string) $_POST['action']),
                ['wps_monitoring_scan_integrity', 'wps_monitoring_scan_malware', 'wps_monitoring_scan_vulnerability', 'wps_monitoring_apply_uploads_protection'],
                true
            );
        }

        return false;
    }

    /**
     * Lay du lieu rate limit cho mot bucket.
     */
    private function increment_runtime_bucket($key, $window_seconds)
    {
        $count = (int) get_transient($key);
        $count++;
        set_transient($key, $count, max(10, (int) $window_seconds));

        return $count;
    }

    /**
     * Kiem tra rate limit theo IP va duong dan request.
     */
    public function is_rate_limited($ip, $request_uri = '', $method = 'GET')
    {
        if (!$this->get_setting('monitoring_enabled', false) || !$this->get_setting('rate_limit_enabled', false)) {
            return false;
        }

        if ($this->should_bypass_runtime_protection() || $this->is_search_bot()) {
            return false;
        }

        $ip = trim((string) $ip);
        if ($ip === '') {
            return false;
        }

        $window = max(10, (int) $this->get_setting('rate_limit_window_seconds', 60));
        $max_requests = max(5, (int) $this->get_setting('rate_limit_max_requests', 120));
        $max_path_requests = max(3, (int) $this->get_setting('rate_limit_path_max_requests', 30));

        $path = $this->normalize_request_path($request_uri);
        $method = strtoupper(sanitize_key((string) $method));

        $ip_key = 'wps_rate_ip_' . md5($ip);
        $path_key = 'wps_rate_path_' . md5($ip . '|' . $method . '|' . $path);

        $ip_count = $this->increment_runtime_bucket($ip_key, $window);
        $path_count = $this->increment_runtime_bucket($path_key, $window);

        return $ip_count > $max_requests || $path_count > $max_path_requests;
    }

    /**
     * Lay ma quoc gia tu header ho tro geo blocking.
     */
    public function get_client_country_code()
    {
        $headers = [
            $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '',
            $_SERVER['HTTP_X_COUNTRY_CODE'] ?? '',
            $_SERVER['HTTP_X_GEO_COUNTRY'] ?? '',
            $_SERVER['HTTP_X_COUNTRY'] ?? '',
            $_SERVER['GEOIP_COUNTRY_CODE'] ?? '',
        ];

        foreach ($headers as $header) {
            $header = strtoupper(trim((string) $header));
            if ($header !== '' && preg_match('/^[A-Z]{2}$/', $header)) {
                return $header;
            }
        }

        return '';
    }

    /**
     * Kiem tra geo blocking theo country code.
     */
    public function is_geo_blocked($ip = '')
    {
        if (!$this->get_setting('monitoring_enabled', false) || !$this->get_setting('geo_block_enabled', false)) {
            return false;
        }

        if ($this->should_bypass_runtime_protection()) {
            return false;
        }

        $countries = $this->get_setting('geo_block_countries', []);
        $countries = array_values(array_filter(array_map(static function ($country) {
            return strtoupper(trim((string) $country));
        }, (array) $countries)));

        if (empty($countries)) {
            return false;
        }

        $country_code = $this->get_client_country_code();
        if ($country_code === '') {
            return false;
        }

        $mode = sanitize_key((string) $this->get_setting('geo_block_mode', 'deny'));

        if ($mode === 'allow') {
            return !in_array($country_code, $countries, true);
        }

        return in_array($country_code, $countries, true);
    }

    /**
     * Kiem tra request co dang truy cap PHP trong uploads hay khong.
     */
    public function should_block_uploads_php_request()
    {
        if (!$this->get_setting('monitoring_enabled', false) || !$this->get_setting('uploads_php_protection', true)) {
            return false;
        }

        if ($this->should_bypass_runtime_protection()) {
            return false;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = (string) parse_url($request_uri, PHP_URL_PATH);
        if ($path === '') {
            $path = $request_uri;
        }

        $path = str_replace('\\', '/', $path);

        return (bool) preg_match('#/(wp-content/uploads|uploads)/.+\.php(?:$|[/?])#i', $path);
    }

    /**
     * Ghi nhan 404 va tu dong block neu vuot nguong.
     */
    public function track_404($ip, $request_uri = '')
    {
        if (!$this->get_setting('monitoring_enabled', false) || !$this->get_setting('monitor_404_enabled', false)) {
            return;
        }

        if ($this->should_bypass_runtime_protection() || $this->is_search_bot()) {
            return;
        }

        $ip = trim((string) $ip);
        if ($ip === '') {
            return;
        }

        $window = max(5, (int) $this->get_setting('monitor_404_window_minutes', 10)) * MINUTE_IN_SECONDS;
        $threshold = max(1, (int) $this->get_setting('monitor_404_threshold', 6));
        $path = $this->normalize_request_path($request_uri);

        $key = 'wps_404_hits_' . md5($ip);
        $data = get_transient($key);
        if (!is_array($data)) {
            $data = [
                'count' => 0,
                'paths' => [],
                'first_seen' => time(),
            ];
        }

        $data['count']++;
        if (!isset($data['paths'][$path])) {
            $data['paths'][$path] = 0;
        }
        $data['paths'][$path]++;
        if (count($data['paths']) > 15) {
            $data['paths'] = array_slice($data['paths'], -15, null, true);
        }

        set_transient($key, $data, $window);

        $this->log_event('404_hit', sprintf(__('Phát hiện 404 từ IP %1$s tại %2$s', 'wp-plugin-security'), $ip, $path), $ip);

        if ($this->get_setting('monitor_404_auto_block', false) && $data['count'] >= $threshold) {
            $this->block_ip($ip, sprintf(__('Tự động chặn do vượt ngưỡng 404 (%d lần)', 'wp-plugin-security'), $data['count']));
        }
    }

    /**
     * Them IP vao blacklist.
     */
    public function block_ip($ip, $reason = '')
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return false;
        }

        $blocked_ips = get_option('wps_blocked_ips', []);
        if (!is_array($blocked_ips)) {
            $blocked_ips = [];
        }

        if (!in_array($ip, $blocked_ips, true)) {
            $blocked_ips[] = $ip;
            update_option('wps_blocked_ips', array_values(array_unique($blocked_ips)));
        }

        if ($reason !== '') {
            $this->log_event('ip_auto_block', sprintf(__('Đã chặn IP %1$s: %2$s', 'wp-plugin-security'), $ip, $reason), $ip);
        }

        return true;
    }

    /**
     * Chuan hoa request path de do luong.
     */
    private function normalize_request_path($request_uri)
    {
        $path = (string) parse_url((string) $request_uri, PHP_URL_PATH);
        if ($path === '') {
            $path = (string) $request_uri;
        }

        $path = wp_parse_url($path, PHP_URL_PATH) ?: $path;
        $path = strtolower(trim(str_replace('\\', '/', $path)));

        if ($path === '') {
            return '/';
        }

        return $path;
    }
}

// Copyright by AcmaTvirus
