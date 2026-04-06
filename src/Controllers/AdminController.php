<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

/**
 * Controller xu ly cac thiet lap trong trang quan tri
 */
class AdminController
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        $plugin_base = plugin_basename(WPS_PLUGIN_FILE);
        add_filter("plugin_action_links_{$plugin_base}", [$this, 'add_plugin_action_links']);
        add_action('admin_footer', [$this, 'add_check_update_script']);
    }

    /**
     * Them lien ket nhanh trong danh sach plugin.
     */
    public function add_plugin_action_links($links)
    {
        $settings_url = admin_url('admin.php?page=wp-plugin-security');
        $nonce = wp_create_nonce('wps_check_update_nonce');

        $custom_links = [
            '<a href="' . esc_url($settings_url) . '">' . __('Cài đặt', 'wp-plugin-security') . '</a>',
            '<a href="#" class="wps-check-update-btn" data-nonce="' . esc_attr($nonce) . '" style="color: #d63638; font-weight: bold;">' . __('Kiểm tra cập nhật', 'wp-plugin-security') . '</a>',
        ];

        return array_merge($custom_links, (array) $links);
    }

    /**
     * Thêm script AJAX cho nút Check Update
     */
    public function add_check_update_script()
    {
        $screen = get_current_screen();
        if ($screen && !in_array($screen->id, ['plugins', 'toplevel_page_wp-plugin-security'])) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.wps-check-update-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var originalText = btn.text();
                
            btn.text('<?php echo esc_js(__('Đang kiểm tra...', 'wp-plugin-security')); ?>').css({'pointer-events': 'none', 'opacity': '0.6'});
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wps_check_update',
                        nonce: btn.data('nonce')
                    },
                    success: function(response) {
                        btn.text(originalText).css({'pointer-events': 'auto', 'opacity': '1'});
                        if(response.success) {
                            alert(response.data.message);
                            if(response.data.has_update) {
                                location.reload();
                            }
                        } else {
                            alert(response.data.message || 'Lỗi kiểm tra bản cập nhật.');
                        }
                    },
                    error: function() {
                        btn.text(originalText).css({'pointer-events': 'auto', 'opacity': '1'});
                        alert('Đã có lỗi xảy ra. Hãy thử lại sau.');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Tao menu trong admin.
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
     * Dang ky settings.
     */
    public function register_settings()
    {
        register_setting('wps_settings_group', 'wps_blocked_ips');
        register_setting('wps_settings_group', 'wps_main_settings');
    }

    /**
     * Render trang cau hinh.
     */
    public function render_admin_page()
    {
        $current_tab = sanitize_key($_GET['tab'] ?? 'general');
        $notices = [];        $tab_meta = [
            'general' => ['label' => __('Hệ thống & WAF', 'wp-plugin-security'), 'icon' => 'dashicons-admin-generic', 'group' => 'security'],
            'login' => ['label' => __('Đăng nhập', 'wp-plugin-security'), 'icon' => 'dashicons-lock', 'group' => 'security'],
            'blacklist' => ['label' => __('Danh sách chặn IP', 'wp-plugin-security'), 'icon' => 'dashicons-no-alt', 'group' => 'security'],
            'audit' => ['label' => __('Nhật ký kiểm tra', 'wp-plugin-security'), 'icon' => 'dashicons-list-view', 'group' => 'security'],
            'monitoring' => ['label' => __('Giám sát', 'wp-plugin-security'), 'icon' => 'dashicons-visibility', 'group' => 'security'],
            'speed' => ['label' => __('Tốc độ', 'wp-plugin-security'), 'icon' => 'dashicons-performance', 'group' => 'performance'],
            'updates' => ['label' => __('Cập nhật', 'wp-plugin-security'), 'icon' => 'dashicons-update', 'group' => 'editor_updates'],
            'seo' => ['label' => __('SEO & Mục lục', 'wp-plugin-security'), 'icon' => 'dashicons-search', 'group' => 'seo_content'],
            'seo_ai' => ['label' => __('SEO AI', 'wp-plugin-security'), 'icon' => 'dashicons-lightbulb', 'group' => 'seo_content'],
            'seo_content_ai' => ['label' => __('SEO Content', 'wp-plugin-security'), 'icon' => 'dashicons-edit-large', 'group' => 'seo_content'],
            'editor' => ['label' => __('Trình soạn thảo', 'wp-plugin-security'), 'icon' => 'dashicons-edit-page', 'group' => 'editor_updates'],
            'google' => ['label' => __('Google', 'wp-plugin-security'), 'icon' => 'dashicons-google', 'group' => 'google'],
            'email' => ['label' => __('Email', 'wp-plugin-security'), 'icon' => 'dashicons-email-alt', 'group' => 'email_notifications'],
            'users' => ['label' => __('Người dùng', 'wp-plugin-security'), 'icon' => 'dashicons-admin-users', 'group' => 'users'],
            'woocommerce' => ['label' => __('WooCommerce', 'wp-plugin-security'), 'icon' => 'dashicons-cart', 'group' => 'woocommerce'],
            'marketing' => ['label' => __('Marketing', 'wp-plugin-security'), 'icon' => 'dashicons-megaphone', 'group' => 'marketing_helpers'],
            'tools' => ['label' => __('Công cụ', 'wp-plugin-security'), 'icon' => 'dashicons-hammer', 'group' => 'marketing_helpers'],
            'changelog' => ['label' => __('Nhật ký thay đổi', 'wp-plugin-security'), 'icon' => 'dashicons-media-document', 'group' => 'marketing_helpers'],
        ];
        $group_meta = [
            'security' => ['label' => __('Bảo mật', 'wp-plugin-security'), 'icon' => 'dashicons-shield-alt'],
            'performance' => ['label' => __('Hiệu năng', 'wp-plugin-security'), 'icon' => 'dashicons-performance'],
            'seo_content' => ['label' => __('SEO & Nội dung', 'wp-plugin-security'), 'icon' => 'dashicons-media-document'],
            'editor_updates' => ['label' => __('Trình soạn thảo & Cập nhật', 'wp-plugin-security'), 'icon' => 'dashicons-edit-page'],
            'google' => ['label' => __('Google', 'wp-plugin-security'), 'icon' => 'dashicons-google'],
            'email_notifications' => ['label' => __('Email & Thông báo', 'wp-plugin-security'), 'icon' => 'dashicons-email-alt'],
            'users' => ['label' => __('Người dùng', 'wp-plugin-security'), 'icon' => 'dashicons-admin-users'],
            'woocommerce' => ['label' => __('WooCommerce', 'wp-plugin-security'), 'icon' => 'dashicons-cart'],
            'marketing_helpers' => ['label' => __('Marketing & Trợ giúp', 'wp-plugin-security'), 'icon' => 'dashicons-megaphone'],
        ];
        $current_group = $tab_meta[$current_tab]['group'] ?? 'security';
        $group_tabs = [];
        foreach ($tab_meta as $tab_key => $meta) {
            $group_tabs[$meta['group']][$tab_key] = $meta;
        }

        $hero_meta_map = [
            'security' => [
                'eyebrow' => __('Nhóm bảo mật', 'wp-plugin-security'),
                'title' => __('Bảo mật, xác thực và tường lửa', 'wp-plugin-security'),
                'description' => __('Gia cố login, 2FA, WAF, geoblocking, chặn brute force và giám sát 404 từ một điềm điều khiển.', 'wp-plugin-security'),
                'pills' => [__('2FA', 'wp-plugin-security'), __('WAF', 'wp-plugin-security'), __('404 monitor', 'wp-plugin-security')],
            ],
            'performance' => [
                'eyebrow' => __('Nhóm hiệu năng', 'wp-plugin-security'),
                'title' => __('Tối ưu tốc độ và cập nhật', 'wp-plugin-security'),
                'description' => __('Dọn rác hệ thống, rút gọn HTML, tắt tài nguyên thừa và kiểm soát vòng đời cập nhật.', 'wp-plugin-security'),
                'pills' => [__('Speed', 'wp-plugin-security'), __('Cleanup', 'wp-plugin-security'), __('Updates', 'wp-plugin-security')],
            ],
            'seo_content' => [
                'eyebrow' => __('Nhóm SEO & nội dung', 'wp-plugin-security'),
                'title' => __('SEO, mục lục và AI nội dung', 'wp-plugin-security'),
                'description' => __('Tự chèn mục lục, tối ưu Rank Math, sinh meta bằng Gemini và quét/rewrite nội dung.', 'wp-plugin-security'),
                'pills' => [__('SEO AI', 'wp-plugin-security'), __('TOC', 'wp-plugin-security'), __('Content AI', 'wp-plugin-security')],
            ],
            'editor_updates' => [
                'eyebrow' => __('Nhóm soạn thảo & cập nhật', 'wp-plugin-security'),
                'title' => __('Soạn thảo cổ điển và khóa cập nhật', 'wp-plugin-security'),
                'description' => __('Giữ classic editor, mở rộng TinyMCE, đồng thời chặn update core/plugin/theme khi cần.', 'wp-plugin-security'),
                'pills' => [__('Editor', 'wp-plugin-security'), __('Updates', 'wp-plugin-security'), __('Classic', 'wp-plugin-security')],
            ],
            'google' => [
                'eyebrow' => __('Nhóm Google', 'wp-plugin-security'),
                'title' => __('Indexing, login và captcha', 'wp-plugin-security'),
                'description' => __('Kết nối Google Indexing API, đăng nhập Google và reCAPTCHA ngay trong một luòng cấu hình.', 'wp-plugin-security'),
                'pills' => [__('Indexing API', 'wp-plugin-security'), __('Google Login', 'wp-plugin-security'), __('reCAPTCHA', 'wp-plugin-security')],
            ],
            'email_notifications' => [
                'eyebrow' => __('Nhóm email & thông báo', 'wp-plugin-security'),
                'title' => __('SMTP, cảnh báo và thanh thông báo', 'wp-plugin-security'),
                'description' => __('Cấu hình SMTP, email tự động, notification bar và luòng thông báo vận hành.', 'wp-plugin-security'),
                'pills' => [__('SMTP', 'wp-plugin-security'), __('Mail', 'wp-plugin-security'), __('Banner', 'wp-plugin-security')],
            ],
            'users' => [
                'eyebrow' => __('Nhóm người dùng', 'wp-plugin-security'),
                'title' => __('Cô lập dữ liệu và avatar nội bộ', 'wp-plugin-security'),
                'description' => __('Giới hạn dữ liệu user thường, avatar cục bộ và các cột quản trị hỗ trợ vận hành.', 'wp-plugin-security'),
                'pills' => [__('Isolation', 'wp-plugin-security'), __('Avatar', 'wp-plugin-security'), __('User ID', 'wp-plugin-security')],
            ],
            'woocommerce' => [
                'eyebrow' => __('Nhóm WooCommerce', 'wp-plugin-security'),
                'title' => __('Tùy biến bán hàng và cảnh báo đơn', 'wp-plugin-security'),
                'description' => __('Đổi text, hiển thị liên hệ khi giá 0 và đẩy cảnh báo đơn qua Telegram.', 'wp-plugin-security'),
                'pills' => [__('Store', 'wp-plugin-security'), __('Telegram', 'wp-plugin-security'), __('Checkout', 'wp-plugin-security')],
            ],
            'marketing_helpers' => [
                'eyebrow' => __('Nhóm marketing & trợ giúp', 'wp-plugin-security'),
                'title' => __('Chat, redirect, injector và công cụ khẩn cấp', 'wp-plugin-security'),
                'description' => __('Dùng chat bubble, code injector, search/replace, redirects và công cụ cứu hộ tại một nơi.', 'wp-plugin-security'),
                'pills' => [__('Chat', 'wp-plugin-security'), __('Redirect', 'wp-plugin-security'), __('Tools', 'wp-plugin-security')],
            ],
        ];
        $hero_meta = $hero_meta_map[$current_group] ?? $hero_meta_map['security'];
        $current_tab_label = $tab_meta[$current_tab]['label'] ?? __('Tổng quan', 'wp-plugin-security');
        if (isset($_POST['wps_tool_action']) && current_user_can('manage_options')) {
            check_admin_referer('wps_tool_nonce_action', 'wps_tool_nonce');
            $action = sanitize_key($_POST['wps_tool_action']);

            if ($action === 'kill_sessions') {
                $destroyed_sessions = $this->destroy_all_sessions();
                $notices[] = sprintf(
                    'Đã đăng xuất %d phiên đăng nhập trên toàn website.',
                    $destroyed_sessions
                );
            } elseif ($action === 'force_pw_reset') {
                $reset_users = $this->force_password_reset_for_all_users();
                $notices[] = sprintf(
                    'Đã vô hiệu hóa mật khẩu và session hiện tại của %d tài khoản.',
                    $reset_users
                );
            }
        }

        if (isset($_POST['wps_maintenance_action']) && current_user_can('manage_options')) {
            check_admin_referer('wps_settings_action', 'wps_settings_nonce');
            $maintenance_action = sanitize_key($_POST['wps_maintenance_action']);

            if ($current_tab === 'speed') {
                if ($maintenance_action === 'cleanup_revisions') {
                    $removed = $this->cleanup_revisions();
                    $notices[] = sprintf('Da xoa %d revision.', $removed);
                } elseif ($maintenance_action === 'cleanup_transients') {
                    $removed = $this->cleanup_expired_transients();
                    $notices[] = sprintf('Da dọn %d transient het han.', $removed);
                }
            }
        }

        if (isset($_POST['wps_search_replace_action']) && current_user_can('manage_options')) {
            check_admin_referer('wps_settings_action', 'wps_settings_nonce');
            if ($current_tab === 'marketing') {
                $search = sanitize_text_field($_POST['search_replace_from'] ?? '');
                $replace = wp_unslash($_POST['search_replace_to'] ?? '');
                if ($search !== '') {
                    $result = $this->run_search_replace($search, $replace);
                    $notices[] = sprintf(
                        'Da cap nhat %d noi dung va %d options.',
                        (int) ($result['content'] ?? 0),
                        (int) ($result['options'] ?? 0)
                    );
                }
            }
        }

        if (isset($_POST['wps_save_settings']) && current_user_can('manage_options')) {
            check_admin_referer('wps_settings_action', 'wps_settings_nonce');

            $main_settings = get_option('wps_main_settings', []);

            if ($current_tab === 'general') {
                $main_settings = array_merge($main_settings, [
                    'disable_xmlrpc' => isset($_POST['disable_xmlrpc']),
                    'disable_rest_api' => isset($_POST['disable_rest_api']),
                    'block_author_scan' => isset($_POST['block_author_scan']),
                    'disable_file_editor' => isset($_POST['disable_file_editor']),
                    'disable_directory_browsing' => isset($_POST['disable_directory_browsing']),
                    'hide_wp_version' => isset($_POST['hide_wp_version']),
                    'enable_security_headers' => isset($_POST['enable_security_headers']),
                    'enable_audit_log' => isset($_POST['enable_audit_log']),
                ]);
            } elseif ($current_tab === 'login') {
                $main_settings = array_merge($main_settings, [
                    'limit_login_attempts' => isset($_POST['limit_login_attempts']),
                    'max_login_attempts' => (int) ($_POST['max_login_attempts'] ?? 5),
                    'lockout_duration' => (int) ($_POST['lockout_duration'] ?? 60),
                    'rename_login_slug' => sanitize_title($_POST['rename_login_slug'] ?? ''),
                    'idle_logout_time' => (int) ($_POST['idle_logout_time'] ?? 0),
                    'enforce_strong_password' => isset($_POST['enforce_strong_password']),
                    'mask_login_errors' => isset($_POST['mask_login_errors']),
                    'enable_two_factor' => isset($_POST['enable_two_factor']),
                    'two_factor_required_roles' => array_values(array_filter(array_map('sanitize_key', (array) ($_POST['two_factor_required_roles'] ?? ['administrator'])))),
                ]);
            } elseif ($current_tab === 'speed') {
                $main_settings = array_merge($main_settings, [
                    'disable_emojis' => isset($_POST['disable_emojis']),
                    'disable_block_library_css' => isset($_POST['disable_block_library_css']),
                    'disable_dashicons' => isset($_POST['disable_dashicons']),
                    'minify_html' => isset($_POST['minify_html']),
                ]);
            } elseif ($current_tab === 'seo') {
                $allowed_post_types = $this->get_toc_post_types();
                $selected_types = array_map('sanitize_key', (array) ($_POST['toc_post_types'] ?? []));
                $selected_types = array_values(array_intersect($selected_types, $allowed_post_types));
                $main_settings = array_merge($main_settings, [
                    'enable_toc' => isset($_POST['enable_toc']),
                    'toc_title' => sanitize_text_field($_POST['toc_title'] ?? __('Mục lục', 'wp-plugin-security')),
                    'toc_post_types' => !empty($selected_types) ? $selected_types : ['post', 'page'],
                    'auto_featured_image' => isset($_POST['auto_featured_image']),
                ]);
            } elseif ($current_tab === 'seo_ai') {
                $allowed_post_types = $this->get_seo_ai_post_types();
                $selected_types = array_map('sanitize_key', (array) ($_POST['seo_ai_post_types'] ?? $allowed_post_types));
                $selected_types = array_values(array_intersect($selected_types, $allowed_post_types));
                $main_settings = array_merge($main_settings, [
                    'seo_ai_enabled' => isset($_POST['seo_ai_enabled']),
                    'seo_ai_sync_rank_math' => isset($_POST['seo_ai_sync_rank_math']),
                    'seo_ai_brand_name' => sanitize_text_field($_POST['seo_ai_brand_name'] ?? get_bloginfo('name')),
                    'seo_ai_post_types' => !empty($selected_types) ? $selected_types : $allowed_post_types,
                    'seo_ai_use_gemini' => isset($_POST['seo_ai_use_gemini']),
                    'seo_ai_gemini_api_key' => sanitize_text_field($_POST['seo_ai_gemini_api_key'] ?? ''),
                    'seo_ai_gemini_model' => sanitize_text_field($_POST['seo_ai_gemini_model'] ?? 'gemini-2.5-flash'),
                    'seo_ai_gemini_temperature' => (float) ($_POST['seo_ai_gemini_temperature'] ?? 0.4),
                    'seo_ai_gemini_prompt' => sanitize_textarea_field(wp_unslash($_POST['seo_ai_gemini_prompt'] ?? '')),
                ]);
            } elseif ($current_tab === 'seo_content_ai') {
                $allowed_post_types = $this->get_seo_content_post_types();
                $selected_types = array_map('sanitize_key', (array) ($_POST['seo_content_post_types'] ?? $allowed_post_types));
                $selected_types = array_values(array_intersect($selected_types, $allowed_post_types));
                $main_settings = array_merge($main_settings, [
                    'seo_content_enabled' => isset($_POST['seo_content_enabled']),
                    'seo_content_auto_update' => isset($_POST['seo_content_auto_update']),
                    'seo_content_post_types' => !empty($selected_types) ? $selected_types : $allowed_post_types,
                    'seo_content_use_gemini' => isset($_POST['seo_content_use_gemini']),
                    'seo_content_gemini_prompt' => sanitize_textarea_field(wp_unslash($_POST['seo_content_gemini_prompt'] ?? '')),
                ]);
            } elseif ($current_tab === 'editor') {
                $main_settings = array_merge($main_settings, [
                    'disable_block_editor' => isset($_POST['disable_block_editor']),
                    'enable_tinymce_advanced' => isset($_POST['enable_tinymce_advanced']),
                ]);
            } elseif ($current_tab === 'updates') {
                $main_settings = array_merge($main_settings, [
                    'block_core_updates' => isset($_POST['block_core_updates']),
                    'block_plugin_updates' => isset($_POST['block_plugin_updates']),
                    'block_theme_updates' => isset($_POST['block_theme_updates']),
                ]);
            } elseif ($current_tab === 'google') {
                $main_settings = array_merge($main_settings, [
                    'google_indexing_enabled' => isset($_POST['google_indexing_enabled']),
                    'google_service_account_json' => wp_unslash($_POST['google_service_account_json'] ?? ''),
                    'google_indexing_project_id' => sanitize_text_field($_POST['google_indexing_project_id'] ?? ''),
                    'google_indexing_post_types' => array_values(array_filter(array_map('sanitize_key', (array) ($_POST['google_indexing_post_types'] ?? ['post'])))),
                    'google_login_enabled' => isset($_POST['google_login_enabled']),
                    'google_client_id' => sanitize_text_field($_POST['google_client_id'] ?? ''),
                    'google_client_secret' => sanitize_text_field($_POST['google_client_secret'] ?? ''),
                    'google_redirect_uri' => esc_url_raw($_POST['google_redirect_uri'] ?? ''),
                    'recaptcha_enabled' => isset($_POST['recaptcha_enabled']),
                    'recaptcha_site_key' => sanitize_text_field($_POST['recaptcha_site_key'] ?? ''),
                    'recaptcha_secret_key' => sanitize_text_field($_POST['recaptcha_secret_key'] ?? ''),
                ]);
            } elseif ($current_tab === 'email') {
                $main_settings = array_merge($main_settings, [
                    'smtp_enabled' => isset($_POST['smtp_enabled']),
                    'smtp_host' => sanitize_text_field($_POST['smtp_host'] ?? ''),
                    'smtp_port' => (int) ($_POST['smtp_port'] ?? 587),
                    'smtp_encryption' => sanitize_key($_POST['smtp_encryption'] ?? 'tls'),
                    'smtp_username' => sanitize_text_field($_POST['smtp_username'] ?? ''),
                    'smtp_password' => sanitize_text_field($_POST['smtp_password'] ?? ''),
                    'smtp_from_email' => sanitize_email($_POST['smtp_from_email'] ?? ''),
                    'smtp_from_name' => sanitize_text_field($_POST['smtp_from_name'] ?? ''),
                    'notification_bar_enabled' => isset($_POST['notification_bar_enabled']),
                    'notification_bar_position' => sanitize_key($_POST['notification_bar_position'] ?? 'top'),
                    'notification_bar_text' => sanitize_textarea_field($_POST['notification_bar_text'] ?? ''),
                    'notification_bar_link' => esc_url_raw($_POST['notification_bar_link'] ?? ''),
                    'notification_bar_button' => sanitize_text_field($_POST['notification_bar_button'] ?? ''),
                ]);
            } elseif ($current_tab === 'marketing') {
                $main_settings = array_merge($main_settings, [
                    'chat_enabled' => isset($_POST['chat_enabled']),
                    'chat_phone' => sanitize_text_field($_POST['chat_phone'] ?? ''),
                    'chat_sms' => sanitize_text_field($_POST['chat_sms'] ?? ''),
                    'chat_zalo' => sanitize_text_field($_POST['chat_zalo'] ?? ''),
                    'chat_messenger' => sanitize_text_field($_POST['chat_messenger'] ?? ''),
                    'chat_telegram' => sanitize_text_field($_POST['chat_telegram'] ?? ''),
                    'chat_whatsapp' => sanitize_text_field($_POST['chat_whatsapp'] ?? ''),
                    'code_inject_head' => wp_unslash($_POST['code_inject_head'] ?? ''),
                    'code_inject_footer' => wp_unslash($_POST['code_inject_footer'] ?? ''),
                    'redirect_rules' => sanitize_textarea_field($_POST['redirect_rules'] ?? ''),
                ]);
            } elseif ($current_tab === 'woocommerce') {
                $main_settings = array_merge($main_settings, [
                    'woo_add_to_cart_text' => sanitize_text_field($_POST['woo_add_to_cart_text'] ?? ''),
                    'woo_price_zero_text' => sanitize_text_field($_POST['woo_price_zero_text'] ?? ''),
                    'woo_telegram_enabled' => isset($_POST['woo_telegram_enabled']),
                    'woo_telegram_bot_token' => sanitize_text_field($_POST['woo_telegram_bot_token'] ?? ''),
                    'woo_telegram_chat_id' => sanitize_text_field($_POST['woo_telegram_chat_id'] ?? ''),
                ]);
            } elseif ($current_tab === 'users') {
                $main_settings = array_merge($main_settings, [
                    'user_isolation_enabled' => isset($_POST['user_isolation_enabled']),
                    'local_avatar_enabled' => isset($_POST['local_avatar_enabled']),
                ]);
            } elseif ($current_tab === 'monitoring') {
                $geo_countries_raw = (string) wp_unslash($_POST['geo_block_countries'] ?? '');
                $geo_countries = preg_split('/[\s,]+/', $geo_countries_raw);
                $geo_countries = array_values(array_filter(array_map(static function ($country) {
                    return strtoupper(sanitize_text_field($country));
                }, (array) $geo_countries)));

                $main_settings = array_merge($main_settings, [
                    'monitoring_enabled' => isset($_POST['monitoring_enabled']),
                    'rate_limit_enabled' => isset($_POST['rate_limit_enabled']),
                    'rate_limit_window_seconds' => max(10, (int) ($_POST['rate_limit_window_seconds'] ?? 60)),
                    'rate_limit_max_requests' => max(5, (int) ($_POST['rate_limit_max_requests'] ?? 120)),
                    'rate_limit_path_max_requests' => max(3, (int) ($_POST['rate_limit_path_max_requests'] ?? 30)),
                    'geo_block_enabled' => isset($_POST['geo_block_enabled']),
                    'geo_block_mode' => in_array(sanitize_key($_POST['geo_block_mode'] ?? 'deny'), ['deny', 'allow'], true) ? sanitize_key($_POST['geo_block_mode']) : 'deny',
                    'geo_block_countries' => $geo_countries,
                    'uploads_php_protection' => isset($_POST['uploads_php_protection']),
                    'monitor_404_enabled' => isset($_POST['monitor_404_enabled']),
                    'monitor_404_threshold' => max(1, (int) ($_POST['monitor_404_threshold'] ?? 6)),
                    'monitor_404_window_minutes' => max(5, (int) ($_POST['monitor_404_window_minutes'] ?? 10)),
                    'monitor_404_auto_block' => isset($_POST['monitor_404_auto_block']),
                    'monitoring_scan_integrity' => isset($_POST['monitoring_scan_integrity']),
                    'monitoring_scan_malware' => isset($_POST['monitoring_scan_malware']),
                    'monitoring_scan_vulnerability' => isset($_POST['monitoring_scan_vulnerability']),
                    'monitoring_cron_enabled' => isset($_POST['monitoring_cron_enabled']),
                    'monitoring_email_alerts' => isset($_POST['monitoring_email_alerts']),
                    'monitoring_cron_frequency' => in_array(sanitize_key($_POST['monitoring_cron_frequency'] ?? 'daily'), ['hourly', 'twicedaily', 'daily'], true) ? sanitize_key($_POST['monitoring_cron_frequency']) : 'daily',
                ]);
            }

            update_option('wps_main_settings', $main_settings);

            if ($current_tab === 'blacklist') {
                $raw_ips = explode("\n", str_replace("\r", '', $_POST['wps_blocked_ips_raw'] ?? ''));
                $clean_ips = array_unique(array_filter(array_map('trim', $raw_ips)));
                update_option('wps_blocked_ips', $clean_ips);
            }

            $notices[] = 'Configuration saved successfully.';
        }

        $main_settings = get_option('wps_main_settings', [
            'limit_login_attempts' => true,
            'max_login_attempts' => 5,
            'lockout_duration' => 60,
            'enable_two_factor' => false,
            'two_factor_required_roles' => ['administrator'],
            'disable_xmlrpc' => true,
            'disable_rest_api' => true,
            'block_author_scan' => true,
            'mask_login_errors' => true,
            'hide_wp_version' => true,
            'disable_file_editor' => true,
            'enable_security_headers' => true,
            'enable_audit_log' => true,
            'enforce_strong_password' => true,
            'disable_emojis' => true,
            'disable_block_library_css' => true,
            'disable_dashicons' => false,
            'minify_html' => false,
                'enable_toc' => true,
                'toc_title' => __('Mục lục', 'wp-plugin-security'),
                'toc_post_types' => $this->get_toc_post_types(),
                'auto_featured_image' => false,
                'seo_ai_enabled' => false,
                'seo_ai_sync_rank_math' => true,
                'seo_ai_brand_name' => get_bloginfo('name'),
                'seo_ai_post_types' => $this->get_seo_ai_post_types(),
                'seo_ai_use_gemini' => false,
                'seo_ai_gemini_api_key' => '',
                'seo_ai_gemini_model' => 'gemini-2.5-flash',
                'seo_ai_gemini_temperature' => 0.4,
                'seo_ai_gemini_prompt' => '',
                'seo_content_enabled' => false,
                'seo_content_auto_update' => false,
                'seo_content_post_types' => $this->get_seo_content_post_types(),
                'seo_content_use_gemini' => false,
                'seo_content_gemini_prompt' => '',
                'monitoring_enabled' => false,
                'rate_limit_enabled' => false,
                'rate_limit_window_seconds' => 60,
                'rate_limit_max_requests' => 120,
                'rate_limit_path_max_requests' => 30,
                'geo_block_enabled' => false,
                'geo_block_mode' => 'deny',
                'geo_block_countries' => [],
                'uploads_php_protection' => true,
                'monitor_404_enabled' => false,
                'monitor_404_threshold' => 6,
                'monitor_404_window_minutes' => 10,
                'monitor_404_auto_block' => false,
                'monitoring_cron_enabled' => false,
                'monitoring_email_alerts' => true,
                'monitoring_cron_frequency' => 'daily',
                'disable_block_editor' => false,
            'enable_tinymce_advanced' => true,
            'block_core_updates' => false,
            'block_plugin_updates' => false,
            'block_theme_updates' => false,
            'smtp_enabled' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
            'google_indexing_enabled' => false,
            'google_service_account_json' => '',
            'google_indexing_project_id' => '',
            'google_indexing_post_types' => ['post'],
            'google_login_enabled' => false,
            'google_client_id' => '',
            'google_client_secret' => '',
            'google_redirect_uri' => '',
            'recaptcha_enabled' => false,
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'notification_bar_enabled' => false,
            'notification_bar_position' => 'top',
            'notification_bar_text' => '',
            'notification_bar_link' => '',
            'notification_bar_button' => '',
            'chat_enabled' => false,
            'chat_phone' => '',
            'chat_sms' => '',
            'chat_zalo' => '',
            'chat_messenger' => '',
            'chat_telegram' => '',
            'chat_whatsapp' => '',
            'code_inject_head' => '',
            'code_inject_footer' => '',
            'redirect_rules' => '',
            'woo_add_to_cart_text' => '',
            'woo_price_zero_text' => __('Liên hệ', 'wp-plugin-security'),
            'woo_telegram_enabled' => false,
            'woo_telegram_bot_token' => '',
            'woo_telegram_chat_id' => '',
            'user_isolation_enabled' => false,
            'local_avatar_enabled' => false,
        ]);

        $blocked_ips = get_option('wps_blocked_ips', []);
        $ips_text = is_array($blocked_ips) ? implode("\n", $blocked_ips) : '';
        $audit_logs = get_option('wps_audit_logs', []);
        $security_logs = get_option('wps_security_logs', []);
        ?>
        <div class="wrap wps-admin-wrap">
            <style>
                .wps-admin-wrap { margin: 0; }
                .wps-admin-shell { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 24px; align-items: start; }
                .wps-sidebar {
                    background: linear-gradient(180deg, #003b6b 0%, #1167ad 100%);
                    color: #fff;
                    padding: 22px;
                    border-radius: 18px;
                    position: sticky;
                    top: 32px;
                    box-shadow: 0 18px 40px rgba(1, 34, 61, 0.18);
                }
                .wps-brand { padding-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.12); margin-bottom: 18px; }
                .wps-brand h1 { margin: 0; color: #fff; font-size: 24px; line-height: 1.2; }
                .wps-brand p { margin: 8px 0 0; opacity: 0.85; }
                .wps-version-chip {
                    display: inline-flex; align-items: center; gap: 8px; margin-top: 14px;
                    padding: 7px 12px; border-radius: 999px; background: rgba(255,255,255,0.14);
                    font-size: 12px; font-weight: 600;
                }
                .wps-nav { display: grid; gap: 10px; }
                .wps-nav-item {
                    display: flex; align-items: center; gap: 12px; padding: 13px 14px;
                    border-radius: 14px; color: rgba(255,255,255,0.9); text-decoration: none;
                    transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease;
                    background: rgba(255,255,255,0.07);
                }
                .wps-nav-item:hover, .wps-nav-item:focus {
                    color: #fff; transform: translateX(3px); background: rgba(255,255,255,0.14);
                }
                .wps-nav-item.is-active { background: #fff; color: #003b6b; box-shadow: 0 14px 28px rgba(1,34,61,0.15); }
                .wps-nav-item .dashicons { font-size: 18px; width: 18px; height: 18px; }
                .wps-sidebar-foot { margin-top: 18px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.12); font-size: 12px; opacity: 0.85; }
                .wps-main { min-width: 0; }
                .wps-top-tabs {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    margin: 18px 0 0;
                    padding: 10px;
                    border-radius: 18px;
                    background: rgba(255,255,255,0.72);
                    backdrop-filter: blur(10px);
                    box-shadow: 0 12px 28px rgba(1,34,61,0.08);
                }
                .wps-top-tab {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 11px 14px;
                    border-radius: 999px;
                    background: #eef5fb;
                    color: #003b6b;
                    text-decoration: none;
                    font-weight: 600;
                    transition: background 0.18s ease, transform 0.18s ease;
                }
                .wps-top-tab:hover { transform: translateY(-1px); }
                .wps-top-tab.is-active {
                    background: linear-gradient(135deg, #1167ad 0%, #003b6b 100%);
                    color: #fff;
                }
                #wpbody {
                    position: relative;
                    margin: 20px;
                }
                .wps-hero {
                    background:
                        radial-gradient(circle at top right, rgba(255,255,255,0.2), transparent 26%),
                        linear-gradient(135deg, #1167ad 0%, #0b4f85 48%, #003b6b 100%);
                    color: #fff;
                    padding: 28px;
                    border-radius: 18px;
                    box-shadow: 0 18px 40px rgba(1,34,61,0.14);
                }
                .wps-hero-shell { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; flex-wrap: wrap; }
                .wps-hero-copy { max-width: 760px; }
                .wps-hero-eyebrow {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 7px 12px;
                    border-radius: 999px;
                    background: rgba(255,255,255,0.14);
                    color: rgba(255,255,255,0.92);
                    font-size: 12px;
                    font-weight: 700;
                    letter-spacing: 0.02em;
                    text-transform: uppercase;
                }
                .wps-hero h2 { margin: 12px 0 0; color: #fff; font-size: 30px; line-height: 1.1; }
                .wps-hero p { margin: 10px 0 0; max-width: 720px; color: rgba(255,255,255,0.92); }
                .wps-hero-meta { display: grid; gap: 10px; justify-items: end; }
                .wps-hero-current {
                    padding: 10px 14px;
                    border-radius: 14px;
                    background: rgba(255,255,255,0.12);
                    color: #fff;
                    font-weight: 700;
                }
                .wps-hero--legacy { display: none; }
                .wps-pill-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
                .wps-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.12); font-size: 12px; font-weight: 600; }
                .wps-notice {
                    margin: 18px 0 0; padding: 14px 16px; border-left: 4px solid #1167ad; background: #f7fbff;
                    color: #003b6b; border-radius: 14px; box-shadow: 0 10px 24px rgba(1,34,61,0.06);
                }
                .wps-panel { margin-top: 18px; padding: 24px; background: #fff; border-radius: 18px; box-shadow: 0 18px 40px rgba(1,34,61,0.08); }
                .wps-content-area h2 { color: #003b6b; margin-top: 0; }
                .wps-content-area .form-table { margin-top: 12px; }
                .wps-content-area .form-table th { width: 320px; }
                .wps-content-area .form-table tr + tr th,
                .wps-content-area .form-table tr + tr td { border-top: 1px solid #edf2f7; }
                .wps-content-area .form-table td,
                .wps-content-area .form-table th { padding-top: 16px; padding-bottom: 16px; }
                .wps-content-area .form-table .description { color: #607083; }
                .wps-content-area textarea,
                .wps-content-area input[type="text"],
                .wps-content-area input[type="number"] {
                    border-radius: 12px;
                    border-color: #c9d8e6;
                    box-shadow: none;
                }
                .wps-content-area textarea:focus,
                .wps-content-area input[type="text"]:focus,
                .wps-content-area input[type="number"]:focus {
                    border-color: #1167ad;
                    box-shadow: 0 0 0 1px #1167ad;
                }
                .wps-content-area .card {
                    max-width: 100%;
                    margin: 18px 0 0;
                    padding: 18px;
                    border: 1px solid #dfeaf5;
                    border-radius: 18px;
                    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
                    box-shadow: 0 12px 24px rgba(1,34,61,0.05);
                }
                .wps-content-area .updated {
                    border-left: 4px solid #1167ad;
                    background: #f7fbff;
                    border-radius: 14px;
                    margin: 0 0 18px;
                }
                .wps-content-area .widefat,
                .wps-content-area .wp-list-table {
                    border: 1px solid #dfeaf5;
                    border-radius: 14px;
                    overflow: hidden;
                    background: #fff;
                }
                .wps-content-area .widefat thead th,
                .wps-content-area .wp-list-table thead th {
                    background: #f1f7fc;
                    color: #003b6b;
                }
                .wps-content-area .submit {
                    position: sticky;
                    bottom: 16px;
                    margin: 18px 0 0;
                    padding: 16px 18px;
                    border-radius: 18px;
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                    z-index: 5;
                }
                .wps-content-area .submit .button,
                .wps-content-area .submit .button-primary {
                    min-height: 42px;
                    padding: 0 18px;
                    border-radius: 12px;
                }
                .wps-content-area .submit .button-primary {
                    background: linear-gradient(135deg, #1167ad 0%, #003b6b 100%);
                    border-color: #003b6b;
                    box-shadow: 0 12px 24px rgba(17,103,173,0.24);
                }
                .wps-section-head { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 18px; flex-wrap: wrap; }
                .wps-section-head h3 { margin: 0; color: #003b6b; font-size: 22px; }
                .wps-section-head p { margin: 6px 0 0; color: #516173; max-width: 760px; }
                .wps-grid { display: grid; gap: 18px; }
                .wps-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .wps-card { background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); border: 1px solid #dfeaf5; border-radius: 18px; padding: 18px; box-shadow: 0 12px 24px rgba(1,34,61,0.05); }
                .wps-card h4, .wps-card h2, .wps-card h3 { margin-top: 0; color: #003b6b; }
                .wps-form-table { margin-top: 10px; }
                .wps-form-table th { width: 320px; }
                .wps-form-table tr + tr th, .wps-form-table tr + tr td { border-top: 1px solid #edf2f7; }
                .wps-form-table td, .wps-form-table th { padding-top: 16px; padding-bottom: 16px; }
                .wps-form-table td.wps-inline-setting { display: flex; align-items: center; gap: 12px; flex-wrap: nowrap; white-space: nowrap; }
                .wps-form-table td.wps-inline-setting .description { display: inline; margin: 0; white-space: normal; }
                .wps-form-table td.wps-inline-setting input[type="checkbox"] { flex: 0 0 auto; margin-top: 0; }
                .wps-form-table .description { color: #607083; }
                .wps-textarea,
                .wps-form-table input[type="text"],
                .wps-form-table input[type="number"],
                .wps-form-table input[type="email"],
                .wps-form-table input[type="url"],
                .wps-form-table input[type="password"],
                .wps-form-table input[type="checkbox"],
                .wps-form-table select {
                    border-radius: 12px;
                    border-color: #c9d8e6;
                    box-shadow: none;
                }
                .wps-textarea:focus,
                .wps-form-table input[type="text"]:focus,
                .wps-form-table input[type="number"]:focus,
                .wps-form-table input[type="email"]:focus,
                .wps-form-table input[type="url"]:focus,
                .wps-form-table input[type="password"]:focus,
                .wps-form-table input[type="checkbox"]:focus,
                .wps-form-table select:focus {
                    border-color: #1167ad;
                    box-shadow: 0 0 0 1px #1167ad;
                }
                .wps-form-table input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    min-width: 18px;
                    margin-top: 0;
                    vertical-align: middle;
                }
                .wps-table-wrap { overflow: auto; border-radius: 14px; border: 1px solid #dfeaf5; background: #fff; }
                .wps-table-wrap table { margin: 0; }
                .wps-danger-card { border-left: 5px solid #c30000; }
                .wps-footer {
                    position: sticky; bottom: 16px; z-index: 5; margin-top: 18px; padding: 16px 18px;
                    border-radius: 18px; background: rgba(255,255,255,0.92); backdrop-filter: blur(8px);
                    box-shadow: 0 18px 40px rgba(1,34,61,0.16); display: flex; justify-content: space-between;
                    gap: 16px; align-items: center; flex-wrap: wrap;
                }
                .wps-footer strong { color: #003b6b; }
                .wps-footer .button, .wps-footer .button-primary { min-height: 42px; padding: 0 18px; border-radius: 12px; }
                .wps-footer .button-primary { background: linear-gradient(135deg, #1167ad 0%, #003b6b 100%); border-color: #003b6b; box-shadow: 0 12px 24px rgba(17,103,173,0.24); }
                .wps-footer .button-link-delete { color: #c30000; }
                @media (max-width: 1180px) {
                    .wps-admin-shell { grid-template-columns: 1fr; }
                    .wps-sidebar { position: static; }
                    .wps-grid.two { grid-template-columns: 1fr; }
                    .wps-hero-shell { flex-direction: column; }
                    .wps-hero-meta { justify-items: start; }
                }
            </style>

            <div class="wps-admin-shell">
                <aside class="wps-sidebar">
                    <div class="wps-brand">
                        <h1><?php _e('WP Security', 'wp-plugin-security'); ?></h1>
                        <p><?php _e('Tabbed control center for hardening, login, audit, and emergency tools.', 'wp-plugin-security'); ?></p>
                        <div class="wps-version-chip"><span class="dashicons dashicons-shield"></span><span>v<?php echo esc_html($this->get_plugin_version()); ?></span></div>
                    </div>
                    <nav class="wps-nav" aria-label="<?php esc_attr_e('Security sections', 'wp-plugin-security'); ?>">
                        <?php foreach ($group_meta as $group_key => $group_data) : ?>
                            <?php $first_tab = array_key_first($group_tabs[$group_key] ?? []); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-plugin-security&tab=' . $first_tab)); ?>" class="wps-nav-item <?php echo $current_group === $group_key ? 'is-active' : ''; ?>">
                                <span class="dashicons <?php echo esc_attr($group_data['icon']); ?>"></span>
                                <span><?php echo esc_html($group_data['label']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
        <div class="wps-sidebar-foot"><?php _e('Giao diện ưu tiên bảo mật. Chỉ dùng thiết lập cục bộ. Không có theo dõi ẩn.', 'wp-plugin-security'); ?></div>
                </aside>
                <main class="wps-main">
                    <section class="wps-hero">
                        <div class="wps-hero-shell">
                            <div class="wps-hero-copy">
                                <span class="wps-hero-eyebrow"><?php echo esc_html($hero_meta['eyebrow'] ?? $current_tab_label); ?></span>
                                <h2><?php echo esc_html($hero_meta['title'] ?? __('Thi?t l?p WP Security', 'wp-plugin-security')); ?></h2>
                                <p><?php echo esc_html($hero_meta['description'] ?? __('Giao di?n qu?n tr? d?ng b? theo phong c�ch agent.', 'wp-plugin-security')); ?></p>
                                <div class="wps-pill-row">
                                    <?php foreach ((array) ($hero_meta['pills'] ?? []) as $pill) : ?>
                                        <span class="wps-pill"><?php echo esc_html($pill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="wps-hero-meta">
                                <span class="wps-hero-current"><?php echo esc_html($current_tab_label); ?></span>
                                <span class="wps-pill"><?php echo esc_html($group_meta[$current_group]['label'] ?? $current_group); ?></span>
                            </div>
                        </div>
                    </section>

                    <nav class="wps-top-tabs" aria-label="<?php esc_attr_e('Feature tabs', 'wp-plugin-security'); ?>">
                        <?php foreach (($group_tabs[$current_group] ?? []) as $tab_key => $tab_data) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-plugin-security&tab=' . $tab_key)); ?>" class="wps-top-tab <?php echo $current_tab === $tab_key ? 'is-active' : ''; ?>">
                                <span class="dashicons <?php echo esc_attr($tab_data['icon']); ?>"></span>
                                <span><?php echo esc_html($tab_data['label']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <?php foreach ($notices as $notice) : ?>
                        <div class="wps-notice"><strong><?php echo esc_html($notice); ?></strong></div>
                    <?php endforeach; ?>

                    <div class="wps-panel">
            <div class="wps-content-area">
                <?php if ($current_tab === 'general') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Tường lửa & Tăng cứng', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
        <?php $this->render_checkbox_row('disable_xmlrpc', 'Vô hiệu hóa XML-RPC', $main_settings, 'Ngăn chặn brute-force qua XML-RPC.'); ?>
                            <?php $this->render_checkbox_row('disable_rest_api', 'Hạn chế REST API', $main_settings, 'Chỉ cho phép người dùng đã đăng nhập truy cập API.'); ?>
        <?php $this->render_checkbox_row('block_author_scan', 'Chặn quét tác giả', $main_settings, 'Ngăn bot dò tìm tên người dùng quản trị viên.'); ?>
                            <?php $this->render_checkbox_row('disable_directory_browsing', 'Chặn Directory Browsing', $main_settings, 'Ngăn truy cập liệt kê file trong thư mục.'); ?>
        <?php $this->render_checkbox_row('disable_file_editor', 'Tắt trình chỉnh sửa file', $main_settings, 'Vô hiệu hóa chỉnh sửa mã nguồn trong admin.'); ?>
                        </table>

                        <hr>

        <h2><?php _e('Quyền riêng tư & Nhật ký', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
        <?php $this->render_checkbox_row('hide_wp_version', 'Ẩn phiên bản WP', $main_settings, 'Xóa dấu hiệu nhận biết phiên bản từ mã nguồn.'); ?>
        <?php $this->render_checkbox_row('enable_security_headers', 'Tiêu đề bảo mật', $main_settings, 'Kích hoạt HSTS, XSS Protection, nosniff...'); ?>
        <?php $this->render_checkbox_row('enable_audit_log', 'Nhật ký kiểm tra', $main_settings, 'Lưu lại mọi hoạt động của người dùng.'); ?>
                        </table>

                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập Hệ thống', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'login') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                        <h2><?php _e('Rename Login', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="rename_login_slug"><?php _e('Đường dẫn đăng nhập mới', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <code><?php echo esc_html(home_url('/')); ?></code>
                <input type="text" id="rename_login_slug" name="rename_login_slug" value="<?php echo esc_attr($main_settings['rename_login_slug'] ?? ''); ?>" placeholder="ví dụ: secret-login" class="regular-text">
                                    <p class="description"><?php _e('Neu de trong, plugin se dung wp-login.php mac dinh.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <h2><?php _e('Brute Force Protection', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
        <?php $this->render_checkbox_row('limit_login_attempts', 'Giới hạn đăng nhập', $main_settings, 'Khóa IP nếu đăng nhập sai nhiều lần.'); ?>
        <?php $this->render_checkbox_row('mask_login_errors', 'Ẩn lỗi đăng nhập', $main_settings, 'Không cho biết tên người dùng hay mật khẩu sai.'); ?>
        <?php $this->render_number_row('max_login_attempts', 'Số lần thử tối đa', $main_settings, 5); ?>
        <?php $this->render_number_row('lockout_duration', 'Thời gian khóa (phút)', $main_settings, 60); ?>
                        </table>

                        <hr>

                        <h2><?php _e('Chính sách người dùng', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
        <?php $this->render_checkbox_row('enforce_strong_password', 'Mật khẩu mạnh', $main_settings, 'Bắt buộc dùng mật khẩu mạnh với ít nhất 12 ký tự.'); ?>
                            <?php $this->render_number_row('idle_logout_time', 'Tự động đăng xuất (phút)', $main_settings, 0, '0 để tắt chức năng này.'); ?>
                        </table>

                        <hr>

                        <h2><?php _e('Xác thực 2 lớp', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
                            <?php $this->render_checkbox_row('enable_two_factor', 'Bật 2FA', $main_settings, 'Hiển thị ô mã xác thực 2FA trên form đăng nhập.'); ?>
                            <tr>
                                <th scope="row"><?php _e('Vai trò bắt buộc', 'wp-plugin-security'); ?></th>
                                <td>
                                    <?php
                                    $required_roles = (array) ($main_settings['two_factor_required_roles'] ?? ['administrator']);
                                    $roles = wp_roles()->roles;
                                    foreach (['administrator', 'editor', 'author', 'contributor', 'subscriber'] as $role_key) :
                                        $role_label = $roles[$role_key]['name'] ?? ucfirst($role_key);
                                        ?>
                                        <label><input type="checkbox" name="two_factor_required_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $required_roles, true)); ?>> <?php echo esc_html($role_label); ?></label><br>
                                    <?php endforeach; ?>
                                    <p class="description"><?php _e('User trong các vai trò này sẽ phải nhập mã 2FA khi đăng nhập.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập Đăng nhập', 'wp-plugin-security')); ?>
                    </form>

                                <?php elseif ($current_tab === 'speed') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                        <h2><?php _e('T?c ?? & T?i uu', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
                                <h4><?php _e('D?n d?p frontend', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('disable_emojis', 'T?t Emoji', $main_settings, 'T?t script v� style emoji tr�n frontend/backend.'); ?>
                                    <?php $this->render_checkbox_row('disable_block_library_css', 'T?t CSS Block', $main_settings, 'B? wp-block-library CSS kh�ng c?n thi?t tr�n frontend.'); ?>
                                    <?php $this->render_checkbox_row('disable_dashicons', 'T?t Dashicons', $main_settings, 'V� hi?u h�a Dashicons cho visitor chua dang nh?p.'); ?>
                                    <?php $this->render_checkbox_row('minify_html', 'R�t g?n HTML', $main_settings, 'Gom kho?ng tr?ng th?a trong HTML output.'); ?>
                                </table>
                            </div>

                            <div class="wps-card">
                                <h4><?php _e('D?n d?p co s? d? li?u', 'wp-plugin-security'); ?></h4>
                                <p><?php _e('X�a revision v� transient h?t h?n d? gi?m r�c d? li?u kh�ng c?n thi?t.', 'wp-plugin-security'); ?></p>
                                <p>
                                    <button type="submit" name="wps_maintenance_action" value="cleanup_revisions" class="button"><?php _e('Clean Revisions', 'wp-plugin-security'); ?></button>
                                    <button type="submit" name="wps_maintenance_action" value="cleanup_transients" class="button"><?php _e('Clean Transients', 'wp-plugin-security'); ?></button>
                                </p>
                            </div>
                        </div>

                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Luu thi?t l?p T?c ??', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'seo') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('SEO & Mục lục', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Mục lục tự động', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('enable_toc', 'Bật TOC', $main_settings, 'Tự động chèn mục lục vào bài viết/trang có heading.'); ?>
                                    <tr>
                <th scope="row"><label for="toc_title"><?php _e('Tiêu đề mục lục', 'wp-plugin-security'); ?></label></th>
                                        <td>
                <input type="text" id="toc_title" name="toc_title" value="<?php echo esc_attr($main_settings['toc_title'] ?? 'Mục lục'); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                <th scope="row"><?php _e('Loại bài viết', 'wp-plugin-security'); ?></th>
                                        <td>
                                            <?php
                                            $toc_types = (array) ($main_settings['toc_post_types'] ?? $this->get_toc_post_types());
                                            foreach ($this->get_toc_post_types() as $post_type) :
                                                $post_object = get_post_type_object($post_type);
                                                $label = $post_object && !empty($post_object->labels->singular_name) ? $post_object->labels->singular_name : $post_type;
                                                ?>
                                                <label><input type="checkbox" name="toc_post_types[]" value="<?php echo esc_attr($post_type); ?>" <?php checked(in_array($post_type, $toc_types, true)); ?>> <?php echo esc_html($label); ?></label><br>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="wps-card">
                                <h4><?php _e('Công cụ nội dung', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('auto_featured_image', 'Tự động ảnh đại diện', $main_settings, 'Tự lấy ảnh đầu tiên trong nội dung làm thumbnail nếu chưa có.'); ?>
                                </table>
                                <p class="description"><?php _e('Tinh nang TOC va thumbnail se duoc xu ly boi controller feature moi.', 'wp-plugin-security'); ?></p>
                            </div>

                        </div>

                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập SEO', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'seo_ai') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('SEO AI + Rank Math', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
                                <h4><?php _e('Tự động tối ưu', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('seo_ai_enabled', 'Bật SEO AI', $main_settings, 'Tự sinh focus keyword, SEO title và meta description cho Rank Math khi lưu bài.'); ?>
                                    <?php $this->render_checkbox_row('seo_ai_sync_rank_math', 'Đồng bộ Rank Math', $main_settings, 'Ghi meta vào các field của Rank Math để plugin này đọc trực tiếp.'); ?>
                                    <tr>
                                        <th scope="row"><label for="seo_ai_brand_name"><?php _e('Tên thương hiệu', 'wp-plugin-security'); ?></label></th>
                                        <td><input type="text" id="seo_ai_brand_name" name="seo_ai_brand_name" value="<?php echo esc_attr($main_settings['seo_ai_brand_name'] ?? get_bloginfo('name')); ?>" class="regular-text"></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php _e('Loại bài viết', 'wp-plugin-security'); ?></th>
                                        <td>
                                            <?php
                                            $seo_ai_types = (array) ($main_settings['seo_ai_post_types'] ?? $this->get_seo_ai_post_types());
                                            foreach ($this->get_seo_ai_post_types() as $post_type) :
                                                $post_object = get_post_type_object($post_type);
                                                $label = $post_object && !empty($post_object->labels->singular_name) ? $post_object->labels->singular_name : $post_type;
                                                ?>
                                                <label><input type="checkbox" name="seo_ai_post_types[]" value="<?php echo esc_attr($post_type); ?>" <?php checked(in_array($post_type, $seo_ai_types, true)); ?>> <?php echo esc_html($label); ?></label><br>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="wps-card">
                                <h4><?php _e('Cách hoạt động', 'wp-plugin-security'); ?></h4>
                                <p><?php _e('Khi lưu bài viết, module sẽ tạo focus keyword, SEO title, meta description và đồng bộ vào meta của Rank Math.', 'wp-plugin-security'); ?></p>
                                <p><?php _e('Điểm SEO AI ước tính chỉ là checklist nội bộ. Điểm thật của Rank Math vẫn phụ thuộc vào nội dung và test của plugin đó.', 'wp-plugin-security'); ?></p>
                            </div>
                            <div class="wps-card">
                                <h4><?php _e('Gemini API', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('seo_ai_use_gemini', 'Dùng Gemini', $main_settings, 'Gọi Gemini để sinh title và description theo prompt thật.'); ?>
                                    <tr>
                                        <th scope="row"><label for="seo_ai_gemini_api_key"><?php _e('Gemini API key', 'wp-plugin-security'); ?></label></th>
                                        <td><input type="password" id="seo_ai_gemini_api_key" name="seo_ai_gemini_api_key" value="<?php echo esc_attr($main_settings['seo_ai_gemini_api_key'] ?? ''); ?>" class="regular-text" autocomplete="off"></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="seo_ai_gemini_model"><?php _e('Model', 'wp-plugin-security'); ?></label></th>
                                        <td><input type="text" id="seo_ai_gemini_model" name="seo_ai_gemini_model" value="<?php echo esc_attr($main_settings['seo_ai_gemini_model'] ?? 'gemini-2.5-flash'); ?>" class="regular-text"></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="seo_ai_gemini_temperature"><?php _e('Temperature', 'wp-plugin-security'); ?></label></th>
                                        <td><input type="number" step="0.1" min="0" max="2" id="seo_ai_gemini_temperature" name="seo_ai_gemini_temperature" value="<?php echo esc_attr($main_settings['seo_ai_gemini_temperature'] ?? 0.4); ?>" class="small-text"></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="seo_ai_gemini_prompt"><?php _e('Prompt mẫu', 'wp-plugin-security'); ?></label></th>
                                        <td>
                                            <textarea id="seo_ai_gemini_prompt" name="seo_ai_gemini_prompt" rows="8" class="large-text code"><?php echo esc_textarea($main_settings['seo_ai_gemini_prompt'] ?? ''); ?></textarea>
                                            <p class="description"><?php _e('Để trống để dùng prompt tối ưu SEO mặc định của plugin. Hỗ trợ placeholder {title}, {content}, {brand}, {post_type}.', 'wp-plugin-security'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                <div class="wps-card" style="margin-top: 20px;">
                    <h4><?php _e('Quét bài viết hiện có', 'wp-plugin-security'); ?></h4>
                    <p class="description"><?php _e('Chạy quét hàng loạt để tạo SEO title, description và Rank Math meta cho các bài viết đã tồn tại.', 'wp-plugin-security'); ?></p>
                            <p>
                                <button type="button" class="button button-secondary" id="wps-seo-ai-bulk-scan" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_seo_ai_bulk_scan')); ?>"><?php _e('Quét và tối ưu toàn bộ bài viết', 'wp-plugin-security'); ?></button>
                                <span id="wps-seo-ai-bulk-status" class="description" style="margin-left: 12px;"></span>
                            </p>
                            <div style="margin-top: 14px;">
                                <div style="height: 16px; border-radius: 999px; background: #e6edf3; overflow: hidden; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);">
                                    <div id="wps-seo-ai-bulk-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #1167ad 0%, #00a3c4 100%); transition: width 180ms ease;"></div>
                                </div>
                                <div id="wps-seo-ai-bulk-progress-text" class="description" style="margin-top: 8px;"><?php _e('Chưa bắt đầu quét.', 'wp-plugin-security'); ?></div>
                            </div>
                            <div style="margin-top: 18px;">
                                <div class="description" style="margin-bottom: 8px;"><?php _e('Danh sách bài viết sẽ được quét theo thứ tự:', 'wp-plugin-security'); ?></div>
                                <ol id="wps-seo-ai-bulk-list" style="margin: 0; padding-left: 20px; max-height: 280px; overflow: auto; background: #f8fbfe; border: 1px solid #d9e3ef; border-radius: 12px; padding-top: 12px; padding-bottom: 12px;"></ol>
                            </div>
                        </div>
                        <script>
                        (function(){
                            var button = document.getElementById('wps-seo-ai-bulk-scan');
                            var status = document.getElementById('wps-seo-ai-bulk-status');
                            var progressBar = document.getElementById('wps-seo-ai-bulk-progress-bar');
                            var progressText = document.getElementById('wps-seo-ai-bulk-progress-text');
                            var queueList = document.getElementById('wps-seo-ai-bulk-list');
                            if (!button) {
                                return;
                            }

                            function setStatus(text) {
                                if (status) {
                                    status.textContent = text;
                                }
                            }

                            function setProgress(current, total) {
                                var percent = 0;
                                if (total > 0) {
                                    percent = Math.min(100, Math.round((current / total) * 100));
                                }

                                if (progressBar) {
                                    progressBar.style.width = percent + '%';
                                }

                                if (progressText) {
                                    progressText.textContent = total > 0
                                        ? '<?php echo esc_js(__('Đã xử lý', 'wp-plugin-security')); ?> ' + current + '/' + total + ' (' + percent + '%)'
                                        : '<?php echo esc_js(__('Không có bài viết nào cần quét.', 'wp-plugin-security')); ?>';
                                }
                            }

                            function renderQueue(items) {
                                if (!queueList) {
                                    return;
                                }

                                queueList.innerHTML = '';
                                items.forEach(function(item, index) {
                                    var li = document.createElement('li');
                                    li.setAttribute('data-index', index);
                                    li.style.margin = '0 0 10px 0';
                                    li.style.padding = '8px 12px';
                                    li.style.background = '#ffffff';
                                    li.style.borderLeft = '4px solid #d1dbe7';
                                    li.style.borderRadius = '8px';
                                    li.style.display = 'flex';
                                    li.style.justifyContent = 'space-between';
                                    li.style.gap = '12px';

                                    var title = document.createElement('span');
                                    title.textContent = (index + 1) + '. ' + (item.title || ('#' + item.id));
                                    title.style.fontWeight = '600';
                                    title.style.color = '#1d2a3b';

                                    var meta = document.createElement('span');
                                    meta.textContent = item.post_type ? '[' + item.post_type + ']' : '';
                                    meta.className = 'description';

                                    var state = document.createElement('span');
                                    state.className = 'description';
                                    state.setAttribute('data-state', 'pending');
                                    state.textContent = '<?php echo esc_js(__('Chờ xử lý', 'wp-plugin-security')); ?>';

                                    li.appendChild(title);
                                    li.appendChild(meta);
                                    li.appendChild(state);
                                    queueList.appendChild(li);
                                });
                            }

                            function setItemState(index, text, color) {
                                if (!queueList) {
                                    return;
                                }

                                var item = queueList.querySelector('li[data-index="' + index + '"] [data-state]');
                                if (!item) {
                                    return;
                                }

                                item.textContent = text;
                                item.style.color = color || '';
                            }

                            function fetchQueue() {
                                var formData = new FormData();
                                formData.append('action', 'wps_seo_ai_bulk_queue');
                                formData.append('nonce', button.getAttribute('data-nonce'));

                                return fetch(ajaxurl, {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    body: formData
                                }).then(function(response){
                                    return response.json();
                                }).then(function(payload){
                                    if (!payload || !payload.success) {
                                        throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Không tải được danh sách bài viết.', 'wp-plugin-security')); ?>');
                                    }

                                    return payload.data || {};
                                });
                            }

                            function processItem(item, index, total) {
                                var formData = new FormData();
                                formData.append('action', 'wps_seo_ai_bulk_process_post');
                                formData.append('nonce', button.getAttribute('data-nonce'));
                                formData.append('post_id', item.id);

                                setItemState(index, '<?php echo esc_js(__('Đang xử lý', 'wp-plugin-security')); ?>', '#1167ad');
                                setStatus('<?php echo esc_js(__('Đang xử lý bài:', 'wp-plugin-security')); ?> ' + (item.title || ('#' + item.id)));

                                return fetch(ajaxurl, {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    body: formData
                                }).then(function(response){
                                    return response.json();
                                }).then(function(payload){
                                    if (!payload || !payload.success) {
                                        throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Quét hàng loạt thất bại.', 'wp-plugin-security')); ?>');
                                    }

                                    var data = payload.data || {};
                                    setItemState(index, '<?php echo esc_js(__('Hoàn tất', 'wp-plugin-security')); ?>', '#1f7a3f');
                                    setStatus((data.message || '<?php echo esc_js(__('Đã tối ưu xong.', 'wp-plugin-security')); ?>') + ' ' + (item.title || ('#' + item.id)));
                                    setProgress(index + 1, total);
                                    return data;
                                }).catch(function(error){
                                    setItemState(index, '<?php echo esc_js(__('Lỗi', 'wp-plugin-security')); ?>', '#b42318');
                                    setStatus((error && error.message ? error.message : '<?php echo esc_js(__('Quét hàng loạt thất bại.', 'wp-plugin-security')); ?>') + ' ' + (item.title || ('#' + item.id)));
                                    setProgress(index + 1, total);
                                    return null;
                                });
                            }

                            async function runQueue(items) {
                                renderQueue(items);
                                var total = items.length;
                                if (!total) {
                                    setStatus('<?php echo esc_js(__('Không có bài viết nào cần quét.', 'wp-plugin-security')); ?>');
                                    setProgress(0, 0);
                                    button.disabled = false;
                                    button.textContent = '<?php echo esc_js(__('Quét và tối ưu toàn bộ bài viết', 'wp-plugin-security')); ?>';
                                    return;
                                }

                                for (var i = 0; i < items.length; i++) {
                                    await processItem(items[i], i, total);
                                }

                                setStatus('<?php echo esc_js(__('Đã quét xong toàn bộ danh sách.', 'wp-plugin-security')); ?>');
                                setProgress(total, total);
                                button.disabled = false;
                                button.textContent = '<?php echo esc_js(__('Quét và tối ưu toàn bộ bài viết', 'wp-plugin-security')); ?>';
                            }

                            button.addEventListener('click', function(event){
                                event.preventDefault();
                                button.disabled = true;
                                button.textContent = '<?php echo esc_js(__('Đang tải danh sách...', 'wp-plugin-security')); ?>';
                                setStatus('<?php echo esc_js(__('Đang lấy danh sách bài viết...', 'wp-plugin-security')); ?>');
                                setProgress(0, 0);

                                fetchQueue().then(function(data){
                                    var items = Array.isArray(data.items) ? data.items : [];
                                    setProgress(0, items.length);
                                    return runQueue(items);
                                }).catch(function(error){
                                    setStatus(error && error.message ? error.message : '<?php echo esc_js(__('Quét hàng loạt thất bại.', 'wp-plugin-security')); ?>');
                                    button.disabled = false;
                                    button.textContent = '<?php echo esc_js(__('Quét và tối ưu toàn bộ bài viết', 'wp-plugin-security')); ?>';
                                });
                            });
                        })();
                        </script>
                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập SEO AI', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'seo_content_ai') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('SEO Content', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
                                <h4><?php _e('Tự động viết lại', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('seo_content_enabled', 'Bật SEO Content', $main_settings, 'Tự quét và viết lại nội dung dựa trên title/content hiện có.'); ?>
                                    <?php $this->render_checkbox_row('seo_content_auto_update', 'Tự động cập nhật khi lưu', $main_settings, 'Mỗi lần lưu bài, plugin sẽ viết lại nội dung và cập nhật post_content.'); ?>
                                    <tr>
                                        <th scope="row"><?php _e('Loại bài viết', 'wp-plugin-security'); ?></th>
                                        <td>
                                            <?php
                                            $seo_content_types = (array) ($main_settings['seo_content_post_types'] ?? $this->get_seo_content_post_types());
                                            foreach ($this->get_seo_content_post_types() as $post_type) :
                                                $post_object = get_post_type_object($post_type);
                                                $label = $post_object && !empty($post_object->labels->singular_name) ? $post_object->labels->singular_name : $post_type;
                                                ?>
                                                <label><input type="checkbox" name="seo_content_post_types[]" value="<?php echo esc_attr($post_type); ?>" <?php checked(in_array($post_type, $seo_content_types, true)); ?>> <?php echo esc_html($label); ?></label><br>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="wps-card">
                                <h4><?php _e('Cách hoạt động', 'wp-plugin-security'); ?></h4>
                                <p><?php _e('SEO Content sẽ dùng Gemini hoặc logic nội bộ để viết lại phần nội dung theo title và content hiện có. Kết quả sẽ được ghi trực tiếp vào post_content.', 'wp-plugin-security'); ?></p>
                                <p><?php _e('Hãy bật trước trên một nhóm bài nhỏ để kiểm tra giọng văn và cấu trúc HTML trước khi quét toàn site.', 'wp-plugin-security'); ?></p>
                            </div>
                            <div class="wps-card">
                                <h4><?php _e('Gemini Prompt', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('seo_content_use_gemini', 'Dùng Gemini', $main_settings, 'Gọi Gemini để viết lại nội dung theo prompt thật.'); ?>
                                    <tr>
                                        <th scope="row"><label for="seo_content_gemini_prompt"><?php _e('Prompt mẫu', 'wp-plugin-security'); ?></label></th>
                                        <td>
                                            <textarea id="seo_content_gemini_prompt" name="seo_content_gemini_prompt" rows="8" class="large-text code"><?php echo esc_textarea($main_settings['seo_content_gemini_prompt'] ?? ''); ?></textarea>
                                            <p class="description"><?php _e('Để trống để dùng prompt mặc định của plugin. Hỗ trợ placeholder {title}, {content}, {brand}, {post_type}.', 'wp-plugin-security'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="wps-card" style="margin-top: 20px;">
                            <h4><?php _e('Quét bài viết hiện có', 'wp-plugin-security'); ?></h4>
                            <p class="description"><?php _e('Lấy danh sách bài viết rồi viết lại từng bài theo title/content hiện có.', 'wp-plugin-security'); ?></p>
                            <p>
                                <button type="button" class="button button-secondary" id="wps-seo-content-bulk-scan" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_seo_content_bulk_scan')); ?>"><?php _e('Quét và cập nhật nội dung', 'wp-plugin-security'); ?></button>
                                <span id="wps-seo-content-bulk-status" class="description" style="margin-left: 12px;"></span>
                            </p>
                            <div style="margin-top: 14px;">
                                <div style="height: 16px; border-radius: 999px; background: #e6edf3; overflow: hidden; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);">
                                    <div id="wps-seo-content-bulk-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #1167ad 0%, #00a3c4 100%); transition: width 180ms ease;"></div>
                                </div>
                                <div id="wps-seo-content-bulk-progress-text" class="description" style="margin-top: 8px;"><?php _e('Chưa bắt đầu quét.', 'wp-plugin-security'); ?></div>
                            </div>
                            <div style="margin-top: 18px;">
                                <div class="description" style="margin-bottom: 8px;"><?php _e('Danh sách bài viết sẽ được quét theo thứ tự:', 'wp-plugin-security'); ?></div>
                                <ol id="wps-seo-content-bulk-list" style="margin: 0; padding-left: 20px; max-height: 280px; overflow: auto; background: #f8fbfe; border: 1px solid #d9e3ef; border-radius: 12px; padding-top: 12px; padding-bottom: 12px;"></ol>
                            </div>
                        </div>
                        <script>
                        (function(){
                            var button = document.getElementById('wps-seo-content-bulk-scan');
                            var status = document.getElementById('wps-seo-content-bulk-status');
                            var progressBar = document.getElementById('wps-seo-content-bulk-progress-bar');
                            var progressText = document.getElementById('wps-seo-content-bulk-progress-text');
                            var queueList = document.getElementById('wps-seo-content-bulk-list');
                            if (!button) {
                                return;
                            }

                            function setStatus(text) {
                                if (status) {
                                    status.textContent = text;
                                }
                            }

                            function setProgress(current, total) {
                                var percent = 0;
                                if (total > 0) {
                                    percent = Math.min(100, Math.round((current / total) * 100));
                                }

                                if (progressBar) {
                                    progressBar.style.width = percent + '%';
                                }

                                if (progressText) {
                                    progressText.textContent = total > 0
                                        ? '<?php echo esc_js(__('Đã xử lý', 'wp-plugin-security')); ?> ' + current + '/' + total + ' (' + percent + '%)'
                                        : '<?php echo esc_js(__('Không có bài viết nào cần quét.', 'wp-plugin-security')); ?>';
                                }
                            }

                            function renderQueue(items) {
                                if (!queueList) {
                                    return;
                                }

                                queueList.innerHTML = '';
                                items.forEach(function(item, index) {
                                    var li = document.createElement('li');
                                    li.setAttribute('data-index', index);
                                    li.style.margin = '0 0 10px 0';
                                    li.style.padding = '8px 12px';
                                    li.style.background = '#ffffff';
                                    li.style.borderLeft = '4px solid #d1dbe7';
                                    li.style.borderRadius = '8px';
                                    li.style.display = 'flex';
                                    li.style.justifyContent = 'space-between';
                                    li.style.gap = '12px';

                                    var title = document.createElement('span');
                                    title.textContent = (index + 1) + '. ' + (item.title || ('#' + item.id));
                                    title.style.fontWeight = '600';
                                    title.style.color = '#1d2a3b';

                                    var meta = document.createElement('span');
                                    meta.textContent = item.post_type ? '[' + item.post_type + ']' : '';
                                    meta.className = 'description';

                                    var state = document.createElement('span');
                                    state.className = 'description';
                                    state.setAttribute('data-state', 'pending');
                                    state.textContent = '<?php echo esc_js(__('Chờ xử lý', 'wp-plugin-security')); ?>';

                                    li.appendChild(title);
                                    li.appendChild(meta);
                                    li.appendChild(state);
                                    queueList.appendChild(li);
                                });
                            }

                            function setItemState(index, text, color) {
                                if (!queueList) {
                                    return;
                                }

                                var item = queueList.querySelector('li[data-index="' + index + '"] [data-state]');
                                if (!item) {
                                    return;
                                }

                                item.textContent = text;
                                item.style.color = color || '';
                            }

                            function fetchQueue() {
                                var formData = new FormData();
                                formData.append('action', 'wps_seo_content_bulk_queue');
                                formData.append('nonce', button.getAttribute('data-nonce'));

                                return fetch(ajaxurl, {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    body: formData
                                }).then(function(response){
                                    return response.json();
                                }).then(function(payload){
                                    if (!payload || !payload.success) {
                                        throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Không tải được danh sách bài viết.', 'wp-plugin-security')); ?>');
                                    }

                                    return payload.data || {};
                                });
                            }

                            function processItem(item, index, total) {
                                var formData = new FormData();
                                formData.append('action', 'wps_seo_content_bulk_process_post');
                                formData.append('nonce', button.getAttribute('data-nonce'));
                                formData.append('post_id', item.id);

                                setItemState(index, '<?php echo esc_js(__('Đang xử lý', 'wp-plugin-security')); ?>', '#1167ad');
                                setStatus('<?php echo esc_js(__('Đang xử lý bài:', 'wp-plugin-security')); ?> ' + (item.title || ('#' + item.id)));

                                return fetch(ajaxurl, {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    body: formData
                                }).then(function(response){
                                    return response.json();
                                }).then(function(payload){
                                    if (!payload || !payload.success) {
                                        throw new Error((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js(__('Quét hàng loạt thất bại.', 'wp-plugin-security')); ?>');
                                    }

                                    var data = payload.data || {};
                                    setItemState(index, '<?php echo esc_js(__('Hoàn tất', 'wp-plugin-security')); ?>', '#1f7a3f');
                                    setStatus((data.message || '<?php echo esc_js(__('Đã tối ưu xong.', 'wp-plugin-security')); ?>') + ' ' + (item.title || ('#' + item.id)));
                                    setProgress(index + 1, total);
                                    return data;
                                }).catch(function(error){
                                    setItemState(index, '<?php echo esc_js(__('Lỗi', 'wp-plugin-security')); ?>', '#b42318');
                                    setStatus((error && error.message ? error.message : '<?php echo esc_js(__('Quét hàng loạt thất bại.', 'wp-plugin-security')); ?>') + ' ' + (item.title || ('#' + item.id)));
                                    setProgress(index + 1, total);
                                    return null;
                                });
                            }

                            async function runQueue(items) {
                                renderQueue(items);
                                var total = items.length;
                                if (!total) {
                                    setStatus('<?php echo esc_js(__('Không có bài viết nào cần quét.', 'wp-plugin-security')); ?>');
                                    setProgress(0, 0);
                                    button.disabled = false;
                                    button.textContent = '<?php echo esc_js(__('Quét và cập nhật nội dung', 'wp-plugin-security')); ?>';
                                    return;
                                }

                                for (var i = 0; i < items.length; i++) {
                                    await processItem(items[i], i, total);
                                }

                                setStatus('<?php echo esc_js(__('Đã quét xong toàn bộ danh sách.', 'wp-plugin-security')); ?>');
                                setProgress(total, total);
                                button.disabled = false;
                                button.textContent = '<?php echo esc_js(__('Quét và cập nhật nội dung', 'wp-plugin-security')); ?>';
                            }

                            button.addEventListener('click', function(event){
                                event.preventDefault();
                                button.disabled = true;
                                button.textContent = '<?php echo esc_js(__('Đang tải danh sách...', 'wp-plugin-security')); ?>';
                                setStatus('<?php echo esc_js(__('Đang lấy danh sách bài viết...', 'wp-plugin-security')); ?>');
                                setProgress(0, 0);

                                fetchQueue().then(function(data){
                                    var items = Array.isArray(data.items) ? data.items : [];
                                    setProgress(0, items.length);
                                    return runQueue(items);
                                }).catch(function(error){
                                    setStatus(error && error.message ? error.message : '<?php echo esc_js(__('Quét hàng loạt thất bại.', 'wp-plugin-security')); ?>');
                                    button.disabled = false;
                                    button.textContent = '<?php echo esc_js(__('Quét và cập nhật nội dung', 'wp-plugin-security')); ?>';
                                });
                            });
                        })();
                        </script>
                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập SEO Content', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'editor') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Trình soạn thảo cổ điển & TinyMCE', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Chế độ soạn thảo', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('disable_block_editor', 'Tắt Block Editor', $main_settings, 'Ép dùng Gutenberg cho tất cả loại bài viết.'); ?>
        <?php $this->render_checkbox_row('enable_tinymce_advanced', 'TinyMCE nâng cao', $main_settings, 'Mở rộng các nút font, code, màu sắc và table.'); ?>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Ghi chú', 'wp-plugin-security'); ?></h4>
        <p><?php _e('Các nút TinyMCE được controller mới thêm vào editor khi tùy chọn này được bật.', 'wp-plugin-security'); ?></p>
                            </div>
                        </div>

                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập Editor', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'updates') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Quản lý cập nhật', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Khóa cập nhật', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('block_core_updates', 'Chặn cập nhật Core', $main_settings, 'Chặn kiểm tra/cập nhật WordPress core.'); ?>
        <?php $this->render_checkbox_row('block_plugin_updates', 'Chặn cập nhật Plugin', $main_settings, 'Chặn cập nhật plugin.'); ?>
        <?php $this->render_checkbox_row('block_theme_updates', 'Chặn cập nhật Theme', $main_settings, 'Chặn cập nhật theme.'); ?>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Kiểm tra bản phát hành', 'wp-plugin-security'); ?></h4>
        <p><?php _e('Nhấn vào Kiểm tra cập nhật để so sánh phiên bản hiện tại với bản GitHub mới nhất.', 'wp-plugin-security'); ?></p>
        <p><a href="#" class="button button-primary wps-check-update-btn" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_check_update_nonce')); ?>"><?php _e('Kiểm tra cập nhật', 'wp-plugin-security'); ?></a></p>
                            </div>
                        </div>

                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập Cập nhật', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'email') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Email & Thông báo', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('SMTP', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('smtp_enabled', 'Bật SMTP', $main_settings, 'Ghi đè wp_mail bằng SMTP server bên ngoài.'); ?>
        <tr><th scope="row"><label for="smtp_host"><?php _e('Máy chủ SMTP', 'wp-plugin-security'); ?></label></th><td><input type="text" id="smtp_host" name="smtp_host" value="<?php echo esc_attr($main_settings['smtp_host'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="smtp_port"><?php _e('Cổng SMTP', 'wp-plugin-security'); ?></label></th><td><input type="number" id="smtp_port" name="smtp_port" value="<?php echo esc_attr($main_settings['smtp_port'] ?? 587); ?>" class="small-text"></td></tr>
        <tr><th scope="row"><label for="smtp_encryption"><?php _e('Mã hóa', 'wp-plugin-security'); ?></label></th><td><select id="smtp_encryption" name="smtp_encryption"><option value="tls" <?php selected(($main_settings['smtp_encryption'] ?? 'tls'), 'tls'); ?>>TLS</option><option value="ssl" <?php selected(($main_settings['smtp_encryption'] ?? 'tls'), 'ssl'); ?>>SSL</option><option value="none" <?php selected(($main_settings['smtp_encryption'] ?? 'tls'), 'none'); ?>>None</option></select></td></tr>
        <tr><th scope="row"><label for="smtp_username"><?php _e('Tên đăng nhập', 'wp-plugin-security'); ?></label></th><td><input type="text" id="smtp_username" name="smtp_username" value="<?php echo esc_attr($main_settings['smtp_username'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="smtp_password"><?php _e('Mật khẩu', 'wp-plugin-security'); ?></label></th><td><input type="password" id="smtp_password" name="smtp_password" value="<?php echo esc_attr($main_settings['smtp_password'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="smtp_from_email"><?php _e('Email gửi', 'wp-plugin-security'); ?></label></th><td><input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo esc_attr($main_settings['smtp_from_email'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="smtp_from_name"><?php _e('Tên gửi', 'wp-plugin-security'); ?></label></th><td><input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo esc_attr($main_settings['smtp_from_name'] ?? ''); ?>" class="regular-text"></td></tr>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Thanh thông báo', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('notification_bar_enabled', 'Bật thanh thông báo', $main_settings, 'Hiển thị thanh thông báo trên/dưới toàn site.'); ?>
        <tr><th scope="row"><label for="notification_bar_position"><?php _e('Vị trí', 'wp-plugin-security'); ?></label></th><td><select id="notification_bar_position" name="notification_bar_position"><option value="top" <?php selected(($main_settings['notification_bar_position'] ?? 'top'), 'top'); ?>>Trên</option><option value="bottom" <?php selected(($main_settings['notification_bar_position'] ?? 'top'), 'bottom'); ?>>Dưới</option></select></td></tr>
        <tr><th scope="row"><label for="notification_bar_text"><?php _e('Nội dung', 'wp-plugin-security'); ?></label></th><td><textarea id="notification_bar_text" name="notification_bar_text" rows="4" class="large-text"><?php echo esc_textarea($main_settings['notification_bar_text'] ?? ''); ?></textarea></td></tr>
        <tr><th scope="row"><label for="notification_bar_link"><?php _e('Liên kết nút', 'wp-plugin-security'); ?></label></th><td><input type="url" id="notification_bar_link" name="notification_bar_link" value="<?php echo esc_attr($main_settings['notification_bar_link'] ?? ''); ?>" class="large-text"></td></tr>
        <tr><th scope="row"><label for="notification_bar_button"><?php _e('Chữ nút', 'wp-plugin-security'); ?></label></th><td><input type="text" id="notification_bar_button" name="notification_bar_button" value="<?php echo esc_attr($main_settings['notification_bar_button'] ?? ''); ?>" class="regular-text"></td></tr>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập Email', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'google') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Google', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Indexing API', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('google_indexing_enabled', 'Bật Indexing API', $main_settings, 'Gửi URL lên Google khi bài viết được xuất bản.'); ?>
        <tr><th scope="row"><label for="google_indexing_project_id"><?php _e('Mã dự án', 'wp-plugin-security'); ?></label></th><td><input type="text" id="google_indexing_project_id" name="google_indexing_project_id" value="<?php echo esc_attr($main_settings['google_indexing_project_id'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="google_service_account_json"><?php _e('JSON tài khoản dịch vụ', 'wp-plugin-security'); ?></label></th><td><textarea id="google_service_account_json" name="google_service_account_json" rows="7" class="large-text code"><?php echo esc_textarea($main_settings['google_service_account_json'] ?? ''); ?></textarea></td></tr>
        <tr><th scope="row"><?php _e('Loại bài viết', 'wp-plugin-security'); ?></th><td><label><input type="checkbox" name="google_indexing_post_types[]" value="post" <?php checked(in_array('post', (array) ($main_settings['google_indexing_post_types'] ?? ['post']), true)); ?>> <?php _e('Bài viết', 'wp-plugin-security'); ?></label><br><label><input type="checkbox" name="google_indexing_post_types[]" value="page" <?php checked(in_array('page', (array) ($main_settings['google_indexing_post_types'] ?? ['post']), true)); ?>> <?php _e('Trang', 'wp-plugin-security'); ?></label></td></tr>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Đăng nhập Google & reCAPTCHA', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('google_login_enabled', 'Đăng nhập Google', $main_settings, 'Hiển thị nút đăng nhập bằng Google trên form đăng nhập.'); ?>
        <tr><th scope="row"><label for="google_client_id"><?php _e('Client ID', 'wp-plugin-security'); ?></label></th><td><input type="text" id="google_client_id" name="google_client_id" value="<?php echo esc_attr($main_settings['google_client_id'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="google_client_secret"><?php _e('Client Secret', 'wp-plugin-security'); ?></label></th><td><input type="password" id="google_client_secret" name="google_client_secret" value="<?php echo esc_attr($main_settings['google_client_secret'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="google_redirect_uri"><?php _e('URI chuyển hướng', 'wp-plugin-security'); ?></label></th><td><input type="url" id="google_redirect_uri" name="google_redirect_uri" value="<?php echo esc_attr($main_settings['google_redirect_uri'] ?? admin_url('admin-post.php?action=wps_google_callback')); ?>" class="large-text"></td></tr>
                                    <?php $this->render_checkbox_row('recaptcha_enabled', 'reCAPTCHA', $main_settings, 'Bật kiểm tra captcha cho form đăng nhập.'); ?>
        <tr><th scope="row"><label for="recaptcha_site_key"><?php _e('Khóa site', 'wp-plugin-security'); ?></label></th><td><input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr($main_settings['recaptcha_site_key'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="recaptcha_secret_key"><?php _e('Khóa bí mật', 'wp-plugin-security'); ?></label></th><td><input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr($main_settings['recaptcha_secret_key'] ?? ''); ?>" class="regular-text"></td></tr>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập Google', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'marketing') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Marketing & Công cụ', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Thanh thông báo', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('notification_bar_enabled', 'Bật thanh thông báo', $main_settings, 'Hiển thị thanh thông báo trên/dưới toàn site.'); ?>
        <tr><th scope="row"><label for="notification_bar_position"><?php _e('Vị trí', 'wp-plugin-security'); ?></label></th><td><select id="notification_bar_position" name="notification_bar_position"><option value="top" <?php selected(($main_settings['notification_bar_position'] ?? 'top'), 'top'); ?>>Trên</option><option value="bottom" <?php selected(($main_settings['notification_bar_position'] ?? 'top'), 'bottom'); ?>>Dưới</option></select></td></tr>
        <tr><th scope="row"><label for="notification_bar_text"><?php _e('Nội dung', 'wp-plugin-security'); ?></label></th><td><textarea id="notification_bar_text" name="notification_bar_text" rows="4" class="large-text"><?php echo esc_textarea($main_settings['notification_bar_text'] ?? ''); ?></textarea></td></tr>
        <tr><th scope="row"><label for="notification_bar_link"><?php _e('Liên kết nút', 'wp-plugin-security'); ?></label></th><td><input type="url" id="notification_bar_link" name="notification_bar_link" value="<?php echo esc_attr($main_settings['notification_bar_link'] ?? ''); ?>" class="large-text"></td></tr>
        <tr><th scope="row"><label for="notification_bar_button"><?php _e('Chữ nút', 'wp-plugin-security'); ?></label></th><td><input type="text" id="notification_bar_button" name="notification_bar_button" value="<?php echo esc_attr($main_settings['notification_bar_button'] ?? ''); ?>" class="regular-text"></td></tr>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Bong bóng chat', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('chat_enabled', 'Bật chat', $main_settings, 'Hiển thị nhanh các nút liên hệ nổi.'); ?>
        <tr><th scope="row"><label for="chat_phone"><?php _e('Điện thoại', 'wp-plugin-security'); ?></label></th><td><input type="text" id="chat_phone" name="chat_phone" value="<?php echo esc_attr($main_settings['chat_phone'] ?? ''); ?>" class="regular-text"></td></tr>
                                    <tr><th scope="row"><label for="chat_sms"><?php _e('SMS', 'wp-plugin-security'); ?></label></th><td><input type="text" id="chat_sms" name="chat_sms" value="<?php echo esc_attr($main_settings['chat_sms'] ?? ''); ?>" class="regular-text"></td></tr>
                                    <tr><th scope="row"><label for="chat_zalo"><?php _e('Zalo', 'wp-plugin-security'); ?></label></th><td><input type="text" id="chat_zalo" name="chat_zalo" value="<?php echo esc_attr($main_settings['chat_zalo'] ?? ''); ?>" class="regular-text" placeholder="https://zalo.me/..."></td></tr>
                                    <tr><th scope="row"><label for="chat_messenger"><?php _e('Messenger', 'wp-plugin-security'); ?></label></th><td><input type="text" id="chat_messenger" name="chat_messenger" value="<?php echo esc_attr($main_settings['chat_messenger'] ?? ''); ?>" class="regular-text" placeholder="https://m.me/..."></td></tr>
                                    <tr><th scope="row"><label for="chat_telegram"><?php _e('Telegram', 'wp-plugin-security'); ?></label></th><td><input type="text" id="chat_telegram" name="chat_telegram" value="<?php echo esc_attr($main_settings['chat_telegram'] ?? ''); ?>" class="regular-text" placeholder="https://t.me/..."></td></tr>
                                    <tr><th scope="row"><label for="chat_whatsapp"><?php _e('WhatsApp', 'wp-plugin-security'); ?></label></th><td><input type="text" id="chat_whatsapp" name="chat_whatsapp" value="<?php echo esc_attr($main_settings['chat_whatsapp'] ?? ''); ?>" class="regular-text" placeholder="https://wa.me/..."></td></tr>
                                </table>
                            </div>
                        </div>

                        <div class="wps-grid two" style="margin-top:18px;">
                            <div class="wps-card">
        <h4><?php _e('Chèn mã', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <tr><th scope="row"><label for="code_inject_head"><?php _e('Mã đầu trang', 'wp-plugin-security'); ?></label></th><td><textarea id="code_inject_head" name="code_inject_head" rows="8" class="large-text code"><?php echo esc_textarea($main_settings['code_inject_head'] ?? ''); ?></textarea></td></tr>
        <tr><th scope="row"><label for="code_inject_footer"><?php _e('Mã chân trang', 'wp-plugin-security'); ?></label></th><td><textarea id="code_inject_footer" name="code_inject_footer" rows="8" class="large-text code"><?php echo esc_textarea($main_settings['code_inject_footer'] ?? ''); ?></textarea></td></tr>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Chuyển hướng & Tìm và thay thế', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <tr><th scope="row"><label for="redirect_rules"><?php _e('Quy tắc chuyển hướng', 'wp-plugin-security'); ?></label></th><td><textarea id="redirect_rules" name="redirect_rules" rows="8" class="large-text code" placeholder="/old|/new|301"><?php echo esc_textarea($main_settings['redirect_rules'] ?? ''); ?></textarea><p class="description"><?php _e('Mỗi dòng: from|to|301. from có thể là path tương đối.', 'wp-plugin-security'); ?></p></td></tr>
        <tr><th scope="row"><label for="search_replace_from"><?php _e('Tìm', 'wp-plugin-security'); ?></label></th><td><input type="text" id="search_replace_from" name="search_replace_from" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="search_replace_to"><?php _e('Thay thế', 'wp-plugin-security'); ?></label></th><td><input type="text" id="search_replace_to" name="search_replace_to" class="regular-text"></td></tr>
                                </table>
                                <p>
        <button type="submit" name="wps_search_replace_action" value="1" class="button button-secondary" onclick="return confirm('<?php echo esc_js(__('Tìm và thay thế sẽ thay đổi nội dung trong bài viết, postmeta và options có liên quan. Tiếp tục?', 'wp-plugin-security')); ?>');"><?php _e('Chạy tìm và thay thế', 'wp-plugin-security'); ?></button>
                                </p>
                            </div>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập Marketing', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'woocommerce') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('WooCommerce', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Văn bản', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <tr><th scope="row"><label for="woo_add_to_cart_text"><?php _e('Chữ "Thêm vào giỏ"', 'wp-plugin-security'); ?></label></th><td><input type="text" id="woo_add_to_cart_text" name="woo_add_to_cart_text" value="<?php echo esc_attr($main_settings['woo_add_to_cart_text'] ?? ''); ?>" class="regular-text" placeholder="<?php esc_attr_e('Thêm vào giỏ', 'wp-plugin-security'); ?>"></td></tr>
        <tr><th scope="row"><label for="woo_price_zero_text"><?php _e('Chữ giá 0', 'wp-plugin-security'); ?></label></th><td><input type="text" id="woo_price_zero_text" name="woo_price_zero_text" value="<?php echo esc_attr($main_settings['woo_price_zero_text'] ?? __('Liên hệ', 'wp-plugin-security')); ?>" class="regular-text"></td></tr>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Cảnh báo Telegram', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('woo_telegram_enabled', 'Bật cảnh báo', $main_settings, 'Gửi thông báo đơn hàng về Telegram bot.'); ?>
        <tr><th scope="row"><label for="woo_telegram_bot_token"><?php _e('Mã bot', 'wp-plugin-security'); ?></label></th><td><input type="password" id="woo_telegram_bot_token" name="woo_telegram_bot_token" value="<?php echo esc_attr($main_settings['woo_telegram_bot_token'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="woo_telegram_chat_id"><?php _e('Chat ID', 'wp-plugin-security'); ?></label></th><td><input type="text" id="woo_telegram_chat_id" name="woo_telegram_chat_id" value="<?php echo esc_attr($main_settings['woo_telegram_chat_id'] ?? ''); ?>" class="regular-text"></td></tr>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập WooCommerce', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'users') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Người dùng', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Cô lập', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('user_isolation_enabled', 'Bật cô lập', $main_settings, 'Chặn user thường xem bài/media của người khác trong admin.'); ?>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Ảnh đại diện cục bộ', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('local_avatar_enabled', 'Bật ảnh đại diện cục bộ', $main_settings, 'Cho phép lưu avatar trong media của site.'); ?>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập Người dùng', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'monitoring') : ?>
                    <?php do_action('wps_render_admin_tab_monitoring', $main_settings); ?>

                <?php elseif ($current_tab === 'blacklist') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                        <div class="card" style="max-width: 100%; margin-top: 0;">
        <h2><?php _e('Quản lý IP bị chặn', 'wp-plugin-security'); ?></h2>
        <p class="description"><?php _e('Nhập mỗi địa chỉ IP trên một dòng.', 'wp-plugin-security'); ?></p>
                            <textarea name="wps_blocked_ips_raw" rows="10" class="large-text code" style="width: 100%;"><?php echo esc_textarea($ips_text); ?></textarea>
                        </div>

                        <div class="card" style="max-width: 100%; margin-top: 20px;">
        <h2><?php _e('Nhật ký chặn tự động (Gần đây)', 'wp-plugin-security'); ?></h2>
                            <table class="widefat fixed striped">
                                <thead>
                                    <tr>
        <th width="150"><?php _e('Thời gian', 'wp-plugin-security'); ?></th>
        <th width="150"><?php _e('IP', 'wp-plugin-security'); ?></th>
        <th><?php _e('Lý do', 'wp-plugin-security'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $auto_blocked = array_filter($security_logs, function ($log) {
                                        return in_array($log['type'], ['ip_blocked', 'dangerous_request'], true);
                                    });
                                    if (empty($auto_blocked)) :
                                        ?>
                                        <tr>
        <td colspan="3"><?php _e('Chưa có IP bị chặn tự động.', 'wp-plugin-security'); ?></td>
                                        </tr>
                                    <?php else : ?>
                                        <?php foreach (array_slice(array_reverse($auto_blocked), 0, 10) as $log) : ?>
                                            <tr>
                                                <td><?php echo esc_html(date('H:i d/m/Y', strtotime($log['time']))); ?></td>
                                                <td><code><?php echo esc_html($log['ip']); ?></code></td>
                                                <td><?php echo esc_html($log['message']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Cập nhật Blacklist', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'audit') : ?>
        <h2><?php _e('Lịch sử hoạt động (Audit Trail)', 'wp-plugin-security'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
        <th width="150"><?php _e('Thời gian', 'wp-plugin-security'); ?></th>
        <th width="150"><?php _e('Người dùng', 'wp-plugin-security'); ?></th>
        <th width="120"><?php _e('Hành động', 'wp-plugin-security'); ?></th>
        <th><?php _e('Chi tiết', 'wp-plugin-security'); ?></th>
        <th width="150"><?php _e('Địa chỉ IP', 'wp-plugin-security'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($audit_logs)) : ?>
                                <tr>
        <td colspan="5"><?php _e('Chưa có hoạt động nào được ghi lại.', 'wp-plugin-security'); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach (array_reverse($audit_logs) as $log) : ?>
                                    <?php
                                    $action = strtolower($log['action'] ?? 'info');
                                    $color = '#64748b';
                                    if (strpos($action, 'login') !== false) {
                                        $color = '#10b981';
                                    }
                                    if (strpos($action, 'failed') !== false || strpos($action, 'blocked') !== false) {
                                        $color = '#ef4444';
                                    }
                                    ?>
                                    <tr>
                                        <td><small><?php echo esc_html(date('d/m/Y H:i:s', strtotime($log['time'] ?? 'now'))); ?></small></td>
        <td><strong><?php echo esc_html($log['user'] ?? __('Khách', 'wp-plugin-security')); ?></strong></td>
                                        <td>
                                            <span style="background: <?php echo esc_attr($color); ?>; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                                                <?php echo esc_html($log['action'] ?? 'INFO'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($log['message'] ?? ''); ?></td>
                                        <td><code><?php echo esc_html($log['ip'] ?? '0.0.0.0'); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($current_tab === 'changelog') : ?>
                    <div class="wps-section-head">
                        <div>
        <h3><?php _e('Changelog & Bản đồ tính năng', 'wp-plugin-security'); ?></h3>
        <p><?php _e('Tổng hợp các nhóm tính năng đã được ghi trong .agent/CHANGELOG.md và cách truy cập nhanh trong admin.', 'wp-plugin-security'); ?></p>
                        </div>
                    </div>

                    <div class="wps-grid two">
                        <div class="wps-card">
        <h4><?php _e('Bảo mật', 'wp-plugin-security'); ?></h4>
        <p><?php _e('Tắt XML-RPC, REST API, lọc upload file, ẩn version, SQL protection và cảnh báo request nguy hiểm.', 'wp-plugin-security'); ?></p>
                        </div>
                        <div class="wps-card">
        <h4><?php _e('SEO & Nội dung', 'wp-plugin-security'); ?></h4>
        <p><?php _e('TOC tự động, auto image saver, SEO URL rewrite, duplicate content, auto featured image.', 'wp-plugin-security'); ?></p>
                        </div>
                        <div class="wps-card">
        <h4><?php _e('Trình soạn thảo & Cập nhật', 'wp-plugin-security'); ?></h4>
        <p><?php _e('Trình soạn thảo cổ điển nâng cao, chặn cập nhật core/plugin/theme, anti copy, ẩn menu gọn hơn.', 'wp-plugin-security'); ?></p>
                        </div>
                        <div class="wps-card">
        <h4><?php _e('Google & Email', 'wp-plugin-security'); ?></h4>
        <p><?php _e('Google Indexing API, social login, reCAPTCHA, SMTP, thanh thông báo, cô lập người dùng, công cụ WooCommerce.', 'wp-plugin-security'); ?></p>
                        </div>
                    </div>

                    <div class="wps-card" style="margin-top: 18px;">
        <h4><?php _e('Thao tác nhanh', 'wp-plugin-security'); ?></h4>
        <p><?php _e('Dùng nút kiểm tra cập nhật nếu bạn muốn so sánh phiên bản hiện tại với bản GitHub mới nhất.', 'wp-plugin-security'); ?></p>
                        <p>
        <a href="#" class="button button-primary wps-check-update-btn" data-nonce="<?php echo esc_attr(wp_create_nonce('wps_check_update_nonce')); ?>"><?php _e('Kiểm tra cập nhật', 'wp-plugin-security'); ?></a>
                        </p>
                    </div>

                <?php elseif ($current_tab === 'tools') : ?>
        <h2><?php _e('Công cụ bảo mật khẩn cấp', 'wp-plugin-security'); ?></h2>
                    <div class="card" style="border-left: 4px solid #d63638;">
        <h3><?php _e('Ngắt toàn bộ phiên làm việc', 'wp-plugin-security'); ?></h3>
        <p><?php _e('Đăng xuất tất cả session trên website, bao gồm cả tài khoản hiện tại.', 'wp-plugin-security'); ?></p>
                        <form method="post" action="" onsubmit="return confirm('Tất cả session đăng nhập sẽ bị hủy. Tiếp tục?');">
                            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
                            <input type="hidden" name="wps_tool_action" value="kill_sessions">
        <button type="submit" class="button button-link-delete" style="color: #d63638;"><?php _e('Kích hoạt Logout All', 'wp-plugin-security'); ?></button>
                        </form>
                    </div>

                    <div class="card" style="border-left: 4px solid #d63638; margin-top: 20px;">
        <h3><?php _e('Đặt lại mật khẩu toàn website', 'wp-plugin-security'); ?></h3>
        <p><?php _e('Đặt mật khẩu ngẫu nhiên mới cho tất cả tài khoản và hủy toàn bộ session hiện tại.', 'wp-plugin-security'); ?></p>
                        <form method="post" action="" onsubmit="return confirm('CẢNH BÁO: Tất cả mật khẩu hiện tại sẽ bị vô hiệu hóa. Tiếp tục?');">
                            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
                            <input type="hidden" name="wps_tool_action" value="force_pw_reset">
        <button type="submit" class="button button-link-delete" style="color: #d63638;"><?php _e('Kích hoạt đặt lại mật khẩu toàn diện', 'wp-plugin-security'); ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
                </div>
            </main>
        </div>
        <?php
    }

    /**
     * Render mot dong checkbox settings.
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
     * Render mot dong input number settings.
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

    /**
     * Dang xuat toan bo session tren website.
     */
    private function destroy_all_sessions()
    {
        if (method_exists('\WP_Session_Tokens', 'destroy_all_for_all_users')) {
            $count = count_users();
            \WP_Session_Tokens::destroy_all_for_all_users();

            return (int) ($count['total_users'] ?? 0);
        }

        $user_ids = get_users([
            'fields' => 'ID',
            'number' => -1,
        ]);

        $destroyed = 0;
        foreach ($user_ids as $user_id) {
            $sessions = \WP_Session_Tokens::get_instance($user_id);
            $sessions->destroy_all();
            $destroyed++;
        }

        return $destroyed;
    }

    /**
     * Dat random password moi cho tat ca tai khoan.
     */
    private function force_password_reset_for_all_users()
    {
        $user_ids = get_users([
            'fields' => 'ID',
            'number' => -1,
        ]);

        foreach ($user_ids as $user_id) {
            wp_set_password(wp_generate_password(32, true, true), $user_id);
            delete_user_meta($user_id, 'wps_last_action');
        }

        $this->destroy_all_sessions();

        return count($user_ids);
    }

    /**
     * Xoa revision cu trong database.
     */
    private function cleanup_revisions()
    {
        $revision_ids = get_posts([
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'fields' => 'ids',
            'numberposts' => -1,
        ]);

        $removed = 0;
        foreach ($revision_ids as $revision_id) {
            if (wp_delete_post((int) $revision_id, true)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Xoa transient het han neu WordPress ho tro.
     */
    private function cleanup_expired_transients()
    {
        if (function_exists('delete_expired_transients')) {
            return (int) delete_expired_transients();
        }

        return 0;
    }

    /**
     * Search & Replace an toan tren posts, postmeta va options.
     */
    private function run_search_replace($search, $replace)
    {
        global $wpdb;

        $result = [
            'content' => 0,
            'options' => 0,
            'meta' => 0,
        ];

        $search = (string) $search;
        $replace = (string) $replace;

        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s OR post_excerpt LIKE %s OR post_title LIKE %s",
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        ));

        foreach ($post_ids as $post_id) {
            $post = get_post((int) $post_id, ARRAY_A);
            if (!$post) {
                continue;
            }

            $updated = false;
            foreach (['post_content', 'post_excerpt', 'post_title'] as $field) {
                if (isset($post[$field]) && strpos($post[$field], $search) !== false) {
                    $post[$field] = str_replace($search, $replace, $post[$field]);
                    $updated = true;
                }
            }

            if ($updated) {
                $wpdb->update(
                    $wpdb->posts,
                    [
                        'post_title' => $post['post_title'],
                        'post_content' => $post['post_content'],
                        'post_excerpt' => $post['post_excerpt'],
                    ],
                    ['ID' => (int) $post_id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
                $result['content']++;
            }
        }

        $option_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE %s",
            '%' . $wpdb->esc_like($search) . '%'
        ), ARRAY_A);

        foreach ($option_rows as $row) {
            $value = maybe_unserialize($row['option_value']);
            $replaced = $this->deep_replace($value, $search, $replace);
            if ($replaced !== $value) {
                update_option($row['option_name'], $replaced);
                $result['options']++;
            }
        }

        $meta_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
            '%' . $wpdb->esc_like($search) . '%'
        ), ARRAY_A);

        foreach ($meta_rows as $row) {
            $value = maybe_unserialize($row['meta_value']);
            $replaced = $this->deep_replace($value, $search, $replace);
            if ($replaced !== $value) {
                $wpdb->update(
                    $wpdb->postmeta,
                    ['meta_value' => maybe_serialize($replaced)],
                    ['meta_id' => (int) $row['meta_id']],
                    ['%s'],
                    ['%d']
                );
                $result['meta']++;
            }
        }

        return $result;
    }

    /**
     * Replace de quy tren array/object/string.
     */
    private function deep_replace($value, $search, $replace)
    {
        if (is_string($value)) {
            return str_replace($search, $replace, $value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->deep_replace($item, $search, $replace);
            }

            return $value;
        }

        if (is_object($value)) {
            foreach ($value as $key => $item) {
                $value->$key = $this->deep_replace($item, $search, $replace);
            }

            return $value;
        }

        return $value;
    }

    /**
     * Lay plugin version tu header file.
     */
    private function get_plugin_version()
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data(WPS_PLUGIN_FILE, false, false);

        return $plugin_data['Version'] ?? '0.0.0';
    }

    /**
     * Danh sách post type public để SEO AI có thể áp dụng.
     */
    private function get_seo_ai_post_types()
    {
        $post_types = get_post_types(['public' => true], 'names');
        $post_types = is_array($post_types) ? array_keys($post_types) : [];
        $excluded = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request'];

        return array_values(array_diff($post_types, $excluded));
    }

    /**
     * Danh sách post type public để chèn mục lục tự động.
     */
    private function get_toc_post_types()
    {
        $post_types = get_post_types(['public' => true], 'names');
        $post_types = is_array($post_types) ? array_keys($post_types) : [];
        $excluded = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request'];

        return array_values(array_diff($post_types, $excluded));
    }

    /**
     * Danh sách post type public để SEO Content có thể áp dụng.
     */
    private function get_seo_content_post_types()
    {
        $post_types = get_post_types(['public' => true], 'names');
        $post_types = is_array($post_types) ? array_keys($post_types) : [];
        $excluded = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request'];

        return array_values(array_diff($post_types, $excluded));
    }
}

// Copyright by AcmaTvirus


