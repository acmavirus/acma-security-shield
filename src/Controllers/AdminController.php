<?php
// Copyright by AcmaTvirus

namespace Acma\WpSecurity\Controllers;

/**
 * Controller xử lý các thiết lập trong trang quản trị
 */
class AdminController
{

    public function __construct()
    {
        // Đăng ký menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Đăng ký settings
        add_action('admin_init', [$this, 'register_settings']);

        // Đăng ký action links trực tiếp trong constructor (vì plugin đã load)
        $plugin_base = plugin_basename(WPS_PLUGIN_FILE);
        add_filter("plugin_action_links_{$plugin_base}", [$this, 'add_plugin_action_links']);
    }

    /**
     * Thêm liên kết Settings và Check Update vào danh sách plugin
     */
    public function add_plugin_action_links($links)
    {
        $settings_url = admin_url('admin.php?page=wp-plugin-security');
        $update_url = wp_nonce_url(admin_url('update-core.php?force-check=1'), 'upgrade-core');

        $custom_links = [
            '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'wp-plugin-security') . '</a>',
            '<a href="' . esc_url($update_url) . '" style="color: #d63638; font-weight: bold;">' . __('Check Update', 'wp-plugin-security') . '</a>'
        ];

        return array_merge($custom_links, (array)$links);
    }

    /**
     * Tạo menu trong admin
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
     * Đăng ký settings
     */
    public function register_settings()
    {
        register_setting('wps_settings_group', 'wps_blocked_ips');
        register_setting('wps_settings_group', 'wps_main_settings');
    }

    /**
     * Render trang cấu hình
     */
    public function render_admin_page()
    {
        $current_tab = $_GET['tab'] ?? 'general';

        // Xử lý các hành động Tools (Post-Hack)
        if (isset($_POST['wps_tool_action']) && current_user_can('manage_options')) {
            check_admin_referer('wps_tool_nonce_action', 'wps_tool_nonce');
            $action = $_POST['wps_tool_action'];

            if ($action === 'kill_sessions') {
                $sessions = \WP_Session_Tokens::get_instance(get_current_user_id());
                $sessions->destroy_all();
                echo '<div class="updated"><p>Tất cả phiên làm việc đã được đăng xuất (bao gồm cả bạn).</p></div>';
            } elseif ($action === 'force_pw_reset') {
                global $wpdb;
                $wpdb->query("UPDATE $wpdb->users SET user_pass = 'RE-SET-ME' WHERE 1=1;");
                echo '<div class="updated"><p>Đã yêu cầu tất cả người dùng đổi mật khẩu (Mật khẩu cũ sẽ bị vô hiệu hóa).</p></div>';
            }
        }

        // Xử lý lưu thiết lập
        if (isset($_POST['wps_save_settings']) && current_user_can('manage_options')) {
            check_admin_referer('wps_settings_action', 'wps_settings_nonce');

            $main_settings = get_option('wps_main_settings', []);

            if ($current_tab === 'general') {
                $main_settings = array_merge($main_settings, [
                    'disable_xmlrpc'          => isset($_POST['disable_xmlrpc']),
                    'disable_rest_api'        => isset($_POST['disable_rest_api']),
                    'block_author_scan'       => isset($_POST['block_author_scan']),
                    'disable_file_editor'     => isset($_POST['disable_file_editor']),
                    'disable_directory_browsing' => isset($_POST['disable_directory_browsing']),
                    'hide_wp_version'         => isset($_POST['hide_wp_version']),
                    'enable_security_headers' => isset($_POST['enable_security_headers']),
                    'enable_audit_log'        => isset($_POST['enable_audit_log']),
                ]);
            } elseif ($current_tab === 'login') {
                $main_settings = array_merge($main_settings, [
                    'limit_login_attempts'    => isset($_POST['limit_login_attempts']),
                    'max_login_attempts'      => (int)($_POST['max_login_attempts'] ?? 5),
                    'lockout_duration'        => (int)($_POST['lockout_duration'] ?? 60),
                    'rename_login_slug'       => sanitize_title($_POST['rename_login_slug'] ?? ''),
                    'idle_logout_time'        => (int)($_POST['idle_logout_time'] ?? 0),
                    'enforce_strong_password' => isset($_POST['enforce_strong_password']),
                    'mask_login_errors'       => isset($_POST['mask_login_errors']),
                ]);
            }

            update_option('wps_main_settings', $main_settings);

            if ($current_tab === 'blacklist') {
                $raw_ips = explode("\n", str_replace("\r", "", $_POST['wps_blocked_ips_raw'] ?? ''));
                $clean_ips = array_unique(array_filter(array_map('trim', $raw_ips)));
                update_option('wps_blocked_ips', $clean_ips);
            }

            echo '<div class="updated"><p>Cấu hình đã được lưu thành công.</p></div>';
        }

        $main_settings = get_option('wps_main_settings', [
            'limit_login_attempts'    => true,
            'max_login_attempts'      => 5,
            'lockout_duration'        => 60,
            'disable_xmlrpc'          => true,
            'disable_rest_api'        => true,
            'block_author_scan'       => true,
            'mask_login_errors'       => true,
            'hide_wp_version'         => true,
            'disable_file_editor'     => true,
            'enable_security_headers' => true,
            'enable_audit_log'        => true,
            'enforce_strong_password' => true,
        ]);

        $blocked_ips = get_option('wps_blocked_ips', []);
        $ips_text = is_array($blocked_ips) ? implode("\n", $blocked_ips) : '';
        $audit_logs = get_option('wps_audit_logs', []);
        $security_logs = get_option('wps_security_logs', []);
?>
        <div class="wrap">
            <h1><?php _e('WP Security Settings', 'wp-plugin-security'); ?> <span class="title-count" style="font-size: 0.5em; background: #eee; padding: 2px 8px; border-radius: 4px; vertical-align: middle;">v<?php echo WPS_PLUGIN_FILE ? '3.0.1' : '1.1.2'; ?></span></h1>
            
            <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
                <a href="?page=wp-plugin-security&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings" style="margin-top: 4px;"></span> <?php _e('Hệ thống & WAF', 'wp-plugin-security'); ?>
                </a>
                <a href="?page=wp-plugin-security&tab=login" class="nav-tab <?php echo $current_tab === 'login' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-lock" style="margin-top: 4px;"></span> <?php _e('Bảo mật Đăng nhập', 'wp-plugin-security'); ?>
                </a>
                <a href="?page=wp-plugin-security&tab=blacklist" class="nav-tab <?php echo $current_tab === 'blacklist' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-no-alt" style="margin-top: 4px;"></span> <?php _e('IP Blacklist', 'wp-plugin-security'); ?>
                </a>
                <a href="?page=wp-plugin-security&tab=audit" class="nav-tab <?php echo $current_tab === 'audit' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-list-view" style="margin-top: 4px;"></span> <?php _e('Audit Trail', 'wp-plugin-security'); ?>
                </a>
                <a href="?page=wp-plugin-security&tab=tools" class="nav-tab <?php echo $current_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-hammer" style="margin-top: 4px;"></span> <?php _e('Công cụ', 'wp-plugin-security'); ?>
                </a>
            </nav>

            <div class="wps-content-area">
                <?php if ($current_tab === 'general') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                        <h2><?php _e('Tường lửa & Hardening', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="disable_xmlrpc"><?php _e('Vô hiệu hóa XML-RPC', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="disable_xmlrpc" name="disable_xmlrpc" value="1" <?php checked($main_settings['disable_xmlrpc'] ?? false); ?>>
                                    <p class="description"><?php _e('Ngăn chặn tấn công brute-force qua cổng XML-RPC.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="disable_rest_api"><?php _e('Hạn chế REST API', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="disable_rest_api" name="disable_rest_api" value="1" <?php checked($main_settings['disable_rest_api'] ?? false); ?>>
                                    <p class="description"><?php _e('Chỉ cho phép người dùng đã đăng nhập truy cập API.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="block_author_scan"><?php _e('Chặn Author Scan', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="block_author_scan" name="block_author_scan" value="1" <?php checked($main_settings['block_author_scan'] ?? false); ?>>
                                    <p class="description"><?php _e('Ngăn bot dò tìm username quản trị viên.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="disable_directory_browsing"><?php _e('Chặn Directory Browsing', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="disable_directory_browsing" name="disable_directory_browsing" value="1" <?php checked($main_settings['disable_directory_browsing'] ?? false); ?>>
                                    <p class="description"><?php _e('Ngăn người lạ duyệt file trong thư mục.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="disable_file_editor"><?php _e('Tắt trình chỉnh sửa file', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="disable_file_editor" name="disable_file_editor" value="1" <?php checked($main_settings['disable_file_editor'] ?? false); ?>>
                                    <p class="description"><?php _e('Vô hiệu hóa chỉnh sửa Code trong Admin.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <h2><?php _e('Quyền riêng tư & Nhật ký', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="hide_wp_version"><?php _e('Ẩn phiên bản WP', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="hide_wp_version" name="hide_wp_version" value="1" <?php checked($main_settings['hide_wp_version'] ?? false); ?>>
                                    <p class="description"><?php _e('Xóa bỏ dấu hiệu nhận biết phiên bản từ mã nguồn.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="enable_security_headers"><?php _e('Security Headers', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="enable_security_headers" name="enable_security_headers" value="1" <?php checked($main_settings['enable_security_headers'] ?? false); ?>>
                                    <p class="description"><?php _e('Kích hoạt HSTS, XSS Protection, nosniff...', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="enable_audit_log"><?php _e('Audit Trail', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="enable_audit_log" name="enable_audit_log" value="1" <?php checked($main_settings['enable_audit_log'] ?? false); ?>>
                                    <p class="description"><?php _e('Lưu lại mọi hoạt động của người dùng.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
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
                                    <code><?php echo home_url('/'); ?></code>
                                    <input type="text" id="rename_login_slug" name="rename_login_slug" value="<?php echo esc_attr($main_settings['rename_login_slug'] ?? ''); ?>" placeholder="vi-du: secret-login" class="regular-text">
                                    <p class="description"><?php _e('Nếu để trống, plugin sẽ dùng `wp-login.php` mặc định.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <h2><?php _e('Brute Force Protection', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="limit_login_attempts"><?php _e('Giới hạn đăng nhập', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="limit_login_attempts" name="limit_login_attempts" value="1" <?php checked($main_settings['limit_login_attempts'] ?? false); ?>>
                                    <p class="description"><?php _e('Khóa IP nếu đăng nhập sai nhiều lần.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mask_login_errors"><?php _e('Ẩn lỗi đăng nhập', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="mask_login_errors" name="mask_login_errors" value="1" <?php checked($main_settings['mask_login_errors'] ?? false); ?>>
                                    <p class="description"><?php _e('Không cho biết username hay password sai.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="max_login_attempts"><?php _e('Thử tối đa', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?php echo esc_attr($main_settings['max_login_attempts'] ?? 5); ?>" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="lockout_duration"><?php _e('Thời gian khóa (phút)', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="number" id="lockout_duration" name="lockout_duration" value="<?php echo esc_attr($main_settings['lockout_duration'] ?? 60); ?>" class="small-text">
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <h2><?php _e('Chính sách người dùng', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="enforce_strong_password"><?php _e('Mật khẩu mạnh', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="enforce_strong_password" name="enforce_strong_password" value="1" <?php checked($main_settings['enforce_strong_password'] ?? false); ?>>
                                    <p class="description"><?php _e('Bắt buộc sử dụng ít nhất 12 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="idle_logout_time"><?php _e('Tự động đăng xuất (phút)', 'wp-plugin-security'); ?></label></th>
                                <td>
                                    <input type="number" id="idle_logout_time" name="idle_logout_time" value="<?php echo esc_attr($main_settings['idle_logout_time'] ?? 0); ?>" class="small-text">
                                    <p class="description"><?php _e('0 để tắt chức năng này.', 'wp-plugin-security'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập Đăng nhập', 'wp-plugin-security')); ?>
                    </form>

                <?php elseif ($current_tab === 'blacklist') : ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                        <div class="card" style="max-width: 100%; margin-top: 0;">
                            <h2><?php _e('Quản lý IP bị chặn', 'wp-plugin-security'); ?></h2>
                            <p class="description"><?php _e('Nhập mỗi địa chỉ IP hoặc dải IP CIDR trên một dòng.', 'wp-plugin-security'); ?></p>
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
                                    $auto_blocked = array_filter($security_logs, fn($l) => in_array($l['type'], ['ip_blocked', 'dangerous_request']));
                                    if (empty($auto_blocked)) : ?>
                                        <tr>
                                            <td colspan="3"><?php _e('Chưa có IP bị chặn tự động.', 'wp-plugin-security'); ?></td>
                                        </tr>
                                    <?php else : foreach (array_slice(array_reverse($auto_blocked), 0, 10) as $log) : ?>
                                            <tr>
                                                <td><?php echo date('H:i d/m/Y', strtotime($log['time'])); ?></td>
                                                <td><code><?php echo esc_html($log['ip']); ?></code></td>
                                                <td><?php echo esc_html($log['message']); ?></td>
                                            </tr>
                                    <?php endforeach;
                                    endif; ?>
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
                            <?php else : foreach (array_reverse($audit_logs) as $log) : ?>
                                    <tr>
                                        <td><small><?php echo date('d/m/Y H:i:s', strtotime($log['time'] ?? 'now')); ?></small></td>
                                        <td><strong><?php echo esc_html($log['user'] ?? 'Guest'); ?></strong></td>
                                        <td>
                                            <?php
                                            $action = strtolower($log['action'] ?? 'info');
                                            $color = '#64748b';
                                            if (strpos($action, 'login') !== false) $color = '#10b981';
                                            if (strpos($action, 'failed') !== false || strpos($action, 'blocked') !== false) $color = '#ef4444';
                                            ?>
                                            <span style="background: <?php echo $color; ?>; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                                                <?php echo esc_html($log['action'] ?? 'INFO'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($log['message'] ?? ''); ?></td>
                                        <td><code><?php echo esc_html($log['ip'] ?? '0.0.0.0'); ?></code></td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($current_tab === 'tools') : ?>
                    <h2><?php _e('Công cụ bảo mật khẩn cấp', 'wp-plugin-security'); ?></h2>
                    <div class="card" style="border-left: 4px solid #d63638;">
                        <h3><?php _e('Ngắt toàn bộ phiên làm việc', 'wp-plugin-security'); ?></h3>
                        <p><?php _e('Đăng xuất tất cả người dùng ngay lập tức (bao gồm cả bạn). Sử dụng nếu nghi ngờ có người lạ xâm nhập trái phép.', 'wp-plugin-security'); ?></p>
                        <form method="post" action="" onsubmit="return confirm('Bạn sẽ bị đăng xuất ngay lập tức. Tiếp tục?');">
                            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
                            <input type="hidden" name="wps_tool_action" value="kill_sessions">
                            <button type="submit" class="button button-link-delete" style="color: #d63638;"><?php _e('Kích hoạt Logout All', 'wp-plugin-security'); ?></button>
                        </form>
                    </div>

                    <div class="card" style="border-left: 4px solid #d63638; margin-top: 20px;">
                        <h3><?php _e('Reset mật khẩu toàn website', 'wp-plugin-security'); ?></h3>
                        <p><?php _e('Vô hiệu hóa toàn bộ mật khẩu hiện tại. Tất cả người dùng (bao gồm admin) sẽ phải dùng chức năng "Quên mật khẩu" để đặt lại mật khẩu mới.', 'wp-plugin-security'); ?></p>
                        <form method="post" action="" onsubmit="return confirm('CẢNH BÁO NGUY HIỂM: Toàn bộ mật khẩu sẽ bị vô hiệu hóa. Tiếp tục?');">
                            <?php wp_nonce_field('wps_tool_nonce_action', 'wps_tool_nonce'); ?>
                            <input type="hidden" name="wps_tool_action" value="force_pw_reset">
                            <button type="submit" class="button button-link-delete" style="color: #d63638;"><?php _e('Kích hoạt Reset Pass Toàn diện', 'wp-plugin-security'); ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
}
// Copyright by AcmaTvirus
