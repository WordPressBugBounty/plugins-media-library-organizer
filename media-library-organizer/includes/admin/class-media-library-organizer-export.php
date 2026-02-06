<?php
/**
 * Export class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Handles exporting Plugin settings.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Export {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.0.0
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
	 * @since   1.0.0
	 *
	 * @param   array $data   Export Data.
	 * @return  array           Export Data
	 */
	public function export( $data ) {

		return array_merge(
			$data,
			array(
				'general'      => $this->base->get_class( 'settings' )->get_settings( 'general' ),
				'user-options' => $this->base->get_class( 'settings' )->get_settings( 'user-options' ),
			)
		);
	}
}
