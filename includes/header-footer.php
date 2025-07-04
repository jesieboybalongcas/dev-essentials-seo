<?php
function dev_essential_header_footer() {

    // Save settings
    if ( isset( $_POST['dev_save_global_scripts'] ) && check_admin_referer( 'dev_global_scripts_action', 'dev_global_scripts_nonce' ) ) {
        update_option( 'dev_global_header_scripts', wp_kses_post( $_POST['dev_global_header_scripts'] ?? '' ) );
        update_option( 'dev_global_footer_scripts', wp_kses_post( $_POST['dev_global_footer_scripts'] ?? '' ) );
        update_option( 'dev_global_header_priority', intval( $_POST['dev_global_header_priority'] ?? 100 ) );
        update_option( 'dev_global_footer_priority', intval( $_POST['dev_global_footer_priority'] ?? 100 ) );
        echo '<div class="notice notice-success"><p>Global scripts & priorities saved.</p></div>';
    }

    $header_script = get_option( 'dev_global_header_scripts', '' );
    $footer_script = get_option( 'dev_global_footer_scripts', '' );
    $header_prio   = get_option( 'dev_global_header_priority', 100 );
    $footer_prio   = get_option( 'dev_global_footer_priority', 100 );
    ?>

    <div class="wrap">
        <h1>Header &amp; Footer Custom Codes</h1>

        <style>
            .dev-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 6px;
                padding: 20px;
                margin-top: 20px;
            }
            .dev-divider {
                border-top: 3px solid #007cba;
                margin: 40px 0 20px;
            }
            .dev-priority-wrapper {
                margin-top: 10px;
            }
            .dev-priority-wrapper label {
                font-weight: 500;
                margin-right: 10px;
            }
            .dev-priority-wrapper input[type="number"] {
                width: 80px;
            }
            .dev-description {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
        </style>

        <form method="post">
            <?php wp_nonce_field( 'dev_global_scripts_action', 'dev_global_scripts_nonce' ); ?>

            <div class="dev-card">
                <h2>Header Custom Scripts</h2>
                <textarea name="dev_global_header_scripts" class="large-text code" rows="8"><?php echo esc_textarea( $header_script ); ?></textarea>
                <div class="dev-description">HTML/JS placed before the closing <code>&lt;/head&gt;</code> tag.</div>
                <div class="dev-priority-wrapper">
                    <label for="dev_global_header_priority">Priority</label>
                    <input type="number" name="dev_global_header_priority" value="<?php echo esc_attr( $header_prio ); ?>">
                    <span class="dev-description">Lower number = runs earlier (default is 100).</span>
                </div>
            </div>

            <div class="dev-divider"></div>

            <div class="dev-card">
                <h2>Footer Custom Scripts</h2>
                <textarea name="dev_global_footer_scripts" class="large-text code" rows="8"><?php echo esc_textarea( $footer_script ); ?></textarea>
                <div class="dev-description">HTML/JS placed before the closing <code>&lt;/body&gt;</code> tag.</div>
                <div class="dev-priority-wrapper">
                    <label for="dev_global_footer_priority">Priority</label>
                    <input type="number" name="dev_global_footer_priority" value="<?php echo esc_attr( $footer_prio ); ?>">
                    <span class="dev-description">Lower number = runs earlier (default is 100).</span>
                </div>
            </div>

            <p style="margin-top: 20px;"><?php submit_button( 'Save Scripts', 'primary', 'dev_save_global_scripts' ); ?></p>
        </form>
    </div>

<?php }

// Front-end script injection with priority
add_action( 'init', function () {
    $head_prio = intval( get_option( 'dev_global_header_priority', 100 ) );
    $foot_prio = intval( get_option( 'dev_global_footer_priority', 100 ) );

    add_action( 'wp_head', function () {
        if ( ! is_admin() ) echo get_option( 'dev_global_header_scripts', '' );
    }, $head_prio );

    add_action( 'wp_footer', function () {
        if ( ! is_admin() ) echo get_option( 'dev_global_footer_scripts', '' );
    }, $foot_prio );
} );