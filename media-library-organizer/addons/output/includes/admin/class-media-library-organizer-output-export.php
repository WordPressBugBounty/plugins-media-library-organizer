<?php
/**
 * Export class.
 *
 * @package   Media_Library_Organizer_Output
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Output_Export' ) ) {

	/**
	 * Includes this Addon's settings in the Plugin's exporter functionality.
	 *
	 * @package   Media_Library_Organizer
	 * @author    WP Media Library
	 * @version   1.1.8
	 */
	class Media_Library_Organizer_Output_Export {

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
					'output' => Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'output' ),
				)
			);
		}
	}

}
