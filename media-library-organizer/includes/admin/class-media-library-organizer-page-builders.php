<?php
/**
 * Page Builders class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Adds Media Category filtering support for Frontend Page Builders
 * that might not trigger WordPress standard functions
 *
 * @since  1.1.4
 */
class Media_Library_Organizer_Page_Builders {

	/**
	 * Holds the base object.
	 *
	 * @since   1.1.4
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.1.4
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Elementor.
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'register_elementor_scripts_css' ) );

		// Thrive Architect.
		add_action( 'tcb_hook_template_redirect', array( $this, 'register_thrive_architect_scripts_css' ) );
	}

	/**
	 * Elementor: Enqueue CSS and JS when frontend editing as Elementor removes actions hooked
	 * to admin_enqueue_scripts / wp_enqueue_scripts / wp_enqueue_media
	 *
	 * @since   1.1.4
	 */
	public function register_elementor_scripts_css() {

		$this->base->get_class( 'media' )->enqueue_js_css();
	}

	/**
	 * Thrive Architect: Enqueue CSS and JS when frontend editing as Thrive Architect removes actions hooked
	 * to admin_enqueue_scripts / wp_enqueue_scripts / wp_enqueue_media
	 *
	 * @since   2.5.8
	 */
	public function register_thrive_architect_scripts_css() {

		$this->base->get_class( 'media' )->enqueue_js_css();
	}
}
