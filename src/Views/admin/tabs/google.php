                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Google', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Indexing API', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('google_indexing_enabled', __('Bật Indexing API', 'wp-plugin-security'), $main_settings, __('Gửi URL lên Google khi bài viết được xuất bản.', 'wp-plugin-security')); ?>
        <tr><th scope="row"><label for="google_indexing_project_id"><?php _e('Mã dự án', 'wp-plugin-security'); ?></label></th><td><input type="text" id="google_indexing_project_id" name="google_indexing_project_id" value="<?php echo esc_attr($main_settings['google_indexing_project_id'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="google_service_account_json"><?php _e('JSON tài khoản dịch vụ', 'wp-plugin-security'); ?></label></th><td><textarea id="google_service_account_json" name="google_service_account_json" rows="7" class="large-text code"><?php echo esc_textarea($main_settings['google_service_account_json'] ?? ''); ?></textarea></td></tr>
        <tr><th scope="row"><?php _e('Loại bài viết', 'wp-plugin-security'); ?></th><td><label><input type="checkbox" name="google_indexing_post_types[]" value="post" <?php checked(in_array('post', (array) ($main_settings['google_indexing_post_types'] ?? ['post']), true)); ?>> <?php _e('Bài viết', 'wp-plugin-security'); ?></label><br><label><input type="checkbox" name="google_indexing_post_types[]" value="page" <?php checked(in_array('page', (array) ($main_settings['google_indexing_post_types'] ?? ['post']), true)); ?>> <?php _e('Trang', 'wp-plugin-security'); ?></label></td></tr>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Đăng nhập Google & reCAPTCHA', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
        <?php $this->render_checkbox_row('google_login_enabled', __('Đăng nhập Google', 'wp-plugin-security'), $main_settings, __('Hiển thị nút đăng nhập bằng Google trên form đăng nhập.', 'wp-plugin-security')); ?>
        <tr><th scope="row"><label for="google_client_id"><?php _e('Client ID', 'wp-plugin-security'); ?></label></th><td><input type="text" id="google_client_id" name="google_client_id" value="<?php echo esc_attr($main_settings['google_client_id'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="google_client_secret"><?php _e('Client Secret', 'wp-plugin-security'); ?></label></th><td><input type="password" id="google_client_secret" name="google_client_secret" value="<?php echo esc_attr($main_settings['google_client_secret'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="google_redirect_uri"><?php _e('URI chuyển hướng', 'wp-plugin-security'); ?></label></th><td><input type="url" id="google_redirect_uri" name="google_redirect_uri" value="<?php echo esc_attr($main_settings['google_redirect_uri'] ?? admin_url('admin-post.php?action=wps_google_callback')); ?>" class="large-text"></td></tr>
                                    <?php $this->render_checkbox_row('recaptcha_enabled', __('reCAPTCHA', 'wp-plugin-security'), $main_settings, __('Bật kiểm tra captcha cho form đăng nhập.', 'wp-plugin-security')); ?>
        <tr><th scope="row"><label for="recaptcha_site_key"><?php _e('Khóa site', 'wp-plugin-security'); ?></label></th><td><input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr($main_settings['recaptcha_site_key'] ?? ''); ?>" class="regular-text"></td></tr>
        <tr><th scope="row"><label for="recaptcha_secret_key"><?php _e('Khóa bí mật', 'wp-plugin-security'); ?></label></th><td><input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr($main_settings['recaptcha_secret_key'] ?? ''); ?>" class="regular-text"></td></tr>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
        <?php submit_button(__('Lưu thiết lập Google', 'wp-plugin-security')); ?>
                    </form>

                