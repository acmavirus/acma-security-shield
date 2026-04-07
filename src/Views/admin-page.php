<?php
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
                    __('Đã đăng xuất %d phiên đăng nhập trên toàn website.', 'wp-plugin-security'),
                    $destroyed_sessions
                );
            } elseif ($action === 'force_pw_reset') {
                $reset_users = $this->force_password_reset_for_all_users();
                $notices[] = sprintf(
                    __('Đã vô hiệu hóa mật khẩu và session hiện tại của %d tài khoản.', 'wp-plugin-security'),
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
                    $notices[] = sprintf(__('Đã xóa %d revision.', 'wp-plugin-security'), $removed);
                } elseif ($maintenance_action === 'cleanup_transients') {
                    $removed = $this->cleanup_expired_transients();
                    $notices[] = sprintf('Đã dọn %d transient hết hạn.', $removed);
                } elseif ($maintenance_action === 'cleanup_autodrafts') {
                    $removed = $this->cleanup_auto_drafts();
                    $notices[] = sprintf(__('Đã xóa %d auto-draft.', 'wp-plugin-security'), $removed);
                } elseif ($maintenance_action === 'cleanup_spam_comments') {
                    $removed = $this->cleanup_spam_comments();
                    $notices[] = sprintf('Đã xóa %d spam comment.', $removed);
                } elseif ($maintenance_action === 'optimize_database_tables') {
                    $optimized = $this->optimize_database_tables();
                    $notices[] = sprintf(__('Đã tối ưu %d bảng cơ sở dữ liệu.', 'wp-plugin-security'), $optimized);
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
                        __('Đã cập nhật %d nội dung và %d tùy chọn.', 'wp-plugin-security'),
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
                    'enable_browser_cache_headers' => isset($_POST['enable_browser_cache_headers']),
                    'defer_noncritical_js' => isset($_POST['defer_noncritical_js']),
                    'enable_preload_hints' => isset($_POST['enable_preload_hints']),
                    'cdn_url' => esc_url_raw($_POST['cdn_url'] ?? ''),
                    'preload_assets' => sanitize_textarea_field(wp_unslash($_POST['preload_assets'] ?? '')),
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

            $notices[] = __('Lưu thiết lập thành công.', 'wp-plugin-security');
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
            'enable_browser_cache_headers' => true,
            'defer_noncritical_js' => false,
            'enable_preload_hints' => false,
            'cdn_url' => '',
            'preload_assets' => '',
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
                                <h2><?php echo esc_html($hero_meta['title'] ?? __('Thiết lập WP Security', 'wp-plugin-security')); ?></h2>
                                <p><?php echo esc_html($hero_meta['description'] ?? __('Giao diện quản trị đồng bộ theo phong cách AI/Agent.', 'wp-plugin-security')); ?></p>
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
                                <?php
                $tab_view = __DIR__ . '/admin/tabs/' . $current_tab . '.php';
                if (is_readable($tab_view)) {
                    include $tab_view;
                } else {
                    include __DIR__ . '/admin/tabs/general.php';
                }
                ?>
            </div>
                </div>
            </main>
        </div>