<?php
/**
 * Taxonomies.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers specialty and group taxonomies.
 */
class Doctor_Directory_Tabs_Taxonomies {
	const SPECIALTY = 'doctor_specialty';
	const GROUP     = 'doctor_group';

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
		add_action( 'created_term', array( $this, 'clear_tax_cache' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'clear_tax_cache' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'clear_tax_cache' ), 10, 4 );
		add_action( 'set_object_terms', array( $this, 'clear_cache_on_assign' ), 10, 6 );
	}

	/** Register taxonomies. */
	public static function register() {
		self::register_taxonomy( self::SPECIALTY, __( 'Specialties', 'doctor-directory-tabs' ), __( 'Specialty', 'doctor-directory-tabs' ), 'specialty' );
		self::register_taxonomy( self::GROUP, __( 'Groups', 'doctor-directory-tabs' ), __( 'Group', 'doctor-directory-tabs' ), 'doctor-group' );
	}

	/**
	 * Register a single taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $plural Plural label.
	 * @param string $singular Singular label.
	 * @param string $slug Rewrite slug.
	 */
	private static function register_taxonomy( $taxonomy, $plural, $singular, $slug ) {
		register_taxonomy(
			$taxonomy,
			Doctor_Directory_Tabs_Post_Type::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => $plural,
					'singular_name' => $singular,
					'search_items'  => sprintf( __( 'Search %s', 'doctor-directory-tabs' ), $plural ),
					'all_items'     => sprintf( __( 'All %s', 'doctor-directory-tabs' ), $plural ),
					'edit_item'     => sprintf( __( 'Edit %s', 'doctor-directory-tabs' ), $singular ),
					'add_new_item'  => sprintf( __( 'Add New %s', 'doctor-directory-tabs' ), $singular ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => $slug ),
			)
		);
	}

	/**
	 * Clear term cache.
	 *
	 * @param int    $term_id Term ID.
	 * @param int    $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 */
	public function clear_tax_cache( $term_id, $tt_id, $taxonomy ) {
		if ( in_array( $taxonomy, array( self::SPECIALTY, self::GROUP ), true ) ) {
			$this->cache->clear_all_cache();
		}
	}

	/** Clear cache when relevant terms are assigned. */
	public function clear_cache_on_assign( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( in_array( $taxonomy, array( self::SPECIALTY, self::GROUP ), true ) ) {
			$this->cache->clear_all_cache();
		}
	}
}
