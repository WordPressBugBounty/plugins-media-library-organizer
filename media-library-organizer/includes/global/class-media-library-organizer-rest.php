<?php
/**
 * Media Library Organizer REST class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Main Media Library Organizer REST class, used to create REST routes..
 *
 * @package   Media_Library_Organizer
 * @author    WP Media Library
 * @version   1.0.0
 */
class Media_Library_Organizer_Rest {

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

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->base->plugin->namespace,
			'/add-taxonomy',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_taxonomy' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_categories' );
				},
				'args'                => array(
					'plural_name'   => array(
						'required'          => true,
						'validate_callback' => function ( $action ) {
							return is_string( $action );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
					'singular_name' => array(
						'required'          => true,
						'validate_callback' => function ( $action ) {
							return is_string( $action );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->base->plugin->namespace,
			'/delete-taxonomy',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_taxonomy' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);
		register_rest_route(
			$this->base->plugin->namespace,
			'/save-settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_settings' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			$this->base->plugin->namespace,
			'/save-list-column',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_columns' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			$this->base->plugin->namespace,
			'/save-frontend-settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_output_settings' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			$this->base->plugin->namespace,
			'/delete-folder',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_folder' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'folders' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_array( $value );
						},
						'sanitize_callback' => function ( $value ) {
							return array_map( 'sanitize_key', $value );
						},
					),
				),
			)
		);

		register_rest_route(
			$this->base->plugin->namespace,
			'/add-folder',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_folder' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'folder_name' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return ! empty( $value ) && is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->base->plugin->namespace,
			'/rename-folder',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rename_folder' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'id'   => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return ! empty( $value ) && is_int( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
					'name' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return ! empty( $value ) && is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->base->plugin->namespace,
			'/download-folder',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'download_folder' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Add taxonomy.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function add_taxonomy( WP_REST_Request $request ) {
		$taxonomy_name = 'mlo-' . sanitize_title( $request->get_param( 'singular_name' ) );
		if ( strlen( $taxonomy_name ) > 32 ) {
			$taxonomy_name = substr( $taxonomy_name, 0, 32 );
		}

		$taxonomies = Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'taxonomy-manager' );
		// Add to settings array.
		$taxonomies[ $taxonomy_name ] = array(
			'plural_name'   => sanitize_text_field( $request->get_param( 'plural_name' ) ),
			'singular_name' => sanitize_text_field( $request->get_param( 'singular_name' ) ),
			'hierarchical'  => absint( $request->get_param( 'hierarchical' ) ),
			'enabled'       => absint( $request->get_param( 'enabled' ) ),
			'public'        => absint( $request->get_param( 'public' ) ),
		);
		$this->base->get_class( 'settings' )->update_settings( 'taxonomy-manager', $taxonomies );

		wp_send_json(
			array(
				'success' => true,
				'message' => __( 'Taxonomy added successfully!', 'media-library-organizer' ),
				'data'    => array(
					$taxonomy_name => $taxonomies[ $taxonomy_name ],
				),
			)
		);
	}

	/**
	 * Delete taxonomy.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function delete_taxonomy( WP_REST_Request $request ) {
		$taxonomy_name = sanitize_title( $request->get_param( 'taxonomy' ) );
		if ( 'mlo-category' === $taxonomy_name ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'This Media category cann\'t be deleted', 'media-library-organizer' ),
				)
			);
		}
		$taxonomies = Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'taxonomy-manager' );

		if ( isset( $taxonomies[ $taxonomy_name ] ) ) {
			unset( $taxonomies[ $taxonomy_name ] );
		} else {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'Taxonomy is not existed!', 'media-library-organizer' ),
				)
			);
		}
		Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'taxonomy-manager', $taxonomies );

		wp_send_json(
			array(
				'success' => true,
				'message' => __( 'Taxonomy deleted successfully!', 'media-library-organizer' ),
			)
		);
	}

	/**
	 * Save settings.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function save_settings( WP_REST_Request $request ) {
		// Bail if user isn't an Administrator.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'media-library-organizer' ), 401 );
		}

		$settings = array_map(
			function ( $r ) {
				if ( is_array( $r ) ) {
					return map_deep( $r, 'sanitize_text_field' );
				}
				return sanitize_text_field( wp_unslash( $r ) );
			},
			$request->get_params() // phpcs:ignore WordPress.Security.NonceVerification
		);

		// Bail if no settings.
		if ( empty( $settings ) ) {
			wp_send_json_error( __( 'No settings data detected.', 'media-library-organizer' ) );
		}

		// Save General Settings.
		$result = Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'general', $settings['general'] );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Save User Options Settings.
		$result = Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'user-options', $settings['user-options'] );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Save Addon Settings.
		$result = apply_filters( 'media_library_organizer_admin_save_settings', true, $settings );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// If here, OK.
		wp_send_json_success( __( 'Settings saved.', 'media-library-organizer' ) );
	}

	/**
	 * Save columns.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function save_columns( WP_REST_Request $request ) {
		$columns = $request->get_param( 'list_view_columns' );
		$columns = array_map( 'sanitize_text_field', $columns );

		$result = Media_Library_Organizer()->get_class( 'settings' )->update_setting( 'output', 'list_view_columns', $columns );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Save columns list', 'media-library-organizer' ) );
	}

	/**
	 * Add frontend settings.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function save_output_settings( WP_REST_Request $request ) {
		$settings = $request->get_params();

		// Bail if no settings for this Addon were posted.
		if ( ! isset( $settings['output'] ) ) {
			wp_send_json_error( __( 'Output is empty!', 'media-library-organizer' ) );
		}

		// If no List View Columns are specified, set a blank array.
		if ( ! isset( $settings['output']['list_view_columns'] ) ) {
			$settings['output']['list_view_columns'] = array();
		}

		$current_settings = Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'output' );
		if ( ! $current_settings ) {
			$current_settings = array();
		}
		$settings = array_merge( $current_settings, $settings );

		// Save Settings.
		$result = Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'output', $settings['output'] );

		if ( ! $result ) {
			wp_send_json_error( __( 'Output setting is not saved!', 'media-library-organizer' ) );
		}

		wp_send_json_success( __( 'Setting saved!', 'media-library-organizer' ) );
	}

	/**
	 * Delete folder.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function delete_folder( WP_REST_Request $request ) {
		$taxonomy_name = apply_filters( 'media_library_organizer_tree_view_media_get_tree_view_taxonomy', 'mlo-category' );
		$term_ids      = $request->get_param( 'folders' );
		$term_ids      = array_filter(
			$term_ids,
			function ( $id ) {
				return $id !== 'all-files' && $id !== '-1';
			}
		);

		if ( empty( $term_ids ) ) {
			wp_send_json_error( __( 'Select at least one term!', 'media-library-organizer' ) );
		}

		foreach ( $term_ids as $_id ) {

			// Get Term.
			$term = get_term_by( 'id', $_id, $taxonomy_name );

			// Bail if the Term doesn't exist.
			if ( ! $term ) {
				wp_send_json_error( __( 'Term does not exist!', 'media-library-organizer' ) );
			}

			// Delete Term.
			$result = $this->base->get_class( 'taxonomies' )->delete_term( $taxonomy_name, $_id );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}
		}

		wp_send_json_success( __( 'Term Deleted!', 'media-library-organizer' ) );
	}

	/**
	 * Add folder.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function add_folder( WP_REST_Request $request ) {
		$folder_name    = $request->get_param( 'folder_name' );
		$taxonomy_name  = apply_filters( 'media_library_organizer_tree_view_media_get_tree_view_taxonomy', 'mlo-category' );
		$term_parent_id = $request->get_param( 'parent_id' );
		$term_id        = $this->base->get_class( 'taxonomies' )->create_term( $taxonomy_name, $folder_name, $term_parent_id );

		// Bail if Term ID is a WP_Error.
		if ( is_wp_error( $term_id ) ) {
			wp_send_json_error( $term_id->get_error_message() );
		}

		$term = get_term( $term_id );
		wp_send_json_success(
			array(
				'message' => __( 'Term Created!', 'media-library-organizer' ),
				'terms'   => array(
					'id'     => $term_id,
					'name'   => $term->name,
					'type'   => 'folder',
					'parent' => $term->parent,
					'count'  => 0,
					'slug'   => $term->slug,
				),
			)
		);
	}

	/**
	 * Rename folder.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function rename_folder( WP_REST_Request $request ) {
		$term_id       = $request->get_param( 'id' );
		$term_name     = $request->get_param( 'name' );
		$taxonomy_name = apply_filters( 'media_library_organizer_tree_view_media_get_tree_view_taxonomy', 'mlo-category' );

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

		wp_send_json_success( __( 'Rename term!', 'media-library-organizer' ) );
	}

	/**
	 * Download the zip file.
	 *
	 * @param WP_REST_Request $request Rest request obect.
	 */
	public function download_folder( WP_REST_Request $request ) {

		$term_id       = sanitize_text_field( $request->get_param( 'term_id' ) );
		$term_name     = sanitize_text_field( wp_unslash( $request->get_param( 'term_name' ) ) );
		$taxonomy_name = apply_filters( 'media_library_organizer_tree_view_media_get_tree_view_taxonomy', 'mlo-category' );
		$zip_folders   = $this->get_zip_folder( $taxonomy_name, (int) $term_id );

		$folder_path = Media_Library_Organizer()->get_class( 'filesystem' )->get_tmp_folder();
		if ( is_wp_error( $folder_path ) ) {
			wp_send_json_error( $folder_path->get_error_message() );
		}

		$file_path = $folder_path . '/wp-media-lib-img-export-' . $term_name . '.zip';
		$zip       = new ZipArchive();

		if ( ! $zip->open( $file_path, ZIPARCHIVE::CREATE ) ) {
			wp_send_json_error( __( 'ZIP is not created!', 'media-library-organizer' ) );
		}

		if ( 'all-files' === $term_id ) {
			$attachments = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			if ( ! count( $attachments ) ) {
				wp_send_json_error( __( 'No attachment added!', 'media-library-organizer' ) );
			}

			$this->build_extract_zip( $attachments, $term_name, $zip );
		} elseif ( 'unassigned' === $term_id ) {
			$attachments = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => $taxonomy_name,
							'operator' => 'NOT EXISTS',
						),
					),
				)
			);

			if ( ! count( $attachments ) ) {
				wp_send_json_error( __( 'No attachment added!', 'media-library-organizer' ) );
			}

			$this->build_extract_zip( $attachments, $term_name, $zip );
		} else {
			$term = get_term( (int) $term_id, $taxonomy_name );
			if ( is_wp_error( $term ) ) {
				wp_send_json_error( $term->get_error_message() );
			}

			if ( $term->count === 0 ) {
				wp_send_json_error( __( 'No attachment added!', 'media-library-organizer' ) );
			}
			array_unshift( $zip_folders, $term_name );

			foreach ( $zip_folders as  $key => $folder ) {
				$_term_name        = explode( '/', trim( $folder, '/' ) );
				$normalized_folder = ltrim( untrailingslashit( $folder ), '/' );
				$dest_path         = $term_name !== $folder ? $term_name . '/' . $normalized_folder : $normalized_folder;
				$attachments       = $this->get_attach_media_from_term_name( end( $_term_name ), $taxonomy_name );

				$this->build_extract_zip( $attachments, $dest_path, $zip );
			}
		}

		$zip->close();

		$result = array(
			'file'       => basename( $file_path ),
			'folder_url' => str_replace( ABSPATH, site_url( '/' ), $file_path ),
		);

		wp_send_json_success( $result );
	}

	/**
	 * Recursively retrieves folder paths for terms and their children for ZIP creation.
	 *
	 * @param string $taxonomy_name The taxonomy name.
	 * @param int    $term_id       The parent term ID.
	 * @param string $parent_term   The parent term path.
	 * @param bool   $check_child   Whether to check child terms.
	 *
	 * @return array                Array of folder paths.
	 */
	private function get_zip_folder( $taxonomy_name, $term_id, $parent_term = '', $check_child = false ) {
		$terms       = get_terms(
			array(
				'taxonomy'   => $taxonomy_name,
				'parent'     => $term_id,
				'hide_empty' => true,
			)
		);
		$folder_path = array();

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$parent_path   = $check_child ? $parent_term . '/' . $term->name : $term->name;
				$folder_path[] = $parent_path;

				$folder_path = array_merge( $folder_path, $this->get_zip_folder( $taxonomy_name, $term->term_id, $parent_path, true ) );
			}
		}

		return $folder_path;
	}

	/**
	 * Retrieves attachment IDs for a given term name and taxonomy.
	 *
	 * @param string|array $term_name      The term name or array of term names to query.
	 * @param string       $taxonomy_name  The taxonomy name to query against.
	 *
	 * @return array                       Array of attachment IDs.
	 */
	private function get_attach_media_from_term_name( $term_name, $taxonomy_name ) {
		$attachment = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'tax_query'      => array(
					array(
						'taxonomy'         => $taxonomy_name,
						'field'            => 'name',
						'terms'            => $term_name,
						'include_children' => false,
					),
				),
				'fields'         => 'ids',
			)
		);

		return $attachment->posts;
	}

	/**
	 * Adds attachments to a ZIP archive under a specified destination path.
	 *
	 * @param array      $attachments Array of attachment IDs to add to the ZIP.
	 * @param string     $dest_path   The destination path inside the ZIP archive.
	 * @param ZipArchive $zip         Reference to the ZipArchive object.
	 */
	private function build_extract_zip( $attachments, $dest_path, &$zip ) {
		// Add sub-folder based on sub-terms.
		$zip->addEmptyDir( $dest_path );

		foreach ( $attachments as $_attachment_id ) {
			$attachment = get_attached_file( $_attachment_id );
			if ( file_exists( $attachment ) ) {
				$zip->addFile( $attachment, $dest_path . '/' . basename( $attachment ) );
			}
		}
	}
}
