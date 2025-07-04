<?php
/**
 * Indexed Pages tab – lookup + bulk CSV index/no-index.
 */
function dev_essential_indexed_pages() {

	$url = '';
	$post_id = 0;
	$status_msg = '';

	/* ──────────────────────────────
	 * 1. Handle single URL lookup
	 * ────────────────────────────── */
	if ( isset( $_POST['dev_lookup'] ) ) {
		$url     = esc_url_raw( $_POST['site_url'] );
		$post_id = url_to_postid( $url );

		if ( ! $post_id ) {
			$status_msg = '<div class="notice notice-error"><p>No WordPress page found for that URL.</p></div>';
		}
	}

	/* ──────────────────────────────
	 * 2. Handle single toggle
	 * ────────────────────────────── */
	if ( isset( $_POST['dev_toggle_index'] ) && check_admin_referer( 'dev_toggle_index_nonce' ) ) {
		$post_id = intval( $_POST['post_id'] );
		$action  = sanitize_text_field( $_POST['index_action'] ); // index / noindex

		if ( $action === 'index' ) {
			delete_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex' );
			$status_msg = '<div class="notice notice-success"><p>Page set to <strong>Index</strong>.</p></div>';
		} else {
			update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '1' );
			$status_msg = '<div class="notice notice-success"><p>Page set to <strong>No-index</strong>.</p></div>';
		}
	}

	/* ──────────────────────────────
	 * 3. Handle CSV bulk upload
	 * ────────────────────────────── */
	if ( isset( $_POST['dev_csv_upload'] ) && ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
		$rows = 0; $success = 0; $errors = 0;

		if ( ( $handle = fopen( $_FILES['csv_file']['tmp_name'], 'r' ) ) !== false ) {
			fgetcsv( $handle ); // skip header
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$rows++;
				$csv_url   = esc_url_raw( trim( $data[0] ?? '' ) );
				$csv_action = strtolower( trim( $data[1] ?? '' ) ); // index / noindex

				if ( ! $csv_url || ! in_array( $csv_action, [ 'index', 'noindex' ], true ) ) { $errors++; continue; }

				$pid = url_to_postid( $csv_url );
				if ( ! $pid ) { $errors++; continue; }

				if ( $csv_action === 'index' ) {
					delete_post_meta( $pid, '_yoast_wpseo_meta-robots-noindex' );
				} else {
					update_post_meta( $pid, '_yoast_wpseo_meta-robots-noindex', '1' );
				}
				$success++;
			}
			fclose( $handle );
		}

		$status_msg = sprintf(
			'<div class="notice notice-info"><p>CSV processed: %d rows — %d updated, %d skipped/invalid.</p></div>',
			$rows, $success, $errors
		);
	}

	/* ──────────────────────────────
	 * 4. Determine current state (for single lookup UI)
	 * ────────────────────────────── */
	$current_flag  = $post_id ? get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) : '';
	$current_state = $post_id ? ( $current_flag === '1' ? 'No-index' : 'Index' ) : '';
	?>

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

	    .dev-section .dev-divider {
	        border-top: 1px dashed #ccc;
	        margin: 30px 0;
	    }

	    .dev-section .button + .button {
	        margin-left: 8px;
	    }
	</style>

	<div class="wrap">
    <h1>Indexed Pages</h1>
    <?php echo $status_msg; ?>

    <div class="dev-section">
        <h2>🔎 Search by URL</h2>
        <form method="post" style="display:flex;gap:10px;align-items:center;max-width:600px;margin-bottom:10px;">
            <label for="site_url" style="margin:0;font-weight:600;">Site URL:</label>
            <input type="url" name="site_url" id="site_url" class="regular-text" style="flex:1" value="<?php echo esc_attr( $url ); ?>" required>
            <?php submit_button( 'Search', 'primary', 'dev_lookup', false ); ?>
        </form>
    </div>

    <?php if ( $post_id ) : ?>
        <div class="dev-section">
            <h2>📄 Search Result</h2>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Index Status</th>
                    <th>Action</th>
                </tr>
                <tr>
                    <td style="text-align: center;"><?php echo esc_html( get_the_title( $post_id ) ); ?></td>
                    <td style="text-align: center;"><?php echo esc_html( $current_state ); ?></td>
                    <td style="text-align: center;">
                        <form method="post" style="margin:0;">
                            <?php wp_nonce_field( 'dev_toggle_index_nonce' ); ?>
                            <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                            <input type="hidden" name="index_action" value="<?php echo $current_flag === '1' ? 'index' : 'noindex'; ?>">
                            <?php
                            if ( $current_flag === '1' ) {
                                submit_button( 'Set to Index', 'medium primary', 'dev_toggle_index', false );
                            } else {
                                submit_button( 'Set to No-index', 'medium primary', 'dev_toggle_index', false );
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
            <?php wp_nonce_field( 'dev_csv_upload_nonce', 'dev_csv_upload_nonce_field' ); ?>
            <?php submit_button( 'Upload and Bulk Update', 'medium primary', 'dev_csv_upload' ); ?>
        </form>
    </div>
</div>
<?php }
