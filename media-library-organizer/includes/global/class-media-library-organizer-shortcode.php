<?php
/**
 * Gallery Shortcode class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Registers filters for the [gallery] shortcode for use by e.g.
 * Dynamic Galleries.
 *
 * @since   1.0.8
 */
class Media_Library_Organizer_Shortcode {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Shortcode prefix.
	 *
	 * @since   1.4.9
	 *
	 * @var     string
	 */
	private $prefix = 'media_library_organizer';

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

		// Register shortcodes in WordPress.
		add_action( 'init', array( $this, 'register' ), 10, 1 );

		// Register [gallery] shortcode filters.
		add_filter( 'post_gallery', array( $this, 'output_gallery' ), 10, 3 );
	}

	/**
	 * Register shortcodes in WordPress.
	 *
	 * @since   1.4.9
	 */
	public function register() {

		// Get shortcodes.
		$shortcodes = $this->get();

		// Bail if no shortcodes are available.
		if ( ! is_array( $shortcodes ) || ! count( $shortcodes ) ) {
			return;
		}

		// Iterate through shortcodes, registering them as shortcodes.
		foreach ( $shortcodes as $shortcode => $properties ) {
			// Register shortcode.
			add_shortcode(
				$this->get_prefix() . '_' . $shortcode,
				array(
					$properties['render_callback'][0],
					$properties['render_callback'][1],
				)
			);
		}
	}

	/**
	 * Returns the namespaced prefix for shortcodes e.g. `media_library_organizer`
	 *
	 * @since   1.4.9
	 *
	 * @return  string
	 */
	public function get_prefix() {

		return $this->prefix;
	}

	/**
	 * Returns registered shortcodes.
	 *
	 * @since   1.4.9
	 *
	 * @return  array   Shortcodes
	 */
	public function get() {

		$shortcodes = array();

		/**
		 * Registers shortcodes.
		 *
		 * @since   1.4.9
		 *
		 * @param   array   $shortcodes     Shortcodes
		 */
		$shortcodes = apply_filters( 'media_library_organizer_shortcode_register', $shortcodes );

		return $shortcodes;
	}

	/**
	 * Unregisters filters added by Addons to the [gallery] shortcode output
	 *
	 * @since   1.0.9
	 */
	public function unregister_gallery_filters() {

		remove_all_filters( 'media_library_organizer_shortcode_output_gallery' );
	}

	/**
	 * Re-registers filters for the [gallery] shortcode output if they have been removed
	 * using unregister_gallery_filters() during a request.
	 *
	 * @since   1.0.9
	 */
	public function reregister_gallery_filters() {

		do_action( 'media_library_organizer_shortcode_reregister_gallery_filters' );
	}

	/**
	 * Allows Addons to modify the [gallery] shortcode HTML
	 *
	 * If output is blank, WordPress' gallery_shortcode() will provide the output
	 * as a fallback.
	 *
	 * @since   1.0.8
	 *
	 * @param   string $output      HTML Output (if blank, gallery_shortcode() is used).
	 * @param   array  $atts        Shortcode Attributes.
	 * @param   int    $instance    Unique numeric ID of this gallery shortcode instance.
	 * @return  string              HTML Output (if blank, gallery_shortcode() is used)
	 */
	public function output_gallery( $output, $atts, $instance ) {

		/**
		 * Allows Addons to modify the [gallery] shortcode HTML
		 *
		 * If output is blank, WordPress' gallery_shortcode() will provide the output
		 * as a fallback.
		 *
		 * @since   1.0.8
		 *
		 * @param   string  $output     HTML Output (if blank, gallery_shortcode() is used).
		 * @param   array   $atts       Shortcode Attributes.
		 * @param   int     $instance   Unique numeric ID of this gallery shortcode instance.
		 * @return  string              HTML Output (if blank, gallery_shortcode() is used)
		 */
		$output = apply_filters( 'media_library_organizer_shortcode_output_gallery', $output, $atts, $instance );

		return $output;
	}
}
