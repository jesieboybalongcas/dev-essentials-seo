<?php
function dev_essential_internal_links() {
    // Save internal linking data
    if (isset($_POST['dev_save_internal_links']) && check_admin_referer('dev_internal_links_action', 'dev_internal_links_nonce')) {
        $links_data = [];
        if (!empty($_POST['keywords']) && !empty($_POST['urls'])) {
            foreach ($_POST['keywords'] as $i => $keyword) {
                $keyword = sanitize_text_field($keyword);
                $url = esc_url_raw($_POST['urls'][$i] ?? '');
                if (!empty($keyword) && !empty($url)) {
                    $links_data[$keyword] = $url;
                }
            }
        }
        update_option('dev_internal_links_data', $links_data);
        echo '<div class="notice notice-success"><p>Internal linking data updated successfully.</p></div>';
    }

    $saved_links = get_option('dev_internal_links_data', []);
    ?>
    <div class="wrap">
        <h1>Internal Linking Updater</h1>
        <style>
            .dev-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-left: 4px solid #2271b1;
                padding: 20px;
                margin-bottom: 30px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            .dev-section h2 {
                margin-top: 0;
                font-size: 20px;
                font-weight: 600;
                border-bottom: 1px solid #eee;
                padding-bottom: 6px;
                margin-bottom: 15px;
                color: #2271b1;
            }
            .dev-link-row {
                display: flex;
                gap: 10px;
                margin-bottom: 10px;
            }
            .dev-link-row input {
                flex: 1;
            }
            .dev-add-row {
                margin-top: 10px;
            }
        </style>

        <div class="dev-section">
            <h2>Manage Internal Links</h2>
            <form method="post">
                <?php wp_nonce_field('dev_internal_links_action', 'dev_internal_links_nonce'); ?>
                <div id="dev-link-rows">
                    <?php if (!empty($saved_links)): ?>
                        <?php foreach ($saved_links as $keyword => $url): ?>
                            <div class="dev-link-row">
                                <input type="text" name="keywords[]" value="<?php echo esc_attr($keyword); ?>" placeholder="Keyword">
                                <input type="url" name="urls[]" value="<?php echo esc_url($url); ?>" placeholder="Target URL">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dev-link-row">
                            <input type="text" name="keywords[]" placeholder="Keyword">
                            <input type="url" name="urls[]" placeholder="Target URL">
                        </div>
                    <?php endif; ?>
                </div>
                <p class="dev-add-row">
                    <button type="button" class="button" id="dev-add-link">+ Add Row</button>
                </p>
                <?php submit_button('Save Internal Links', 'primary', 'dev_save_internal_links'); ?>
            </form>
        </div>
    </div>

    <script>
        (function($){
            $('#dev-add-link').on('click', function(){
                $('#dev-link-rows').append(
                    '<div class="dev-link-row">'+
                    '<input type="text" name="keywords[]" placeholder="Keyword">'+
                    '<input type="url" name="urls[]" placeholder="Target URL">'+
                    '</div>'
                );
            });
        })(jQuery);
    </script>
<?php }

/**
 * Filter post content to inject internal links
 */
add_filter('the_content', function ($content) {
    if (is_admin()) return $content;
    $links = get_option('dev_internal_links_data', []);
    if (empty($links)) return $content;

    foreach ($links as $keyword => $url) {
        if (!$keyword || !$url) continue;
        $pattern = '/(' . preg_quote($keyword, '/') . ')(?!([^<]+)?>)/i';
        $replacement = '<a href="' . esc_url($url) . '">$1</a>';
        $content = preg_replace($pattern, $replacement, $content, 1); // first occurrence only
    }
    return $content;
});