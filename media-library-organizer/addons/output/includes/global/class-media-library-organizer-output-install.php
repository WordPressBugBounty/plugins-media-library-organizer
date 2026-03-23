<?php
/**
 * Installation class.
 *
 * @package   Media_Library_Organizer_Output
 * @author    Themeisle
 */

/**
 * Routines run when installing and upgrading this Addon.
 *
 * @package   Media_Library_Organizer_Output
 * @author    Themeisle
 */
class Media_Library_Organizer_Output_Install {

	/**
	 * Holds the base class object.
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;
	}

	/**
	 * Runs migration routines when the plugin is updated
	 */
	public function upgrade() {

		// Get current installed version number.
		$installed_version = get_option( $this->base->plugin->name . '-version' );

		// If the version number matches the plugin version, bail.
		if ( $installed_version === Media_Library_Organizer()->plugin->version ) {
			return;
		}

		$this->migrate_settings();

		// Update the version number.
		update_option( $this->base->plugin->name . '-version', Media_Library_Organizer()->plugin->version );
	}

	/**
	 * Migrate settings.
	 */
	private function migrate_settings() {
		$current_columns = array( 'alt_text', 'caption', 'description', 'slug', 'file_extension', 'file_type', 'file_mime', 'file_size', 'dimensions', 'width', 'height', 'attachment_id', 'url' );
		$columns         = Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'output', 'list_view_columns' );

		if ( empty( $columns ) ) {
			return;
		}

		foreach ( $columns as $key => $column ) {
			if ( in_array( $column, $current_columns, true ) && 0 !== strpos( $column, 'mlo_' ) ) {
				$columns[ $key ] = 'mlo_' . $column;
			}
		}

		Media_Library_Organizer()->get_class( 'settings' )->update_setting( 'output', 'list_view_columns', $columns );
	}
}
