<?php
/**
 * AJAX class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Registers AJAX actions for Term management, Attachment editing and Search.
 *
 * @since   1.0.9
 */
class Media_Library_Organizer_AJAX {

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

		add_action( 'wp_ajax_media_library_organizer_add_term', array( $this, 'add_term' ) );
		add_action( 'wp_ajax_media_library_organizer_edit_term', array( $this, 'edit_term' ) );
		add_action( 'wp_ajax_media_library_organizer_delete_term', array( $this, 'delete_term' ) );
		add_action( 'wp_ajax_media_library_organizer_categorize_attachments', array( $this, 'categorize_attachments' ) );
		add_action( 'wp_ajax_media_library_organizer_search_authors', array( $this, 'search_authors' ) );
		add_action( 'wp_ajax_media_library_organizer_search_taxonomy_terms', array( $this, 'search_taxonomy_terms' ) );
		add_action( 'wp_ajax_media_library_organizer_get_taxonomies_terms', array( $this, 'get_taxonomies_terms' ) );
		add_action( 'wp_ajax_media_library_organizer_get_taxonomy_terms', array( $this, 'get_taxonomy_terms' ) );
	}

	/**
	 * Adds a Term
	 *
	 * @since   1.1.1
	 */
	public function add_term() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_add_term', 'nonce' );

		// Get vars.
		$taxonomy_name  = isset( $_REQUEST['taxonomy_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['taxonomy_name'] ) ) : '';
		$term_name      = isset( $_REQUEST['term_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['term_name'] ) ) : '';
		$term_parent_id = isset( $_REQUEST['term_parent_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['term_parent_id'] ) ) : '';
		$term_id        = $this->base->get_class( 'taxonomies' )->create_term( $taxonomy_name, $term_name, $term_parent_id );

		// Bail if Term ID is a WP_Error.
		if ( is_wp_error( $term_id ) ) {
			wp_send_json_error( $term_id->get_error_message() );
		}

		// Get Taxonomy and Term.
		$taxonomy = $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name );
		$term     = get_term_by( 'id', $term_id, $taxonomy_name );

		// Return success with created Term, List View compatible dropdown filter and Grid View Edit Attachment checkbox reflecting changes.
		wp_send_json_success(
			array(
				// The Created Term.
				'term'            => $term,
				// The List View <select> dropdown filter, reflecting the changes i.e. the new Term.
				'dropdown_filter' => $this->base->get_class( 'media' )->get_list_table_category_filter( $taxonomy_name, $taxonomy->label ),
				// The Grid View Edit Attachment <li> checkbox, which can be injected into the Edit Attachment Backbone modal.
				'checkbox'        => $this->base->get_class( 'media' )->get_grid_edit_attachment_checkbox( $taxonomy_name, $term ),
				// The Taxonomy.
				'taxonomy'        => $taxonomy,
				// All Terms.
				'terms'           => $this->base->get_class( 'common' )->get_terms_hierarchical( $taxonomy_name ),
			)
		);
	}

	/**
	 * Edit a Term
	 *
	 * @since   1.1.1
	 */
	public function edit_term() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_edit_term', 'nonce' );

		// Get vars.
		$taxonomy_name = isset( $_REQUEST['taxonomy_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['taxonomy_name'] ) ) : '';
		$term_id       = isset( $_REQUEST['term_id'] ) ? absint( $_REQUEST['term_id'] ) : 0;
		$term_name     = isset( $_REQUEST['term_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['term_name'] ) ) : '';

		// Get what will become the Old Term.
		$old_term = get_term_by( 'id', $term_id, $taxonomy_name );

		// Bail if the (Old) Term doesn't exist.
		if ( ! $old_term ) {
			wp_send_json_error( __( 'Category does not exist, so cannot be deleted', 'media-library-organizer' ) );
		}

		// Update Term.
		$result = $this->base->get_class( 'taxonomies' )->update_term( $taxonomy_name, $term_id, $term_name );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Get Taxonomy.
		$taxonomy = $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name );

		// Return success with old term, edited Term and List View compatible dropdown filter reflecting changes.
		wp_send_json_success(
			array(
				// Old Term.
				'old_term'        => $old_term,
				// New (Edited) Term.
				'term'            => get_term_by( 'id', $term_id, $taxonomy_name ),
				// The List View <select> dropdown filter, reflecting the changes i.e. the edited Term.
				'dropdown_filter' => $this->base->get_class( 'media' )->get_list_table_category_filter( $taxonomy_name, $taxonomy->label ),
				// The Taxonomy.
				'taxonomy'        => $taxonomy,
				// All Terms.
				'terms'           => $this->base->get_class( 'common' )->get_terms_hierarchical( $taxonomy_name ),
			)
		);
	}

	/**
	 * Delete a Term
	 *
	 * @since   1.1.1
	 */
	public function delete_term() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_delete_term', 'nonce' );

		// Get vars.
		$taxonomy_name = isset( $_REQUEST['taxonomy_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['taxonomy_name'] ) ) : '';
		$term_id       = isset( $_REQUEST['term_id'] ) ? absint( $_REQUEST['term_id'] ) : 0;

		// Get Term.
		$term = get_term_by( 'id', $term_id, $taxonomy_name );

		// Bail if the Term doesn't exist.
		if ( ! $term ) {
			wp_send_json_error( __( 'Term does not exist, so cannot be deleted', 'media-library-organizer' ) );
		}

		// Delete Term.
		$result = $this->base->get_class( 'taxonomies' )->delete_term( $taxonomy_name, $term_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Get Taxonomy.
		$taxonomy = $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name );

		// Return success with deleted Term and List View compatible dropdown filter reflecting changes.
		wp_send_json_success(
			array(
				// Deleted Term.
				'term'            => $term,
				// The List View <select> dropdown filter, reflecting the changes i.e. the deleted Term.
				'dropdown_filter' => $this->base->get_class( 'media' )->get_list_table_category_filter( $taxonomy_name, $taxonomy->label ),
				// The Taxonomy.
				'taxonomy'        => $taxonomy,
				// All Terms.
				'terms'           => $this->base->get_class( 'common' )->get_terms_hierarchical( $taxonomy_name ),
			)
		);
	}

	/**
	 * Categorizes the given Attachment IDs with the given Term ID
	 *
	 * @since   1.1.1
	 */
	public function categorize_attachments() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_categorize_attachments', 'nonce' );

		// Get vars.
		$taxonomy_name  = isset( $_REQUEST['taxonomy_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['taxonomy_name'] ) ) : '';
		$term_id        = isset( $_REQUEST['term_id'] ) ? (int) sanitize_text_field( wp_unslash( $_REQUEST['term_id'] ) ) : 0;
		$attachment_ids = isset( $_REQUEST['attachment_ids'] ) ? array_map( 'intval', $_REQUEST['attachment_ids'] ) : array();

		$attachments = array();
		foreach ( $attachment_ids as $attachment_id ) {
			// Get attachment.
			$attachment = new Media_Library_Organizer_Attachment( absint( $attachment_id ) );

			// If the Term ID is -1, remove Terms.
			// Otherwise append them.
			if ( -1 === $term_id ) {
				$attachment->remove_terms( $taxonomy_name );
			} else {
				$attachment->append_terms( $taxonomy_name, array( $term_id ) );
			}

			// Update the Attachment.
			$result = $attachment->update();

			// Bail if an error occured.
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			// Add to return data.
			$attachments[] = array(
				'id'    => $attachment_id,
				'terms' => wp_get_post_terms( $attachment_id, $taxonomy_name ),
			);

			// Destroy the class.
			unset( $attachment );
		}

		// Get Taxonomy.
		$taxonomy = $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name );

		// Return the Attachment IDs and their Categories.
		wp_send_json_success(
			array(
				// Attachments updated, with Terms.
				'attachments'     => $attachments,
				// Term Assigned to Attachments.
				'term'            => get_term_by( 'id', $term_id, $taxonomy_name ),
				// The List View <select> dropdown filter, reflecting the changes i.e. the edited Term.
				'dropdown_filter' => $this->base->get_class( 'media' )->get_list_table_category_filter( $taxonomy_name, $taxonomy->label ),
				// The Taxonomy.
				'taxonomy'        => $taxonomy,
				// All Terms.
				'terms'           => $this->base->get_class( 'common' )->get_terms_hierarchical( $taxonomy_name ),
			)
		);
	}

	/**
	 * Searches for Authors for the given freeform text
	 *
	 * @since   1.0.9
	 */
	public function search_authors() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_search_authors', 'nonce' );

		// Get vars.
		$query = isset( $_REQUEST['query'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['query'] ) ) : '';

		// Get results.
		$users = new WP_User_Query(
			array(
				'search' => '*' . $query . '*',
			)
		);

		// Build array.
		$users_array = array();
		$results     = $users->get_results();
		if ( ! empty( $results ) ) {
			foreach ( $results as $user ) {
				$users_array[] = array(
					'id'         => $user->ID,
					'user_login' => $user->user_login,
				);
			}
		}

		// Done.
		wp_send_json_success( $users_array );
	}

	/**
	 * Searches Categories for the given freeform text
	 *
	 * @since   1.0.9
	 */
	public function search_taxonomy_terms() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_search_taxonomy_terms', 'nonce' );

		// Get vars.
		$taxonomy_name = false;
		if ( isset( $_REQUEST['taxonomy_name'] ) ) {
			$taxonomy_name = sanitize_text_field( wp_unslash( $_REQUEST['taxonomy_name'] ) );
		} elseif ( isset( $_REQUEST['args'] ) && isset( $_REQUEST['args']['taxonomy_name'] ) ) {
			$taxonomy_name = sanitize_text_field( wp_unslash( $_REQUEST['args']['taxonomy_name'] ) );
		}
		$query = isset( $_REQUEST['query'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['query'] ) ) : '';

		// Bail if no Taxonomy Name specified.
		if ( ! $taxonomy_name ) {
			return wp_send_json_error( __( 'The taxonomy_name or args[taxonomy_name] parameter must be included in the request.', 'media-library-organizer' ) );
		}

		// Get results.
		$terms = new WP_Term_Query(
			array(
				'taxonomy'   => $taxonomy_name,
				'search'     => $query,
				'hide_empty' => false,
			)
		);

		// Build array.
		$terms_array = array();
		if ( ! empty( $terms->terms ) ) {
			foreach ( $terms->terms as $term ) {
				$terms_array[] = array(
					'id'   => $term->term_id,
					'term' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		// Done.
		wp_send_json_success( $terms_array );
	}

	/**
	 * Returns all Terms for all Taxonomies
	 *
	 * @since   1.3.3
	 */
	public function get_taxonomies_terms() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_get_taxonomies_terms', 'nonce' );

		// Iterate through Taxonomies.
		$response = array();
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			$response[ $taxonomy_name ] = array(
				'taxonomy' => $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name ),
				'terms'    => $this->base->get_class( 'common' )->get_terms_hierarchical( $taxonomy_name ),
			);
		}

		// Return success with Taxonomies and Terms.
		wp_send_json_success( $response );
	}

	/**
	 * Returns all Terms for the given Taxonomy
	 *
	 * @since   1.3.3
	 */
	public function get_taxonomy_terms() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_get_taxonomy_terms', 'nonce' );

		// Get vars.
		$taxonomy_name = isset( $_REQUEST['taxonomy_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['taxonomy_name'] ) ) : '';

		// Return success with Taxonomy and Terms.
		wp_send_json_success(
			array(
				'taxonomy' => $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name ),
				'terms'    => $this->base->get_class( 'common' )->get_terms_hierarchical( $taxonomy_name ),
			)
		);
	}
}
