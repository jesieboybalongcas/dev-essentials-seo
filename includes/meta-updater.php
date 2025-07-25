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
            fgetcsv($handle); // skip header
            while (($data = fgetcsv($handle)) !== false) {
                $rows++;
                $url = esc_url_raw(trim($data[0] ?? ''));
                $title = trim($data[1] ?? '');
                $desc = trim($data[2] ?? '');
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
            <h2>Single Meta Update</h2>
            <form method="post">
                <table class="form-table">
                    <tr><th>Page / Post / CPT / Taxonomy URL</th>
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
            <h2>Bulk CSV Upload</h2>
            <p><strong>Note:</strong> Works for Posts, Pages, CPTs, WooCommerce Products, Product Categories, Tags, and other taxonomies.  
            Leave Title or Description blank to keep existing values unchanged.</p>
            <p><a class="button" href="<?php echo plugin_dir_url(__FILE__) . '../meta-bulk-update-template.csv'; ?>">Download Template</a></p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required>
                <?php submit_button('Upload and Bulk Update', 'secondary', 'meta_csv_upload'); ?>
            </form>
        </div>
    </div>
<?php }

/**
 * Detect whether URL belongs to a post type (post, page, CPT, product) or a taxonomy term.
 */
function dev_essential_find_object_by_url($url) {
    // Try as post/page/product/CPT first
    $post_id = url_to_postid($url);
    if ($post_id) {
        return ['post', $post_id];
    }

    // Try taxonomy term (nested URLs supported)
    $parsed = wp_parse_url($url);
    if (!empty($parsed['path'])) {
        $segments = array_filter(explode('/', untrailingslashit($parsed['path'])));
        $slug = sanitize_title(end($segments));

        $taxonomies = get_taxonomies(['public' => true], 'names');
        foreach ($taxonomies as $taxonomy) {
            $term = get_term_by('slug', $slug, $taxonomy);
            if ($term && !is_wp_error($term)) {
                return ['term', $term->term_id];
            }
        }
    }
    return [null, 0];
}

/**
 * Update meta fields for posts (all post types) or taxonomy terms.
 * Supports Yoast, AIOSEO, Rank Math, SEOPress, Squirrly.
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
            update_term_meta($object_id, 'wpseo_title', $meta_title);
            update_term_meta($object_id, '_aioseo_title', $meta_title);
            update_term_meta($object_id, 'rank_math_title', $meta_title);
            update_term_meta($object_id, '_seopress_titles_title', $meta_title);
            update_term_meta($object_id, '_sq_title', $meta_title);
        }
        if (!empty($meta_desc)) {
            update_term_meta($object_id, 'wpseo_desc', $meta_desc);
            update_term_meta($object_id, '_aioseo_description', $meta_desc);
            update_term_meta($object_id, 'rank_math_description', $meta_desc);
            update_term_meta($object_id, '_seopress_titles_desc', $meta_desc);
            update_term_meta($object_id, '_sq_description', $meta_desc);
        }
    }
}