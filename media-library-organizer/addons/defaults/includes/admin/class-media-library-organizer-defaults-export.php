<?php
/**
 * Export class.
 *
 * @package   Media_Library_Organizer_Defaults
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Export' ) ) {

	/**
	 * Exports this Addon's settings when using the main Plugin's
	 * export functionality.
	 *
	 * @package   Media_Library_Organizer_Defaults
	 * @author    WP Media Library
	 * @version   1.1.8
	 */
	class Media_Library_Organizer_Defaults_Export {

		/**
		 * Holds the base class object.
		 *
		 * @since   1.1.8
		 *
		 * @var     object
		 */
		public $base;

		/**
		 * Constructor
		 *
		 * @since   1.1.8
		 *
		 * @param   object $base    Base Plugin Class.
		 */
		public function __construct( $base ) {

			// Store base class.
			$this->base = $base;

			// Export.
			add_filter( 'media_library_organizer_export', array( $this, 'export' ) );
		}

		/**
		 * Export data
		 *
		 * @since   1.1.8
		 *
		 * @param   array $data   Export Data.
		 * @return  array           Export Data
		 */
		public function export( $data ) {

			return array_merge(
				$data,
				array(
					'defaults' => Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'defaults' ),
				)
			);
		}
	}
}
