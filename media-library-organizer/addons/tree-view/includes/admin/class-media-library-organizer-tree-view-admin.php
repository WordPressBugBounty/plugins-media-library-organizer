<?php
/**
 * Tree View Admin class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Tree View class
 *
 * @since   1.1.1
 */
class Media_Library_Organizer_Tree_View_Admin {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.1.1
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.1.1
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Settings.
		add_filter( 'media_library_organizer_admin_save_settings', array( $this, 'save_settings' ), 10, 2 );
	}

	/**
	 * Save Settings for this Addon.
	 *
	 * @since   1.1.1
	 *
	 * @param   mixed $result     Result (WP_Error|true).
	 * @param   array $settings   Settings.
	 * @return  bool              Success
	 */
	public function save_settings( $result, $settings ) {

		// Bail if no settings for this Addon were posted.
		if ( ! isset( $settings['tree-view'] ) ) {
			return $result;
		}

		// Save Settings.
		return Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'tree-view', $settings['tree-view'] );
	}

	/**
	 * Helper method to get the setting value from the Plugin settings
	 *
	 * @since   1.1.1
	 *
	 * @param   string $screen   Screen.
	 * @param   string $key      Setting Key.
	 * @return  mixed               Value
	 */
	public function get_setting( $screen = '', $key = '' ) {

		return Media_Library_Organizer()->get_class( 'settings' )->get_setting( $screen, $key );
	}
}
