<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity;

/**
 * Class chính của plugin.
 */
class Plugin
{
    /**
     * @var Plugin
     */
    private static $instance;

    /**
     * Trả về instance duy nhất của plugin.
     *
     * @return Plugin
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Khởi chạy plugin.
     */
    public function run()
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        new Controllers\SecurityController();
        new Controllers\FeatureController();
        new Controllers\IntegrationController();
        new Controllers\UserController();
        new Controllers\SeoAiController();
        new Controllers\SeoContentController();
        new Controllers\MonitoringController();
        new Controllers\AdminController();
        new Controllers\UpdateController();
    }

    /**
     * Nạp text domain để hỗ trợ đa ngôn ngữ.
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'wp-plugin-security',
            false,
            dirname(plugin_basename(WPS_PLUGIN_FILE)) . '/languages'
        );
    }
}

// Copyright by AcmaTvirus
