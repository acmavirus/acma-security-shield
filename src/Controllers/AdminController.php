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
        include __DIR__ . '/../Views/admin-page.php';
    }


    /**
     * Render mot dong checkbox settings.
     */
    private function render_checkbox_row($key, $label, array $settings, $description)
    {
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td class="wps-inline-setting">
                <input type="checkbox" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($settings[$key] ?? false); ?>>
                <span class="description"><?php echo esc_html($description); ?></span>
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
            <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <input type="number" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($settings[$key] ?? $default); ?>" class="small-text">
                <?php if ($description !== '') : ?>
                    <p class="description"><?php echo esc_html($description); ?></p>
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
     * Xoa auto-draft cu trong database.
     */
    private function cleanup_auto_drafts()
    {
        $draft_ids = get_posts([
            'post_type' => 'any',
            'post_status' => 'auto-draft',
            'fields' => 'ids',
            'numberposts' => -1,
        ]);

        $removed = 0;
        foreach ($draft_ids as $draft_id) {
            if (wp_delete_post((int) $draft_id, true)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Xoa spam va trash comments.
     */
    private function cleanup_spam_comments()
    {
        global $wpdb;

        $comment_ids = $wpdb->get_col(
            "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved IN ('spam', 'trash')"
        );

        $removed = 0;
        foreach ($comment_ids as $comment_id) {
            if (wp_delete_comment((int) $comment_id, true)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Toi uu cac bang co so du lieu WordPress.
     */
    private function optimize_database_tables()
    {
        global $wpdb;

        $tables = [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->comments,
            $wpdb->commentmeta,
            $wpdb->options,
            $wpdb->terms,
            $wpdb->term_taxonomy,
            $wpdb->term_relationships,
            $wpdb->users,
            $wpdb->usermeta,
        ];

        $optimized = 0;
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
            $optimized++;
        }

        return $optimized;
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
