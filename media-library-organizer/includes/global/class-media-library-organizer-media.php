<?php
/**
 * Media class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Handles output of Taxonomy Filters in List, Grid and Modal views.
 * Saves Taxonomy Term data when changed / saved on Attachments.
 * Stores User Preferences for Order By and Order filters.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Media {

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

		// Add the Taxonomy as a dropdown filter to the WP_List_Table List view.
		add_action( 'restrict_manage_posts', array( $this, 'output_list_table_filters' ), 10, 2 );

		// Enqueue necessary JS and CSS for the taxonomy dropdown filter to the Grid view (selectize).
		add_action( 'wp_enqueue_media', array( $this, 'enqueue_js_css' ) );

		// Enqueue necessary JS and CSS for the taxonomy dropdown filter to the List view (selectize).
		add_action( 'media_library_organizer_admin_scripts_js_media', array( $this, 'enqueue_js_css' ), 10, 0 );

		// Manage Columns displayed on in the List View.
		add_filter( 'manage_media_columns', array( $this, 'define_list_view_columns' ), 10, 2 );
		add_action( 'manage_media_custom_column', array( $this, 'define_list_view_columns_output' ), 10, 2 );

		// Alter query arguments in WP_Query which is run when filtering Attachments in the Grid View.
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_attachments_grid' ) );

		// Add query arguments to the WP_Query which is run when filtering Attachments in the List View.
		add_filter( 'pre_get_posts', array( $this, 'filter_attachments_list' ) );

		// Define fields to display when editing an attachment in the WordPress Admin.
		add_filter( 'add_meta_boxes_attachment', array( $this, 'attachment_edit_form_fields' ), 10, 1 );

		// Save fields when the attachment is saved in the WordPress Admin.
		add_action( 'edit_attachment', array( $this, 'attachment_edit_save_fields' ), 10, 1 );

		// Define fields to display when editing an attachment in a modal.
		add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_edit_modal_form_fields' ), 10, 2 );

		// Save Categories when the attachment is saved in a modal or via Quick Edit.
		add_filter( 'attachment_fields_to_save', array( $this, 'attachment_edit_modal_save_fields' ), 10, 2 );

		// Register Backbone Media Modal Views.
		add_filter( 'print_media_templates', array( $this, 'print_media_templates' ) );

		// Output HTML in the Upload List and Grid Views.
		add_action( 'admin_footer-upload.php', array( $this, 'media_library_footer' ) );
	}

	/**
	 * Fetches the Grid Edit Attachment Taxonomy Term Checkbox HTML for the given Taxonomy
	 *
	 * @since   1.3.3
	 *
	 * @param   string  $taxonomy_name  Taxonomy Name.
	 * @param   WP_Term $term           Term.
	 * @return  string                  HTML List Item Checkbox Output
	 */
	public function get_grid_edit_attachment_checkbox( $taxonomy_name, $term ) {

		return '<li id="' . $taxonomy_name . '-' . $term->term_id . '">
            <label class="selectit">
                <input value="1" type="checkbox" name="' . $taxonomy_name . '_' . $term->term_id . '" class="check" id="in-' . $taxonomy_name . '-' . $term->term_id . '" checked="checked">' .
				$term->name . '
            </label>
        </li>';
	}

	/**
	 * Outputs Taxonomy Filters and Sorting in the Attachment WP_List_Table
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @param   string $view_name  View Name.
	 */
	public function output_list_table_filters( $post_type, $view_name ) {

		// Bail if we're not viewing Attachments.
		if ( 'attachment' !== $post_type ) {
			return;
		}

		// Bail if we're not in the bar view.
		if ( 'bar' !== $view_name ) {
			return;
		}

		// Determine the current orderby.
		if ( isset( $_REQUEST['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Get from the <select> dropdown.
			$current_orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		} elseif ( $this->base->get_class( 'settings' )->get_setting( 'user-options', 'orderby_enabled' ) ) {
			// Get orderby default from the User's Options, if set to persist.
			$current_orderby = $this->base->get_class( 'user_option' )->get_orderby( get_current_user_id() );
		} else {
			// Get from Plugin Defaults.
			$current_orderby = $this->base->get_class( 'common' )->get_orderby_default();
		}

		// Determine the current order.
		if ( isset( $_REQUEST['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Get from the <select> dropdown.
			$current_order = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		} elseif ( $this->base->get_class( 'settings' )->get_setting( 'user-options', 'order_enabled' ) ) {
			// Get orderby default from the User's Options, if set to persist.
			$current_order = $this->base->get_class( 'user_option' )->get_order( get_current_user_id() );
		} else {
			// Get from Plugin Defaults.
			$current_order = $this->base->get_class( 'common' )->get_order_default();
		}

		// Taxonomy Filters.
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Skip if this Taxonomy Filter isn't enabled.
			if ( ! $this->base->get_class( 'settings' )->get_setting( 'general', $taxonomy_name . '_enabled' ) ) {
				continue;
			}

			echo $this->get_list_table_category_filter( $taxonomy_name, sanitize_text_field( $taxonomy['plural_name'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Outputs Taxonomy Filters and Sorting in the Attachment WP_List_Table
		 *
		 * @since   1.1.1
		 */
		do_action( 'media_library_organizer_media_output_list_table_filters' );

		// Order By and Order Filters.
		if ( $this->base->get_class( 'settings' )->get_setting( 'general', 'orderby_enabled' ) ||
			$this->base->get_class( 'settings' )->get_setting( 'general', 'order_enabled' ) ) {

			include $this->base->plugin->folder . '/views/global/media-list-view-order.php';

		}
	}

	/**
	 * Fetches the List Table Category Filter <select> dropdown
	 *
	 * @since   1.2.6
	 *
	 * @param   string $taxonomy_name      Taxonomy Name.
	 * @param   string $taxonomy_label     Taxonomy Label, Plural.
	 * @return  string                     HTML Select Output
	 */
	public function get_list_table_category_filter( $taxonomy_name, $taxonomy_label ) {

		$taxonomy_filter_args = array(
			'show_option_all'   => sprintf(
				/* translators: Taxonomy Label, Plural */
				__( 'All %s', 'media-library-organizer' ),
				$taxonomy_label
			),
			'show_option_none'  => __( '(Unassigned)', 'media-library-organizer' ),
			'option_none_value' => -1,
			'orderby'           => 'name',
			'order'             => 'ASC',
			'show_count'        => true,
			'hide_empty'        => false,
			'echo'              => false,
			'selected'          => $this->get_selected_terms_slugs( $taxonomy_name ),
			'hierarchical'      => true,
			'name'              => $taxonomy_name,
			'id'                => $taxonomy_name,
			'taxonomy'          => $taxonomy_name,
			'value_field'       => 'slug',
		);

		// If logged in as an Administrator, prevent PublishPress Permissions from attempting to filter Term counts,
		// otherwise they will display as zero for Administrators (other User Roles are unaffected).
		if ( is_user_logged_in() && 'administrator' === wp_get_current_user()->roles[0] ) {
			$taxonomy_filter_args['pp_no_filter'] = true;
		}

		/**
		 * Define the wp_dropdown_categories() compatible arguments for the Media Categories Taxonomy Filter
		 * in the Media Library List View
		 *
		 * @since   1.1.1
		 *
		 * @param   array   $taxonomy_filters_args      wp_dropdown_categories() compatible arguments.
		 * @param   string  $taxonomy_name              Taxonomy Name.
		 */
		$taxonomy_filter_args = apply_filters( 'media_library_organizer_media_output_list_table_filters_taxonomy_filter_args', $taxonomy_filter_args, $taxonomy_name );

		// Filter the output of wp_dropdown_categories().
		add_filter( 'wp_dropdown_cats', array( $this, 'output_list_table_filters_taxonomy' ), 10, 2 );

		$output = wp_dropdown_categories( $taxonomy_filter_args );

		// Remove filter for output of wp_dropdown_categories(), so we don't affect any other calls to this function.
		remove_filter( 'wp_dropdown_cats', array( $this, 'output_list_table_filters_taxonomy' ) );

		return $output;
	}

	/**
	 * Filters the HTML output produced by wp_dropdown_categories() in the Media Library List View
	 * immediately before it's output.
	 *
	 * @since   1.2.2
	 *
	 * @param   string $output     wp_dropdown_categories() HTML <select> output.
	 * @param   array  $args       wp_dropdown_categories() arguments.
	 * @return  string              wp_dropdown_categories() HTML <select> output
	 */
	public function output_list_table_filters_taxonomy( $output, $args ) {

		/**
		 * Filters the HTML output produced by wp_dropdown_categories() in the Media Library List View
		 * immediately before it's output.
		 *
		 * @since   1.2.2
		 *
		 * @param   string  $output     wp_dropdown_categories() HTML <select> output
		 * @param   array   $args       wp_dropdown_categories() arguments
		 * @return  string              wp_dropdown_categories() HTML <select> output
		 */
		$output = apply_filters( 'media_library_organizer_media_output_list_table_filters_taxonomy', $output, $args );

		return $output;
	}

	/**
	 * Enqueues JS and CSS whenever wp_enqueue_media() is called, which is used for
	 * any media upload, management or selection screens / views.
	 *
	 * Also Outputs Taxonomy Filters and Sorting in the Attachment Backbone Grid View
	 * (wp.media.view.AttachmentsBrowser)
	 *
	 * @since   1.0.0
	 */
	public function enqueue_js_css() {

		// Determine whether to load minified JS.
		$ext = ( $this->base->dashboard->should_load_minified_js() ? 'min' : '' );

		// JS: Register selectize.
		$this->register_selectize_js_css( $ext );

		// Iterate through Registered Taxonomies.
		// Build Taxonomies data, including each Taxonomy's Terms and Selected Term(s).
		$taxonomies = array();
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			$taxonomies[ $taxonomy_name ] = array(
				'terms'         => $this->base->get_class( 'common' )->get_terms_hierarchical( $taxonomy_name ),
				'taxonomy'      => get_taxonomy( $taxonomy_name ),
				'selected_term' => $this->get_selected_terms_slugs( $taxonomy_name ),
			);
		}

		// JS: Enqueue.
		wp_enqueue_script( $this->base->plugin->name . '-media', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'media' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'media-editor', 'media-views' ), $this->base->plugin->version, true );
		wp_localize_script(
			$this->base->plugin->name . '-media',
			'media_library_organizer_media',
			array(
				'order'                 => $this->base->get_class( 'common' )->get_order_options(),
				'orderby'               => $this->base->get_class( 'common' )->get_orderby_options(),
				'settings'              => $this->base->get_class( 'settings' )->get_settings( 'general' ),
				'taxonomies'            => $taxonomies,
				'show_attachment_count' => $this->show_attachment_count(),

				// Default Values for orderby and order, based on either the User Defaults or the Plugin / WordPress Defaults.
				'defaults'              => array(
					'orderby' => (
						$this->base->get_class( 'settings' )->get_setting( 'user-options', 'orderby_enabled' ) ?
						$this->base->get_class( 'user_option' )->get_orderby( get_current_user_id() ) :
						$this->base->get_class( 'common' )->get_orderby_default()
					),
					'order'   => (
						$this->base->get_class( 'settings' )->get_setting( 'user-options', 'order_enabled' ) ?
						$this->base->get_class( 'user_option' )->get_order( get_current_user_id() ) :
						$this->base->get_class( 'common' )->get_order_default()
					),
				),

				// Media View (list|grid).
				'media_view'            => Media_Library_Organizer()->get_class( 'common' )->get_media_view(),

				// Add New Taxonomy Term.
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'create_term'           => array(
					'action' => 'media_library_organizer_add_term',
					'nonce'  => wp_create_nonce( 'media_library_organizer_add_term' ),
				),

				// Get Taxonomies Terms.
				'get_taxonomies_terms'  => array(
					'action' => 'media_library_organizer_get_taxonomies_terms',
					'nonce'  => wp_create_nonce( 'media_library_organizer_get_taxonomies_terms' ),
				),

				// Get Taxonomy Terms.
				'get_taxonomy_terms'    => array(
					'action' => 'media_library_organizer_get_taxonomy_terms',
					'nonce'  => wp_create_nonce( 'media_library_organizer_get_taxonomy_terms' ),
				),

				// Labels.
				'labels'                => array(
					'unassigned' => __( '(Unassigned)', 'media-library-organizer' ),
				),
			)
		);

		// JS: Register.
		wp_register_script( $this->base->plugin->name . '-modal', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'modal' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'media-editor', 'media-views' ), $this->base->plugin->version, true );

		// CSS.
		wp_enqueue_style( $this->base->plugin->name . '-media', $this->base->plugin->url . 'assets/css/media.css', array(), $this->base->plugin->version );
		wp_enqueue_style( 'wpzinc-admin-selectize' );

		/**
		 * Enqueue JS and CSS for Media Views.
		 *
		 * @since   1.0.7
		 *
		 * @param   string  $ext    If defined, output minified JS and CSS
		 */
		do_action( 'media_library_organizer_media_enqueue_js_css', $ext );
	}

	/**
	 * Registers selectize JS, ready for enqueuing.
	 *
	 * As both wp_enqueue_media, admin_enqueue_scripts and wp_enqueue_scripts hooks
	 * might enqueue selectize, each of the above hooks calls this function to ensure
	 * that selectize is readily available.
	 *
	 * @since   1.3.2
	 *
	 * @param   string $ext    Whether to load minified versions or not.
	 */
	public function register_selectize_js_css( $ext ) {

		// If selectize is already registered, bail as we don't need to register it again.
		if ( wp_script_is( $this->base->plugin->name . '-selectize', 'registered' ) ) {
			return;
		}

		// JS: Register.
		wp_register_script( $this->base->plugin->name . '-selectize', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'selectize' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'wpzinc-admin-selectize', 'jquery', 'jquery-ui-sortable' ), $this->base->plugin->version, true );

		// Define the selectize DOM selectors.
		$selectize_selectors = array(
			'simple'   => array(
				'.media-library-organizer-selectize',
			),
			'multiple' => array(
				'.media-library-organizer-selectize-multiple',
			),
			'ajax'     => array(
				'.media-library-organizer-selectize-search',
			),
		);

		/**
		 * Defines the selectize DOM selectors for various Selectize JS instances, such as
		 * Simple and AJAX implementations.
		 *
		 * @since   1.1.1
		 *
		 * @param   array   $selectize_selectors    Selectize DOM Selectors.
		 */
		$selectize_selectors = apply_filters( 'media_library_organizer_media_enqueue_js_css_selectize_selectors', $selectize_selectors );

		// JS: Localize.
		wp_localize_script(
			$this->base->plugin->name . '-selectize',
			'media_library_organizer_selectize',
			array(
				'selectors' => $selectize_selectors,
			)
		);

		// CSS: Register.
		wp_register_style( 'wpzinc-admin-selectize', $this->base->dashboard->dashboard_url . 'css/selectize.css', array(), $this->base->plugin->version );
	}

	/**
	 * Defines the Columns to display in the List View WP_List_Table.
	 *
	 * @since   1.1.4
	 *
	 * @param   array $columns        Columns.
	 * @param   bool  $is_detached    Is Attachment Detached.
	 * @return  array                   Columns
	 */
	public function define_list_view_columns( $columns, $is_detached = false ) {

		/**
		 * Defines the Columns to display in the List View WP_List_Table.
		 *
		 * @since   1.1.4
		 *
		 * @param   array   $columns        Columns.
		 * @param   bool    $is_detached    Is Attachment Detached.
		 * @return  array                   Columns
		 */
		$columns = apply_filters( 'media_library_organizer_media_define_list_view_columns', $columns, $is_detached );

		// Return.
		return $columns;
	}

	/**
	 * Defines the data to display in the List View WP_List_Table Column, for the given column
	 * and Attachment.
	 *
	 * @since   1.1.4
	 *
	 * @param   string $column_name    Column Name.
	 * @param   int    $id             Attachment ID.
	 */
	public function define_list_view_columns_output( $column_name, $id ) {

		// Assume there's nothing to output.
		$output = '';

		/**
		 * Defines the data to display in the List View WP_List_Table Column, for the given column
		 * and Attachment.
		 *
		 * @since   1.2.5
		 *
		 * @param   string  $output         Output.
		 * @param   string  $column_name    Column Name.
		 * @param   int     $id             Attachment ID.
		 * @return  string                  Output
		 */
		$output = apply_filters( 'media_library_organizer_media_define_list_view_columns_output', $output, $column_name, $id );

		/**
		 * Defines the data to display in the List View WP_List_Table Column, for the given column
		 * and Attachment.
		 *
		 * @since   1.1.4
		 *
		 * @param   string  $output         Output.
		 * @param   int     $id             Attachment ID.
		 * @return  string                  Output
		 */
		$output = apply_filters( 'media_library_organizer_media_define_list_view_columns_output_' . $column_name, $output, $id );

		// Output.
		echo wp_kses_post( $output ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Extends the functionality of the Media Views WP_Query, by adding support
	 * for the following filter options this Plugin provides:
	 * - Apply the orderby User Option, if supplied
	 * - Apply the order User Option, if supplied
	 * - No Taxonomy Term assigned
	 *
	 * @since   1.0.0
	 *
	 * @param   array $args   WP_Query Arguments.
	 * @return  array           WP_Query Arguments
	 */
	public function filter_attachments_grid( $args ) {

		// Update the orderby and order User Options.
		if ( isset( $args['orderby'] ) ) {
			$this->base->get_class( 'user_option' )->update_option( get_current_user_id(), 'orderby', $args['orderby'] );
		}
		if ( isset( $args['order'] ) ) {
			$this->base->get_class( 'user_option' )->update_option( get_current_user_id(), 'order', $args['order'] );
		}

		// Iterate through Registered Taxonomies.
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Don't filter the query if our Taxonomy is not set.
			if ( ! isset( $args[ $taxonomy_name ] ) ) {
				continue;
			}

			// Don't filter the query if our Taxonomy Term isn't -1 (i.e. Unassigned).
			$term = sanitize_text_field( $args[ $taxonomy_name ] );
			if ( '-1' !== $term && -1 !== $term ) {
				continue;
			}

			// Unset the Taxonomy query var, as we'll be using tax_query.
			unset( $args[ $taxonomy_name ] );

			// Register tax_query if it doesn't exist.
			if ( ! isset( $args['tax_query'] ) ) {
				$args['tax_query'] = array();
			}

			// Filter the query to include Attachments with no Term.
			$args['tax_query'][] = array(
				'taxonomy' => $taxonomy_name,
				'operator' => 'NOT EXISTS',
			);
		}

		// wp_ajax_query_attachments() doesn't attribute $_REQUEST['query'] attributes to $args that aren't Post or Taxonomy Related.
		// Include these in the below filter so they're accessible to Addons which might need to read the request input to modify $args.
		$query = isset( $_REQUEST['query'] ) ? map_deep( $_REQUEST['query'], 'sanitize_text_field' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		/**
		 * Defines the arguments used when querying for Media in the Media Grid View.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $args   WP_Query compatible arguments.
		 * @param   array   $query  Request query, which may include custom attributes not included in $args.
		 */
		$args = apply_filters( 'media_library_organizer_media_filter_attachments_grid', $args, $query );

		// Add a filter for the WHERE clause on Attachments.
		add_filter( 'posts_where', array( $this, 'filter_attachments_where_clause' ), 10, 2 );

		// Return.
		return $args;
	}

	/**
	 * Extends the functionality of the Media Views WP_Query used in the List View, by adding support
	 * for the following filter options this Plugin provides:
	 * - Apply the orderby User Option, if supplied
	 * - Apply the order User Option, if supplied
	 * - No Taxonomy Term assigned
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Query $query  WP_Query Object.
	 * @return  WP_Query            WP_Query Object
	 */
	public function filter_attachments_list( $query ) {

		// Bail if on the frontend site.
		if ( ! is_admin() ) {
			return $query;
		}

		// Bail if we can't get the current screen.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $query;
		}

		// Get current screen.
		$screen = get_current_screen();

		// Bail if we're not on the Upload screen.
		if ( ! $screen ) {
			return $query;
		}
		if ( 'upload' !== $screen->id ) {
			return $query;
		}

		// Bail if we're not in List Mode.
		$mode = $this->base->get_class( 'common' )->get_media_view();
		if ( 'list' !== $mode ) {
			return $query;
		}

		// Don't filter the query if we're unable to determine the post type.
		if ( ! isset( $query->query['post_type'] ) ) {
			return $query;
		}

		// Don't filter the query if we're not querying for attachments.
		if ( is_array( $query->query['post_type'] ) && ! in_array( 'attachment', $query->query['post_type'], true ) ) {
			return $query;
		}
		if ( ! is_array( $query->query['post_type'] ) && strpos( $query->query['post_type'], 'attachment' ) === false ) {
			return $query;
		}

		// Sanitize request.
		$request = map_deep( $_REQUEST, 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification

		// File Type.
		if ( isset( $request['mlo-file-type'] ) ) {
			$file_type       = $request['mlo-file-type'];
			$post_mime_types = get_post_mime_types();
			$filter_applied  = false;

			foreach ( array_keys( $post_mime_types ) as $type ) {
				if ( "post_mime_type:$type" === $file_type ) {
					$query->set( 'post_mime_type', $type );
					$query->query['post_mime_type'] = $type;
					$filter_applied                 = true;
					break;
				}
			}

			if ( ! $filter_applied ) {
				/**
				 * Filter Attachments by File Type, if a Filter has not yet been applied.
				 *
				 * @since   1.1.1
				 *
				 * @param   WP_Query    $query      WordPress Query.
				 * @param   string      $file_type  File Type.
				 */
				$query = apply_filters( 'media_library_organizer_media_filter_attachments_list_file_type', $query, $file_type );
			}

			// Unattached.
			if ( sanitize_text_field( $request['mlo-file-type'] ) === 'detached' ) {
				$query->set( 'post_parent', 0 );
				$query->query['post_parent'] = 0;
			}

			// Mine.
			if ( sanitize_text_field( $request['mlo-file-type'] ) === 'mine' ) {
				$query->set( 'author', get_current_user_id() );
				$query->query['author'] = get_current_user_id();
			}
		}

		// Order By: Get / set User Options, if enabled.
		if ( isset( $request['orderby'] ) ) {
			// Store the chosen filter in the User's Options, if set to persist.
			if ( $this->base->get_class( 'settings' )->get_setting( 'user-options', 'orderby_enabled' ) ) {
				$this->base->get_class( 'user_option' )->update_option( get_current_user_id(), 'orderby', $request['orderby'] );
			}
		} else {
			// Get orderby default from the User's Options, if set to persist.
			if ( $this->base->get_class( 'settings' )->get_setting( 'user-options', 'orderby_enabled' ) ) {
				$orderby = $this->base->get_class( 'user_option' )->get_orderby( get_current_user_id() );
			} else {
				// Get from Plugin Defaults.
				$orderby = $this->base->get_class( 'common' )->get_orderby_default();
			}

			// Update WP_Query with the orderby parameter.
			$query->set( 'orderby', $orderby );
			$query->query['orderby'] = $orderby;
		}

		// Order: Get / set User Options, if enabled.
		if ( isset( $request['order'] ) ) {
			// Store the chosen filter in the User's Options, if set to persist.
			if ( $this->base->get_class( 'settings' )->get_setting( 'user-options', 'order_enabled' ) ) {
				$this->base->get_class( 'user_option' )->update_option( get_current_user_id(), 'order', $request['order'] );
			}
		} else {
			// Get orderby default from the User's Options, if set to persist.
			if ( $this->base->get_class( 'settings' )->get_setting( 'user-options', 'order_enabled' ) ) {
				$order = $this->base->get_class( 'user_option' )->get_order( get_current_user_id() );
			} else {
				// Get from Plugin Defaults.
				$order = $this->base->get_class( 'common' )->get_order_default();
			}

			// Update WP_Query with the order parameter.
			$query->set( 'order', $order );
			$query->query['order'] = $order;
		}

		// Iterate through Registered Taxonomies.
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Don't filter the query if our Taxonomy is not set.
			if ( ! isset( $request[ $taxonomy_name ] ) ) {
				continue;
			}

			// Don't filter the query if our Taxonomy Term isn't -1 (i.e. Unassigned).
			$term = $request[ $taxonomy_name ];
			if ( '-1' !== $term && -1 !== $term ) {
				continue;
			}

			// Unset the Taxonomy query var, as we'll be using tax_query.
			unset( $query->query_vars[ $taxonomy_name ] );

			// Filter the query to include Attachments with no Term.
			if ( ! isset( $tax_query ) ) {
				$tax_query = array();
			}
			$tax_query[] = array(
				'taxonomy' => $taxonomy_name,
				'operator' => 'NOT EXISTS',
			);
		}

		// If a Taxonomy Query exists, Assign it to both the WP_Query and tax_query objects.
		if ( isset( $tax_query ) ) {
			$query->set( 'tax_query', $tax_query );
			$query->tax_query = new WP_Tax_Query( $tax_query );
		}

		/**
		 * Defines the arguments used when querying for Media in the Media List View.
		 *
		 * @since   1.0.7
		 *
		 * @param   WP_Query    $query      WordPress Query object.
		 * @param   array       $request    Sanitized $_REQUEST data.
		 */
		$query = apply_filters( 'media_library_organizer_media_filter_attachments', $query, $request );

		// Add a filter for the WHERE clause on Attachments.
		add_filter( 'posts_where', array( $this, 'filter_attachments_where_clause' ), 10, 2 );

		// Return.
		return $query;
	}

	/**
	 * Filters Attachments in both List and Grid views by allowing the WHERE clause
	 * to be altered
	 *
	 * @since   1.3.4
	 *
	 * @param   string   $where  WHERE Clause.
	 * @param   WP_Query $query  WordPress Search Query.
	 * @return  string               WHERE Clause
	 */
	public function filter_attachments_where_clause( $where, $query ) {

		/**
		 * Filters Attachments in both List and Grid views by allowing the WHERE clause
		 * to be altered
		 *
		 * @since   1.3.4
		 *
		 * @param   string      $where  WHERE Clause.
		 * @param   WP_Query    $query  WordPress Search Query.
		 */
		$where = apply_filters( 'media_library_organizer_media_filter_attachments_where_clause', $where, $query );

		// Return.
		return $where;
	}

	/**
	 * Adds Meta Boxes to the Attachment Edit view.
	 *
	 * @since   1.0.9
	 *
	 * @param   WP_Post $post   Attachment Post.
	 */
	public function attachment_edit_form_fields( $post ) {

		/**
		 * Adds Meta Boxes to the Attachment Edit view.
		 *
		 * @since   1.0.9
		 *
		 * @param   WP_Post     $post   Attachment Post.
		 */
		do_action( 'media_library_organizer_media_attachment_edit_form_fields', $post );
	}

	/**
	 * Save Meta Box Fields when saving an Attachment.
	 *
	 * @since   1.0.9
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function attachment_edit_save_fields( $post_id ) {

		/**
		 * Save Meta Box fields when saving an Attachment
		 *
		 * @since   1.0.9
		 *
		 * @param   int $post_id    Post ID.
		 */
		do_action( 'media_library_organizer_media_attachment_save_fields', $post_id );
	}

	/**
	 * Adds the Taxonomy as a checkbox list in the Attachment Modal view.
	 *
	 * By default, WordPress adds all Taxonomies as input[type=text] when editing an attachment.
	 *
	 * This defines the options and values for the Taxonomy, ensuring the field is output
	 * as checkboxes when using the Backbone Modal view.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $form_fields    Attachment Form Fields.
	 * @param   WP_Post $post           Attachment Post.
	 * @return  array                       Attachment Form Fields
	 */
	public function attachment_edit_modal_form_fields( $form_fields, $post = null ) {

		// Don't add Media Categories if the current user can't edit this Attachment.
		if ( ! is_null( $post ) && ! current_user_can( 'edit_post', $post->ID ) ) {
			return $form_fields;
		}

		// Don't add Media Categories if the current user can't edit any Attachments.
		if ( is_null( $post ) && ! current_user_can( 'edit_post' ) ) {
			return $form_fields;
		}

		// Determine the current screen we're on.
		$screen = get_current_screen();

		// Bail if we're on the attachment post screen, as this screen outputs
		// taxonomies form fo correctly.
		if ( isset( $screen->base ) && 'post' === $screen->base ) {
			return $form_fields;
		}

		// Iterate through Registered Taxonomies.
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Define the Taxonomy Field as a Checkbox list.
			$form_fields[ $taxonomy_name ] = array(
				'label' => $taxonomy['plural_name'],
				'input' => 'html',
				'html'  => $this->terms_checkbox_modal( $taxonomy_name, $post ),
			);
		}

		/**
		 * Defines the fields to display when editing an Attachment in the modal.
		 *
		 * @since   1.0.9
		 *
		 * @param   array       $form_fields    Form Fields.
		 * @param   WP_Post     $post           Attachment Post.
		 */
		$form_fields = apply_filters( 'media_library_organizer_media_attachment_edit_modal_form_fields', $form_fields, $post );

		// Return.
		return $form_fields;
	}


	/**
	 * Similar to post_categories_meta_box(), but returns the output
	 * instead of immediately outputting it for Backbone Modal views.
	 *
	 * @since   1.0.0
	 *
	 * @param   string       $taxonomy_name  Taxonomy Name.
	 * @param   bool|WP_Post $post           Post (false  = no Post).
	 * @return  string                          Taxonomy HTML Checkboxes
	 */
	public function terms_checkbox_modal( $taxonomy_name, $post = false ) {

		// Define Post ID.
		$post_id = ( ! $post ? 0 : $post->ID );

		// Get Taxonomy.
		$taxonomy = $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name );

		// Get Parent Taxonomy Dropdown Args.
		$parent_dropdown_args = array(
			'taxonomy'         => $taxonomy_name,
			'hide_empty'       => 0,
			'name'             => 'new' . $taxonomy_name . '_parent',
			'orderby'          => 'name',
			'hierarchical'     => 1,
			'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;',
		);

		// Build HTML Output, using our custom Taxonomy Walker for wp_terms_checklist().
		$html = '
        <div id="taxonomy-' . $taxonomy_name . '" class="categorydiv">
            <div id="' . $taxonomy_name . '-all" class="tabs-panel">
                <ul id="' . $taxonomy_name . 'checklist" class="categorychecklist"> ' .
					$this->base->get_class( 'taxonomies' )->get_terms_checklist(
						$post_id,
						array(
							'taxonomy' => $taxonomy_name,
							'echo'     => false,
							'walker'   => new Media_Library_Organizer_Taxonomy_Walker(),
						)
					)
					. '
                </ul>
            </div>';

		// Include Add New Taxonomy option, if the user has the capability to do this.
		if ( current_user_can( $taxonomy->cap->edit_terms ) ) {
			$html .= '
            <div id="mlo-taxonomy-term-add">
                <a href="#" class="taxonomy-add-new" data-taxonomy="' . $taxonomy_name . '">' .
					/* translators: %s: Add New taxonomy label. */
					sprintf( __( '+ %s', 'media-library-organizer' ), $taxonomy->labels->add_new_item ) . '
                </a>
                <div class="mlo-taxonomy-term-add-fields hidden ' . $taxonomy_name . '">
                    <input type="text" name="' . $taxonomy_name . '-add" placeholder="' . esc_attr( $taxonomy->labels->new_item_name ) . '" value="" />
                    <input type="button" data-taxonomy="' . $taxonomy_name . '" class="button" value="' . __( 'Add', 'media-library-organizer' ) . '" />
                </div>
            </div>';
		}

		$html .= '</div>';

		// Return output.
		return $html;
	}

	/**
	 * Similar to post_tags_meta_box() but returns the output
	 * instead of immediately outputting it for Backbone Modal views.
	 *
	 * @since   1.3.2
	 *
	 * @param   string       $taxonomy_name  Taxonomy Name.
	 * @param   bool|WP_Post $post           Post (false  = no Post).
	 * @return  string                          Taxonomy HTML Tag Box
	 */
	public function terms_tag_modal( $taxonomy_name, $post = false ) {

		// Define Post ID.
		$post_id = ( ! $post ? 0 : $post->ID );

		// Get Taxonomy.
		$taxonomy              = $this->base->get_class( 'taxonomies' )->get_taxonomy( $taxonomy_name );
		$user_can_assign_terms = current_user_can( $taxonomy->cap->assign_terms );
		$comma                 = _x( ',', 'tag delimiter', 'media-library-organizer' );
		$terms                 = get_the_terms( $post_id, $taxonomy_name );

		// Define HTML.
		$html = '
        <div id="' . $taxonomy_name . '" class="tagsdiv">
            <div class="jaxtag">';

		if ( $user_can_assign_terms ) {
			$html .= '
            <div class="ajaxtag hide-if-no-js">
                <label class="screen-reader-text" for="new-tag-' . $taxonomy_name . '">' . $taxonomy->labels->add_new_item . '</label>
                <input data-wp-taxonomy="' . $taxonomy_name . '" type="text" id="new-tag-' . $taxonomy_name . '" name="' . $taxonomy_name . '_newtag" class="newtag form-input-tip" size="16" autocomplete="off" aria-describedby="new-tag-' . $taxonomy_name . '-desc" value="" />
                <input type="button" class="button tagadd" value="' . __( 'Add', 'media-library-organizer' ) . '" />
            </div>
            <p class="howto" id="new-tag-' . $taxonomy_name . '-desc">' . $taxonomy->labels->separate_items_with_commas . '</p>';
		}

		$html .= '
            </div>
            <ul class="tagchecklist" role="list">';

		// Output existing Tags, if any exist.
		if ( $terms ) {
			foreach ( $terms as $index => $term ) {
				$html .= '<li>
                    <button type="button" id="' . $taxonomy_name . '-check-num-' . $index . '" class="ntdelbutton">
                        <span class="remove-tag-icon" aria-hidden="true"></span>
                        <span class="screen-reader-text">Remove term: ' . $term->name . '</span>
                    </button>
                    ' . $term->name . '
                    <input type="hidden" name="' . $taxonomy_name . '[' . $term->term_id . ']" value="1" />
                </li>';
			}
		}

		$html .= '
            </ul>
        </div>';

		// Return output.
		return $html;
	}

	/**
	 * Similar to post_categories_meta_box(), but returns the output
	 * instead of immediately outputting it for non-Modal views.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $taxonomy_name                  Taxonomy Name.
	 * @param   string $field_name                     Field Name.
	 * @param   array  $selected_term_ids              Selected Term IDs.
	 * @return  string                                      Taxonomy HTML Checkboxes
	 */
	public function terms_checkbox( $taxonomy_name, $field_name, $selected_term_ids = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Get Taxonomy Terms.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy_name,
				'hide_empty' => false,
			)
		);

		// Build HTML Output, using our custom Taxonomy Walker for wp_terms_checklist().
		$html = '
        <div id="taxonomy-' . $taxonomy_name . '" class="categorydiv">
            <div class="tax-selection">
                <div class="tabs-panel" style="height: 70px;">
                    <ul class="list:category categorychecklist form-no-clear" style="margin: 0; padding: 0;">' .
						$this->base->get_class( 'taxonomies' )->get_terms_checklist(
							0,
							array(
								'taxonomy' => $taxonomy_name,
								'echo'     => false,
							),
							$field_name
						)
						. '
                    </ul>
                </div>
            </div>
        </div>';

		// Return output.
		return $html;
	}

	/**
	 * Saves the Terms to the Attachment when we're in the Attachment Modal or Quick Edit view.
	 *
	 * Because the Backbone Modal view doesn't support field names of e.g. attachments[post_id][taxonomy],
	 * we send the data as $_REQUEST['taxonomy_termID'] - so the $attachment argument is of no use to us.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $post             Attachment Post.
	 * @param   array $attachment       Attachment $_POST['attachment'] data (not used).
	 * @return  array                       Attachment Post
	 */
	public function attachment_edit_modal_save_fields( $post, $attachment ) {

		// Determine the current screen we're on.
		$screen = get_current_screen();

		// Bail if we're on the attachment post screen, as this screen saves
		// the taxonomy correctly.
		if ( isset( $screen->base ) && 'post' === $screen->base ) {
			return $post;
		}

		// Sanitize request.
		$request = map_deep( $_REQUEST, 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification

		// Iterate through Registered Taxonomies.
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Build an array of Term IDs that have been selected.
			$term_ids = array();

			foreach ( $request as $key => $value ) {
				// Sanitize the key.
				$key = sanitize_text_field( $key );

				// Skip if the key doesn't contain our taxonomy name.
				if ( strpos( $key, $taxonomy_name . '_' ) === false ) {
					continue;
				}

				// Extract the Term ID.
				list( $prefix, $term_id ) = explode( '_', $key );

				// Add the Term ID to the array, as an integer.
				$term_ids[] = absint( $term_id );
			}

			// If no Term IDs exist, delete all Terms associated with this Attachment.
			if ( empty( $term_ids ) ) {
				wp_delete_object_term_relationships( $post['ID'], $taxonomy_name );
				continue;
			}

			// Term IDs were selected, so associate them with this Attachment.
			wp_set_object_terms( $post['ID'], $term_ids, $taxonomy_name, false );
		}

		/**
		 * Save form field data from the Attachment modal or Quick Edit Form against the Attachment.
		 *
		 * @since   1.0.9
		 *
		 * @param   array     $post         Attachment Post.
		 * @param   array     $attachment   Attachment $_POST['attachment'] data (not used).
		 * @param   array     $request      Sanitized Form Fields Data
		 */
		do_action( 'media_library_organizer_media_attachment_edit_modal_save_fields', $post, $attachment, $request );

		// Return.
		return $post;
	}

	/**
	 * Registers Backbone Views for wp.media.Modal
	 *
	 * @since   1.0.7
	 */
	public function print_media_templates() {

		// Register the container views for the modal content and sidebar.
		require_once $this->base->plugin->folder . '/views/admin/media-views.php';

		/**
		 * Register Backbone Views for wp.media.Modal
		 *
		 * @since   1.0.7
		 */
		do_action( 'media_library_organizer_media_print_media_templates' );
	}

	/**
	 * Perform actions in the Media Library footer
	 *
	 * @since   1.1.1
	 */
	public function media_library_footer() {

		/**
		 * Perform actions in the Media Library footer
		 *
		 * @since   1.1.1.
		 */
		do_action( 'media_library_organizer_media_media_library_footer' );
	}

	/**
	 * Returns the selected term ID, covering several different ways that WordPress might
	 * filter by Taxonomy Term:
	 * - List View: $taxonomy_name=slug (via Dropdown or Tree View)
	 * - List View: taxonomy=$taxonomy_name&term=slug (via WP_List_Table)
	 * - Grid View: $taxonomy_name=slug (via Tree View)
	 *
	 * @since   1.1.4
	 *
	 * @param   string $taxonomy_name  Taxonony Name.
	 * @return  mixed                   false | int
	 */
	public function get_selected_terms_ids( $taxonomy_name ) {

		// Get selected Terms.
		$selected_terms = $this->get_selected_terms( $taxonomy_name );

		// Bail if no selected Terms.
		if ( ! $selected_terms ) {
			return false;
		}

		// If more than one Term selected, return ids for all selected Terms.
		if ( is_array( $selected_terms ) ) {
			$ids = array();
			foreach ( $selected_terms as $selected_term ) {
				$ids[] = ( $selected_term->term_id );
			}

			return $ids;
		}

		return absint( $selected_terms->term_id );
	}

	/**
	 * Returns the selected term slug, covering several different ways that WordPress might
	 * filter by Taxonomy Term:
	 * - List View: $taxonomy_name=slug (via Dropdown or Tree View)
	 * - List View: $taxonomy_name[]=slug&$taxonomy_name[]=anotherslug (via Dropdown)
	 * - List View: taxonomy=$taxonomy_name&term=slug (via WP_List_Table)
	 * - Grid View: $taxonomy_name=slug (via Tree View)
	 *
	 * @since   1.1.4
	 *
	 * @param   string $taxonomy_name  Taxonony Name.
	 * @return  mixed                   false | string
	 */
	public function get_selected_terms_slugs( $taxonomy_name ) {

		// Get selected Terms.
		$selected_terms = $this->get_selected_terms( $taxonomy_name );

		// Bail if no selected Terms.
		if ( ! $selected_terms ) {
			return false;
		}

		// If more than one Term selected, return slugs for all selected Terms.
		if ( is_array( $selected_terms ) ) {
			$slugs = array();
			foreach ( $selected_terms as $selected_term ) {
				$slugs[] = $selected_term->slug;
			}

			return implode( ',', $slugs );
		}

		return $selected_terms->slug;
	}

	/**
	 * Returns the selected term, covering several different ways that WordPress might
	 * filter by Taxonomy Term:
	 * - List View: $taxonomy_name=slug (via Dropdown or Tree View)
	 * - List View: $taxonomy_name[]=slug&$taxonomy_name[]=anotherslug (via Dropdown)
	 * - List View: taxonomy=$taxonomy_name&term=slug (via WP_List_Table)
	 * - Grid View: $taxonomy_name=slug (via Tree View)
	 *
	 * @since   1.1.4
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @return  mixed                   false | array of WP_Term | single WP_Term
	 */
	public function get_selected_terms( $taxonomy_name ) {

		// Assume no Term is selected.
		$selected_terms = false;

		// Sanitize request.
		$request = map_deep( $_REQUEST, 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification

		// Check some request variables.
		if ( isset( $request[ $taxonomy_name ] ) ) {
			$selected_terms = $request[ $taxonomy_name ];
		}

		if ( isset( $request['taxonomy'] ) && isset( $request['term'] ) ) {
			if ( $request['taxonomy'] === $taxonomy_name ) {
				$selected_terms = $request['term'];
			}
		}

		// If no Term has been selected, bail.
		if ( ! $selected_terms ) {
			return false;
		}

		// If more than one Term was selected, return an array of Terms.
		if ( is_array( $selected_terms ) ) {
			$terms = array();
			foreach ( $selected_terms as $selected_term ) {
				// If Term is numeric, get Term by ID.
				if ( is_numeric( $selected_term ) ) {
					$terms[] = get_term_by( 'term_id', absint( $selected_term ), $taxonomy_name );
					continue;
				}

				// Get Term by Slug.
				$terms[] = get_term_by( 'slug', $selected_term, $taxonomy_name );
			}

			return $terms;
		}

		// A single Term was selected.
		// If Term is numeric, get Term by ID.
		if ( is_numeric( $selected_terms ) ) {
			return get_term_by( 'term_id', absint( $selected_terms ), $taxonomy_name );
		}

		// Get Term by Slug.
		return get_term_by( 'slug', $selected_terms, $taxonomy_name );
	}

	/**
	 * Whether to show Attachment counts for Terms on Taxonomy <select> dropdowns.
	 *
	 * We don't display these outside of WordPress Admin > Media, because the Attachment count
	 * will include all file types, meaning it's inaccurate when e.g. selecting a Featured Image
	 * for a Page/Post, as the media view will be restricted to images.
	 *
	 * Calculating counts by MIME type is expensive, so it's better not to show the count.
	 *
	 * @since   1.4.1
	 *
	 * @return  bool    Show Attachment Count
	 */
	public function show_attachment_count() {

		// By default, don't show attachment count for each Term.
		$show_attachment_count = false;

		// Return if we can't determine the current screen.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $show_attachment_count;
		}

		// Get screen.
		$screen = get_current_screen();

		// Return if we can't determine the current screen.
		if ( is_null( $screen ) ) {
			return $show_attachment_count;
		}

		// Depending on the WordPress Admin screen, show or hide term counts.
		switch ( $screen->base ) {
			case 'upload':
				$show_attachment_count = true;
				break;

			default:
				$show_attachment_count = false;
				break;
		}

		/**
		 * Whether to show Attachment counts for Terms on Taxonomy select dropdowns.
		 *
		 * We don't display these outside of WordPress Admin > Media, because the Attachment count
		 * will include all file types, meaning it's inaccurate when e.g. selecting a Featured Image
		 * for a Page/Post, as the media view will be restricted to images.
		 *
		 * Calculating counts by MIME type is expensive, so it's better not to show the count.
		 *
		 * @since   1.4.1
		 *
		 * @param   bool        $show_attachment_count  Show Attachment Count.
		 * @param   WP_Screen   $screen                 WordPress Screen object.
		 */
		$show_attachment_count = apply_filters( 'media_library_organizer_media_show_attachment_count', $show_attachment_count, $screen );

		// Return.
		return $show_attachment_count;
	}
}
