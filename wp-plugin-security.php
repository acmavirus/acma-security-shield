<?php

/**
 * Plugin Name: WP Plugin Security
 * Plugin URI:  https://github.com/acmavirus/wp-plugin-security
 * Description: Giải pháp bảo mật toàn diện cho WordPress dựa trên kiến trúc Clean Architecture.
 * Version:     3.0.10
 * Author:      AcmaTvirus
 * Author URI:  https://thuc.me
 * License:     GPL2
 * Text Domain: wp-plugin-security
 * Domain Path: /languages
 *
 * Copyright by AcmaTvirus
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Định nghĩa hằng số
if (!defined('WPS_PLUGIN_FILE')) {
    define('WPS_PLUGIN_FILE', __FILE__);
}

// Nạp Autoloader từ Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Khởi tạo Plugin ngay lập tức
 */
if (class_exists('Acma\\WpSecurity\\Plugin')) {
    \Acma\WpSecurity\Plugin::instance()->run();
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>WP Plugin Security:</strong> Hệ thống Autoload không hoạt động hoặc không tìm thấy Class chính. Vui lòng kiểm tra lại thư mục <code>vendor</code>.</p></div>';
    });
}

// Copyright by AcmaTvirus
