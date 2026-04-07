                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Người dùng', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
        <h4><?php _e('Cô lập', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('user_isolation_enabled', __('Bật cô lập', 'wp-plugin-security'), $main_settings, __('Chặn user thường xem bài/media của người khác trong admin.', 'wp-plugin-security')); ?>
                                </table>
                            </div>
                            <div class="wps-card">
        <h4><?php _e('Ảnh đại diện cục bộ', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('local_avatar_enabled', __('Bật ảnh đại diện cục bộ', 'wp-plugin-security'), $main_settings, __('Cho phép lưu avatar trong media của site.', 'wp-plugin-security')); ?>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập Người dùng', 'wp-plugin-security')); ?>
                    </form>

                