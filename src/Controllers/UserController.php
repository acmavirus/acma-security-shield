<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

use Acma\WpSecurity\Services\SecurityService;

/**
 * Controller cho user isolation, avatar local va hien thi User ID.
 */
class UserController
{
    private $security_service;

    public function __construct()
    {
        $this->security_service = new SecurityService();
        add_action('admin_init', [$this, 'restrict_admin_access']);
        add_action('pre_get_posts', [$this, 'limit_posts_to_owner']);
        add_filter('ajax_query_attachments_args', [$this, 'limit_media_to_owner']);

        add_action('show_user_profile', [$this, 'render_avatar_field']);
        add_action('edit_user_profile', [$this, 'render_avatar_field']);
        add_action('personal_options_update', [$this, 'save_avatar_field']);
        add_action('edit_user_profile_update', [$this, 'save_avatar_field']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_profile_media']);

        add_action('show_user_profile', [$this, 'render_two_factor_field']);
        add_action('edit_user_profile', [$this, 'render_two_factor_field']);
        add_action('personal_options_update', [$this, 'save_two_factor_field']);
        add_action('edit_user_profile_update', [$this, 'save_two_factor_field']);

        add_filter('get_avatar', [$this, 'filter_local_avatar'], 20, 6);
        add_filter('manage_users_columns', [$this, 'add_user_id_column']);
        add_filter('manage_users_custom_column', [$this, 'render_user_id_column'], 10, 3);
    }

    public function restrict_admin_access()
    {
        if (!$this->get_setting('user_isolation_enabled', false)) {
            return;
        }

        if (!is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        if (current_user_can('manage_options')) {
            return;
        }

        $allowed_pages = [
            'profile.php',
            'user-edit.php',
            'admin-ajax.php',
            'admin-post.php',
            'async-upload.php',
            'upload.php',
            'media-new.php',
            'edit.php',
            'post.php',
            'post-new.php',
        ];
        $current = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
        if (!in_array($current, $allowed_pages, true)) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    public function limit_posts_to_owner($query)
    {
        if (!$this->get_setting('user_isolation_enabled', false)) {
            return;
        }

        if (!is_admin() || !$query->is_main_query() || current_user_can('manage_options')) {
            return;
        }

        global $pagenow;
        if (($pagenow ?? '') !== 'edit.php') {
            return;
        }

        $query->set('author', get_current_user_id());
    }

    public function limit_media_to_owner($query)
    {
        if (!$this->get_setting('user_isolation_enabled', false)) {
            return $query;
        }

        if (current_user_can('manage_options')) {
            return $query;
        }

        $query['author'] = get_current_user_id();
        return $query;
    }

    public function enqueue_profile_media($hook)
    {
        if (!in_array($hook, ['profile.php', 'user-edit.php'], true)) {
            return;
        }

        wp_enqueue_media();
        wp_add_inline_script('jquery-core', '
            jQuery(function($){
                $(document).on("click", ".wps-avatar-upload", function(e){
                    e.preventDefault();
                    var button = $(this);
            var frame = wp.media({ title: "Chọn ảnh đại diện", button: { text: "Dùng ảnh này" }, multiple: false });
                    frame.on("select", function(){
                        var attachment = frame.state().get("selection").first().toJSON();
                        button.closest("td").find("input.wps-avatar-url").val(attachment.url);
                        button.closest("td").find("img.wps-avatar-preview").attr("src", attachment.url).show();
                    });
                    frame.open();
                });
            });
        ');
    }

    public function render_avatar_field($user)
    {
        if (!$this->get_setting('local_avatar_enabled', false)) {
            return;
        }

        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $avatar_url = get_user_meta($user->ID, 'wps_local_avatar', true);
        ?>
        <h2><?php esc_html_e('Ảnh đại diện cục bộ', 'wp-plugin-security'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="wps_local_avatar"><?php esc_html_e('URL ảnh đại diện', 'wp-plugin-security'); ?></label></th>
                <td>
                    <img class="wps-avatar-preview" src="<?php echo esc_url($avatar_url ?: get_avatar_url($user->ID)); ?>" style="width:72px;height:72px;border-radius:999px;object-fit:cover;display:block;margin-bottom:12px;" alt="">
                    <input type="text" class="regular-text wps-avatar-url" id="wps_local_avatar" name="wps_local_avatar" value="<?php echo esc_attr($avatar_url); ?>">
                    <p><button class="button wps-avatar-upload"><?php esc_html_e('Chọn từ Thư viện', 'wp-plugin-security'); ?></button></p>
                    <p class="description"><?php esc_html_e('Ảnh đại diện cục bộ được lưu trong thư viện media của site, không cần Gravatar.', 'wp-plugin-security'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_avatar_field($user_id)
    {
        if (!$this->get_setting('local_avatar_enabled', false)) {
            return;
        }

        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $avatar_url = esc_url_raw(trim((string) ($_POST['wps_local_avatar'] ?? '')));
        if ($avatar_url !== '') {
            update_user_meta($user_id, 'wps_local_avatar', $avatar_url);
        } else {
            delete_user_meta($user_id, 'wps_local_avatar');
        }
    }

    /**
     * Hiển thị cài đặt 2FA trong hồ sơ user.
     */
    public function render_two_factor_field($user)
    {
        if (!$this->get_setting('enable_two_factor', false)) {
            return;
        }

        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $enabled = (bool) get_user_meta($user->ID, 'wps_2fa_enabled', true);
        $secret = $this->security_service->get_two_factor_secret($user->ID);
        if ($enabled && $secret === '') {
            $secret = $this->security_service->generate_two_factor_secret();
            update_user_meta($user->ID, 'wps_2fa_secret', $secret);
        }

        $issuer = get_bloginfo('name');
        $uri = $secret !== '' ? $this->security_service->get_two_factor_provisioning_uri($issuer, $user->user_login, $secret) : '';
        ?>
        <h2><?php esc_html_e('Xác thực 2 lớp', 'wp-plugin-security'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Bật 2FA', 'wp-plugin-security'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wps_2fa_enabled" value="1" <?php checked($enabled); ?>>
                        <?php esc_html_e('Yêu cầu mã xác thực 2FA khi đăng nhập', 'wp-plugin-security'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Mã 2FA sẽ được kiểm tra trên form đăng nhập bằng ứng dụng như Google Authenticator hoặc Authy.', 'wp-plugin-security'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Secret 2FA', 'wp-plugin-security'); ?></th>
                <td>
                    <input type="text" class="regular-text code" name="wps_2fa_secret" value="<?php echo esc_attr($secret); ?>" autocomplete="off">
                    <p class="description"><?php esc_html_e('Quét thủ công hoặc nhập secret này vào ứng dụng authenticator của bạn.', 'wp-plugin-security'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('URI cài đặt', 'wp-plugin-security'); ?></th>
                <td>
                    <input type="text" class="large-text code" readonly value="<?php echo esc_attr($uri); ?>">
                    <p class="description"><?php esc_html_e('Có thể dùng URI này cho QR code bên ngoài hoặc nhập trực tiếp.', 'wp-plugin-security'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Lưu 2FA của user.
     */
    public function save_two_factor_field($user_id)
    {
        if (!$this->get_setting('enable_two_factor', false)) {
            return;
        }

        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $enabled = isset($_POST['wps_2fa_enabled']);
        $secret = sanitize_text_field($_POST['wps_2fa_secret'] ?? '');

        if ($enabled && $secret === '') {
            $secret = $this->security_service->generate_two_factor_secret();
        }

        if ($enabled) {
            update_user_meta($user_id, 'wps_2fa_enabled', 1);
            update_user_meta($user_id, 'wps_2fa_secret', $secret);
        } else {
            delete_user_meta($user_id, 'wps_2fa_enabled');
            delete_user_meta($user_id, 'wps_2fa_secret');
        }
    }

    public function filter_local_avatar($avatar, $id_or_email, $size, $default, $alt, $args)
    {
        if (!$this->get_setting('local_avatar_enabled', false)) {
            return $avatar;
        }

        $user_id = 0;
        if ($id_or_email instanceof \WP_User) {
            $user_id = (int) $id_or_email->ID;
        } elseif (is_numeric($id_or_email)) {
            $user_id = (int) $id_or_email;
        } elseif ($id_or_email instanceof \WP_Comment) {
            $user = get_user_by('email', $id_or_email->comment_author_email);
            $user_id = $user ? (int) $user->ID : 0;
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            $user_id = $user ? (int) $user->ID : 0;
        }

        if (!$user_id) {
            return $avatar;
        }

        $local_avatar = get_user_meta($user_id, 'wps_local_avatar', true);
        if ($local_avatar === '') {
            return $avatar;
        }

        $size = (int) $size > 0 ? (int) $size : 96;
        $html = sprintf(
            '<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" loading="lazy" decoding="async" />',
            esc_attr($alt ?: get_the_author_meta('display_name', $user_id)),
            esc_url($local_avatar),
            $size,
            $size,
            $size
        );

        return $html;
    }

    public function add_user_id_column($columns)
    {
        $columns['wps_user_id'] = __('Mã người dùng', 'wp-plugin-security');
        return $columns;
    }

    public function render_user_id_column($value, $column_name, $user_id)
    {
        if ($column_name === 'wps_user_id') {
            return (string) absint($user_id);
        }

        return $value;
    }

    private function get_setting($key, $default = false)
    {
        $settings = get_option('wps_main_settings', []);

        return $settings[$key] ?? $default;
    }
}

// Copyright by AcmaTvirus
