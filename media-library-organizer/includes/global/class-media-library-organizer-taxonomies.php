<?php
/**
 * Taxonomies class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Registers Media Taxonomies and provides helper functions for creating or updating
 * Terms.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Taxonomies {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.3.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the array of Taxonomies that are registered
	 * using the media_library_organizer_taxonomies_register filter
	 *
	 * @since   1.3.2
	 *
	 * @var     array
	 */
	public $taxonomies = array();

	/**
	 * Constructor
	 *
	 * @since   1.3.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Actions.
		add_action( 'init', array( $this, 'register' ), 20 );
	}

	/**
	 * Registers Taxonomies
	 *
	 * @since   1.3.2
	 */
	public function register() {

		// Get all Taxonomies to Register.
		$taxonomies = $this->get_taxonomies_to_register();

		// Bail if no Taxonomies.
		if ( count( $taxonomies ) === 0 ) {
			return;
		}

		// Iterate through Taxonomies, registering them.
		foreach ( $taxonomies as $taxonomy_name => $taxonomy ) {
			// Skip if not enabled.
			if ( ! $taxonomy['enabled'] ) {
				continue;
			}

			// Define taxonomy arguments.
			$args = array(
				'labels'                => array(
					'name'              => $taxonomy['plural_name'],
					'singular_name'     => $taxonomy['singular_name'],
					/* translators: Taxonomy Label, Plural */
					'search_items'      => sprintf( __( 'Search %s', 'media-library-organizer' ), $taxonomy['plural_name'] ),
					/* translators: Taxonomy Label, Plural */
					'all_items'         => sprintf( __( 'All %s', 'media-library-organizer' ), $taxonomy['plural_name'] ),
					/* translators: Taxonomy Label, Singular */
					'parent_item'       => sprintf( __( 'Parent %s', 'media-library-organizer' ), $taxonomy['singular_name'] ),
					/* translators: Taxonomy Label, Singular */
					'parent_item_colon' => sprintf( __( 'Parent %s:', 'media-library-organizer' ), $taxonomy['singular_name'] ),
					/* translators: Taxonomy Label, Singular */
					'edit_item'         => sprintf( __( 'Edit %s', 'media-library-organizer' ), $taxonomy['singular_name'] ),
					/* translators: Taxonomy Label, Singular */
					'update_item'       => sprintf( __( 'Update %s', 'media-library-organizer' ), $taxonomy['singular_name'] ),
					/* translators: Taxonomy Label, Singular */
					'add_new_item'      => sprintf( __( 'Add New %s', 'media-library-organizer' ), $taxonomy['singular_name'] ),
					/* translators: Taxonomy Label, Singular */
					'new_item_name'     => sprintf( __( 'New %s', 'media-library-organizer' ), $taxonomy['singular_name'] ),
					'menu_name'         => $taxonomy['plural_name'],
				),
				'public'                => false,
				'publicly_queryable'    => false,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_in_nav_menus'     => false,
				'show_in_rest'          => true,
				'show_tagcloud'         => false,
				'show_in_quick_edit'    => true,
				'show_admin_column'     => true,
				'hierarchical'          => $taxonomy['hierarchical'],

				// Force counts on Terms.
				'update_count_callback' => '_update_generic_term_count',
			);

			/**
			 * Defines the parameters for registering the Media Categories Taxonomy
			 *
			 * @since   1.1.0
			 *
			 * @param   array   $args           Arguments.
			 * @param   string  $taxonomy_name  Programmatic Taxonomy Name.
			 * @return  array                   Arguments
			 */
			$args = apply_filters( 'media_library_organizer_taxonomy_register_taxonomy', $args, $taxonomy_name );

			// Register taxonomy.
			$result = register_taxonomy( $taxonomy_name, array( 'attachment' ), $args );

			// If an error occured, continue.
			if ( is_wp_error( $result ) ) {
				continue;
			}

			// Add this Taxonomy to the array of registered Taxonomies.
			$this->taxonomies[ $taxonomy_name ] = $taxonomy;
		}
	}

	/**
	 * Returns all Taxonomies to be registered by this Plugin
	 *
	 * They may or may not have been registered using register_taxonomy()
	 *
	 * @since   1.3.2
	 *
	 * @return  array   Taxonomies
	 */
	public function get_taxonomies_to_register() {

		// Define the Categories Taxonomy.
		$taxonomies = array(
			'mlo-category' => array(
				'plural_name'   => __( 'Media Categories', 'media-library-organizer' ),
				'singular_name' => __( 'Media Category', 'media-library-organizer' ),
				'hierarchical'  => true,
				'enabled'       => true,
				'public'        => 0,
			),
		);

		/**
		 * Defines Taxonomies to Register against Attachments
		 *
		 * @since   1.3.2
		 *
		 * @param   array   $taxonomies     Taxonomies to Register
		 */
		$taxonomies = apply_filters( 'media_library_organizer_taxonomies_register', $taxonomies );

		// Ensure expected keys are set.
		foreach ( $taxonomies as $taxonomy_name => $taxonomy ) {
			if ( ! array_key_exists( 'public', $taxonomy ) ) {
				$taxonomies[ $taxonomy_name ]['public'] = 0;
			}
		}

		// Return.
		return $taxonomies;
	}

	/**
	 * Returns all Taxonomies that were successfully registered by this Plugin
	 *
	 * @since   1.3.2
	 *
	 * @return  array       Taxonomies
	 */
	public function get_taxonomies() {

		return $this->taxonomies;
	}

	/**
	 * Returns the Taxonomy object
	 *
	 * @since   1.0.5
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @return  WP_Taxonomy                 Taxonomy
	 */
	public function get_taxonomy( $taxonomy_name ) {

		return get_taxonomy( $taxonomy_name );
	}

	/**
	 * Returns a wp_terms_checklist(), replacing each input name's tax_input[taxonomy-name]
	 * with the supplied Field Name.
	 *
	 * @since   1.2.3
	 *
	 * @param   int         $post_id        Post ID.
	 * @param   array       $args           wp_terms_checklist() compatible arguments.
	 * @param   bool|string $field_name     Field Name to use in place of tax_input[taxonomy-name].
	 * @return  string                      wp_terms_checklist() markup
	 */
	public function get_terms_checklist( $post_id, $args, $field_name = false ) {

		// Get checklist HTML.
		$checklist = wp_terms_checklist( $post_id, $args );

		// Replace field name.
		if ( false !== $field_name ) {
			$checklist = str_replace( 'name="tax_input[' . $args['taxonomy'] . ']', 'name="' . $field_name, $checklist );
		}

		// Return.
		return $checklist;
	}

	/**
	 * Creates or Updates a Term for this Taxonomy
	 *
	 * @since   1.0.5
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @param   string $term           Term Name.
	 * @param   int    $parent_term    Parent Term.
	 * @return  mixed                   WP_Error | Term ID
	 */
	public function create_or_update_term( $taxonomy_name, $term, $parent_term = 0 ) {

		// Check to see if the Term already exists.
		$existing_term_id = term_exists( $term, $taxonomy_name, $parent_term );

		if ( $existing_term_id ) {
			$result = wp_update_term(
				absint( $existing_term_id['term_id'] ),
				$taxonomy_name,
				array(
					'name'   => $term,
					'parent' => (int) $parent_term,
				)
			);
		} else {
			$result = wp_insert_term(
				$term,
				$taxonomy_name,
				array(
					'parent' => (int) $parent_term,
				)
			);
		}

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return Term ID.
		return $result['term_id'];
	}

	/**
	 * Creates a Term for this Taxonomy
	 *
	 * @since   1.1.1
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @param   string $name           Term Name.
	 * @param   int    $parent_term    Parent Term.
	 * @return  mixed                  WP_Error | integer
	 */
	public function create_term( $taxonomy_name, $name, $parent_term = 0 ) {

		$result = wp_insert_term(
			$name,
			$taxonomy_name,
			array(
				'parent' => (int) $parent_term,
			)
		);

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return Term ID.
		return absint( $result['term_id'] );
	}

	/**
	 * Updates a Term for this Taxonomy
	 *
	 * @since   1.1.1
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @param   int    $term_id        Term ID.
	 * @param   string $name           Term Name.
	 * @param   int    $parent_term    Parent Term.
	 * @return  mixed                  WP_Error | integer
	 */
	public function update_term( $taxonomy_name, $term_id, $name, $parent_term = 0 ) {

		// Build args.
		$args = array(
			'name' => $name,
		);
		if ( $parent_term > 0 ) {
			$args['parent'] = $parent_term;
		}

		// Update.
		$result = wp_update_term( $term_id, $taxonomy_name, $args );

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return Term ID.
		return absint( $result['term_id'] );
	}

	/**
	 * Deletes Term for this Taxonomy
	 *
	 * @since   1.1.1
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @param   int    $term_id        Term ID.
	 * @return  mixed                   WP_Error | bool
	 */
	public function delete_term( $taxonomy_name, $term_id ) {

		return wp_delete_term( $term_id, $taxonomy_name );
	}

	/**
	 * Wrapper for wp_set_object_terms(), which also sets the metadata on any newly created terms
	 * to denote which Addon created them
	 *
	 * @since   1.1.0
	 *
	 * @param   string      $taxonomy_name  Taxonomy Name.
	 * @param   int         $attachment_id  Attachment ID.
	 * @param   array       $terms          Terms.
	 * @param   bool|string $meta_key       Meta Key to store against each created Term (false = don't store Meta).
	 * @param   mixed       $meta_value     Meta Value to store against the Meta Key for each created Term.
	 * @return  mixed                       WP_Error | array
	 */
	public function append_attachment_terms( $taxonomy_name, $attachment_id, $terms, $meta_key = false, $meta_value = 1 ) {

		// Set attachment terms.
		$result = wp_set_object_terms( $attachment_id, $terms, $taxonomy_name, true );

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return result if we're not defining metadata.
		if ( ! $meta_key ) {
			return $result;
		}

		// Define meta key/value pair for each taxonomy term.
		foreach ( $result as $taxonomy_term_id ) {
			update_term_meta( $taxonomy_term_id, $meta_key, $meta_value );
		}

		// Return original result.
		return $result;
	}
}
