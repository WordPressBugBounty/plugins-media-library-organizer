<?php
/**
 * Admin class.
 *
 * @package   Media_Library_Organizer_Output
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Output_Admin' ) ) {

	/**
	 * Outputs options in the Plugin's settings
	 *
	 * @package   Media_Library_Organizer_Output
	 * @author    WP Media Library
	 * @version   1.1.3
	 */
	class Media_Library_Organizer_Output_Admin {

		/**
		 * Holds the base class object.
		 *
		 * @since   1.1.3
		 *
		 * @var     object
		 */
		public $base;

		/**
		 * Constructor
		 *
		 * @since   1.1.3
		 *
		 * @param   object $base    Base Plugin Class.
		 */
		public function __construct( $base ) {

			// Store base class.
			$this->base = $base;

			add_filter( 'media_library_organizer_admin_save_settings', array( $this, 'save_settings' ), 10, 2 );
			add_filter( 'media_library_organizer_localize_settings', array( $this, 'localize_settings' ) );
			add_filter( 'media_library_organizer_output_settings', array( $this, 'localize_output_settings' ) );
		}

		/**
		 * Save Settings for this Addon.
		 *
		 * @since   1.1.3
		 *
		 * @param   mixed $result     Result (WP_Error|true).
		 * @param   array $settings   Settings.
		 * @return  bool              Settings saved
		 */
		public function save_settings( $result, $settings ) {

			// Bail if no settings for this Addon were posted.
			if ( ! isset( $settings['output'] ) ) {
				return $result;
			}

			// If no List View Columns are specified, set a blank array.
			if ( ! isset( $settings['output']['list_view_columns'] ) ) {
				$settings['output']['list_view_columns'] = array();
			}

			// Save Settings.
			return Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'output', $settings['output'] );
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

		/**
		 * Add output data to the localize.
		 *
		 * @param  array $settings settings array.
		 * @return array
		 */
		public function localize_settings( $settings ) {
			$settings['output'] = Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'output' );
			return $settings;
		}

		/**
		 * Localize the supported columns.
		 *
		 * @param array $settings Localize settings array.
		 * @return array
		 */
		public function localize_output_settings( $settings ) {
			$settings['supported'] = $this->base->get_class( 'media' )->get_supported_list_view_columns();
			return $settings;
		}
	}

}
