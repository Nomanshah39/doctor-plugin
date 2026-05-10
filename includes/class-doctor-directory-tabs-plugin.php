<?php
/**
 * Main plugin bootstrap.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin services.
 */
class Doctor_Directory_Tabs_Plugin {
	/** @var Doctor_Directory_Tabs_Plugin|null */
	private static $instance = null;

	/** @var Doctor_Directory_Tabs_Cache_Manager */
	private $cache;

	/**
	 * Singleton accessor.
	 *
	 * @return Doctor_Directory_Tabs_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation tasks.
	 */
	public static function activate() {
		Doctor_Directory_Tabs_Post_Type::register();
		Doctor_Directory_Tabs_Taxonomies::register();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation tasks.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Bootstrap services.
	 */
	private function __construct() {
		$this->cache = new Doctor_Directory_Tabs_Cache_Manager();

		new Doctor_Directory_Tabs_Post_Type( $this->cache );
		new Doctor_Directory_Tabs_Taxonomies( $this->cache );
		new Doctor_Directory_Tabs_Meta_Boxes( $this->cache );
		new Doctor_Directory_Tabs_Admin_Columns();
		new Doctor_Directory_Tabs_Assets();
		new Doctor_Directory_Tabs_Frontend( $this->cache );
		new Doctor_Directory_Tabs_Ajax( $this->cache );
		new Doctor_Directory_Tabs_CSV_Importer( $this->cache );

		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'doctor-directory-tabs', false, dirname( DDT_PLUGIN_BASENAME ) . '/languages' );
	}
}
