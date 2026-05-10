<?php
/**
 * AJAX filtering.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Handles public AJAX requests. */
class Doctor_Directory_Tabs_Ajax {
	/** @var Doctor_Directory_Tabs_Frontend */
	private $frontend;

	/** Constructor. */
	public function __construct( $cache ) {
		$this->frontend = new Doctor_Directory_Tabs_Frontend( $cache );
		remove_shortcode( Doctor_Directory_Tabs_Frontend::SHORTCODE );
		add_shortcode( Doctor_Directory_Tabs_Frontend::SHORTCODE, array( $this->frontend, 'shortcode' ) );
		add_action( 'wp_ajax_ddt_filter_doctors', array( $this, 'filter' ) );
		add_action( 'wp_ajax_nopriv_ddt_filter_doctors', array( $this, 'filter' ) );
	}

	/** Filter doctors by specialty/group. */
	public function filter() {
		if ( ! check_ajax_referer( 'ddt_filter_doctors', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'doctor-directory-tabs' ) ), 403 );
		}
		$atts = Doctor_Directory_Tabs_Frontend::sanitize_atts(
			array(
				'group'      => isset( $_POST['group'] ) ? wp_unslash( $_POST['group'] ) : '',
				'columns'    => isset( $_POST['columns'] ) ? wp_unslash( $_POST['columns'] ) : 3,
				'image_size' => isset( $_POST['image_size'] ) ? wp_unslash( $_POST['image_size'] ) : 'medium',
				'show_all'   => isset( $_POST['show_all'] ) ? wp_unslash( $_POST['show_all'] ) : 'true',
			)
		);
		$specialty = isset( $_POST['specialty'] ) ? sanitize_title( wp_unslash( $_POST['specialty'] ) ) : '';
		$doctors   = $this->frontend->get_doctors( $atts, $specialty );
		$html      = $this->frontend->render_doctors( $doctors, $atts );
		wp_send_json_success( array( 'html' => $html ) );
	}
}
