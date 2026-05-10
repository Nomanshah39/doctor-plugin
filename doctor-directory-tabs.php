<?php
/**
 * Plugin Name: Doctor Directory Tabs
 * Plugin URI: https://example.com/doctor-directory-tabs
 * Description: Production-ready doctor directory with specialty tabs, AJAX filtering, CSV import, and optimized caching.
 * Version: 1.0.0
 * Author: Doctor Directory Tabs
 * Text Domain: doctor-directory-tabs
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DDT_VERSION', '1.0.0' );
define( 'DDT_PLUGIN_FILE', __FILE__ );
define( 'DDT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DDT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DDT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once DDT_PLUGIN_PATH . 'includes/class-doctor-directory-tabs-autoloader.php';
Doctor_Directory_Tabs_Autoloader::register();

register_activation_hook( __FILE__, array( 'Doctor_Directory_Tabs_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Doctor_Directory_Tabs_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Doctor_Directory_Tabs_Plugin', 'instance' ) );
