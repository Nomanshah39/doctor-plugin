<?php
/**
 * Doctor meta boxes.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Handles doctor profile fields. */
class Doctor_Directory_Tabs_Meta_Boxes {
	const NONCE_ACTION = 'ddt_save_doctor_meta';
	const NONCE_NAME   = 'ddt_doctor_meta_nonce';

	/** @var Doctor_Directory_Tabs_Cache_Manager */
	private $cache;

	/** Constructor. */
	public function __construct( $cache ) {
		$this->cache = $cache;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Doctor_Directory_Tabs_Post_Type::POST_TYPE, array( $this, 'save' ), 10, 2 );
	}

	/** Add meta box. */
	public function add_meta_boxes() {
		add_meta_box( 'ddt_doctor_details', __( 'Doctor Details', 'doctor-directory-tabs' ), array( $this, 'render' ), Doctor_Directory_Tabs_Post_Type::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * Render fields.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$image_id = absint( get_post_meta( $post->ID, Doctor_Directory_Tabs_Helpers::IMAGE_ID, true ) );
		$fields   = array(
			Doctor_Directory_Tabs_Helpers::FIRST_NAME    => __( 'First name', 'doctor-directory-tabs' ),
			Doctor_Directory_Tabs_Helpers::LAST_NAME     => __( 'Last name', 'doctor-directory-tabs' ),
			Doctor_Directory_Tabs_Helpers::PROFILE_LINK  => __( 'Profile link', 'doctor-directory-tabs' ),
			Doctor_Directory_Tabs_Helpers::DESIGNATION   => __( 'Designation/title', 'doctor-directory-tabs' ),
			Doctor_Directory_Tabs_Helpers::CLINIC        => __( 'Clinic', 'doctor-directory-tabs' ),
			Doctor_Directory_Tabs_Helpers::DEPARTMENT    => __( 'Department', 'doctor-directory-tabs' ),
			Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER => __( 'Display order', 'doctor-directory-tabs' ),
		);
		?>
		<div class="ddt-admin-fields">
			<div class="ddt-admin-image-field">
				<label><?php esc_html_e( 'Doctor image', 'doctor-directory-tabs' ); ?></label>
				<div class="ddt-image-preview" data-placeholder="<?php esc_attr_e( 'No image selected', 'doctor-directory-tabs' ); ?>">
					<?php echo $image_id ? wp_get_attachment_image( $image_id, 'thumbnail' ) : esc_html__( 'No image selected', 'doctor-directory-tabs' ); ?>
				</div>
				<input type="hidden" id="ddt_image_id" name="ddt_image_id" value="<?php echo esc_attr( $image_id ); ?>" />
				<button type="button" class="button ddt-upload-image"><?php esc_html_e( 'Select image', 'doctor-directory-tabs' ); ?></button>
				<button type="button" class="button ddt-remove-image"><?php esc_html_e( 'Remove image', 'doctor-directory-tabs' ); ?></button>
			</div>
			<?php foreach ( $fields as $key => $label ) : ?>
				<p class="ddt-admin-field">
					<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
					<input type="<?php echo Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER === $key ? 'number' : 'text'; ?>" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $key, true ) ); ?>" class="widefat" />
				</p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/** Save fields. */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || Doctor_Directory_Tabs_Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}

		$image_id = isset( $_POST['ddt_image_id'] ) ? absint( $_POST['ddt_image_id'] ) : 0;
		$image_id ? update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::IMAGE_ID, $image_id ) : delete_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::IMAGE_ID );

		$text_fields = array(
			Doctor_Directory_Tabs_Helpers::FIRST_NAME,
			Doctor_Directory_Tabs_Helpers::LAST_NAME,
			Doctor_Directory_Tabs_Helpers::DESIGNATION,
			Doctor_Directory_Tabs_Helpers::CLINIC,
			Doctor_Directory_Tabs_Helpers::DEPARTMENT,
		);
		foreach ( $text_fields as $field ) {
			$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
			'' !== $value ? update_post_meta( $post_id, $field, $value ) : delete_post_meta( $post_id, $field );
		}

		$url = isset( $_POST[ Doctor_Directory_Tabs_Helpers::PROFILE_LINK ] ) ? esc_url_raw( wp_unslash( $_POST[ Doctor_Directory_Tabs_Helpers::PROFILE_LINK ] ) ) : '';
		$url ? update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::PROFILE_LINK, $url ) : delete_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::PROFILE_LINK );

		$order = isset( $_POST[ Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER ] ) && '' !== $_POST[ Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER ] ? (int) $_POST[ Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER ] : '';
		'' !== $order ? update_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER, $order ) : delete_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER );
		$this->cache->clear_all_cache();
	}
}
