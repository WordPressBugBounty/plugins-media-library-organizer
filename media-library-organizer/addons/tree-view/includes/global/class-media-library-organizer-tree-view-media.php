<?php
/**
 * Tree View Media class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Outputs the Tree View in the Media Library Footer
 *
 * @version   1.1.1
 */
class Media_Library_Organizer_Tree_View_Media {

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

		// Enqueue JS and CSS for Tree View.
		add_action( 'media_library_organizer_admin_scripts_js_media', array( $this, 'enqueue_js' ), 10, 4 );
		add_action( 'media_library_organizer_admin_scripts_css_media', array( $this, 'enqueue_css' ), 10, 0 );

		// Output Move Column in List View.
		add_filter( 'media_library_organizer_media_define_list_view_columns', array( $this, 'define_list_view_columns' ), 10, 1 );
		add_filter( 'media_library_organizer_media_define_list_view_columns_output_tree-view-move', array( $this, 'define_list_view_columns_output_tree_view_move' ), 10, 1 );

		// Output HTML in the Upload List and Grid Views.
		add_action( 'media_library_organizer_media_media_library_footer', array( $this, 'media_library_footer' ) );

		// Initilize a cron event to removed exported zip files.
		add_action( 'init', array( $this, 'schedule_expired_zip_cleanup' ) );
	}

	/**
	 * Enqueue JS for the WordPress Admin > Media screens
	 *
	 * @since   1.1.1
	 *
	 * @param   WP_Screen $screen     Current WordPress Screen.
	 * @param   array     $screens    Plugin Registered Screens.
	 * @param   string    $mode       View Mode (list|grid).
	 * @param   string    $ext        If defined, loads minified JS.
	 */
	public function enqueue_js( $screen, $screens, $mode, $ext ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if Tree View isn't enabled.
		if ( ! Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'tree-view', 'enabled' ) ) {
			return;
		}

		// WP Zinc.
		wp_enqueue_script( 'wpzinc-admin-notification' );

		// jQuery UI.
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-menu' );
		wp_enqueue_script( 'jquery-ui-widget' );

		wp_enqueue_script( $this->base->plugin->name . '-jstree', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'jstree' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'jquery' ), Media_Library_Organizer()->plugin->version, true );
		wp_enqueue_script( $this->base->plugin->name . '-resize-sensor', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'resize-sensor' . ( $ext ? '-' . $ext : '' ) . '.js', array(), Media_Library_Organizer()->plugin->version, true );
		wp_enqueue_script( $this->base->plugin->name . '-sticky-sidebar', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'sticky-sidebar' . ( $ext ? '-' . $ext : '' ) . '.js', array(), Media_Library_Organizer()->plugin->version, true );
		wp_enqueue_script( $this->base->plugin->name . '-jquery-ui-contextmenu', $this->base->plugin->url . 'assets/js/jquery.ui-contextmenu-min.js', array( 'jquery' ), Media_Library_Organizer()->plugin->version, true );
		wp_enqueue_script( $this->base->plugin->name . '-media-sidebar', MEDIA_LIBRARY_ORGANIZER_PLUGIN_URL . 'assets/build/sidebar.js', array(), Media_Library_Organizer()->plugin->version, true );
		wp_enqueue_script( $this->base->plugin->name . '-media', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'media' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'jquery', $this->base->plugin->name . '-media-sidebar' ), Media_Library_Organizer()->plugin->version, true );

		// Get Tree View Taxonomy.
		$taxonomy = $this->get_tree_view_taxonomy();

		// Add Context Menu to Add, Edit and Delete Categories if the User's Role permits this.
		$context_menu = false;

		// Add special option (download) to the context menu.
		$context_menu_special_option = false;
		if ( current_user_can( 'manage_categories' ) ) {
			$context_menu = array(
				array(
					'title' => __( 'Add Child', 'media-library-organizer' ),
					'cmd'   => 'create_term',
				),
				array(
					'title' => __( 'Edit', 'media-library-organizer' ),
					'cmd'   => 'edit_term',
				),
				array(
					'title' => __( 'Delete', 'media-library-organizer' ),
					'cmd'   => 'delete_term',
				),
			);

			$context_menu_special_option = array(
				array(
					'title' => __( 'Download', 'media-library-organizer' ),
					'cmd'   => 'download_folder',
				),
			);

			$context_menu = array_merge( $context_menu, $context_menu_special_option );
		}

		/**
		 * Defines the menu items for the Tree View's Context Menu, triggered when a user
		 * right clicks on a Category in the Tree View.
		 *
		 * @since   1.3.9
		 *
		 * @param   mixed   $context_menu   Context Menu (false: none, array).
		 */
		$context_menu = apply_filters( 'media_library_organizer_tree_view_media_context_menu', $context_menu );

		/**
		 * Defines the menu items for the Tree View's Context Menu, triggered when a user
		 * right clicks on a Category in the Tree View.
		 *
		 * @since   1.3.9
		 *
		 * @param   mixed   $context_menu_download_option   Context Menu (false: none, array).
		 */
		$context_menu_special_option = apply_filters( 'media_library_organizer_tree_view_media_context_menu_download_option', $context_menu_special_option );

		// Define the AJAX actions supported by Tree View.
		$actions = array(
			'create_term'            => array(
				'action' => 'media_library_organizer_add_term',
				'nonce'  => wp_create_nonce( 'media_library_organizer_add_term' ),
				'prompt' => sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Enter a %s Name', 'media-library-organizer' ),
					$taxonomy->labels->singular_name
				),
			),
			'edit_term'              => array(
				'action'       => 'media_library_organizer_edit_term',
				'nonce'        => wp_create_nonce( 'media_library_organizer_edit_term' ),
				'prompt'       => sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Edit %s Name', 'media-library-organizer' ),
					$taxonomy->labels->singular_name
				),
				'no_selection' => sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'You must select a %s first', 'media-library-organizer' ),
					$taxonomy->labels->singular_name
				),
			),
			'delete_term'            => array(
				'action'       => 'media_library_organizer_delete_term',
				'nonce'        => wp_create_nonce( 'media_library_organizer_delete_term' ),
				'prompt'       => sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Delete %s?', 'media-library-organizer' ),
					$taxonomy->labels->singular_name
				),
				'no_selection' => sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'You must select a %s first', 'media-library-organizer' ),
					$taxonomy->labels->singular_name
				),
			),
			'categorize_attachments' => array(
				'action' => 'media_library_organizer_categorize_attachments',
				'nonce'  => wp_create_nonce( 'media_library_organizer_categorize_attachments' ),
			),
			'get_tree_view'          => array(
				'action' => 'media_library_organizer_tree_view_get_tree_view',
				'nonce'  => wp_create_nonce( 'media_library_organizer_tree_view_get_tree_view' ),
			),
			'download_folder'        => array(
				'action' => 'media_library_organizer_download_folder',
				'nonce'  => wp_create_nonce( 'media_library_organizer_download_folder' ),
			),
		);

		/**
		 * Defines the AJAX actions supported by the Tree View. Any context menu items should have
		 * a corresponding action defined here.
		 *
		 * @since   1.3.9
		 *
		 * @param   array   $actions    Actions.
		 */
		$actions = apply_filters( 'media_library_organizer_tree_view_media_actions', $actions );

		// Define Media Settings.
		$media_settings  = array(
			'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
			'actions'                     => $actions,
			'context_menu'                => $context_menu,
			'taxonomy'                    => $taxonomy,
			'selected_term'               => Media_Library_Organizer()->get_class( 'media' )->get_selected_terms_slugs( $taxonomy->name ),
			'selected_term_id'            => Media_Library_Organizer()->get_class( 'media' )->get_selected_terms_ids( $taxonomy->name ),
			'media_view'                  => Media_Library_Organizer()->get_class( 'common' )->get_media_view(),
			'jstree'                      => Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'tree-view', 'expand_collapse' ),
			'labels'                      => array(
				/* translators: Number of attachments */
				'categorized_attachments' => __( 'Categorized %s items', 'media-library-organizer' ),
				/* translators: Number of attachments */
				'categorize_attachments'  => __( 'Categorize %s items', 'media-library-organizer' ),
				'categorize_attachment'   => __( 'Categorize 1 item', 'media-library-organizer' ),
			),
			'context_menu_special_option' => $context_menu_special_option,
		);
		$sidebar_settigs = array(
			'api'        => rest_url( Media_Library_Organizer()->plugin->namespace ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'settings'   => Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'output' ),
			'folders'    => $this->get_folders(),
			'taxonomy'   => apply_filters( 'media_library_organizer_tree_view_media_get_tree_view_taxonomy', 'mlo-category' ),
			'media_view' => Media_Library_Organizer()->get_class( 'common' )->get_media_view(),
			'is_pro'     => function_exists( 'Media_Library_Organizer_Pro' ) && Media_Library_Organizer_Pro()->check_license_key_valid(),
		);
		$sidebar_settigs = apply_filters( 'media_library_organizer_output_settings', $sidebar_settigs );

		// Localize Media script.
		wp_localize_script( $this->base->plugin->name . '-media', 'media_library_organizer_tree_view', $media_settings );
		wp_localize_script( $this->base->plugin->name . '-media-sidebar', 'sidebar', $sidebar_settigs );
		wp_set_script_translations( $this->base->plugin->name . '-media-sidebar', 'media-library-organizer' );
	}

	/**
	 * Recursively update term counts to include child term counts.
	 *
	 * @param array  $terms_by_id Array of terms indexed by term ID.
	 * @param string $taxonomy_name Taxonomy name.
	 * @return void
	 */
	private function update_recursive_counts( &$terms_by_id, $taxonomy_name ) {
		foreach ( $terms_by_id as $term_id => &$term_data ) {
			if ( ! empty( $term_data['children'] ) ) {
				$term_data['count'] = $this->calculate_recursive_count( $term_data, $taxonomy_name );
			}
		}
	}

	/**
	 * Calculate the total count for a term including all child terms (unique attachments only).
	 *
	 * @param array  $term_data Term data array.
	 * @param string $taxonomy_name Taxonomy name.
	 * @return int Total count of unique attachments including children.
	 */
	private function calculate_recursive_count( $term_data, $taxonomy_name ) {
		$term_ids = $this->collect_term_ids( $term_data );

		// Query for unique attachments across all these terms.
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => $taxonomy_name,
						'field'    => 'term_id',
						'terms'    => $term_ids,
					),
				),
			)
		);

		return $query->found_posts;
	}

	/**
	 * Collect all term IDs from a term and its descendants.
	 *
	 * @param array $term_data Term data array.
	 * @return array Array of term IDs.
	 */
	private function collect_term_ids( $term_data ) {
		$term_ids = array( $term_data['id'] );

		if ( ! empty( $term_data['children'] ) ) {
			foreach ( $term_data['children'] as $child ) {
				$term_ids = array_merge( $term_ids, $this->collect_term_ids( $child ) );
			}
		}

		return $term_ids;
	}

	/**
	 * Get folder structure.
	 *
	 * @return array
	 */
	public function get_folders() {
		$attachments_count = wp_count_posts( 'attachment' );
		$total_attachments = isset( $attachments_count->inherit ) ? $attachments_count->inherit : 0;
		$taxonomy          = $this->get_tree_view_taxonomy();
		$folders           = array();
		$startup_folder    = Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'output', 'startup_folder', '' );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		// Prepare an array of terms indexed by term ID for easy lookup.
		$terms_by_id = array();
		foreach ( $terms as $term ) {
			$created_at                    = get_term_meta( $term->term_id, '_created_at', true );
			$modified_at                   = get_term_meta( $term->term_id, '_modified_at', true );
			$terms_by_id[ $term->term_id ] = array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'count'       => $term->count,
				'parent'      => $term->parent ? $term->parent : null,
				'children'    => array(),
				'type'        => 'folder',
				'created_at'  => $created_at,
				'modified_at' => $modified_at,
				'slug'        => $term->slug,
			);
		}

		foreach ( $terms_by_id as $term_id => $term_data ) {
			if ( $term_data['parent'] && isset( $terms_by_id[ $term_data['parent'] ] ) ) {
				$terms_by_id[ $term_data['parent'] ]['children'][] = &$terms_by_id[ $term_id ];
			} else {
				$folders[] = &$terms_by_id[ $term_id ];
			}
		}

		// Update counts to include child term counts recursively.
		$this->update_recursive_counts( $terms_by_id, $taxonomy->name );

		$unassigned_attachment = new WP_Query(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'tax_query'   => array(
					array(
						'taxonomy' => $taxonomy->name,
						'operator' => 'NOT EXISTS',
					),
				),
				'fields'      => 'ids',
				'nopaging'    => true,
			)
		);

		$special_folders = array(
			array(
				'id'     => 'all-files',
				'name'   => __( 'All Files', 'media-library-organizer' ),
				'type'   => 'all',
				'count'  => $total_attachments,
				'parent' => null,
				'slug'   => 'all-files',
			),
			array(
				'id'     => 'unassigned',
				'name'   => __( 'Uncategorized', 'media-library-organizer' ),
				'type'   => 'unassigned',
				'count'  => $unassigned_attachment->found_posts,
				'parent' => null,
				'slug'   => '-1',
			),
		);

		return array_merge( $special_folders, $folders );
	}

	/**
	 * Enqueue JS for the WordPress Admin > Media screens
	 *
	 * @since   1.1.1
	 */
	public function enqueue_css() {

		// Bail if Tree View isn't enabled.
		if ( ! Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'tree-view', 'enabled' ) ) {
			return;
		}

		// CSS.
		wp_enqueue_style( $this->base->plugin->name . '-media', $this->base->plugin->url . '/assets/css/media.css', array(), Media_Library_Organizer()->plugin->version );
		wp_enqueue_style( $this->base->plugin->name . '-media-sidebar', MEDIA_LIBRARY_ORGANIZER_PLUGIN_URL . 'assets/build/sidebar.css', array(), Media_Library_Organizer()->plugin->version );
	}

	/**
	 * Defines the Columns to display in the List View WP_List_Table
	 *
	 * @since   1.1.4
	 *
	 * @param   array $columns        Columns.
	 * @return  array                   Columns
	 */
	public function define_list_view_columns( $columns ) {

		// Bail if Tree View isn't enabled.
		if ( ! Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'tree-view', 'enabled' ) ) {
			return $columns;
		}

		// Inject Move Column after the checkbox.
		return Media_Library_Organizer()->get_class( 'common' )->array_insert_after(
			$columns,
			'cb',
			array(
				'tree-view-move' => '<span class="dashicons dashicons-move"></span>',
			)
		);
	}

	/**
	 * Defines the data to display in the List View WP_List_Table Move Column
	 *
	 * @since   1.1.4
	 *
	 * @param   string $output         Output.
	 * @return  string                  Output
	 */
	public function define_list_view_columns_output_tree_view_move( $output ) {

		// Bail if Tree View isn't enabled.
		if ( ! Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'tree-view', 'enabled' ) ) {
			return $output;
		}

		return '<span class="dashicons dashicons-move"></span>';
	}

	/**
	 * Outputs the Tree View markup on the Media Library screens
	 *
	 * @since   1.1.1
	 */
	public function media_library_footer() {

		// Bail if Tree View isn't enabled.
		if ( ! Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'tree-view', 'enabled' ) ) {
			return;
		}

		echo '<div id="media-library-organizer-tree-view" ></div>';

		// Output Notification.
		require_once Media_Library_Organizer()->plugin->folder . '/_modules/dashboard/views/notification.php';
	}

	/**
	 * Returns the Taxonomy object to be used in the Tree View.
	 *
	 * @since   1.3.2
	 *
	 * @return  WP_Taxonomy     Taxonomy
	 */
	public function get_tree_view_taxonomy() {

		/**
		 * Defines the Taxonomy to display Terms for in the Tree View.
		 *
		 * @since   1.3.2
		 *
		 * @param   string  $taxonomy_name      Taxonomy Name.
		 */
		$taxonomy_name = apply_filters( 'media_library_organizer_tree_view_media_get_tree_view_taxonomy', 'mlo-category' );

		// Return taxonomy object.
		return Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name );
	}

	/**
	 * Gets the Tree View markup.
	 *
	 * @since   1.1.1
	 *
	 * @param   string         $taxonomy_name          Taxonomy Name.
	 * @param   bool|int|array $selected_term_ids      Selected Term ID(s) (false | int | array of integers).
	 */
	public function get_tree_view( $taxonomy_name, $selected_term_ids = false ) {

		// Define walker class to use for this Tree View.
		$walker = new Media_Library_Organizer_Tree_View_Taxonomy_Walker();

		// Build args.
		$args = array(
			'echo'       => 0,
			'hide_empty' => 0,
			'show_count' => 1,
			'taxonomy'   => $taxonomy_name,
			'title_li'   => 0,
			'walker'     => $walker,
		);

		// If logged in as an Administrator, prevent PublishPress Permissions from attempting to filter Term counts,
		// otherwise they will display as zero for Administrators (other User Roles are unaffected).
		if ( is_user_logged_in() && 'administrator' === wp_get_current_user()->roles[0] ) {
			$args['pp_no_filter'] = true;
		}

		// If a current Term ID is specified, add it to the arguments now.
		if ( false !== $selected_term_ids ) {
			// Cast integers.
			if ( is_array( $selected_term_ids ) ) {
				foreach ( $selected_term_ids as $index => $selected_term_id ) {
					$selected_term_ids[ $index ] = absint( $selected_term_id );
				}
			} else {
				$selected_term_ids = absint( $selected_term_ids );
			}

			$args['current_category'] = $selected_term_ids;
		}

		// Output.
		$output = '<ul>
            <li class="cat-item-all context-menu">
                <a href="' . $this->get_all_terms_link() . '">' . __( 'All', 'media-library-organizer' ) . '</a>
            </li>
            <li class="cat-item-unassigned context-menu">
                <a href="' . $this->get_unassigned_term_link( $taxonomy_name ) . '">' . __( '(Unassigned)', 'media-library-organizer' ) . '</a>
            </li>' .
			wp_list_categories( $args ) /* @phpstan-ignore-line */ . '
        </ul>';

		// Return.
		return $output;
	}

	/**
	 * Returns the All Categories contextual link, depending on the screen
	 * we're on.
	 *
	 * @since   1.1.1
	 *
	 * @return  string  URL
	 */
	public function get_all_terms_link() {

		return add_query_arg( $this->get_filters(), 'upload.php' );
	}

	/**
	 * Returns the Uncategorized contextual link, depending on the screen
	 * we're on.
	 *
	 * @since   1.1.1
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @return  string                  URL
	 */
	public function get_unassigned_term_link( $taxonomy_name ) {

		return add_query_arg(
			array_merge(
				$this->get_filters(),
				array(
					$taxonomy_name => -1,
				)
			),
			'upload.php'
		);
	}

	/**
	 * Returns an array of filters that the user might have applied to the Media Library view
	 *
	 * @since   1.1.1
	 *
	 * @return  array   Filters
	 */
	private function get_filters() {

		$args       = array(
			'mode' => Media_Library_Organizer()->get_class( 'common' )->get_media_view(),
		);
		$conditions = array(
			'attachment-filter',
			'm',
			'orderby',
			'order',
			'paged',
		);
		foreach ( $conditions as $condition ) {
			if ( ! isset( $_REQUEST[ $condition ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				continue;
			}

			if ( empty( $_REQUEST[ $condition ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				continue;
			}

			$args[ $condition ] = sanitize_text_field( wp_unslash( $_REQUEST[ $condition ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		return $args;
	}

	/**
	 * Add cron job to remove exported zip files.
	 */
	public function schedule_expired_zip_cleanup() {
		$is_scheduled = wp_next_scheduled( 'media_library_organizer_remove_exported_zip' );
		if ( ! $is_scheduled ) {
			wp_schedule_event( time() + 60, 'daily', 'media_library_organizer_remove_exported_zip' );
		}
	}
}
