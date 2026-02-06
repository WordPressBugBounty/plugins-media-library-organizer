<?php
/**
 * Tree View Settings class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Settings class
 *
 * @version   1.1.1
 */
class Media_Library_Organizer_Tree_View_Settings {

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

		add_filter( 'media_library_organizer_settings_get_default_settings', array( $this, 'get_default_settings' ), 10, 1 );
	}

	/**
	 * Defines default settings for this Plugin
	 *
	 * @since   1.1.1
	 *
	 * @param   array $defaults   Default Settings.
	 * @return  array               Default Settings
	 */
	public function get_default_settings( $defaults ) {

		// Define Defaults.
		$defaults['tree-view'] = array(
			'enabled'        => 1,
			'jstree_enabled' => 0,
		);

		// Return.
		return $defaults;
	}
}
