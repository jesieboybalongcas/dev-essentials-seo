<?php
function dev_essential_canonical_url() {
    if (isset($_POST['canonical_submit'])) {
        $url = esc_url_raw($_POST['page_url']);
        $canonical = trim($_POST['canonical_url']);

        [$object_type, $object_id] = dev_essential_find_object_by_url($url);

        if ($object_id) {
            dev_essential_update_canonical_url($object_type, $object_id, $canonical);
            echo '<div class="notice notice-success"><p>Canonical URL updated for: ' . esc_url($url) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Object not found for: ' . esc_url($url) . '</p></div>';
        }
    }

    if (isset($_POST['canonical_csv_upload']) && !empty($_FILES['csv_file']['tmp_name'])) {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $rows = 0; $success = 0;
        if ($handle !== false) {
            fgetcsv($handle); // Skip header
            while (($data = fgetcsv($handle)) !== false) {
                $rows++;
                $url = esc_url_raw(trim($data[0] ?? ''));
                $canonical = trim($data[1] ?? '');
                if ($url) {
                    [$object_type, $object_id] = dev_essential_find_object_by_url($url);
                    if ($object_id) {
                        dev_essential_update_canonical_url($object_type, $object_id, $canonical);
                        $success++;
                    }
                }
            }
            fclose($handle);
            echo "<div class='notice notice-success'><p>CSV processed: $success of $rows rows updated.</p></div>";
        }
    }
    ?>
    <div class="wrap">
        <h1>Canonical URL Manager</h1>
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
        </style>

        <div class="dev-section">
            <h2>🔎 Single Canonical URL Update</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Page URL</th>
                        <td><input type="url" name="page_url" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th>Canonical URL</th>
                        <td><input type="url" name="canonical_url" class="regular-text" placeholder="Leave empty to keep current"></td>
                    </tr>
                </table>
                <?php submit_button('Update Canonical URL', 'primary', 'canonical_submit'); ?>
            </form>
        </div>

        <div class="dev-section">
            <h2>📥 Bulk CSV Upload</h2>

            <p><strong>CSV Format:</strong> Page URL, Canonical URL  |  
			<a class="button" href="<?php echo plugin_dir_url(__FILE__) . '../canonical-bulk-template.csv'; ?>">Download CSV Template Here</a>
			</p>
            <p><i><strong>Note:</strong> Works for WooCommerce Products, Product Categories, CPTs, Posts, Pages, and all taxonomies.  
            Leave blank to keep existing canonical unchanged.</i></p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required>
                <?php submit_button('Upload and Bulk Update', 'primary', 'canonical_csv_upload'); ?>
            </form>
        </div>
    </div>
<?php }

/**
 * Updates canonical URLs for posts (post types) or terms (taxonomies)
 * Supports all major SEO plugins
 */
function dev_essential_update_canonical_url($type, $object_id, $canonical) {
    if (empty($canonical)) {
        return; // Skip update if empty
    }

    if ($type === 'post') {
        update_post_meta($object_id, '_yoast_wpseo_canonical', esc_url_raw($canonical));
        update_post_meta($object_id, '_aioseo_canonical_url', esc_url_raw($canonical));
        update_post_meta($object_id, 'rank_math_canonical_url', esc_url_raw($canonical));
        update_post_meta($object_id, '_seopress_robots_canonical', esc_url_raw($canonical));
        update_post_meta($object_id, '_sq_canonical', esc_url_raw($canonical));
    }

    if ($type === 'term') {
        update_term_meta($object_id, 'wpseo_canonical', esc_url_raw($canonical));
        update_term_meta($object_id, '_aioseo_canonical_url', esc_url_raw($canonical));
        update_term_meta($object_id, 'rank_math_canonical_url', esc_url_raw($canonical));
        update_term_meta($object_id, '_seopress_robots_canonical', esc_url_raw($canonical));
        update_term_meta($object_id, '_sq_canonical', esc_url_raw($canonical));
    }
}