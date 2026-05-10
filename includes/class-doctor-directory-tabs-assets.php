<?php
/**
 * Asset loader.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registers and conditionally enqueues assets. */
class Doctor_Directory_Tabs_Assets {
	/** Constructor. */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/** Register frontend assets. */
	public function register_frontend() {
		wp_register_style( 'ddt-frontend', DDT_PLUGIN_URL . 'assets/css/frontend.css', array(), DDT_VERSION );
		wp_register_script( 'ddt-frontend', DDT_PLUGIN_URL . 'assets/js/frontend.js', array(), DDT_VERSION, true );
		wp_localize_script(
			'ddt-frontend',
			'ddtFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ddt_filter_doctors' ),
				'error'   => __( 'Unable to load doctors. Please try again.', 'doctor-directory-tabs' ),
			)
		);
	}

	/** Admin assets. */
	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || Doctor_Directory_Tabs_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style( 'ddt-admin', DDT_PLUGIN_URL . 'assets/css/admin.css', array(), DDT_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'ddt-admin', DDT_PLUGIN_URL . 'assets/js/admin.js', array(), DDT_VERSION, true );
	}
}
