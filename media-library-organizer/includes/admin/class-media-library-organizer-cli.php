<?php
/**
 * WP-CLI class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Registers WP-CLI Plugin Commands.
 *
 * @since 1.0.9
 */
class Media_Library_Organizer_CLI {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.9
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.0.9
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		add_action( 'cli_init', array( $this, 'register_commands' ) );
	}

	/**
	 * Registers WP-CLI Commands
	 *
	 * @since   1.0.9
	 */
	public function register_commands() {
	}
}
