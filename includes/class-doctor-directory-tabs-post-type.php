<?php
/**
 * Doctor post type.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers doctor CPT and cache hooks.
 */
class Doctor_Directory_Tabs_Post_Type {
	const POST_TYPE = 'doctor';

	/** @var Doctor_Directory_Tabs_Cache_Manager */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @param Doctor_Directory_Tabs_Cache_Manager $cache Cache manager.
	 */
	public function __construct( $cache ) {
		$this->cache = $cache;
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'clear_cache' ), 20 );
		add_action( 'deleted_post', array( $this, 'maybe_clear_cache' ) );
		add_action( 'trashed_post', array( $this, 'maybe_clear_cache' ) );
		add_action( 'untrashed_post', array( $this, 'maybe_clear_cache' ) );
	}

	/**
	 * Register CPT.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'Doctors', 'doctor-directory-tabs' ),
			'singular_name'      => __( 'Doctor', 'doctor-directory-tabs' ),
			'add_new_item'       => __( 'Add New Doctor', 'doctor-directory-tabs' ),
			'edit_item'          => __( 'Edit Doctor', 'doctor-directory-tabs' ),
			'new_item'           => __( 'New Doctor', 'doctor-directory-tabs' ),
			'view_item'          => __( 'View Doctor', 'doctor-directory-tabs' ),
			'search_items'       => __( 'Search Doctors', 'doctor-directory-tabs' ),
			'not_found'          => __( 'No doctors found.', 'doctor-directory-tabs' ),
			'menu_name'          => __( 'Doctors', 'doctor-directory-tabs' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => $labels,
				'public'        => true,
				'show_ui'       => true,
				'show_in_menu'  => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-id',
				'supports'      => array( 'title' ),
				'has_archive'   => false,
				'rewrite'       => array( 'slug' => 'doctor' ),
				'capability_type' => 'post',
			)
		);
	}

	/** Clear cache on save. */
	public function clear_cache() {
		$this->cache->clear_all_cache();
	}

	/**
	 * Clear cache when a doctor is deleted/trashed/restored.
	 *
	 * @param int $post_id Post ID.
	 */
	public function maybe_clear_cache( $post_id ) {
		if ( self::POST_TYPE === get_post_type( $post_id ) ) {
			$this->cache->clear_all_cache();
		}
	}
}
