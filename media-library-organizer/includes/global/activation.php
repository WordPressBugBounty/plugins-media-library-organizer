<?php
/**
 * Plugin activation routine.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Runs the installation and update routines when the plugin is activated.
 *
 * @since   1.0.5
 *
 * @param   bool $network_wide   Is network wide activation.
 */
function media_library_organizer_activate( $network_wide ) {

	// Initialise Plugin.
	$media_library_organizer = Media_Library_Organizer::get_instance();
	$media_library_organizer->initialize();

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site activation.
		$media_library_organizer->get_class( 'install' )->install();
	} else {
		// Multisite network wide activation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( (int) $site->blog_id );
			$media_library_organizer->get_class( 'install' )->install();
			restore_current_blog();
		}
	}
}

/**
 * Runs the installation and update routines when the plugin is activated
 * on a WPMU site.
 *
 * @since   1.0.5
 *
 * @param   mixed $site_or_blog_id    WP_Site or Blog ID.
 */
function media_library_organizer_activate_new_site( $site_or_blog_id ) {

	// Check if $site_or_blog_id is a WP_Site or a blog ID.
	if ( is_a( $site_or_blog_id, 'WP_Site' ) ) {
		$site_or_blog_id = $site_or_blog_id->blog_id;
	}

	// Initialise Plugin.
	$media_library_organizer = Media_Library_Organizer::get_instance();
	$media_library_organizer->initialize();

	// Run installation routine.
	switch_to_blog( $site_or_blog_id );
	$media_library_organizer->get_class( 'install' )->install();
	restore_current_blog();
}
