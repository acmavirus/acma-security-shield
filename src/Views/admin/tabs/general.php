                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
        <h2><?php _e('Tường lửa & Tăng cứng', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
        <?php $this->render_checkbox_row('disable_xmlrpc', __('Vô hiệu hóa XML-RPC', 'wp-plugin-security'), $main_settings, __('Ngăn chặn brute-force qua XML-RPC.', 'wp-plugin-security')); ?>
                            <?php $this->render_checkbox_row('disable_rest_api', __('Hạn chế REST API', 'wp-plugin-security'), $main_settings, __('Chỉ cho phép người dùng đã đăng nhập truy cập API.', 'wp-plugin-security')); ?>
        <?php $this->render_checkbox_row('block_author_scan', __('Chặn quét tác giả', 'wp-plugin-security'), $main_settings, __('Ngăn bot dò tìm tên người dùng quản trị viên.', 'wp-plugin-security')); ?>
                            <?php $this->render_checkbox_row('disable_directory_browsing', __('Chặn Directory Browsing', 'wp-plugin-security'), $main_settings, __('Ngăn truy cập liệt kê file trong thư mục.', 'wp-plugin-security')); ?>
        <?php $this->render_checkbox_row('disable_file_editor', __('Tắt trình chỉnh sửa file', 'wp-plugin-security'), $main_settings, __('Vô hiệu hóa chỉnh sửa mã nguồn trong admin.', 'wp-plugin-security')); ?>
                        </table>

                        <hr>

        <h2><?php _e('Quyền riêng tư & Nhật ký', 'wp-plugin-security'); ?></h2>
                        <table class="form-table" role="presentation">
        <?php $this->render_checkbox_row('hide_wp_version', __('Ẩn phiên bản WP', 'wp-plugin-security'), $main_settings, __('Xóa dấu hiệu nhận biết phiên bản từ mã nguồn.', 'wp-plugin-security')); ?>
        <?php $this->render_checkbox_row('enable_security_headers', __('Tiêu đề bảo mật', 'wp-plugin-security'), $main_settings, __('Kích hoạt HSTS, XSS Protection, nosniff...', 'wp-plugin-security')); ?>
        <?php $this->render_checkbox_row('enable_audit_log', __('Nhật ký kiểm tra', 'wp-plugin-security'), $main_settings, __('Lưu lại mọi hoạt động của người dùng.', 'wp-plugin-security')); ?>
                        </table>

                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập Hệ thống', 'wp-plugin-security')); ?>
                    </form>

                