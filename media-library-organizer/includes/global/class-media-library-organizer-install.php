<?php
/**
 * Plugin installation, upgrade and uninstallation class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Runs installation, upgrade and uninstallation routines when the Plugin
 * is activated, updated and deactivated.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Install {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.5
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.0.5
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;
	}

	/**
	 * Runs the installation routine on the currently active site
	 *
	 * @since   1.0.0
	 *
	 * @param   bool $flag_redirect  Flag a fresh install for the one-time redirect to the Media Library.
	 *                               Only a single-site activation passes true; network and new-site
	 *                               activations must not redirect.
	 */
	public function install( $flag_redirect = false ) {

		// Flag a fresh single-site install so admin_init can redirect to the Media Library once.
		// Every other activation clears any flag left over from a recent fresh install.
		if ( $flag_redirect && ! get_option( $this->base->plugin->name . '-version' ) ) {
			set_transient( $this->base->plugin->name . '-activation-redirect', 1, 30 );
		} else {
			delete_transient( $this->base->plugin->name . '-activation-redirect' );
		}

		// Update the version number.
		update_option( $this->base->plugin->name . '-version', $this->base->plugin->version );
	}

	/**
	 * Runs migration routines when the plugin is updated
	 *
	 * @since   1.0.0
	 */
	public function upgrade() {

		global $wpdb;

		// Get current installed version number (either false or version number e.g. 1.1.7).
		$installed_version = get_option( $this->base->plugin->name . '-version' );

		// If the version number matches the plugin version, bail.
		if ( $installed_version === $this->base->plugin->version ) {
			return;
		}

		/**
		 * 1.3.2: Migrate general[taxonomy_enabled] to general[mlo-category_enabled]
		 */
		if ( ! $installed_version || $installed_version < '1.3.2' ) {
			$settings                         = $this->base->get_class( 'settings' )->get_settings( 'general' );
			$settings['mlo-category_enabled'] = ( isset( $settings['taxonomy_enabled'] ) ? $settings['taxonomy_enabled'] : 1 );
			unset( $settings['taxonomy_enabled'] );
			$this->base->get_class( 'settings' )->update_settings( 'general', $settings );
		}

		// Update the version number.
		update_option( $this->base->plugin->name . '-version', $this->base->plugin->version );
	}

	/**
	 * Runs the uninstallation routine on the currently active site
	 *
	 * @since   1.0.0
	 */
	public function uninstall() {

		// A pending first-install redirect must not outlive a deactivation.
		delete_transient( $this->base->plugin->name . '-activation-redirect' );

		// Discard any queued Import, so that its background process doesn't stay scheduled
		// once the Plugin is deactivated.
		$this->base->get_class( 'import' )->reset();
	}
}
