<?php
function strip_tracking_params($url) {
    $parsed = wp_parse_url($url);
    $clean = '';

    if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
        $clean .= $parsed['scheme'] . '://' . $parsed['host'];
    }
    if (!empty($parsed['path'])) {
        $clean .= $parsed['path'];
    }
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $query_vars);
        $tracking_keys = [
            '__hstc','__hssc','__hsfp','__hscc',
            'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
            '_gl','_ga','_gcl_au'
        ];
        $filtered_query = array_diff_key($query_vars, array_flip($tracking_keys));
        if (!empty($filtered_query)) {
            $clean .= '?' . http_build_query($filtered_query);
        }
    }
    return esc_url($clean);
}

function dev_essential_internal_links() {
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
        </style>

        <div class="dev-section">
            <h2>Inject or Remove Links</h2>
            <form method="post">
                <?php wp_nonce_field('sili_form_action', 'sili_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="target">Target (Post/Page URL or Term URL/Slug)</label></th>
                        <td><input type="text" name="target" id="target" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="anchor_text">Anchor Text</label></th>
                        <td><input type="text" name="anchor_text" id="anchor_text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="destination_url">Destination URL</label></th>
                        <td>
                            <input type="url" name="destination_url" id="destination_url" class="regular-text">
                            <p class="description">Leave empty to remove existing links for this anchor.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="occurrence_index">Occurrence Number</label></th>
                        <td><input type="number" name="occurrence_index" id="occurrence_index" class="small-text" min="1" value="1" required></td>
                    </tr>
                </table>
                <?php submit_button('Inject/Update Link'); ?>
            </form>
        </div>
    </div>
    <?php

    // Process injection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('sili_form_action', 'sili_nonce')) {
        $input      = trim($_POST['target']);
        $anchor     = sanitize_text_field($_POST['anchor_text']);
        $raw_dest   = esc_url_raw($_POST['destination_url']);
        $occurrence = max(1, intval($_POST['occurrence_index']));
        $clean_dest = strip_tracking_params($raw_dest);
        $is_internal = strpos($clean_dest, home_url()) === 0;

        $linked = !empty($clean_dest)
            ? '<a href="' . esc_url($clean_dest) . '"' . (!$is_internal ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . esc_html($anchor) . '</a>'
            : esc_html($anchor);

        $pattern = '/(<a[^>]*>\s*)?(' . preg_quote($anchor, '/') . ')(\s*<\/a>)?/i';

        // Try as post or page
        $post_id = url_to_postid($input);
        if ($post_id && $post = get_post($post_id)) {
            $content = $post->post_content;
            $count = 0;
            $updated = preg_replace_callback($pattern, function ($matches) use ($linked, &$count, $occurrence) {
                $count++;
                return ($count === $occurrence) ? $linked : $matches[0];
            }, $content);

            if ($count >= $occurrence) {
                wp_update_post(['ID' => $post_id, 'post_content' => $updated]);
                echo '<div class="notice notice-success"><p>✅ ' . (!empty($clean_dest) ? 'Link injected' : 'Link removed') . ' in <a href="' . esc_url(get_permalink($post_id)) . '" target="_blank">post</a> at occurrence #' . $occurrence . '.</p></div>';
                return;
            } else {
                echo '<div class="notice notice-warning"><p>⚠️ Anchor not found enough times in the post.</p></div>';
                return;
            }
        }

        // Try as taxonomy term
        $parsed_url = wp_parse_url($input);
        $slug_candidate = !empty($parsed_url['path'])
            ? sanitize_title(end(array_filter(explode('/', $parsed_url['path']))))
            : sanitize_title($input);

        $taxonomies = get_taxonomies(['public' => true], 'names');
        $found = false;

        foreach ($taxonomies as $taxonomy) {
            $term = get_term_by('slug', $slug_candidate, $taxonomy);
            if ($term && !is_wp_error($term)) {
                $desc = term_description($term, $taxonomy);
                $count = 0;

                $updated = preg_replace_callback($pattern, function ($matches) use ($linked, &$count, $occurrence) {
                    $count++;
                    return ($count === $occurrence) ? $linked : $matches[0];
                }, $desc);

                if ($count >= $occurrence) {
                    wp_update_term($term->term_id, $taxonomy, ['description' => $updated]);
                    echo '<div class="notice notice-success"><p>✅ ' . (!empty($clean_dest) ? 'Link injected' : 'Link removed') . ' in taxonomy term "<strong>' . esc_html($term->name) . '</strong>" in <code>' . esc_html($taxonomy) . '</code> at occurrence #' . $occurrence . '.</p></div>';
                    $found = true;
                    break;
                } else {
                    echo '<div class="notice notice-warning"><p>⚠️ Anchor not found enough times in taxonomy description.</p></div>';
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            echo '<div class="notice notice-error"><p>❌ Target not found: must be a valid post/page URL or taxonomy term slug or URL.</p></div>';
        }
    }
}