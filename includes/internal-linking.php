<?php
/**
 * Internal Linking Updater – inject internal links into content.
 * Consistent styling with other Dev Essential features.
 */

function dev_essential_internal_links() {
    // Save or update linking keywords
    if ( isset( $_POST['dev_save_internal_links'] ) && check_admin_referer( 'dev_internal_links_action', 'dev_internal_links_nonce' ) ) {
        $links_data = array_map( 'sanitize_text_field', $_POST['internal_links'] ?? [] );
        update_option( 'dev_internal_links_data', $links_data );
        echo '<div class="notice notice-success"><p>Internal links updated successfully.</p></div>';
    }

    // Load current data
    $saved_links = get_option( 'dev_internal_links_data', [] );
    ?>
    <div class="wrap">
        <h1>Internal Linking Updater</h1>

        <style>
            .dev-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 6px;
                padding: 20px;
                margin-top: 20px;
            }
            .dev-card h2 {
                margin-top: 0;
                font-size: 18px;
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
            .dev-description {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            .dev-add-row {
                margin-top: 10px;
            }
        </style>

        <div class="dev-card">
            <h2>Link Keywords and Target URLs</h2>
            <p class="description dev-description">Add keywords and the URLs they should link to. These links will be auto-inserted in post content.</p>

            <form method="post" id="dev_internal_links_form">
                <?php wp_nonce_field( 'dev_internal_links_action', 'dev_internal_links_nonce' ); ?>

                <div id="dev-link-rows">
                    <?php if ( ! empty( $saved_links ) ) : ?>
                        <?php foreach ( $saved_links as $keyword => $url ) : ?>
                            <div class="dev-link-row">
                                <input type="text" name="internal_links[<?php echo esc_attr( $keyword ); ?>]" value="<?php echo esc_attr( $url ); ?>" placeholder="Target URL">
                                <input type="text" disabled value="<?php echo esc_attr( $keyword ); ?>" placeholder="Keyword">
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="dev-link-row">
                            <input type="text" name="internal_links[Example Keyword]" value="https://example.com" placeholder="Target URL">
                            <input type="text" disabled value="Example Keyword" placeholder="Keyword">
                        </div>
                    <?php endif; ?>
                </div>

                <p class="dev-add-row">
                    <button type="button" class="button" id="dev-add-link">+ Add Keyword</button>
                </p>

                <?php submit_button( 'Save Internal Links', 'primary', 'dev_save_internal_links' ); ?>
            </form>
        </div>
    </div>

    <script>
        (function($){
            $('#dev-add-link').on('click', function(){
                let newRow = `
                    <div class="dev-link-row">
                        <input type="text" name="internal_links[New Keyword]" value="" placeholder="Target URL">
                        <input type="text" disabled value="New Keyword" placeholder="Keyword">
                    </div>`;
                $('#dev-link-rows').append(newRow);
            });
        })(jQuery);
    </script>
<?php }

/**
 * Filter content to auto-inject internal links.
 */
add_filter( 'the_content', function( $content ) {
    if ( is_admin() ) return $content;

    $links = get_option( 'dev_internal_links_data', [] );
    if ( empty( $links ) ) return $content;

    foreach ( $links as $keyword => $url ) {
        if ( ! $keyword || ! $url ) continue;
        $pattern = '/(' . preg_quote( $keyword, '/' ) . ')/i';
        $replacement = '<a href="' . esc_url( $url ) . '">$1</a>';
        $content = preg_replace( $pattern, $replacement, $content, 1 ); // link first occurrence only
    }

    return $content;
});