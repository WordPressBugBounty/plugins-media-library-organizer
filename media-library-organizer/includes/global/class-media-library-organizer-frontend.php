<?php
/**
 * Frontend class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Provides actions to register and enqueue JS and CSS on the frontend site.
 *
 * @since   1.0.8
 */
class Media_Library_Organizer_Frontend {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.0.8
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Actions.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_css' ) );
	}

	/**
	 * Enqueues scripts and CSS
	 *
	 * @since   1.0.8
	 */
	public function enqueue_scripts_css() {

		/**
		 * Enqueue Javascript
		 *
		 * @since   1.0.8
		 */
		do_action( 'media_library_organizer_frontend_enqueue_scripts' );

		/**
		 * Enqueue CSS
		 *
		 * @since   1.0.8
		 */
		do_action( 'media_library_organizer_frontend_enqueue_css' );
	}
}
