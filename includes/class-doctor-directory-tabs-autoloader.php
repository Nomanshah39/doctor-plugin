<?php
/**
 * Class autoloader.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads Doctor Directory Tabs classes from includes, admin, and public directories.
 */
class Doctor_Directory_Tabs_Autoloader {
	/**
	 * Register autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload matching classes.
	 *
	 * @param string $class Class name.
	 */
	public static function autoload( $class ) {
		if ( 0 !== strpos( $class, 'Doctor_Directory_Tabs' ) ) {
			return;
		}

		$file = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		$paths = array(
			DDT_PLUGIN_PATH . 'includes/' . $file,
			DDT_PLUGIN_PATH . 'admin/' . $file,
			DDT_PLUGIN_PATH . 'public/' . $file,
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
