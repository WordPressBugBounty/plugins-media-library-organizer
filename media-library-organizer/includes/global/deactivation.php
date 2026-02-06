<?php
/**
 * Plugin deactivation routine.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Runs the uninstallation routines when the plugin is deactivated.
 *
 * @since   1.0.5
 *
 * @param   bool $network_wide   Is network wide deactivation.
 */
function media_library_organizer_deactivate( $network_wide ) {

	// Initialise Plugin.
	$media_library_organizer = Media_Library_Organizer::get_instance();
	$media_library_organizer->initialize();

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site deactivation.
		$media_library_organizer->get_class( 'install' )->uninstall();
	} else {
		// Multisite network wide deactivation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( (int) $site->blog_id );
			$media_library_organizer->get_class( 'install' )->uninstall();
			restore_current_blog();
		}
	}
}
