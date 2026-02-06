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
	 */
	public function install() {

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
	}
}
