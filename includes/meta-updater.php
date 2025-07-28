<?php
function dev_essential_meta_updater() {
    if (isset($_POST['meta_updater_submit'])) {
        $url = esc_url_raw($_POST['page_url']);
        $meta_title = trim($_POST['meta_title']);
        $meta_desc = trim($_POST['meta_description']);

        [$object_type, $object_id] = dev_essential_find_object_by_url($url);

        if ($object_id) {
            dev_essential_update_meta_fields($object_type, $object_id, $meta_title, $meta_desc);
            echo '<div class="notice notice-success"><p>Meta updated for: ' . esc_url($url) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Object not found for: ' . esc_url($url) . '</p></div>';
        }
    }

    if (isset($_POST['meta_csv_upload']) && !empty($_FILES['csv_file']['tmp_name'])) {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $rows = 0; $success = 0;
        if ($handle !== false) {
            fgetcsv($handle); // Skip header
            while (($data = fgetcsv($handle)) !== false) {
                $rows++;
                $url   = esc_url_raw(trim($data[0] ?? ''));
                $title = trim($data[1] ?? '');
                $desc  = trim($data[2] ?? '');
                if ($url) {
                    [$object_type, $object_id] = dev_essential_find_object_by_url($url);
                    if ($object_id) {
                        dev_essential_update_meta_fields($object_type, $object_id, $title, $desc);
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
        <h1>Meta Updater</h1>
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
            <h2>🔎 Single Meta Update</h2>
            <form method="post">
                <table class="form-table">
                    <tr><th>Page URL</th>
                        <td><input type="url" name="page_url" class="regular-text" required></td></tr>
                    <tr><th>Meta Title</th>
                        <td><input type="text" name="meta_title" class="regular-text" placeholder="Leave empty to keep current"></td></tr>
                    <tr><th>Meta Description</th>
                        <td><textarea name="meta_description" class="large-text" rows="3" placeholder="Leave empty to keep current"></textarea></td></tr>
                </table>
                <?php submit_button('Update Meta Data', 'primary', 'meta_updater_submit'); ?>
            </form>
        </div>

        <div class="dev-section">
            <h2>📥 Bulk CSV Upload</h2>
            <p><strong>CSV Format:</strong> Site URL, Meta Title, Meta Description  |  
			<a href="<?php echo plugin_dir_url( __FILE__ ) . '../meta-bulk-update-template.csv'; ?>">
				Download CSV Template Here
			</a>
			</p>
            <p><i><strong>Note:</strong> Works for WooCommerce Products, Product Categories, and all CPTs. Leave blank to keep existing meta unchanged.</i></p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required>
                <?php submit_button('Upload and Bulk Update', 'primary', 'meta_csv_upload'); ?>
            </form>
        </div>
    </div>
<?php }

/**
 * Updates meta titles & descriptions for posts or taxonomies across all major SEO plugins.
 */
function dev_essential_update_meta_fields($type, $object_id, $meta_title, $meta_desc) {
    if ($type === 'post') {
        if (!empty($meta_title)) {
            update_post_meta($object_id, '_yoast_wpseo_title', $meta_title);
            update_post_meta($object_id, '_aioseo_title', $meta_title);
            update_post_meta($object_id, 'rank_math_title', $meta_title);
            update_post_meta($object_id, '_seopress_titles_title', $meta_title);
            update_post_meta($object_id, '_sq_title', $meta_title);
        }
        if (!empty($meta_desc)) {
            update_post_meta($object_id, '_yoast_wpseo_metadesc', $meta_desc);
            update_post_meta($object_id, '_aioseo_description', $meta_desc);
            update_post_meta($object_id, 'rank_math_description', $meta_desc);
            update_post_meta($object_id, '_seopress_titles_desc', $meta_desc);
            update_post_meta($object_id, '_sq_description', $meta_desc);
        }
    }

    if ($type === 'term') {
        if (!empty($meta_title)) {
            update_term_meta($object_id, 'wpseo_title', $meta_title); // Yoast standard
            update_term_meta($object_id, '_aioseo_title', $meta_title);
            update_term_meta($object_id, 'rank_math_title', $meta_title);
            update_term_meta($object_id, '_seopress_titles_title', $meta_title);
            update_term_meta($object_id, '_sq_title', $meta_title);
        }

        if (!empty($meta_desc)) {
            update_term_meta($object_id, 'wpseo_desc', $meta_desc); // Yoast standard
            update_term_meta($object_id, '_aioseo_description', $meta_desc);
            update_term_meta($object_id, 'rank_math_description', $meta_desc);
            update_term_meta($object_id, '_seopress_titles_desc', $meta_desc);
            update_term_meta($object_id, '_sq_description', $meta_desc);
        }

        // ✅ Yoast-specific taxonomy meta update (important for product_cat)
        if (!empty($meta_title) || !empty($meta_desc)) {
            $taxonomy = get_term($object_id)->taxonomy;
            $option_name = 'wpseo_taxonomy_meta';
            $all_tax_meta = get_option($option_name, []);

            if (!isset($all_tax_meta[$taxonomy])) {
                $all_tax_meta[$taxonomy] = [];
            }

            if (!isset($all_tax_meta[$taxonomy][$object_id])) {
                $all_tax_meta[$taxonomy][$object_id] = [];
            }

            if (!empty($meta_title)) {
                $all_tax_meta[$taxonomy][$object_id]['wpseo_title'] = $meta_title;
            }
            if (!empty($meta_desc)) {
                $all_tax_meta[$taxonomy][$object_id]['wpseo_desc'] = $meta_desc;
            }

            update_option($option_name, $all_tax_meta);
        }

        // ✅ Clear WooCommerce product category cache
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients();
        }

        // ✅ Clear Yoast indexables/cache
        if (function_exists('wpseo_clear_cache')) {
            wpseo_clear_cache();
        }
        if (class_exists('Yoast\WP\SEO\Actions\Indexing\Indexing_Action')) {
            (new Yoast\WP\SEO\Actions\Indexing\Indexing_Action())->index();
        }
    }
}