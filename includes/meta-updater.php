<?php
function dev_essential_meta_updater() {
    if (isset($_POST['meta_updater_submit'])) {
        $url = esc_url_raw($_POST['page_url']);
        $meta_title = sanitize_text_field($_POST['meta_title']);
        $meta_desc = sanitize_textarea_field($_POST['meta_description']);
        $post_id = url_to_postid($url);

        if ($post_id) {
            dev_essential_update_meta_fields($post_id, $meta_title, $meta_desc);
            echo '<div class="notice notice-success"><p>Meta updated for: ' . esc_url($url) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Page not found: ' . esc_url($url) . '</p></div>';
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
                $title = sanitize_text_field(trim($data[1] ?? ''));
                $desc = sanitize_textarea_field(trim($data[2] ?? ''));
                if ($url && $title) {
                    $post_id = url_to_postid($url);
                    if ($post_id) {
                        dev_essential_update_meta_fields($post_id, $title, $desc);
                        $success++;
                    }
                }
            }
            fclose($handle);
            echo "<div class='notice notice-success'><p>CSV processed: $success of $rows updated.</p></div>";
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
            .dev-section .dev-divider {
                border-top: 1px dashed #ccc;
                margin: 30px 0;
            }
        </style>

        <div class="dev-section">
            <h2>Single Meta Update</h2>
            <form method="post">
                <table class="form-table">
                    <tr><th>Page URL</th><td><input type="url" name="page_url" class="regular-text" required></td></tr>
                    <tr><th>Meta Title</th><td><input type="text" name="meta_title" class="regular-text" required></td></tr>
                    <tr><th>Meta Description</th><td><textarea name="meta_description" class="large-text" rows="3"></textarea></td></tr>
                </table>
                <?php submit_button('Update Meta Data', 'primary', 'meta_updater_submit'); ?>
            </form>
        </div>

        <div class="dev-section">
            <h2>Bulk CSV Upload</h2>
            <p><a class="button" href="<?php echo plugin_dir_url(__FILE__) . '../meta-bulk-update-template.csv'; ?>">Download Template</a></p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required>
                <?php submit_button('Upload and Bulk Update', 'secondary', 'meta_csv_upload'); ?>
            </form>
        </div>
    </div>
<?php }

function dev_essential_update_meta_fields($post_id, $meta_title, $meta_desc) {
    update_post_meta($post_id, '_yoast_wpseo_title', $meta_title);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_desc);
    update_post_meta($post_id, '_aioseo_title', $meta_title);
    update_post_meta($post_id, '_aioseo_description', $meta_desc);
    update_post_meta($post_id, 'rank_math_title', $meta_title);
    update_post_meta($post_id, 'rank_math_description', $meta_desc);
    update_post_meta($post_id, '_seopress_titles_title', $meta_title);
    update_post_meta($post_id, '_seopress_titles_desc', $meta_desc);
    update_post_meta($post_id, '_sq_title', $meta_title);
    update_post_meta($post_id, '_sq_description', $meta_desc);
}