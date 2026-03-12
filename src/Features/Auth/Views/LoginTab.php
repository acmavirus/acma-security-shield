<?php
// Copyright by AcmaTvirus
if (!defined('ABSPATH')) exit;
?>

<form method="post" action="">
    <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
    <input type="hidden" name="wps_save_settings" value="1">

    <div class="wps-card">
        <h2 class="title">Đổi đường dẫn đăng nhập</h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Đường dẫn mới</th>
                    <td>
                        <code><?php echo home_url('/'); ?></code>
                        <input type="text" name="rename_login_slug" value="<?php echo esc_attr($main_settings['rename_login_slug'] ?? ''); ?>" placeholder="secret-login" class="regular-text">
                        <p class="description">Nếu để trống, plugin sẽ dùng <code>wp-login.php</code> mặc định.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="wps-card">
        <h2 class="title">Brute Force Protection</h2>
        <table class="form-table">
            <tbody>
                <?php 
                $login_protection = [
                    'limit_login_attempts' => ['Giới hạn đăng nhập', 'Khóa IP nếu đăng nhập sai nhiều lần.'],
                    'mask_login_errors' => ['Ẩn lỗi đăng nhập', 'Không cho biết chi tiết lỗi (username hay password sai).'],
                ];
                foreach($login_protection as $key => $info):
                ?>
                    <tr>
                        <th scope="row"><?php echo $info[0]; ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $key; ?>" <?php checked($main_settings[$key] ?? false); ?>>
                                <span class="description"><?php echo $info[1]; ?></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th scope="row">Thông số bộ lọc</th>
                    <td>
                        Thử tối đa: <input type="number" name="max_login_attempts" value="<?php echo esc_attr($main_settings['max_login_attempts'] ?? 5); ?>" style="width: 60px;"> lần. 
                        Khóa trong: <input type="number" name="lockout_duration" value="<?php echo esc_attr($main_settings['lockout_duration'] ?? 60); ?>" style="width: 60px;"> phút.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="wps-card">
        <h2 class="title">Chính sách & Xác thực</h2>
        <table class="form-table">
            <tbody>
                <?php 
                $auth_policies = [
                    'enforce_strong_password' => ['Bắt buộc mật khẩu mạnh', 'Sử dụng 12 ký tự + ký tự đặc biệt.'],
                    'enable_2fa' => ['Xác thực 2 lớp (2FA)', 'Gửi mã xác thực qua email khi đăng nhập.'],
                ];
                foreach($auth_policies as $key => $info):
                ?>
                    <tr>
                        <th scope="row"><?php echo $info[0]; ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $key; ?>" <?php checked($main_settings[$key] ?? false); ?>>
                                <span class="description"><?php echo $info[1]; ?></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th scope="row">Google reCAPTCHA v3</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">Google reCAPTCHA v3</legend>
                            <label>Site Key:<br> <input type="text" name="recaptcha_site_key" value="<?php echo esc_attr($main_settings['recaptcha_site_key'] ?? ''); ?>" class="regular-text"></label><br>
                            <label>Secret Key:<br> <input type="password" name="recaptcha_secret_key" value="<?php echo esc_attr($main_settings['recaptcha_secret_key'] ?? ''); ?>" class="regular-text"></label>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="notice notice-warning inline" style="margin-top: 20px;">
        <p><strong>Chú ý:</strong> Sử dụng tính năng <strong>Đổi đường dẫn đăng nhập</strong> là cách hiệu quả nhất để ngăn chặn các cuộc tấn công Brute Force tự động từ Bot.</p>
    </div>

    <?php submit_button('Lưu thiết lập Đăng nhập', 'primary', 'submit', true); ?>
</form>
<?phpn type="submit" class="bg-black text-white px-10 py-4 rounded-2xl font-bold text-sm hover:shadow-2xl transition-all active:scale-95">Lưu thiết lập Đăng nhập</button>
    </div>
</form>
