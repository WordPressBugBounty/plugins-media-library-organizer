<?php
/**
 * Abilities class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Registers the Plugin's folder management features with the WordPress Abilities API.
 *
 * No-op on WordPress versions that do not ship the Abilities API.
 */
class Media_Library_Organizer_Abilities {

	/**
	 * Ability category slug.
	 *
	 * @var string
	 */
	const CATEGORY = 'media-organizer';

	/**
	 * Maximum number of items accepted by a batch ability.
	 *
	 * @var int
	 */
	const BATCH_LIMIT = 100;

	/**
	 * Maximum number of folders returned by the list ability.
	 *
	 * @var int
	 */
	const LIST_LIMIT = 1000;

	/**
	 * Holds the base class object.
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Registers the ability category.
	 */
	public function register_category() {

		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Media Library Organizer', 'media-library-organizer' ),
				'description' => __( 'Manage media folders and file attachments into them.', 'media-library-organizer' ),
			)
		);
	}

	/**
	 * Registers the abilities.
	 */
	public function register_abilities() {

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$folder_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'     => array( 'type' => 'integer' ),
				'name'   => array( 'type' => 'string' ),
				'slug'   => array( 'type' => 'string' ),
				'parent' => array( 'type' => 'integer' ),
				'count'  => array( 'type' => 'integer' ),
				'order'  => array( 'type' => 'integer' ),
			),
		);

		$taxonomy_input = array(
			'type'        => 'string',
			'description' => __( 'Attachment taxonomy registered by the plugin. Defaults to the folder taxonomy.', 'media-library-organizer' ),
		);

		wp_register_ability(
			'media-organizer/list-folders',
			array(
				'label'               => __( 'List media folders', 'media-library-organizer' ),
				'description'         => __( 'Returns the media folder tree: id, name, slug, parent, media count and order, plus the taxonomy slug the folders belong to.', 'media-library-organizer' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'taxonomy' => $taxonomy_input,
						'parent'   => array(
							'type'        => 'integer',
							'description' => __( 'Only return folders below this folder ID.', 'media-library-organizer' ),
							'minimum'     => 0,
						),
						'search'   => array(
							'type'        => 'string',
							'description' => __( 'Only return folders whose name matches this text.', 'media-library-organizer' ),
						),
						'format'   => array(
							'type'        => 'string',
							'description' => __( 'nested returns a tree with children, flat returns a plain list.', 'media-library-organizer' ),
							'enum'        => array( 'nested', 'flat' ),
							'default'     => 'nested',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy'  => array( 'type' => 'string' ),
						'format'    => array( 'type' => 'string' ),
						'total'     => array( 'type' => 'integer' ),
						'truncated' => array( 'type' => 'boolean' ),
						'folders'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'list_folders' ),
				'permission_callback' => array( $this, 'can_manage_folders' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'media-organizer/upsert-folder',
			array(
				'label'               => __( 'Create or update a media folder', 'media-library-organizer' ),
				'description'         => __( 'Creates a folder when folder_id is omitted. With folder_id, name renames the folder and parent_id moves it (0 = top level). Circular moves are refused.', 'media-library-organizer' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'folder_id' => array(
							'type'        => 'integer',
							'description' => __( 'Folder ID to rename or move. Omit to create a folder.', 'media-library-organizer' ),
							'minimum'     => 1,
						),
						'name'      => array(
							'type'        => 'string',
							'description' => __( 'Folder name. Required when creating.', 'media-library-organizer' ),
						),
						'parent_id' => array(
							'type'        => 'integer',
							'description' => __( 'Parent folder ID. 0 = top level.', 'media-library-organizer' ),
							'minimum'     => 0,
						),
						'taxonomy'  => $taxonomy_input,
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array( 'type' => 'integer' ),
						'name'     => array( 'type' => 'string' ),
						'slug'     => array( 'type' => 'string' ),
						'parent'   => array( 'type' => 'integer' ),
						'count'    => array( 'type' => 'integer' ),
						'order'    => array( 'type' => 'integer' ),
						'taxonomy' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'upsert_folder' ),
				'permission_callback' => array( $this, 'can_manage_folders' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'media-organizer/reorder-folders',
			array(
				'label'               => __( 'Reorder media folders', 'media-library-organizer' ),
				'description'         => __( 'Sets the order of the folders that share one parent. All given folders must exist and have that parent.', 'media-library-organizer' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'parent_id'   => array(
							'type'        => 'integer',
							'description' => __( 'Parent folder ID. 0 = top level.', 'media-library-organizer' ),
							'minimum'     => 0,
						),
						'ordered_ids' => array(
							'type'        => 'array',
							'description' => __( 'Folder IDs in the desired order.', 'media-library-organizer' ),
							'items'       => array( 'type' => 'integer' ),
							'minItems'    => 1,
							'maxItems'    => self::BATCH_LIMIT,
						),
					),
					'required'             => array( 'parent_id', 'ordered_ids' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy'  => array( 'type' => 'string' ),
						'parent_id' => array( 'type' => 'integer' ),
						'folders'   => array(
							'type'  => 'array',
							'items' => $folder_schema,
						),
					),
				),
				'execute_callback'    => array( $this, 'reorder_folders' ),
				'permission_callback' => array( $this, 'can_manage_folders' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'media-organizer/categorize',
			array(
				'label'               => __( 'Categorize media', 'media-library-organizer' ),
				'description'         => __( 'Appends, replaces or removes folder / category terms on a batch of attachments and returns a result per attachment. remove or replace with no term_ids clears all terms of the taxonomy.', 'media-library-organizer' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'media_ids' => array(
							'type'        => 'array',
							'description' => __( 'Attachment IDs.', 'media-library-organizer' ),
							'items'       => array( 'type' => 'integer' ),
							'minItems'    => 1,
							'maxItems'    => self::BATCH_LIMIT,
						),
						'term_ids'  => array(
							'type'        => 'array',
							'description' => __( 'Folder / term IDs.', 'media-library-organizer' ),
							'items'       => array( 'type' => 'integer' ),
							'maxItems'    => self::BATCH_LIMIT,
						),
						'mode'      => array(
							'type'    => 'string',
							'enum'    => array( 'append', 'replace', 'remove' ),
							'default' => 'append',
						),
						'taxonomy'  => $taxonomy_input,
					),
					'required'             => array( 'media_ids' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy' => array( 'type' => 'string' ),
						'mode'     => array( 'type' => 'string' ),
						'updated'  => array( 'type' => 'integer' ),
						'failed'   => array( 'type' => 'integer' ),
						'results'  => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'media_id' => array( 'type' => 'integer' ),
									'ok'       => array( 'type' => 'boolean' ),
									'terms'    => array(
										'type'  => 'array',
										'items' => array( 'type' => 'integer' ),
									),
									'error'    => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'categorize' ),
				'permission_callback' => array( $this, 'can_manage_folders' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'media-organizer/delete-folder',
			array(
				'label'               => __( 'Delete media folders', 'media-library-organizer' ),
				'description'         => __( 'Deletes one or more folders. Media in them is kept and becomes unfiled; child folders move up one level.', 'media-library-organizer' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'folder_ids' => array(
							'type'        => 'array',
							'description' => __( 'Folder IDs to delete.', 'media-library-organizer' ),
							'items'       => array( 'type' => 'integer' ),
							'minItems'    => 1,
							'maxItems'    => self::BATCH_LIMIT,
						),
						'taxonomy'   => $taxonomy_input,
					),
					'required'             => array( 'folder_ids' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy' => array( 'type' => 'string' ),
						'deleted'  => array( 'type' => 'integer' ),
						'failed'   => array( 'type' => 'integer' ),
						'results'  => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'folder_id' => array( 'type' => 'integer' ),
									'ok'        => array( 'type' => 'boolean' ),
									'error'     => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'delete_folder' ),
				'permission_callback' => array( $this, 'can_manage_folders' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Permission check, matching the Plugin's folder REST routes and Tree View.
	 *
	 * @return  bool
	 */
	public function can_manage_folders() {

		return current_user_can( 'manage_categories' );
	}

	/**
	 * Lists folders.
	 *
	 * @param   mixed $input  Ability input.
	 * @return  array|WP_Error
	 */
	public function list_folders( $input = array() ) {

		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->resolve_taxonomy( $input );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}

		$format = ( isset( $input['format'] ) && 'flat' === $input['format'] ) ? 'flat' : 'nested';
		$parent = isset( $input['parent'] ) ? absint( $input['parent'] ) : 0;
		$search = isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '';

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => self::LIST_LIMIT + 1,
		);

		if ( $parent > 0 ) {
			if ( ! get_term_by( 'id', $parent, $taxonomy ) ) {
				return new WP_Error( 'media_library_organizer_folder_not_found', __( 'Parent folder does not exist', 'media-library-organizer' ) );
			}
			$args['child_of'] = $parent;
		}

		if ( '' !== $search ) {
			$args['search'] = $search;
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$truncated = count( $terms ) > self::LIST_LIMIT;
		if ( $truncated ) {
			$terms = array_slice( $terms, 0, self::LIST_LIMIT );
		}

		$folders = array();
		foreach ( $terms as $term ) {
			$folders[] = $this->format_folder( $term );
		}
		usort( $folders, array( $this, 'sort_folders' ) );

		if ( 'nested' === $format ) {
			$folders = $this->nest_folders( $folders );
		}

		return array(
			'taxonomy'  => $taxonomy,
			'format'    => $format,
			'total'     => count( $terms ),
			'truncated' => $truncated,
			'folders'   => $folders,
		);
	}

	/**
	 * Creates, renames or moves a folder.
	 *
	 * @param   mixed $input  Ability input.
	 * @return  array|WP_Error
	 */
	public function upsert_folder( $input = array() ) {

		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->resolve_taxonomy( $input );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}

		$folder_id  = isset( $input['folder_id'] ) ? absint( $input['folder_id'] ) : 0;
		$name       = isset( $input['name'] ) ? trim( sanitize_text_field( $input['name'] ) ) : '';
		$has_parent = array_key_exists( 'parent_id', $input );
		$parent_id  = $has_parent ? absint( $input['parent_id'] ) : 0;

		if ( $parent_id > 0 ) {
			if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
				return new WP_Error( 'media_library_organizer_not_hierarchical', __( 'This taxonomy does not support parent folders.', 'media-library-organizer' ) );
			}
			if ( ! get_term_by( 'id', $parent_id, $taxonomy ) ) {
				return new WP_Error( 'media_library_organizer_parent_not_found', __( 'Parent folder does not exist', 'media-library-organizer' ) );
			}
		}

		// Create.
		if ( ! $folder_id ) {
			if ( '' === $name ) {
				return new WP_Error( 'media_library_organizer_missing_name', __( 'A folder name is required to create a folder.', 'media-library-organizer' ) );
			}

			$term_id = $this->base->get_class( 'taxonomies' )->create_or_update_term( $taxonomy, $name, $parent_id );
			if ( is_wp_error( $term_id ) ) {
				return $term_id;
			}

			return $this->get_folder_response( $term_id, $taxonomy );
		}

		// Update.
		$term = get_term_by( 'id', $folder_id, $taxonomy );
		if ( ! $term ) {
			return new WP_Error( 'media_library_organizer_folder_not_found', __( 'The selected folder no longer exists. Please refresh and try again.', 'media-library-organizer' ) );
		}

		if ( '' === $name && ! $has_parent ) {
			return new WP_Error( 'media_library_organizer_nothing_to_update', __( 'Provide a name and / or a parent_id to update the folder.', 'media-library-organizer' ) );
		}

		$move = $has_parent && (int) $term->parent !== $parent_id;

		// Validate the move before changing anything.
		if ( $move ) {
			if ( $parent_id === $folder_id ) {
				return new WP_Error( 'media_library_organizer_circular_move', __( 'Cannot move a folder into itself', 'media-library-organizer' ) );
			}

			if ( $parent_id > 0 ) {
				$ancestors = array_map( 'absint', get_ancestors( $parent_id, $taxonomy, 'taxonomy' ) );
				if ( in_array( $folder_id, $ancestors, true ) ) {
					return new WP_Error( 'media_library_organizer_circular_move', __( 'Cannot move a folder into its own subfolder', 'media-library-organizer' ) );
				}
			}
		}

		// Rename.
		if ( '' !== $name && $name !== $term->name ) {
			$result = $this->base->get_class( 'taxonomies' )->update_term( $taxonomy, $folder_id, $name );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Move.
		if ( $move ) {
			$result = wp_update_term(
				$folder_id,
				$taxonomy,
				array(
					'parent' => $parent_id,
				)
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return $this->get_folder_response( $folder_id, $taxonomy );
	}

	/**
	 * Reorders folders that share the same parent.
	 *
	 * @param   mixed $input  Ability input.
	 * @return  array|WP_Error
	 */
	public function reorder_folders( $input = array() ) {

		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->resolve_taxonomy( array() );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}

		$parent_id  = isset( $input['parent_id'] ) ? absint( $input['parent_id'] ) : 0;
		$folder_ids = $this->sanitize_ids( isset( $input['ordered_ids'] ) ? $input['ordered_ids'] : array() );

		if ( empty( $folder_ids ) ) {
			return new WP_Error( 'media_library_organizer_missing_ids', __( 'Please select at least one term.', 'media-library-organizer' ) );
		}
		if ( count( $folder_ids ) > self::BATCH_LIMIT ) {
			return $this->batch_limit_error();
		}

		// Validate all folder IDs exist and have the same parent.
		foreach ( $folder_ids as $folder_id ) {
			$term = get_term_by( 'id', $folder_id, $taxonomy );
			if ( ! $term ) {
				return new WP_Error( 'media_library_organizer_folder_not_found', __( 'One or more folders do not exist', 'media-library-organizer' ) );
			}
			if ( (int) $term->parent !== $parent_id ) {
				return new WP_Error( 'media_library_organizer_parent_mismatch', __( 'All folders must have the same parent', 'media-library-organizer' ) );
			}
		}

		// Update the order for each folder using term meta.
		$position = 0;
		foreach ( $folder_ids as $folder_id ) {
			update_term_meta( $folder_id, '_folder_order', $position );
			++$position;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'parent'     => $parent_id,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$folders = array();
		foreach ( $terms as $term ) {
			$folders[] = $this->format_folder( $term );
		}
		usort( $folders, array( $this, 'sort_folders' ) );

		return array(
			'taxonomy'  => $taxonomy,
			'parent_id' => $parent_id,
			'folders'   => $folders,
		);
	}

	/**
	 * Appends, replaces or removes Terms on a batch of Attachments.
	 *
	 * @param   mixed $input  Ability input.
	 * @return  array|WP_Error
	 */
	public function categorize( $input = array() ) {

		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->resolve_taxonomy( $input );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}

		$mode      = isset( $input['mode'] ) ? sanitize_key( $input['mode'] ) : 'append';
		$media_ids = $this->sanitize_ids( isset( $input['media_ids'] ) ? $input['media_ids'] : array() );
		$term_ids  = $this->sanitize_ids( isset( $input['term_ids'] ) ? $input['term_ids'] : array() );

		if ( ! in_array( $mode, array( 'append', 'replace', 'remove' ), true ) ) {
			return new WP_Error( 'media_library_organizer_invalid_mode', __( 'mode must be one of append, replace or remove.', 'media-library-organizer' ) );
		}
		if ( empty( $media_ids ) ) {
			return new WP_Error( 'media_library_organizer_missing_ids', __( 'Provide at least one attachment ID.', 'media-library-organizer' ) );
		}
		if ( count( $media_ids ) > self::BATCH_LIMIT || count( $term_ids ) > self::BATCH_LIMIT ) {
			return $this->batch_limit_error();
		}
		if ( 'append' === $mode && empty( $term_ids ) ) {
			return new WP_Error( 'media_library_organizer_missing_terms', __( 'Provide at least one term ID to append.', 'media-library-organizer' ) );
		}

		$taxonomy_object = $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy );
		if ( ! current_user_can( $taxonomy_object->cap->assign_terms ) ) {
			return new WP_Error( 'media_library_organizer_forbidden', __( 'Sorry, you are not allowed to assign terms in this taxonomy.', 'media-library-organizer' ) );
		}

		$missing = array();
		foreach ( $term_ids as $term_id ) {
			if ( ! get_term_by( 'id', $term_id, $taxonomy ) ) {
				$missing[] = $term_id;
			}
		}
		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'media_library_organizer_term_not_found',
				sprintf(
					/* translators: %s: comma separated list of Term IDs */
					__( 'These terms do not exist in the taxonomy: %s', 'media-library-organizer' ),
					implode( ', ', $missing )
				)
			);
		}

		$results = array();
		$updated = 0;
		foreach ( $media_ids as $media_id ) {
			$post = get_post( $media_id );
			if ( ! $post || 'attachment' !== $post->post_type ) {
				$results[] = $this->item_error( 'media_id', $media_id, __( 'Attachment not found.', 'media-library-organizer' ) );
				continue;
			}
			if ( ! current_user_can( 'edit_post', $media_id ) ) {
				$results[] = $this->item_error( 'media_id', $media_id, __( 'Sorry, you are not allowed to edit this attachment.', 'media-library-organizer' ) );
				continue;
			}

			$attachment = new Media_Library_Organizer_Attachment( $media_id );
			$current    = array_map( 'absint', (array) $attachment->get_terms( $taxonomy ) );

			if ( 'append' === $mode ) {
				$new = array_values( array_unique( array_merge( $current, $term_ids ) ) );
			} elseif ( 'replace' === $mode ) {
				$new = $term_ids;
			} else {
				$new = empty( $term_ids ) ? array() : array_values( array_diff( $current, $term_ids ) );
			}

			if ( empty( $new ) ) {
				$attachment->remove_terms( $taxonomy );
			} else {
				$attachment->set_terms( $taxonomy, $new );
			}

			$result = $attachment->update();
			unset( $attachment );

			if ( is_wp_error( $result ) ) {
				$results[] = $this->item_error( 'media_id', $media_id, $result->get_error_message() );
				continue;
			}

			$terms = wp_get_object_terms( $media_id, $taxonomy, array( 'fields' => 'ids' ) );
			++$updated;
			$results[] = array(
				'media_id' => $media_id,
				'ok'       => true,
				'terms'    => is_wp_error( $terms ) ? array() : array_map( 'absint', $terms ),
			);
		}

		return array(
			'taxonomy' => $taxonomy,
			'mode'     => $mode,
			'updated'  => $updated,
			'failed'   => count( $results ) - $updated,
			'results'  => $results,
		);
	}

	/**
	 * Deletes folders. Attachments are never deleted.
	 *
	 * @param   mixed $input  Ability input.
	 * @return  array|WP_Error
	 */
	public function delete_folder( $input = array() ) {

		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->resolve_taxonomy( $input );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}

		$folder_ids = $this->sanitize_ids( isset( $input['folder_ids'] ) ? $input['folder_ids'] : array() );
		if ( empty( $folder_ids ) ) {
			return new WP_Error( 'media_library_organizer_missing_ids', __( 'Please select at least one term.', 'media-library-organizer' ) );
		}
		if ( count( $folder_ids ) > self::BATCH_LIMIT ) {
			return $this->batch_limit_error();
		}

		$results = array();
		$deleted = 0;
		foreach ( $folder_ids as $folder_id ) {
			if ( ! get_term_by( 'id', $folder_id, $taxonomy ) ) {
				$results[] = $this->item_error( 'folder_id', $folder_id, __( 'The selected term no longer exists. It may have been deleted.', 'media-library-organizer' ) );
				continue;
			}

			$result = $this->base->get_class( 'taxonomies' )->delete_term( $taxonomy, $folder_id );
			if ( is_wp_error( $result ) ) {
				$results[] = $this->item_error( 'folder_id', $folder_id, $result->get_error_message() );
				continue;
			}
			if ( true !== $result ) {
				$results[] = $this->item_error( 'folder_id', $folder_id, __( 'The folder could not be deleted.', 'media-library-organizer' ) );
				continue;
			}

			++$deleted;
			$results[] = array(
				'folder_id' => $folder_id,
				'ok'        => true,
			);
		}

		return array(
			'taxonomy' => $taxonomy,
			'deleted'  => $deleted,
			'failed'   => count( $results ) - $deleted,
			'results'  => $results,
		);
	}

	/**
	 * Resolves the Taxonomy to work on: the given Plugin Taxonomy, or the folder (Tree View) Taxonomy.
	 *
	 * @param   array $input  Ability input.
	 * @return  string|WP_Error
	 */
	private function resolve_taxonomy( $input ) {

		$folder_taxonomy = apply_filters( 'media_library_organizer_tree_view_media_get_tree_view_taxonomy', 'mlo-category' );
		$taxonomy        = ( isset( $input['taxonomy'] ) && '' !== $input['taxonomy'] ) ? sanitize_key( $input['taxonomy'] ) : $folder_taxonomy;
		$allowed         = array_keys( $this->base->get_class( 'taxonomies' )->get_taxonomies() );
		$allowed[]       = $folder_taxonomy;

		if ( ! in_array( $taxonomy, $allowed, true ) || ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'media_library_organizer_invalid_taxonomy', __( 'Taxonomy does not exist.', 'media-library-organizer' ) );
		}

		return $taxonomy;
	}

	/**
	 * Returns a list of unique, positive integers.
	 *
	 * @param   mixed $ids    IDs.
	 * @return  array
	 */
	private function sanitize_ids( $ids ) {

		if ( ! is_array( $ids ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	/**
	 * Returns the folder data for the given Term.
	 *
	 * @param   WP_Term $term   Term.
	 * @return  array
	 */
	private function format_folder( $term ) {

		$order = get_term_meta( $term->term_id, '_folder_order', true );

		return array(
			'id'     => (int) $term->term_id,
			'name'   => $term->name,
			'slug'   => $term->slug,
			'parent' => (int) $term->parent,
			'count'  => (int) $term->count,
			'order'  => '' !== $order ? (int) $order : 999999,
		);
	}

	/**
	 * Returns the ability response for a single folder.
	 *
	 * @param   int    $term_id    Term ID.
	 * @param   string $taxonomy   Taxonomy Name.
	 * @return  array|WP_Error
	 */
	private function get_folder_response( $term_id, $taxonomy ) {

		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) ) {
			return $term;
		}
		if ( ! $term ) {
			return new WP_Error( 'media_library_organizer_folder_not_found', __( 'The selected folder no longer exists. Please refresh and try again.', 'media-library-organizer' ) );
		}

		$folder             = $this->format_folder( $term );
		$folder['taxonomy'] = $taxonomy;

		return $folder;
	}

	/**
	 * Sorts folders by their stored order, then by name.
	 *
	 * @param   array $a  Folder.
	 * @param   array $b  Folder.
	 * @return  int
	 */
	public function sort_folders( $a, $b ) {

		if ( $a['order'] !== $b['order'] ) {
			return $a['order'] < $b['order'] ? -1 : 1;
		}

		return strcasecmp( $a['name'], $b['name'] );
	}

	/**
	 * Builds a tree from a sorted, flat list of folders.
	 * Folders whose parent is not in the list are returned at the top level.
	 *
	 * @param   array $folders    Flat folders.
	 * @return  array
	 */
	private function nest_folders( $folders ) {

		$ids = array();
		foreach ( $folders as $folder ) {
			$ids[ $folder['id'] ] = true;
		}

		// Group folders by parent, keeping the sorted order.
		$roots    = array();
		$children = array();
		foreach ( $folders as $folder ) {
			if ( $folder['parent'] === $folder['id'] || ! isset( $ids[ $folder['parent'] ] ) ) {
				$roots[] = $folder;
				continue;
			}
			$children[ $folder['parent'] ][] = $folder;
		}

		return $this->attach_children( $roots, $children, 0 );
	}

	/**
	 * Recursively attaches children to the given folders.
	 *
	 * @param   array $folders    Folders.
	 * @param   array $children   Folders grouped by parent ID.
	 * @param   int   $depth      Current depth, used to stop on malformed hierarchies.
	 * @return  array
	 */
	private function attach_children( $folders, $children, $depth ) {

		foreach ( $folders as $index => $folder ) {
			$folders[ $index ]['children'] = array();
			if ( $depth < 50 && isset( $children[ $folder['id'] ] ) ) {
				$folders[ $index ]['children'] = $this->attach_children( $children[ $folder['id'] ], $children, $depth + 1 );
			}
		}

		return $folders;
	}

	/**
	 * Returns a per-item failure.
	 *
	 * @param   string $key        Item ID key.
	 * @param   int    $id         Item ID.
	 * @param   string $message    Error message.
	 * @return  array
	 */
	private function item_error( $key, $id, $message ) {

		return array(
			$key    => $id,
			'ok'    => false,
			'error' => $message,
		);
	}

	/**
	 * Returns the batch limit error.
	 *
	 * @return  WP_Error
	 */
	private function batch_limit_error() {

		return new WP_Error(
			'media_library_organizer_batch_limit',
			sprintf(
				/* translators: %d: maximum number of items */
				__( 'A maximum of %d items can be processed per call.', 'media-library-organizer' ),
				self::BATCH_LIMIT
			)
		);
	}
}
