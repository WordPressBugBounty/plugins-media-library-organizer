<?php
/**
 * Settings class.
 *
 * @package   Media_Library_Organizer_Output
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Output_Settings' ) ) {

	/**
	 * Gets default settings for this Addon.
	 *
	 * @package   Media_Library_Organizer_Output
	 * @author    WP Media Library
	 * @version   1.1.3
	 */
	class Media_Library_Organizer_Output_Settings {

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

			add_filter( 'media_library_organizer_settings_get_default_settings', array( $this, 'get_default_settings' ), 10, 1 );
		}

		/**
		 * Defines default settings for this Plugin
		 *
		 * @since   1.1.3
		 *
		 * @param   array $defaults   Default Settings.
		 * @return  array               Default Settings
		 */
		public function get_default_settings( $defaults ) {

			// Define Defaults.
			$defaults['output'] = array(
				'grid_size'         => 0,
				'frontend_size'     => 'medium',
				'list_view_columns' => array(
					'tree-view-move',
					'title',
					'author',
					'taxonomy-mlo-category',
					'parent',
					'comments',
					'date',
				),
				'preview_hover'     => 0,
				'show_empty_folder' => 1,
				'sort_order'        => 'asc',
			);

			// Return.
			return $defaults;
		}
	}
}
