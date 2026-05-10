<?php
/**
 * Cache manager.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralizes cache keys and invalidation.
 */
class Doctor_Directory_Tabs_Cache_Manager {
	const OPTION_VERSION = 'ddt_cache_version';
	const GROUP          = 'doctor_directory_tabs';
	const TTL            = 12 * HOUR_IN_SECONDS;

	/**
	 * Build versioned key.
	 *
	 * @param string $context Cache context.
	 * @param array  $args Key args.
	 * @return string
	 */
	public function key( $context, array $args = array() ) {
		$version = (string) get_option( self::OPTION_VERSION, '1' );
		ksort( $args );

		return 'ddt_' . md5( $version . '|' . sanitize_key( $context ) . '|' . wp_json_encode( $args ) );
	}

	/**
	 * Get cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed|false
	 */
	public function get( $key ) {
		$value = wp_cache_get( $key, self::GROUP );
		if ( false !== $value ) {
			return $value;
		}

		return get_transient( $key );
	}

	/**
	 * Store cached value.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Value.
	 * @param int    $ttl TTL.
	 */
	public function set( $key, $value, $ttl = self::TTL ) {
		wp_cache_set( $key, $value, self::GROUP, $ttl );
		set_transient( $key, $value, $ttl );
	}

	/**
	 * Invalidate all versioned caches.
	 */
	public function clear_all_cache() {
		update_option( self::OPTION_VERSION, (string) time(), false );
	}
}
