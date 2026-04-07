<?php

/**
 * Plugin Name: Acma Security Shield
 * Plugin URI:  https://github.com/acmavirus/acma-security-shield
 * Description: Advanced security solution for WordPress built with Clean Architecture.
 * Version:     3.0.19
 * Author:      AcmaTvirus
 * Author URI:  https://thuc.me
 * License:     GPL2
 * Text Domain: acma-security-shield
 * Domain Path: /languages
 *
 * Copyright by AcmaTvirus
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
if (!defined('WPS_PLUGIN_FILE')) {
    define('WPS_PLUGIN_FILE', __FILE__);
}

if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
    $plugin_dir = __DIR__;
    foreach ([
        $plugin_dir . '/src/Plugin.php',
        $plugin_dir . '/src/Controllers/AdminController.php',
        $plugin_dir . '/src/Controllers/SecurityController.php',
        $plugin_dir . '/src/Controllers/MonitoringController.php',
    ] as $cached_file) {
        @opcache_invalidate($cached_file, true);
    }
}

// Load Composer autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Boot the plugin.
 */
if (class_exists('Acma\\WpSecurity\\Plugin')) {
    \Acma\WpSecurity\Plugin::instance()->run();
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('Acma Security Shield', 'acma-security-shield') . ':</strong> ' . esc_html__('Autoload failed or main class not found. Please check your <code>vendor</code> directory.', 'acma-security-shield') . '</p></div>';
    });
}

// Copyright by AcmaTvirus
