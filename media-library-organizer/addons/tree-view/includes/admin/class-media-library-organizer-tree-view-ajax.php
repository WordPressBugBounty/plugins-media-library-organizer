<?php
/**
 * Tree View AJAX class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * AJAX class
 *
 * @version   1.1.1
 */
class Media_Library_Organizer_Tree_View_AJAX {

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

		add_action( 'wp_ajax_media_library_organizer_tree_view_get_tree_view', array( $this, 'get_tree_view' ) );
	}

	/**
	 * Returns the Tree View HTML in a JSON payload
	 *
	 * @since   1.1.1
	 */
	public function get_tree_view() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_tree_view_get_tree_view', 'nonce' );

		// Get Folders.
		$folders = $this->base->get_class( 'media' )->get_folders();

		// Done.
		wp_send_json_success(
			array(
				'folders' => $folders,
			)
		);
	}
}
