<?php
/**
 * Media Library Organizer WordPress Plugin.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 *
 * @wordpress-plugin
 * Plugin Name: Media Library Organizer
 * Plugin URI: https://wpmedialibrary.com
 * Version: 1.6.5
 * Author: Optimole
 * License: GPLv2 or later
 * Author URI: https://optimole.com
 * Description: Organize and Search your Media Library, quicker and easier.
 * Text Domain: media-library-organizer
 * WordPress Available:  yes
 * Requires License:    no
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail if Media Library Organizer is alread loaded.
if ( class_exists( 'Media_Library_Organizer' ) ) {
	return;
}

// Define Plugin version and build date.
define( 'MEDIA_LIBRARY_ORGANIZER_PLUGIN_VERSION', '1.6.5' );
define( 'MEDIA_LIBRARY_ORGANIZER_PLUGIN_BUILD_DATE', '2022-11-15 18:00:00' );

// Define Plugin paths.
define( 'MEDIA_LIBRARY_ORGANIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Define the autoloader for this Plugin
 *
 * @since   1.0.0
 *
 * @param   string $class_name     The class to load.
 */
function media_library_organizer_autoloader( $class_name ) {

	// Define the required start of the class name.
	$class_start_name = 'Media_Library_Organizer';

	// Get the number of parts the class start name has.
	$class_parts_count = count( explode( '_', $class_start_name ) );

	// Break the class name into an array.
	$class_path = explode( '_', $class_name );

	// Bail if it's not a minimum length (i.e. doesn't potentially have Media_Library_Organizer).
	if ( count( $class_path ) < $class_parts_count ) {
		return;
	}

	// Build the base class path for this class.
	$base_class_path = '';
	for ( $i = 0; $i < $class_parts_count; $i++ ) {
		$base_class_path .= $class_path[ $i ] . '_';
	}
	$base_class_path = trim( $base_class_path, '_' );

	// Bail if the first parts don't match what we expect.
	if ( $base_class_path !== $class_start_name ) {
		return;
	}

	// Define the file name.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

	// Define the paths to search for the file.
	$include_paths = array(
		MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'includes/admin/',
		MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'includes/global/',
	);

	// Iterate through the include paths to find the file.
	foreach ( $include_paths as $path ) {
		if ( file_exists( $path . '/' . $file_name ) ) {
			require_once $path . '/' . $file_name;
			return;
		}
	}
}
spl_autoload_register( 'media_library_organizer_autoloader' );

// Load Activation, Cron and Deactivation functions.
require_once MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'includes/global/activation.php';
require_once MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'includes/global/deactivation.php';
register_activation_hook( __FILE__, 'media_library_organizer_activate' );
if ( version_compare( get_bloginfo( 'version' ), '5.1', '>=' ) ) {
	add_action( 'wp_insert_site', 'media_library_organizer_activate_new_site' );
} else {
	add_action( 'wpmu_new_blog', 'media_library_organizer_activate_new_site' );
}
add_action( 'activate_blog', 'media_library_organizer_activate_new_site' );
register_deactivation_hook( __FILE__, 'media_library_organizer_deactivate' );

/**
 * Main function to return Plugin instance.
 *
 * @since   1.0.5
 */
function Media_Library_Organizer() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

	return Media_Library_Organizer::get_instance();
}
$vendor_file = MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'vendor/autoload.php';
if ( is_readable( $vendor_file ) ) {

	include_once $vendor_file;
}
add_filter(
	'themeisle_sdk_products',
	function ( $products ) {
		$products[] = __FILE__;

		return $products;
	}
);
// Finally, initialize the Plugin.
require_once MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'includes/class-media-library-organizer.php';
$media_library_organizer = Media_Library_Organizer();
