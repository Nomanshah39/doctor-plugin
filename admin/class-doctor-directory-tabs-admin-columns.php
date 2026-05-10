<?php
/**
 * Admin columns and filters.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Enhances doctor list table. */
class Doctor_Directory_Tabs_Admin_Columns {
	/** Constructor. */
	public function __construct() {
		add_filter( 'manage_' . Doctor_Directory_Tabs_Post_Type::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . Doctor_Directory_Tabs_Post_Type::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . Doctor_Directory_Tabs_Post_Type::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'taxonomy_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_query' ) );
	}

	/** Columns. */
	public function columns( $columns ) {
		return array(
			'cb'            => $columns['cb'],
			'ddt_image'     => __( 'Image', 'doctor-directory-tabs' ),
			'title'         => __( 'Doctor', 'doctor-directory-tabs' ),
			'ddt_first'     => __( 'First name', 'doctor-directory-tabs' ),
			'ddt_last'      => __( 'Last name', 'doctor-directory-tabs' ),
			'ddt_specialty' => __( 'Specialty', 'doctor-directory-tabs' ),
			'ddt_group'     => __( 'Group', 'doctor-directory-tabs' ),
			'ddt_profile'   => __( 'Profile link', 'doctor-directory-tabs' ),
			'modified'      => __( 'Last modified', 'doctor-directory-tabs' ),
		);
	}

	/** Render a column. */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'ddt_image':
				$image_id = absint( get_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::IMAGE_ID, true ) );
				echo $image_id ? wp_kses_post( wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'class' => 'ddt-admin-thumb' ) ) ) : '&mdash;';
				break;
			case 'ddt_first':
				echo esc_html( get_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::FIRST_NAME, true ) );
				break;
			case 'ddt_last':
				echo esc_html( get_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::LAST_NAME, true ) );
				break;
			case 'ddt_specialty':
				echo wp_kses_post( get_the_term_list( $post_id, Doctor_Directory_Tabs_Taxonomies::SPECIALTY, '', ', ', '' ) ?: '&mdash;' );
				break;
			case 'ddt_group':
				echo wp_kses_post( get_the_term_list( $post_id, Doctor_Directory_Tabs_Taxonomies::GROUP, '', ', ', '' ) ?: '&mdash;' );
				break;
			case 'ddt_profile':
				$url = get_post_meta( $post_id, Doctor_Directory_Tabs_Helpers::PROFILE_LINK, true );
				echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open', 'doctor-directory-tabs' ) . '</a>' : '&mdash;';
				break;
		}
	}

	/** Sortable columns. */
	public function sortable_columns( $columns ) {
		$columns['ddt_first'] = 'ddt_first';
		$columns['ddt_last']  = 'ddt_last';
		return $columns;
	}

	/** Apply sort. */
	public function sort_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || Doctor_Directory_Tabs_Post_Type::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		$orderby = $query->get( 'orderby' );
		if ( 'ddt_first' === $orderby ) {
			$query->set( 'meta_key', Doctor_Directory_Tabs_Helpers::FIRST_NAME );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'ddt_last' === $orderby ) {
			$query->set( 'meta_key', Doctor_Directory_Tabs_Helpers::LAST_NAME );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/** Render taxonomy dropdown filters. */
	public function taxonomy_filters() {
		global $typenow;
		if ( Doctor_Directory_Tabs_Post_Type::POST_TYPE !== $typenow ) {
			return;
		}
		foreach ( array( Doctor_Directory_Tabs_Taxonomies::SPECIALTY, Doctor_Directory_Tabs_Taxonomies::GROUP ) as $taxonomy ) {
			$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';
			wp_dropdown_categories(
				array(
					'show_option_all' => get_taxonomy( $taxonomy )->labels->all_items,
					'taxonomy'        => $taxonomy,
					'name'            => $taxonomy,
					'orderby'         => 'name',
					'value_field'     => 'slug',
					'hide_empty'      => false,
					'selected'        => $selected,
				)
			);
		}
	}

	/** Keep taxonomy filters as slugs. */
	public function filter_query( $query ) {
		global $pagenow;
		if ( 'edit.php' !== $pagenow || empty( $query->query_vars['post_type'] ) || Doctor_Directory_Tabs_Post_Type::POST_TYPE !== $query->query_vars['post_type'] ) {
			return $query;
		}
		foreach ( array( Doctor_Directory_Tabs_Taxonomies::SPECIALTY, Doctor_Directory_Tabs_Taxonomies::GROUP ) as $taxonomy ) {
			if ( ! empty( $query->query_vars[ $taxonomy ] ) ) {
				$query->query_vars[ $taxonomy ] = sanitize_title( $query->query_vars[ $taxonomy ] );
			}
		}
		return $query;
	}
}
