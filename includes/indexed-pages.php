<?php
function dev_essential_indexed_pages() {
    $url = ''; 
    $post_id = 0; 
    $status_msg = '';

    // Lookup page
    if (isset($_POST['dev_lookup'])) {
        $url = esc_url_raw($_POST['site_url']);
        $post_id = url_to_postid($url);
        if (!$post_id) {
            $status_msg = '<div class="notice notice-error"><p>No WordPress page found for that URL.</p></div>';
        }
    }

    // Toggle index / no-index
    if (isset($_POST['dev_toggle_index']) && check_admin_referer('dev_toggle_index_nonce')) {
        $post_id = intval($_POST['post_id']);
        $action = sanitize_text_field($_POST['index_action']);
        dev_essential_set_index_status($post_id, $action);
        $status_msg = "<div class='notice notice-success'><p>Page set to <strong>$action</strong>.</p></div>";
    }

    $current_state = $post_id ? dev_essential_get_index_status($post_id) : '';
    ?>
    <div class="wrap">
        <h1>Indexed Pages</h1>
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
            .dev-section table {
                border: 1px solid #ccc;
                width: 100%;
                border-collapse: collapse;
                background: #fdfdfd;
            }
            .dev-section table th,
            .dev-section table td {
                border: 1px solid #ddd;
                padding: 10px;
                vertical-align: middle;
            }
        </style>

        <?php echo $status_msg; ?>

        <div class="dev-section">
            <h2>🔎 Search Page Index Status</h2>
            <form method="post" style="display:flex;gap:10px;align-items:center;max-width:600px;">
                <label for="site_url" style="margin:0;font-weight:600;">Site URL:</label>
                <input type="url" name="site_url" id="site_url" value="<?php echo esc_attr($url); ?>" class="regular-text" style="flex:1" required>
                <?php submit_button('Search', 'primary', 'dev_lookup', false); ?>
            </form>
        </div>

        <?php if ($post_id): ?>
            <div class="dev-section">
                <h2>📄 Search Result</h2>
                <table>
                    <tr>
                        <th>Title</th>
                        <th>Index Status</th>
                        <th>Action</th>
                    </tr>
                    <tr>
                        <td><?php echo esc_html(get_the_title($post_id)); ?></td>
                        <td><?php echo esc_html($current_state); ?></td>
                        <td>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('dev_toggle_index_nonce'); ?>
                                <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
                                <input type="hidden" name="index_action" value="<?php echo $current_state === 'No-index' ? 'index' : 'noindex'; ?>">
                                <?php
                                if ($current_state === 'No-index') {
                                    submit_button('Set to Index', 'secondary small', 'dev_toggle_index', false);
                                } else {
                                    submit_button('Set to No-index', 'secondary small', 'dev_toggle_index', false);
                                }
                                ?>
                            </form>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>

        <div class="dev-section">
            <h2>📥 Bulk CSV Upload</h2>
            <p><strong>Format:</strong> <code>Site URL, Action</code> (Action = <code>index</code> or <code>noindex</code>)  |  <a href="<?php echo plugin_dir_url( __FILE__ ) . '../index-pages-template.csv'; ?>">Download CSV Template Here</a></p>
            <form method="post" enctype="multipart/form-data" style="margin-top:10px;max-width:400px;">
                <input type="file" name="csv_file" accept=".csv" required>
                <?php submit_button('Upload CSV', 'secondary', 'dev_csv_upload'); ?>
            </form>
        </div>
    </div>
<?php }

function dev_essential_set_index_status($post_id, $action) {
    $noindex = ($action === 'noindex');
    update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', $noindex ? '1' : '');
    update_post_meta($post_id, '_aioseo_robots_noindex', $noindex ? '1' : '0');
    update_post_meta($post_id, 'rank_math_robots', $noindex ? 'noindex' : 'index');
    update_post_meta($post_id, '_seopress_robots_index', $noindex ? '0' : '1');
    update_post_meta($post_id, '_sq_robots', $noindex ? 'noindex' : 'index');
}

function dev_essential_get_index_status($post_id) {
    if (get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true) === '1') return 'No-index';
    if (get_post_meta($post_id, '_aioseo_robots_noindex', true) === '1') return 'No-index';
    if (get_post_meta($post_id, 'rank_math_robots', true) === 'noindex') return 'No-index';
    if (get_post_meta($post_id, '_seopress_robots_index', true) === '0') return 'No-index';
    if (get_post_meta($post_id, '_sq_robots', true) === 'noindex') return 'No-index';
    return 'Index';
}