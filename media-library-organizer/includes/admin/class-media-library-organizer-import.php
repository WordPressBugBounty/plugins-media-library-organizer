<?php
/**
 * Import class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
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
		if ( ! empty( $eml ) ) {
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
		// FileBird v5+ stores folders in a custom `fbv` table; older versions used WP taxonomy `nt_wmc_folder`.
		if ( $this->filebird_has_data() ) {
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
			$import_sources['import_folders'] = array(
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

		// Media Library Assistant (David Lingren) — taxonomy: attachment_category.
		$mla_terms = $this->get_terms( 'attachment_category' );
		if ( false !== $mla_terms ) {
			$import_sources['import_mla'] = array(
				'name'          => 'import_mla',
				'label'         => __( 'Import from Media Library Assistant', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-mla.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-media-library-assistant/',
			);
		}

		// Mediamatic (Plugincraft) — taxonomy: mediamatic_wpfolder.
		$mediamatic_terms = $this->get_terms( 'mediamatic_wpfolder' );
		if ( false !== $mediamatic_terms ) {
			$import_sources['import_mediamatic'] = array(
				'name'          => 'import_mediamatic',
				'label'         => __( 'Import from Mediamatic', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-mediamatic.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-mediamatic/',
			);
		}

		// WP Real Media Library (devowl.io) — stores folders in `wp_realmedialibrary` custom table.
		if ( $this->rml_has_data() ) {
			$import_sources['import_rml'] = array(
				'name'          => 'import_rml',
				'label'         => __( 'Import from WP Real Media Library', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-rml.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-real-media-library/',
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
			$this->error_message = __( 'The uploaded file is not a valid settings file, or it may be damaged. Please export a new copy and try again.', 'media-library-organizer' ); // @phpstan-ignore-line.
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
			return $this->import_filebird();
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

		// Media Library Assistant (David Lingren).
		if ( isset( $import['import_mla'] ) ) {
			return $this->import_third_party_taxonomy_terms( 'attachment_category' );
		}

		// Mediamatic (Plugincraft).
		if ( isset( $import['import_mediamatic'] ) ) {
			return $this->import_third_party_taxonomy_terms( 'mediamatic_wpfolder' );
		}

		// WP Real Media Library (devowl.io).
		if ( isset( $import['import_rml'] ) ) {
			return $this->import_rml();
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
				$result = $this->create_term( $child_term->name, $child_term->description, ( isset( $term_mappings[ $child_term->parent ] ) ? $term_mappings[ $child_term->parent ] : '' ) );

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
				$term_mappings[ $child_term->term_taxonomy_id ] = $result;
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
					__( 'No terms were imported. The source may be empty or incompatible.', 'media-library-organizer' )
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
	 * Checks whether FileBird has any folder data, supporting both
	 * legacy (WP taxonomy: nt_wmc_folder) and v5+ (custom `fbv` table).
	 *
	 * @since   2.1.0
	 *
	 * @return  bool
	 */
	private function filebird_has_data() {

		global $wpdb;

		$table_fbv = $wpdb->prefix . 'fbv';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_fbv ) ) ) === $table_fbv ) {
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_fbv} WHERE type = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $count > 0 ) {
				return true;
			}
		}

		return false !== $this->get_terms( 'nt_wmc_folder' );
	}

	/**
	 * Imports FileBird folders into Media Library Organizer.
	 *
	 * Dispatches to the v5+ custom-table importer if the `fbv` table exists,
	 * otherwise falls back to the legacy WP taxonomy import.
	 *
	 * @since   2.1.0
	 *
	 * @return  WP_Error|bool
	 */
	private function import_filebird() {

		global $wpdb;

		$table_fbv = $wpdb->prefix . 'fbv';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_fbv ) ) ) === $table_fbv ) {
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_fbv} WHERE type = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $count > 0 ) {
				return $this->import_filebird_v5();
			}
		}

		return $this->import_third_party_taxonomy_terms( 'nt_wmc_folder' );
	}

	/**
	 * Imports FileBird v5+ folders from the `fbv` and `fbv_attachment_folder` tables.
	 *
	 * Schema:
	 *   fbv                 : id, name, parent (int, 0 = root), type (0 = regular folder), ord
	 *   fbv_attachment_folder: folder_id, attachment_id
	 *
	 * @since   2.1.0
	 *
	 * @return  WP_Error|bool
	 */
	private function import_filebird_v5() {

		global $wpdb;

		$folders = $wpdb->get_results(
			"SELECT id AS term_taxonomy_id, name, parent, '' AS description
			 FROM {$wpdb->prefix}fbv
			 WHERE type = 0
			 ORDER BY parent ASC, ord ASC" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		if ( empty( $folders ) ) {
			return new WP_Error(
				'media_library_organizer_import_filebird_v5',
				__( 'No FileBird folders were found to import.', 'media-library-organizer' )
			);
		}

		$terms = array();
		foreach ( $folders as $folder ) {
			$terms[ $folder->term_taxonomy_id ] = $folder;
		}

		$term_mappings = array();
		$terms_errors  = array();

		foreach ( $terms as $import_term_id => $import_term ) {
			$terms_stack = array();
			$has_parent  = true;

			while ( $has_parent ) {
				$terms_stack[ $import_term->term_taxonomy_id ] = $import_term;

				if ( 0 === (int) $import_term->parent ) {
					$has_parent = false;
					break;
				}

				if ( ! isset( $terms[ $import_term->parent ] ) ) {
					$has_parent = false;
					break;
				}

				$import_term = $terms[ $import_term->parent ];
			}

			$terms_stack = array_reverse( $terms_stack );

			foreach ( $terms_stack as $child_term ) {
				if ( empty( $child_term->name ) ) {
					continue;
				}

				if ( isset( $term_mappings[ $child_term->term_taxonomy_id ] ) ) {
					continue;
				}

				$result = $this->create_term(
					$child_term->name,
					$child_term->description,
					isset( $term_mappings[ $child_term->parent ] ) ? $term_mappings[ $child_term->parent ] : ''
				);

				if ( is_wp_error( $result ) ) {
					$terms_errors[] = sprintf(
						/* translators: %1$s: Term name to create, %2$s: Error message from attempting to create term */
						__( 'Term Name: %1$s, Error: %2$s', 'media-library-organizer' ),
						$child_term->name,
						$result->get_error_message()
					);
					continue;
				}

				$term_mappings[ $child_term->term_taxonomy_id ] = $result;
			}
		}

		if ( empty( $term_mappings ) ) {
			if ( count( $terms_errors ) ) {
				return new WP_Error(
					'media_library_organizer_import_filebird_v5',
					sprintf(
						/* translators: %s: List of errors */
						__( 'No Terms were imported, as the following errors were encountered: %s', 'media-library-organizer' ),
						'<br />' . implode( '<br />', $terms_errors )
					)
				);
			}
			return new WP_Error(
				'media_library_organizer_import_filebird_v5',
				__( 'No terms were imported. The source may be empty or incompatible.', 'media-library-organizer' )
			);
		}

		$folder_ids   = array_keys( $term_mappings );
		$placeholders = implode( ', ', array_fill( 0, count( $folder_ids ), '%d' ) );

		// Check if the fbv_attachment_folder table exists before querying.
		$table_fbv_attachment = $wpdb->prefix . 'fbv_attachment_folder';
		$attachments          = array();

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_fbv_attachment ) ) ) === $table_fbv_attachment ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT attachment_id, folder_id AS term_taxonomy_id
					 FROM {$wpdb->prefix}fbv_attachment_folder
					 WHERE folder_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$folder_ids
				)
			);

			foreach ( (array) $rows as $row ) {
				$attachments[ $row->attachment_id ][] = absint( $row->term_taxonomy_id );
			}
		}

		foreach ( $attachments as $attachment_id => $old_term_ids ) {
			$term_ids = array();
			foreach ( $old_term_ids as $old_term_id ) {
				if ( isset( $term_mappings[ $old_term_id ] ) ) {
					$term_ids[] = absint( $term_mappings[ $old_term_id ] );
				}
			}

			if ( empty( $term_ids ) ) {
				continue;
			}

			$result = wp_set_object_terms( $attachment_id, $term_ids, 'mlo-category', false );

			if ( is_wp_error( $result ) ) {
				$terms_errors[] = sprintf(
					/* translators: %1$s: Attachment ID, %2$s: Term IDs, %3$s: Error message */
					__( 'Attachment ID: %1$s, Term IDs: %2$s, Error: %3$s', 'media-library-organizer' ),
					$attachment_id,
					implode( ',', $term_ids ),
					$result->get_error_message()
				);
			}
		}

		if ( count( $terms_errors ) ) {
			return new WP_Error(
				'media_library_organizer_import_filebird_v5',
				sprintf(
					/* translators: %s: List of errors */
					__( 'Terms were imported, however some errors were encountered.  They may have no impact on the import, but you\'ll need to check: %s', 'media-library-organizer' ),
					'<br />' . implode( '<br />', $terms_errors )
				)
			);
		}

		return true;
	}

	/**
	 * Checks whether WP Real Media Library (devowl.io) has folder data.
	 *
	 * RML stores folders in the custom `wp_realmedialibrary` table.
	 * Only type = 0 rows represent regular folders (not collections/galleries).
	 *
	 * @since   2.1.0
	 *
	 * @return  bool
	 */
	private function rml_has_data() {

		global $wpdb;

		$table_rml = $wpdb->prefix . 'realmedialibrary';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_rml ) ) ) !== $table_rml ) {
			return false;
		}

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_rml} WHERE type = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $count > 0;
	}

	/**
	 * Imports WP Real Media Library (devowl.io) folders into Media Library Organizer.
	 *
	 * Schema:
	 *   wp_realmedialibrary      : id, name, parent (int, -1 = root), type (0 = folder), ord
	 *   wp_realmedialibrary_posts: fid (folder id), attachment (post ID)
	 *
	 * @since   2.1.0
	 *
	 * @return  WP_Error|bool
	 */
	private function import_rml() {

		global $wpdb;

		$table_rml       = $wpdb->prefix . 'realmedialibrary';
		$table_rml_posts = $wpdb->prefix . 'realmedialibrary_posts';

		// Fetch regular folders only (type = 0). Root parent is -1 in RML.
		$folders = $wpdb->get_results(
			"SELECT id AS term_taxonomy_id,
			        name,
			        parent,
			        '' AS description
			 FROM {$wpdb->prefix}realmedialibrary
			 WHERE type = 0
			 ORDER BY parent ASC, ord ASC" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		if ( empty( $folders ) ) {
			return new WP_Error(
				'media_library_organizer_import_rml',
				__( 'No WP Real Media Library folders were found to import.', 'media-library-organizer' )
			);
		}

		$terms = array();
		foreach ( $folders as $folder ) {
			$terms[ $folder->term_taxonomy_id ] = $folder;
		}

		$term_mappings = array();
		$terms_errors  = array();

		foreach ( $terms as $import_term_id => $import_term ) {
			$terms_stack = array();
			$has_parent  = true;

			while ( $has_parent ) {
				$terms_stack[ $import_term->term_taxonomy_id ] = $import_term;

				// RML uses -1 (or any value not in the table) as the virtual root.
				if ( (int) $import_term->parent < 0 || ! isset( $terms[ $import_term->parent ] ) ) {
					$has_parent = false;
					break;
				}

				$import_term = $terms[ $import_term->parent ];
			}

			$terms_stack = array_reverse( $terms_stack );

			foreach ( $terms_stack as $child_term ) {
				if ( empty( $child_term->name ) ) {
					continue;
				}

				if ( isset( $term_mappings[ $child_term->term_taxonomy_id ] ) ) {
					continue;
				}

				// Only look up parent MLO ID when the parent is a real (non-root) folder.
				$parent_mlo_id = '';
				if ( (int) $child_term->parent >= 0 && isset( $term_mappings[ $child_term->parent ] ) ) {
					$parent_mlo_id = $term_mappings[ $child_term->parent ];
				}

				$result = $this->create_term( $child_term->name, $child_term->description, $parent_mlo_id );

				if ( is_wp_error( $result ) ) {
					$terms_errors[] = sprintf(
						/* translators: %1$s: Term name to create, %2$s: Error message from attempting to create term */
						__( 'Term Name: %1$s, Error: %2$s', 'media-library-organizer' ),
						$child_term->name,
						$result->get_error_message()
					);
					continue;
				}

				$term_mappings[ $child_term->term_taxonomy_id ] = $result;
			}
		}

		if ( empty( $term_mappings ) ) {
			if ( count( $terms_errors ) ) {
				return new WP_Error(
					'media_library_organizer_import_rml',
					sprintf(
						/* translators: %s: List of errors */
						__( 'No Terms were imported, as the following errors were encountered: %s', 'media-library-organizer' ),
						'<br />' . implode( '<br />', $terms_errors )
					)
				);
			}
			return new WP_Error(
				'media_library_organizer_import_rml',
				__( 'No terms were imported. The source may be empty or incompatible.', 'media-library-organizer' )
			);
		}

		// Fetch attachment relationships from RML's posts table.
		$folder_ids   = array_keys( $term_mappings );
		$placeholders = implode( ', ', array_fill( 0, count( $folder_ids ), '%d' ) );
		$attachments  = array();

		// Check if the realmedialibrary_posts table exists before querying.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_rml_posts ) ) ) === $table_rml_posts ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT attachment, fid AS term_taxonomy_id
					 FROM {$wpdb->prefix}realmedialibrary_posts
					 WHERE fid IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$folder_ids
				)
			);

			foreach ( (array) $rows as $row ) {
				$attachments[ $row->attachment ][] = absint( $row->term_taxonomy_id );
			}
		}

		foreach ( $attachments as $attachment_id => $old_folder_ids ) {
			$term_ids = array();
			foreach ( $old_folder_ids as $old_folder_id ) {
				if ( isset( $term_mappings[ $old_folder_id ] ) ) {
					$term_ids[] = absint( $term_mappings[ $old_folder_id ] );
				}
			}

			if ( empty( $term_ids ) ) {
				continue;
			}

			$result = wp_set_object_terms( $attachment_id, $term_ids, 'mlo-category', false );

			if ( is_wp_error( $result ) ) {
				$terms_errors[] = sprintf(
					/* translators: %1$s: Attachment ID, %2$s: Term IDs, %3$s: Error message */
					__( 'Attachment ID: %1$s, Term IDs: %2$s, Error: %3$s', 'media-library-organizer' ),
					$attachment_id,
					implode( ',', $term_ids ),
					$result->get_error_message()
				);
			}
		}

		if ( count( $terms_errors ) ) {
			return new WP_Error(
				'media_library_organizer_import_rml',
				sprintf(
					/* translators: %s: List of errors */
					__( 'Terms were imported, however some errors were encountered.  They may have no impact on the import, but you\'ll need to check: %s', 'media-library-organizer' ),
					'<br />' . implode( '<br />', $terms_errors )
				)
			);
		}

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
	 * @return  array|null        Attachment to Term ID Relationships
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
