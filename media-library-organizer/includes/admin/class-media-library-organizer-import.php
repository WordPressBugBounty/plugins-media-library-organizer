<?php
/**
 * Import class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Handles importing settings from this Plugin, and other Plugins, into
 * this Plugin.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Import {

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

		// Define Import Sources.
		add_filter( 'media_library_organizer_import_sources', array( $this, 'import_sources' ) );

		// Importers.
		add_action( 'media_library_organizer_import', array( $this, 'import' ), 10, 2 );

		// Enhanced Media Library.
		add_filter( 'media_library_organizer_import_third_party', array( $this, 'import_third_party' ), 10, 2 );
	}

	/**
	 * Helper method to retrieve an array of import sources that this plugin
	 * can import link data from.
	 *
	 * These will typically be other WordPress Plugins that have data stored
	 * in this WordPress installation.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $import_sources     Import Sources.
	 * @return  array                       Import Sources
	 */
	public function import_sources( $import_sources ) {

		// Enhanced Media Library.
		$eml = get_option( 'wpuxss_eml_version' );
		if ( ! empty( $eml ) && false !== $eml ) {
			$import_sources['import_enhanced_media_library'] = array(
				'name'          => 'import_enhanced_media_library',
				'label'         => __( 'Import from Enhanced Media Library', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-enhanced-media-library.php',
				'data'          => array(
					'taxonomies' => get_option( 'wpuxss_eml_taxonomies' ),
				),
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-enhanced-media-library/',
			);
		}

		// FileBird.
		$filebird_terms = $this->get_terms( 'nt_wmc_folder' );
		if ( false !== $filebird_terms ) {
			$import_sources['import_filebird'] = array(
				'name'          => 'import_filebird',
				'label'         => __( 'Import from FileBird', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-filebird.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-filebird/',
			);
		}

		// Folders.
		$folders_terms = $this->get_terms( 'media_folder' );
		if ( false !== $folders_terms ) {
			$import_sources['v'] = array(
				'name'          => 'import_folders',
				'label'         => __( 'Import from Folders (Premio)', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-folders.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-folders-premio/',
			);
		}

		// HappyFiles.
		$happyfiles_terms = $this->get_terms( 'happyfiles_category' );
		if ( false !== $happyfiles_terms ) {
			$import_sources['import_happyfiles'] = array(
				'name'          => 'import_happyfiles',
				'label'         => __( 'Import from HappyFiles', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-happyfiles.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-happyfiles/',
			);
		}

		// WP Media Folder.
		$wp_media_folder_terms = $this->get_terms( 'wpmf-category' );
		if ( false !== $wp_media_folder_terms ) {
			$import_sources['import_wp_media_folder'] = array(
				'name'          => 'import_wp_media_folder',
				'label'         => __( 'Import from WP Media Folder', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-wp-media-folder.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-wp-media-folder/',
			);
		}

		// Wicked Folders.
		$wicked_folders_terms = $this->get_terms( 'wf_attachment_folders' );
		if ( false !== $wicked_folders_terms ) {
			$import_sources['import_wicked_folders'] = array(
				'name'          => 'import_wicked_folders',
				'label'         => __( 'Import from Wicked Folders', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-wicked-folders.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-wicked-folders/',
			);
		}

		// Return.
		return $import_sources;
	}

	/**
	 * Import data created by this Plugin's export functionality
	 *
	 * @since   1.0.0
	 *
	 * @param   bool  $success    Success.
	 * @param   array $import     Array.
	 */
	public function import( $success, $import ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if no data.
		if ( ! is_array( $import['data'] ) ) {
			$this->error_message = __( 'Supplied file is not a valid JSON settings file, or has become corrupt.', 'media-library-organizer' ); // @phpstan-ignore-line.
			return;
		}

		// Iterate through settings screens ($data), saving the settings.
		foreach ( $import['data'] as $type => $value ) {
			$this->base->get_class( 'settings' )->update_settings( $type, $value );
		}
	}

	/**
	 * Import data from a third party
	 *
	 * @since   1.1.0
	 *
	 * @param   mixed $success    WP_Error | bool.
	 * @param   array $import     Import Parameters.
	 * @return  mixed               WP_Error | bool
	 */
	public function import_third_party( $success, $import ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Check which importer we need to run.
		if ( isset( $import['import_enhanced_media_library'] ) ) {
			return $this->import_enhanced_media_library( $import );
		}

		if ( isset( $import['import_filebird'] ) ) {
			return $this->import_third_party_taxonomy_terms( 'nt_wmc_folder' );
		}

		if ( isset( $import['import_folders'] ) ) {
			return $this->import_third_party_taxonomy_terms( 'media_folder' );
		}

		if ( isset( $import['import_happyfiles'] ) ) {
			return $this->import_third_party_taxonomy_terms( 'happyfiles_category' );
		}

		if ( isset( $import['import_wicked_folders'] ) ) {
			return $this->import_third_party_taxonomy_terms( 'wf_attachment_folders' );
		}

		if ( isset( $import['import_wp_media_folder'] ) ) {
			return $this->import_third_party_taxonomy_terms( 'wpmf-category' );
		}
	}

	/**
	 * Import data from Enhanced Media Library
	 *
	 * @since   1.0.0
	 *
	 * @param   array $import     Import Parameters.
	 * @return  mixed               WP_Error | bool
	 */
	public function import_enhanced_media_library( $import ) {

		// Bail if no Taxonomies were selected.
		if ( ! isset( $import['taxonomies'] ) || empty( $import['taxonomies'] ) ) {
			return new WP_Error( 'media_library_organizer_import_enhanced_media_library', __( 'Import from Enhanced Media Library: Please select at least one Taxonomy to import.', 'media-library-organizer' ) );
		}

		/**
		 * 1. General
		 */
		// N/A.

		/**
		 * 2. Taxonomy Terms
		 */
		foreach ( $import['taxonomies'] as $taxonomy ) {
			$this->import_third_party_taxonomy_terms( $taxonomy );
		}

		// Done.
		return true;
	}

	/**
	 * Copies Taxonomy Terms from the third party Taxonomy into Media Library Organizer,
	 * assigning them to Attachments they were previously assigned to.
	 *
	 * Doesn't need the third party Taxonmoy to be registered, and honors Term hierarchies.
	 *
	 * @since   1.1.2
	 *
	 * @param   string $taxonomy   Taxonomy.
	 * @return  WP_Error|bool               Success
	 */
	private function import_third_party_taxonomy_terms( $taxonomy ) {

		// Fetch Taxonomy Terms.
		$terms = $this->get_terms( $taxonomy );

		// Define an array to store old to new Term mappings.
		$term_mappings = array();
		$terms_errors  = array();

		// If no Terms were found, skip.
		if ( ! $terms ) {
			return false;
		}

		// For each Term, add it to this Plugin's Taxonomy.
		foreach ( $terms as $import_term_id => $import_term ) {

			// For this Term, iterate through any parent term(s) that might exist
			// until the Top Level Term is reached.  This builds an array
			// of child --> child --> parent.
			$terms_stack = array();
			$has_parent  = true;
			while ( $has_parent ) {
				// Note that this is a Child Term.
				$terms_stack[ $import_term->term_taxonomy_id ] = $import_term;

				// If this Term does not have a Parent, exit the loop.
				if ( 0 === $import_term->parent || '0' === $import_term->parent ) {
					$has_parent = false;
					break;
				}

				// If here, a Parent Term exists.
				// Get Parent Term.
				$import_term = $terms[ $import_term->parent ];
			}

			// Reverse the array of stacked terms, so we're working from Parent --> Child --> Child etc.
			$terms_stack = array_reverse( $terms_stack );

			// We can now safely iterate through this collection of Terms, assigning each to its parent
			// if a Parent exists.
			// Because it's ordered Parent --> Child --> Child, no Child Term can be assigned to a Parent
			// that does not exist.

			// Iterate through the Terms Stack, creating them for this Plugin's Taxonomy.
			foreach ( $terms_stack as $child_term ) {
				// Skip if the Term Name is empty.
				if ( empty( $child_term->name ) ) {
					continue;
				}

				// Create Term.
				$result = $this->create_term( $child_term->name, $child_term->description, ( isset( $term_mappings[ $child_term->parent ] ) ? $term_mappings[ $child_term->parent ] : '' ) ); // @phpstan-ignore-line.

				// Skip if an error occured.
				if ( is_wp_error( $result ) ) {
					$terms_errors[] = sprintf(
						/* translators: %1$s: Term name to create, %2$s: Error message from attempting to create term */
						__( 'Term Name: %1$s, Error: %2$s', 'media-library-organizer' ),
						$child_term->name,
						$result->get_error_message()
					);
					continue;
				}

				// Map this Term.
				$term_mappings[ $child_term->term_taxonomy_id ] = $result; // @phpstan-ignore-line.
			}
		}

		// If no Term Mappings exist, bail.
		if ( empty( $term_mappings ) ) {
			if ( count( $terms_errors ) ) {
				return new WP_Error(
					'media_library_organizer_import_import_third_party_taxonomy_terms',
					sprintf(
						/* translators: Errors when trying to import Terms from another Plugin */
						__( 'No Terms were imported, as the following errors were encountered: %s', 'media-library-organizer' ),
						'<br />' . implode( '<br />', $terms_errors )
					)
				);
			} else {
				return new WP_Error(
					'media_library_organizer_import_import_third_party_taxonomy_terms',
					__( 'No Terms were imported', 'media-library-organizer' )
				);
			}
		}

		// Get Term Relationships with Attachments.
		$attachments = $this->get_term_relationships( array_keys( $term_mappings ) );

		// Iterate through Attachments, creating new Term Relationships.
		if ( is_array( $attachments ) && count( $attachments ) > 0 ) {
			foreach ( $attachments as $attachment_id => $old_term_ids ) {
				// Build an array of the new Plugin Taxonomy Term IDs for this Attachment.
				$term_ids = array();
				foreach ( $old_term_ids as $old_term_id ) {
					// Skip if, for some reason, the old Term doesn't have a new Plugin Taxonomy Term ID.
					if ( ! isset( $term_mappings[ $old_term_id ] ) ) {
						continue;
					}

					// Add the new Plugin Taxonomy Term ID to the Attachment.
					$term_ids[] = absint( $term_mappings[ $old_term_id ] );
				}

				// If no Plugin Taxonomy Term IDs were mapped, skip.
				if ( count( $term_ids ) === 0 ) {
					continue;
				}

				// Assign the Plugin Taxonomy Term IDs to the Attachment.
				$result = wp_set_object_terms( $attachment_id, $term_ids, 'mlo-category', false );

				// Store error if something went wrong.
				if ( is_wp_error( $result ) ) {
					$terms_errors[] = sprintf(
						/* translators: %1$s: Attachment ID, %2$s: Term IDs to assign to Attachment ID, %3$s: Error message when trying to assign Terms to Attachment */
						__( 'Attachment ID: %1$s, Term IDs: %2$s, Error: %3$s', 'media-library-organizer' ),
						$attachment_id,
						implode( ',', $term_ids ),
						$result->get_error_message()
					);
				}
			}
		}

		// Return WP_Error if error(s) were detected during the import process.
		if ( count( $terms_errors ) ) {
			return new WP_Error(
				'media_library_organizer_import_import_third_party_taxonomy_terms',
				sprintf(
					/* translators: Errors encountered when trying to import and assign Terms to Attachments */
					__( 'Terms were imported, however some errors were encountered.  They may have no impact on the import, but you\'ll need to check: %s', 'media-library-organizer' ),
					'<br />' . implode( '<br />', $terms_errors )
				)
			);
		}

		// All OK, no errors.
		return true;
	}

	/**
	 * Creates a new Term for this Plugin's Taxonomy, if it does not already exist.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $term_name      Term Name.
	 * @param   string $description    Term Description.
	 * @param   int    $parent_id      Parent Term ID.
	 * @return  WP_Error|int                    WP_Error|Term ID
	 */
	private function create_term( $term_name, $description = '', $parent_id = 0 ) {

		// Check if this Term Name already exists in this Plugin's Taxonomy
		// If so, return its ID.
		$existing_term = get_term_by( 'name', $term_name, 'mlo-category' );
		if ( false !== $existing_term ) {
			return $existing_term->term_id;
		}

		// Term Name does not exist.
		// Create Term for this Plugin's Taxonomy.
		$result = wp_insert_term(
			$term_name,
			'mlo-category',
			array(
				'description' => $description,
				'parent'      => $parent_id,
			)
		);

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return the ID.
		return $result['term_id'];
	}

	/**
	 * Returns an array of Term IDs and Names for the given Taxonomy, when the Taxonomy
	 * might not be registered in WordPress (i.e. it's a Taxonomy registered through
	 * a third party Plugin that isn't active).
	 *
	 * @since   1.0.0
	 *
	 * @param   string $taxonomy   Taxonomy Name.
	 * @return  mixed               false | array of Taxonomy Term IDs
	 */
	private function get_terms( $taxonomy ) {

		global $wpdb;

		// Get Term data for the given Taxonomy.
		$terms = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT  {$wpdb->term_taxonomy}.term_taxonomy_id,
                {$wpdb->term_taxonomy}.description,
                {$wpdb->term_taxonomy}.parent,
                {$wpdb->terms}.name 
                FROM {$wpdb->term_taxonomy}
                LEFT JOIN {$wpdb->terms}
                ON {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id
                WHERE {$wpdb->term_taxonomy}.taxonomy = %s",
				$taxonomy
			)
		);

		// If no Terms, bail.
		if ( empty( $terms ) ) {
			return false;
		}

		// Make Terms associative, so the keys are the Term IDs.
		$terms_assoc = array();
		foreach ( $terms as $term ) {
			$terms_assoc[ $term->term_taxonomy_id ] = $term;
		}

		// Return.
		return $terms_assoc;
	}

	/**
	 * Returns results from _terms_relationships, comprising of Attachment IDs and their
	 * Taxonomy Term ID, for the given array of Term IDs, when the Taxonomy
	 * might not be registered in WordPress (i.e. it's a Taxonomy registered through
	 * a third party Plugin that isn't active).
	 *
	 * @since   1.0.0
	 *
	 * @param   array $term_ids   Term IDs.
	 * @return  array               Attachment to Term ID Relationships
	 */
	private function get_term_relationships( $term_ids ) {

		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
		// Get Attachment IDs that have any of the given Term IDs assigned to them.
		$attachments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT  {$wpdb->term_relationships}.object_id, {$wpdb->term_relationships}.term_taxonomy_id
				FROM {$wpdb->term_relationships}
				WHERE {$wpdb->term_relationships}.term_taxonomy_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$term_ids
			)
		);

		// If no Attachments, bail.
		if ( empty( $attachments ) ) {
			return $attachments;
		}

		// Iterate through results, storing by Attachment ID.
		$attachments_assoc = array();
		foreach ( $attachments as $attachment ) {
			if ( ! isset( $attachments_assoc[ $attachment->object_id ] ) ) {
				$attachments_assoc[ $attachment->object_id ] = array( absint( $attachment->term_taxonomy_id ) );
			} else {
				$attachments_assoc[ $attachment->object_id ][] = absint( $attachment->term_taxonomy_id );
			}
		}

		// Return.
		return $attachments_assoc;
	}
}
