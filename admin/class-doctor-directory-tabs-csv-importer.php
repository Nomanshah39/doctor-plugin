<?php
/**
 * CSV importer.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Secure admin CSV import tool. */
class Doctor_Directory_Tabs_CSV_Importer {
	const NONCE_ACTION = 'ddt_csv_import';
	const LOG_OPTION   = 'ddt_last_import_log';

	/** @var Doctor_Directory_Tabs_Cache_Manager */
	private $cache;

	/** Constructor. */
	public function __construct( $cache ) {
		$this->cache = $cache;
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_ddt_import_csv', array( $this, 'handle_import' ) );
		add_action( 'admin_post_ddt_download_import_log', array( $this, 'download_log' ) );
	}

	/** Add submenu. */
	public function menu() {
		add_submenu_page( 'edit.php?post_type=' . Doctor_Directory_Tabs_Post_Type::POST_TYPE, __( 'CSV Import', 'doctor-directory-tabs' ), __( 'CSV Import', 'doctor-directory-tabs' ), 'manage_options', 'ddt-csv-import', array( $this, 'render_page' ) );
	}

	/** Render page. */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import doctors.', 'doctor-directory-tabs' ) );
		}
		$summary = get_transient( 'ddt_import_summary_' . get_current_user_id() );
		delete_transient( 'ddt_import_summary_' . get_current_user_id() );
		?>
		<div class="wrap ddt-import-page">
			<h1><?php esc_html_e( 'Doctor CSV Import', 'doctor-directory-tabs' ); ?></h1>
			<?php if ( $summary ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $summary ); ?></p></div>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ddt_download_import_log' ), 'ddt_download_import_log' ) ); ?>"><?php esc_html_e( 'Download import log', 'doctor-directory-tabs' ); ?></a></p>
			<?php endif; ?>
			<p><?php esc_html_e( 'Required headers: First Name, Last Name, Speciality or Specialty, Group Name, Profile Link. Optional: Image URL, Designation, Clinic, Department, Display Order.', 'doctor-directory-tabs' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="action" value="ddt_import_csv" />
				<input type="file" name="ddt_csv" accept=".csv,text/csv" required />
				<?php submit_button( __( 'Import CSV', 'doctor-directory-tabs' ) ); ?>
			</form>
		</div>
		<?php
	}

	/** Handle import upload and processing. */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'doctor-directory-tabs' ) );
		}
		check_admin_referer( self::NONCE_ACTION );
		$log = array();
		$stats = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'warnings' => 0, 'errors' => 0 );

		if ( empty( $_FILES['ddt_csv']['name'] ) ) {
			$this->redirect_with_log( $stats, array( array( 'error', __( 'No CSV file uploaded.', 'doctor-directory-tabs' ) ) ) );
		}

		$file = $_FILES['ddt_csv'];
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'csv' => 'text/csv' ) );
		if ( 'csv' !== $check['ext'] ) {
			$stats['errors']++;
			$this->redirect_with_log( $stats, array( array( 'error', __( 'Please upload a valid CSV file.', 'doctor-directory-tabs' ) ) ) );
		}

		$upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => array( 'csv' => 'text/csv' ) ) );
		if ( isset( $upload['error'] ) ) {
			$stats['errors']++;
			$this->redirect_with_log( $stats, array( array( 'error', $upload['error'] ) ) );
		}

		$handle = fopen( $upload['file'], 'r' );
		if ( ! $handle ) {
			$stats['errors']++;
			$this->redirect_with_log( $stats, array( array( 'error', __( 'Unable to read uploaded CSV.', 'doctor-directory-tabs' ) ) ) );
		}

		$headers = fgetcsv( $handle );
		$map     = $this->map_headers( is_array( $headers ) ? $headers : array() );
		if ( ! $this->has_required_headers( $map ) ) {
			fclose( $handle );
			$stats['errors']++;
			$this->redirect_with_log( $stats, array( array( 'error', __( 'Missing required CSV headers.', 'doctor-directory-tabs' ) ) ) );
		}

		$row_number = 1;
		while ( false !== ( $row = fgetcsv( $handle ) ) ) {
			$row_number++;
			if ( $this->row_is_empty( $row ) ) {
				continue;
			}
			$result = $this->process_row( $row, $map, $row_number );
			$stats[ $result['status'] ]++;
			foreach ( $result['log'] as $entry ) {
				if ( 'warning' === $entry[0] ) {
					$stats['warnings']++;
				} elseif ( 'error' === $entry[0] ) {
					$stats['errors']++;
				}
				$log[] = $entry;
			}
		}
		fclose( $handle );
		@unlink( $upload['file'] );
		$this->cache->clear_all_cache();
		$this->redirect_with_log( $stats, $log );
	}

	/** Map flexible headers. */
	private function map_headers( $headers ) {
		$aliases = array(
			'first_name'    => 'first_name',
			'last_name'     => 'last_name',
			'speciality'    => 'specialty',
			'specialty'     => 'specialty',
			'group_name'    => 'group',
			'group'         => 'group',
			'profile_link'  => 'profile_link',
			'image_url'     => 'image_url',
			'designation'   => 'designation',
			'clinic'        => 'clinic',
			'department'    => 'department',
			'display_order' => 'display_order',
		);
		$map = array();
		foreach ( $headers as $index => $header ) {
			$key = Doctor_Directory_Tabs_Helpers::normalize_key( $header );
			if ( isset( $aliases[ $key ] ) ) {
				$map[ $aliases[ $key ] ] = $index;
			}
		}
		return $map;
	}

	/** Required headers. */
	private function has_required_headers( $map ) {
		foreach ( array( 'first_name', 'last_name', 'specialty', 'group', 'profile_link' ) as $key ) {
			if ( ! array_key_exists( $key, $map ) ) {
				return false;
			}
		}
		return true;
	}

	/** Process one CSV row. */
	private function process_row( $row, $map, $row_number ) {
		$log = array();
		$first = sanitize_text_field( $this->value( $row, $map, 'first_name' ) );
		$last  = sanitize_text_field( $this->value( $row, $map, 'last_name' ) );
		if ( '' === $first && '' === $last ) {
			return array( 'status' => 'skipped', 'log' => array( array( 'warning', "Row {$row_number}: missing first and last name." ) ) );
		}
		$profile = esc_url_raw( $this->value( $row, $map, 'profile_link' ) );
		if ( $this->value( $row, $map, 'profile_link' ) && ! $profile ) {
			$log[] = array( 'warning', "Row {$row_number}: invalid profile link skipped." );
		}

		$existing = get_posts( array(
			'post_type'      => Doctor_Directory_Tabs_Post_Type::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 2,
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => Doctor_Directory_Tabs_Helpers::FIRST_NAME, 'value' => $first ),
				array( 'key' => Doctor_Directory_Tabs_Helpers::LAST_NAME, 'value' => $last ),
			),
		) );
		if ( count( $existing ) > 1 ) {
			$log[] = array( 'warning', "Row {$row_number}: duplicate doctor names found; updated first match." );
		}

		$post_data = array( 'post_type' => Doctor_Directory_Tabs_Post_Type::POST_TYPE, 'post_status' => 'publish', 'post_title' => trim( $first . ' ' . $last ) );
		if ( $existing ) {
			$post_data['ID'] = $existing[0]->ID;
			$post_id = wp_update_post( $post_data, true );
			$status = 'updated';
		} else {
			$post_id = wp_insert_post( $post_data, true );
			$status = 'created';
		}
		if ( is_wp_error( $post_id ) ) {
			return array( 'status' => 'skipped', 'log' => array( array( 'error', "Row {$row_number}: " . $post_id->get_error_message() ) ) );
		}

		update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::FIRST_NAME, $first );
		update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::LAST_NAME, $last );
		$profile ? update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::PROFILE_LINK, $profile ) : delete_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::PROFILE_LINK );
		foreach ( array( 'designation' => Doctor_Directory_Tabs_Helpers::DESIGNATION, 'clinic' => Doctor_Directory_Tabs_Helpers::CLINIC, 'department' => Doctor_Directory_Tabs_Helpers::DEPARTMENT ) as $csv => $meta ) {
			$value = sanitize_text_field( $this->value( $row, $map, $csv ) );
			'' !== $value ? update_post_meta( $post_id, $meta, $value ) : delete_post_meta( $post_id, $meta );
		}
		$order = $this->value( $row, $map, 'display_order' );
		'' !== $order ? update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER, (int) $order ) : delete_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER );

		$this->assign_term( $post_id, Doctor_Directory_Tabs_Taxonomies::SPECIALTY, $this->value( $row, $map, 'specialty' ), $row_number, $log );
		$this->assign_term( $post_id, Doctor_Directory_Tabs_Taxonomies::GROUP, $this->value( $row, $map, 'group' ), $row_number, $log );
		$image_url = esc_url_raw( $this->value( $row, $map, 'image_url' ) );
		if ( $image_url ) {
			$image_id = $this->sideload_image_once( $image_url, $post_id, $log, $row_number );
			if ( $image_id ) {
				update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::IMAGE_ID, $image_id );
			}
		}
		$log[] = array( 'info', "Row {$row_number}: {$status} {$first} {$last}." );
		return array( 'status' => $status, 'log' => $log );
	}

	/** Value by mapped key. */
	private function value( $row, $map, $key ) {
		return isset( $map[ $key ], $row[ $map[ $key ] ] ) ? trim( (string) $row[ $map[ $key ] ] ) : '';
	}

	/** Empty row check. */
	private function row_is_empty( $row ) {
		return '' === trim( implode( '', array_map( 'trim', (array) $row ) ) );
	}

	/** Create/assign term. */
	private function assign_term( $post_id, $taxonomy, $name, $row_number, &$log ) {
		$name = sanitize_text_field( $name );
		if ( '' === $name ) {
			return;
		}
		$term = term_exists( $name, $taxonomy );
		if ( ! $term ) {
			$term = wp_insert_term( $name, $taxonomy );
		}
		if ( is_wp_error( $term ) ) {
			$log[] = array( 'error', "Row {$row_number}: unable to create term {$name}." );
			return;
		}
		wp_set_object_terms( $post_id, array( (int) $term['term_id'] ), $taxonomy, false );
	}

	/** Sideload image unless already stored for URL. */
	private function sideload_image_once( $url, $post_id, &$log, $row_number ) {
		$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => Doctor_Directory_Tabs_Helpers::IMAGE_SOURCE, 'meta_value' => $url ) );
		if ( $existing ) {
			return (int) $existing[0];
		}
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$image_id = media_sideload_image( $url, $post_id, null, 'id' );
		if ( is_wp_error( $image_id ) ) {
			$log[] = array( 'warning', "Row {$row_number}: image download failed." );
			return 0;
		}
		update_post_meta( $image_id, Doctor_Directory_Tabs_Helpers::IMAGE_SOURCE, $url );
		return (int) $image_id;
	}

	/** Redirect with summarized log. */
	private function redirect_with_log( $stats, $log ) {
		update_option( self::LOG_OPTION, $log, false );
		$summary = sprintf( __( 'Import complete. Created: %1$d, Updated: %2$d, Skipped: %3$d, Warnings: %4$d, Errors: %5$d.', 'doctor-directory-tabs' ), $stats['created'], $stats['updated'], $stats['skipped'], $stats['warnings'], $stats['errors'] );
		set_transient( 'ddt_import_summary_' . get_current_user_id(), $summary, MINUTE_IN_SECONDS * 5 );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Doctor_Directory_Tabs_Post_Type::POST_TYPE . '&page=ddt-csv-import' ) );
		exit;
	}

	/** Download last import log as CSV. */
	public function download_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'doctor-directory-tabs' ) );
		}
		check_admin_referer( 'ddt_download_import_log' );
		$log = get_option( self::LOG_OPTION, array() );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=doctor-import-log.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Level', 'Message' ) );
		foreach ( (array) $log as $entry ) {
			fputcsv( $out, array( sanitize_text_field( $entry[0] ), sanitize_text_field( $entry[1] ) ) );
		}
		fclose( $out );
		exit;
	}
}
