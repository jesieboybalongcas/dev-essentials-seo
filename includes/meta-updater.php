<?php
function dev_essential_meta_updater() { ?>
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
		<h1>Meta Updater</h1>

		<?php if ( ! defined( 'WPSEO_VERSION' ) ) : ?>
			<div class="notice notice-warning"><p><strong>Yoast SEO plugin not detected.</strong></p></div>
		<?php endif; ?>

		<?php
		/*--------------------------------------------------------------
		# Single-page update
		--------------------------------------------------------------*/
		if ( isset( $_POST['meta_updater_submit'] ) ) {
			$url        = esc_url_raw( $_POST['page_url'] );
			$meta_title = sanitize_text_field( $_POST['meta_title'] );
			$meta_desc  = sanitize_textarea_field( $_POST['meta_description'] );

			// Robust lookup (handles nested URLs, CPTs, trailing slashes, etc.)
			$post_id = url_to_postid( $url );

			if ( $post_id && defined( 'WPSEO_VERSION' ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_title',   $meta_title );
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
				echo '<div class="notice notice-success"><p>SEO meta updated for: ' . esc_url( $url ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>Could not update meta for: ' . esc_url( $url ) . '</p></div>';
			}
		}

		/*--------------------------------------------------------------
		# Bulk CSV upload
		--------------------------------------------------------------*/
		if ( isset( $_POST['meta_csv_upload'] ) && ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
			$handle   = fopen( $_FILES['csv_file']['tmp_name'], 'r' );
			$row      = 0;
			$success  = 0;

			if ( $handle ) {
				fgetcsv( $handle ); // Skip header row
				while ( ( $data = fgetcsv( $handle ) ) !== false ) {
					$row++;
					$url        = esc_url_raw( trim( $data[0] ?? '' ) );
					$meta_title = sanitize_text_field( trim( $data[1] ?? '' ) );
					$meta_desc  = sanitize_textarea_field( trim( $data[2] ?? '' ) );

					if ( $url && $meta_title && $meta_desc ) {
						$post_id = url_to_postid( $url );
						if ( $post_id && defined( 'WPSEO_VERSION' ) ) {
							update_post_meta( $post_id, '_yoast_wpseo_title',   $meta_title );
							update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
							$success++;
						}
					}
				}
				fclose( $handle );
				echo '<div class="notice notice-success"><p>CSV upload complete: ' . $success . ' of ' . $row . ' rows updated.</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>Unable to open CSV file.</p></div>';
			}
		}
		?>

		<div class="dev-section">
			<h2>🔎 Single Meta Update</h2>
			<form method="post">
				<table class="form-table">
					<tr>
						<th><label for="page_url">Page URL</label></th>
						<td><input name="page_url" id="page_url" type="url" class="regular-text" style="width: 100%;" required></td>
					</tr>
					<tr>
						<th><label for="meta_title">Meta Title</label></th>
						<td><input name="meta_title" id="meta_title" type="text" class="regular-text" style="width: 100%;" required></td>
					</tr>
					<tr>
						<th><label for="meta_description">Meta Description</label></th>
						<td><textarea name="meta_description" id="meta_description" class="large-text" rows="3" required></textarea></td>
					</tr>
				</table>
				<?php submit_button( 'Update Meta Data', 'primary', 'meta_updater_submit' ); ?>
			</form>
		</div>

		<hr>
		<div class="dev-section">
			<h2>📥 Bulk CSV Upload</h2>
			<p><strong>CSV Format:</strong> Site URL, Meta Title, Meta Description  |  
			   <a href="<?php echo plugin_dir_url( __FILE__ ) . '../meta-bulk-update-template.csv'; ?>">
				   Download CSV Template Here
			   </a>
			</p>
			<form method="post" enctype="multipart/form-data">
				<input type="file" name="csv_file" accept=".csv" required>
				<?php submit_button( 'Upload and Bulk Update', 'primary', 'meta_csv_upload' ); ?>
			</form>
		</div>
	</div>
<?php } ?>
