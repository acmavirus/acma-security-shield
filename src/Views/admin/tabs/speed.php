                    <form method="post" action="">
                        <?php wp_nonce_field('wps_settings_action', 'wps_settings_nonce'); ?>
                        <h2><?php _e('Tốc độ & Tối ưu', 'wp-plugin-security'); ?></h2>
                        <div class="wps-grid two">
                            <div class="wps-card">
                                <h4><?php _e('Cache & Delivery', 'wp-plugin-security'); ?></h4>
                                <table class="form-table wps-form-table" role="presentation">
                                    <?php $this->render_checkbox_row('disable_emojis', __('Tắt Emoji', 'wp-plugin-security'), $main_settings, __('Tắt script và style emoji trên frontend/backend.', 'wp-plugin-security')); ?>
                                    <?php $this->render_checkbox_row('disable_block_library_css', __('Tắt CSS Block', 'wp-plugin-security'), $main_settings, __('Bỏ wp-block-library CSS không cần thiết trên frontend.', 'wp-plugin-security')); ?>
                                    <?php $this->render_checkbox_row('disable_dashicons', __('Tắt Dashicons', 'wp-plugin-security'), $main_settings, __('Vô hiệu hóa Dashicons cho visitor chưa đăng nhập.', 'wp-plugin-security')); ?>
                                    <?php $this->render_checkbox_row('minify_html', __('Rút gọn HTML', 'wp-plugin-security'), $main_settings, __('Gom khoảng trắng thừa trong HTML output.', 'wp-plugin-security')); ?>
                                    <?php $this->render_checkbox_row('enable_browser_cache_headers', __('Browser Cache Headers', 'wp-plugin-security'), $main_settings, __('Thêm Cache-Control và Vary cho tài nguyên frontend.', 'wp-plugin-security')); ?>
                                    <?php $this->render_checkbox_row('defer_noncritical_js', __('Defer JavaScript', 'wp-plugin-security'), $main_settings, __('Trì hoãn script không thiết yếu để tăng tốc hiển thị ban đầu.', 'wp-plugin-security')); ?>
                                    <?php $this->render_checkbox_row('enable_preload_hints', __('Preload Hints', 'wp-plugin-security'), $main_settings, __('In link preload cho font, ảnh banner hoặc CSS/JS quan trọng.', 'wp-plugin-security')); ?>
                                    <tr>
                                        <th scope="row"><label for="cdn_url"><?php _e('CDN URL', 'wp-plugin-security'); ?></label></th>
                                        <td>
                                            <input type="url" id="cdn_url" name="cdn_url" value="<?php echo esc_attr($main_settings['cdn_url'] ?? ''); ?>" class="regular-text" placeholder="https://cdn.example.com">
                                            <p class="description"><?php _e('Nếu có CDN, plugin sẹ rewrite URL của assets và media sang domain CDN này.', 'wp-plugin-security'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="preload_assets"><?php _e('Preload assets', 'wp-plugin-security'); ?></label></th>
                                        <td>
                                            <textarea id="preload_assets" name="preload_assets" rows="4" class="large-text code" placeholder="<?php esc_attr_e("https://example.com/wp-content/uploads/banner.webp\nhttps://example.com/wp-content/themes/theme/fonts/main.woff2", 'wp-plugin-security'); ?>"><?php echo esc_textarea($main_settings['preload_assets'] ?? ''); ?></textarea>
                                            <p class="description"><?php _e('Mỗi dòng hoặc mỗi dấu phẩy là một URL preload. Hỗ trợ ảnh, font, CSS, JS.', 'wp-plugin-security'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="wps-card">
                                <h4><?php _e('Media & Database', 'wp-plugin-security'); ?></h4>
                                <p><?php _e('Lazy loading ảnh/iframe đã là mặc định của WordPress hiện đại. Phần này tập trung vào dọn rác và tối ưu dữ liệu.', 'wp-plugin-security'); ?></p>
                                <p>
                                    <button type="submit" name="wps_maintenance_action" value="cleanup_autodrafts" class="button"><?php _e('Clean Auto Drafts', 'wp-plugin-security'); ?></button>
                                    <button type="submit" name="wps_maintenance_action" value="cleanup_revisions" class="button"><?php _e('Clean Revisions', 'wp-plugin-security'); ?></button>
                                    <button type="submit" name="wps_maintenance_action" value="cleanup_spam_comments" class="button"><?php _e('Clean Spam Comments', 'wp-plugin-security'); ?></button>
                                    <button type="submit" name="wps_maintenance_action" value="cleanup_transients" class="button"><?php _e('Clean Transients', 'wp-plugin-security'); ?></button>
                                    <button type="submit" name="wps_maintenance_action" value="optimize_database_tables" class="button button-primary"><?php _e('Optimize Database Tables', 'wp-plugin-security'); ?></button>
                                </p>
                                <p class="description"><?php _e('Page cache và object cache phụ thuộc hạ tầng như LiteSpeed, Redis hoặc Memcached. Plugin này chỉ cung cấp lớp tối ưu an toàn ở mức ứng dụng.', 'wp-plugin-security'); ?></p>
                            </div>
                        </div>

                        <input type="hidden" name="wps_save_settings" value="1">
                        <?php submit_button(__('Lưu thiết lập Tốc độ', 'wp-plugin-security')); ?>
                    </form>

                