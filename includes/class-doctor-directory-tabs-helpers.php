<?php
/**
 * Helper methods.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Shared helpers. */
class Doctor_Directory_Tabs_Helpers {
	/** Meta keys. */
	const IMAGE_ID      = '_ddt_image_id';
	const FIRST_NAME    = '_ddt_first_name';
	const LAST_NAME     = '_ddt_last_name';
	const PROFILE_LINK  = '_ddt_profile_link';
	const DESIGNATION   = '_ddt_designation';
	const CLINIC        = '_ddt_clinic';
	const DEPARTMENT    = '_ddt_department';
	const DISPLAY_ORDER = '_ddt_display_order';
	const IMAGE_SOURCE  = '_ddt_image_source_url';

	/**
	 * Readable doctor name.
	 *
	 * @param int|WP_Post $post Post.
	 * @return string
	 */
	public static function doctor_name( $post ) {
		$post_id = is_object( $post ) ? $post->ID : absint( $post );
		$first   = trim( (string) get_post_meta( $post_id, self::FIRST_NAME, true ) );
		$last    = trim( (string) get_post_meta( $post_id, self::LAST_NAME, true ) );
		$name    = trim( $first . ' ' . $last );

		return '' !== $name ? $name : get_the_title( $post_id );
	}

	/**
	 * Normalize CSV/header keys.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function normalize_key( $value ) {
		$value = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $value );
		$value = strtolower( trim( $value ) );
		$value = preg_replace( '/\s+/', ' ', $value );
		return str_replace( array( ' ', '-' ), '_', $value );
	}

	/**
	 * Sanitize image size against registered sizes and safe defaults.
	 *
	 * @param string $size Size.
	 * @return string
	 */
	public static function sanitize_image_size( $size ) {
		$size    = sanitize_key( $size );
		$allowed = array_merge( array( 'thumbnail', 'medium', 'medium_large' ), get_intermediate_image_sizes() );
		return in_array( $size, $allowed, true ) ? $size : 'medium';
	}
}
