<?php
/**
 * Common class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Functions that don't particularly fit in one specific class.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Common {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.5
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.0.5
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;
	}

	/**
	 * Returns an array of Javascript DOM selectors to enable the keyword
	 * autocomplete functionality on.
	 *
	 * @since   1.1.6
	 *
	 * @return  array   Javascript DOM Selectors
	 */
	public function get_autocomplete_enabled_fields() {

		// Get fields.
		$fields = array(
			'input.wpzinc-autocomplete',
			'textarea.wpzinc-autocomplete',
		);

		/**
		 * Defines an array of Javascript DOM selectors to enable the keyword
		 * autocomplete functionality on.
		 *
		 * @since   1.1.6
		 *
		 * @param   array   $fields  Supported Fields.
		 */
		$fields = apply_filters( 'media_library_organizer_defaults_common_get_autocomplete_enabled_fields', $fields );

		// Return filtered results.
		return $fields;
	}

	/**
	 * Helper method to return the author options for WP_Query calls
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Author options
	 */
	public function get_author_options() {

		// Define array for options.
		$options = array();

		// Get users.
		$users = get_users();

		// Build options.
		foreach ( $users as $user ) {
			$options[ $user->ID ] = $user->user_login;
		}

		/**
		 * Defines the available Author options.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $options    Authors (WordPress Users).
		 */
		$options = apply_filters( 'media_library_organizer_common_get_author_options', $options );

		// Return filtered results.
		return $options;
	}

	/**
	 * Helper method to return the operator options for WP_Query calls
	 *
	 * @since   1.0.7
	 *
	 * @return  array           Operator Options
	 */
	public function get_operator_options() {

		// Build options.
		$options = array(
			'AND' => __( 'All', 'media-library-organizer' ),
			'OR'  => __( 'Any', 'media-library-organizer' ),
		);

		/**
		 * Defines the available comparison operator options.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $options    Comparison Operators
		 */
		$options = apply_filters( 'media_library_organizer_common_get_operator_options', $options );

		// Return filtered results.
		return $options;
	}

	/**
	 * Helper method to return the position options
	 *
	 * @since   1.0.7
	 *
	 * @return  array           Position Options
	 */
	public function get_position_options() {

		// Build options.
		$options = array(
			'top'        => __( 'Above', 'media-library-organizer' ),
			'bottom'     => __( 'Below', 'media-library-organizer' ),
			'top_bottom' => __( 'Above and Below', 'media-library-organizer' ),
		);

		/**
		 * Defines the available layout position options.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $options    Layout Positions
		 */
		$options = apply_filters( 'media_library_organizer_common_get_position_options', $options );

		// Return filtered results.
		return $options;
	}

	/**
	 * Helper method to return the file type options for WP_Query calls
	 *
	 * @since   1.1.1
	 *
	 * @return  array   File Type options
	 */
	public function get_file_type_options() {

		global $post_mime_types, $avail_post_mime_types;

		$options = array(
			'all' => __( 'All media items', 'media-library-organizer' ),
		);

		foreach ( $post_mime_types as $mime_type => $label ) {
			$options[ 'post_mime_type:' . esc_attr( $mime_type ) ] = $label[0];
		}

		$options['detached'] = __( 'Unattached', 'media-library-organizer' );
		$options['mine']     = _x( 'Mine', 'media items', 'media-library-organizer' );

		/**
		 * Defines the available file type options for WP_Query calls
		 *
		 * @since   1.1.1
		 *
		 * @param   array   $options                File Type Options.
		 * @param   array   $post_mime_types        Post MIME Types.
		 * @param   array   $avail_post_mime_types  Available Post MIME Types.
		 * @return  array                           File Type Options
		 */
		$options = apply_filters( 'media_library_organizer_common_get_file_type_options', $options, $post_mime_types, $avail_post_mime_types );

		// Return filtered results.
		return $options;
	}

	/**
	 * Helper method to return the orderby options for WP_Query calls
	 *
	 * @since   1.0.0
	 *
	 * @return  array   orderby options
	 */
	public function get_orderby_options() {

		// Build options.
		$options = array(
			'ID'        => __( 'Attachment ID', 'media-library-organizer' ),
			'author'    => __( 'Author (Uploader)', 'media-library-organizer' ),
			'date'      => __( 'Date', 'media-library-organizer' ),
			'name'      => __( 'Filename', 'media-library-organizer' ),
			'modified'  => __( 'Modified Date', 'media-library-organizer' ),
			'parent'    => __( 'Uploaded to', 'media-library-organizer' ),
			'title'     => __( 'Title', 'media-library-organizer' ),
			'post_date' => __( 'Uploaded Date', 'media-library-organizer' ),
		);

		/**
		 * Defines the available WP_Query compatible order by options.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $options    Order By Options.
		 */
		$options = apply_filters( 'media_library_organizer_common_get_orderby_options', $options );

		// Return filtered results.
		return $options;
	}

	/**
	 * Helper method to return the default orderby option
	 *
	 * @since   1.0.0
	 *
	 * @return  string  orderby default
	 */
	public function get_orderby_default() {

		$default = 'date';

		/**
		 * Defines the default order_by value for WP_Query queries
		 *
		 * @since   1.0.7
		 *
		 * @param   string   $default   Default order_by value for WP_Query queries.
		 */
		$default = apply_filters( 'media_library_organizer_common_get_orderby_default', $default );

		// Return filtered results.
		return $default;
	}

	/**
	 * Helper method to return the order options for WP_Query calls
	 *
	 * @since   1.0.0
	 *
	 * @return  array   order options
	 */
	public function get_order_options() {

		// Build options.
		$options = array(
			'ASC'  => __( 'Ascending (A-Z)', 'media-library-organizer' ),
			'DESC' => __( 'Descending (Z-A)', 'media-library-organizer' ),
		);

		/**
		 * Defines the available WP_Query compatible order options.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $options    Order Options.
		 */
		$options = apply_filters( 'media_library_organizer_common_get_order_options', $options );

		// Return filtered results.
		return $options;
	}

	/**
	 * Helper method to return the default order option
	 *
	 * @since   1.0.0
	 *
	 * @return  string  order default
	 */
	public function get_order_default() {

		$default = 'DESC';

		/**
		 * Defines the default order value for WP_Query queries
		 *
		 * @since   1.0.7
		 *
		 * @param   string   $default   Default order value for WP_Query queries.
		 */
		$default = apply_filters( 'media_library_organizer_common_get_order_default', $default );

		// Return filtered results.
		return $default;
	}

	/**
	 * Helper method to return the Attachment Display Settings: Alignment options
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Alignment Options
	 */
	public function get_attachment_display_settings_alignment() {

		$options = array(
			'none'   => __( 'None', 'media-library-organizer' ),
			'left'   => __( 'Left', 'media-library-organizer' ),
			'center' => __( 'Center', 'media-library-organizer' ),
			'right'  => __( 'Right', 'media-library-organizer' ),
		);

		$options = apply_filters( 'media_library_organizer_common_get_attachment_display_settings_alignment', $options );

		return $options;
	}

	/**
	 * Helper method to return the Attachment Display Settings: Link To options
	 *
	 * @since   1.0.5
	 *
	 * @param   string $file_type  File Type.
	 * @return  array               Alignment Options
	 */
	public function get_attachment_display_settings_link_to( $file_type ) {

		switch ( $file_type ) {

			/**
			 * Image.
			 */
			case 'image':
				$options = array(
					'none'   => __( 'None', 'media-library-organizer' ),
					'file'   => __( 'Media File', 'media-library-organizer' ),
					'post'   => __( 'Attachment Page', 'media-library-organizer' ),
					'custom' => __( 'Custom URL', 'media-library-organizer' ),
				);
				break;

			/**
			 * Audio.
			 */
			case 'video':
			case 'audio':
				$options = array(
					'file'  => __( 'Link to Media File', 'media-library-organizer' ),
					'embed' => __( 'Embed Media Player', 'media-library-organizer' ),
					'post'  => __( 'Link to Attachment Page', 'media-library-organizer' ),
				);
				break;

			/**
			 * Document.
			 */
			case 'document':
				$options = array(
					'none' => __( 'None', 'media-library-organizer' ),
					'file' => __( 'Media File', 'media-library-organizer' ),
					'post' => __( 'Attachment Page', 'media-library-organizer' ),
				);
				break;

			/**
			 * Other File Types.
			 */
			default:
				$options = array(
					'none' => __( 'None', 'media-library-organizer' ),
					'file' => __( 'Media File', 'media-library-organizer' ),
					'post' => __( 'Attachment Page', 'media-library-organizer' ),
				);
				break;

		}

		/**
		 * Defines the available Attachment Display Settings: Link To options.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $options    Attachment Display Settings: Link To Options.
		 */
		$options = apply_filters( 'media_library_organizer_common_get_attachment_display_settings_link_to', $options );

		// Return filtered results.
		return $options;
	}

	/**
	 * Helper method to return the Attachment Display Settings: Size options
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Alignment Options
	 */
	public function get_attachment_display_settings_size() {

		$options = apply_filters(
			'image_size_names_choose',
			array(
				'thumbnail' => __( 'Thumbnail', 'media-library-organizer' ),
				'medium'    => __( 'Medium', 'media-library-organizer' ),
				'large'     => __( 'Large', 'media-library-organizer' ),
				'full'      => __( 'Full Size', 'media-library-organizer' ),
			)
		);

		/**
		 * Defines the available WordPress registered Image Sizes.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $options    Attachment Display Settings: Link To Options.
		 */
		$options = apply_filters( 'media_library_organizer_common_get_attachment_display_settings_size', $options );

		// Return filtered results.
		return $options;
	}

	/**
	 * Returns a flat array ordered by parent > child > child.
	 * When iterated through and output, would produce structure
	 * the same as wp_dropdown_cats().
	 *
	 * @since   1.0.0
	 *
	 * @param   string $taxonomy  Taxonomy.
	 * @return  mixed               false | array of Terms
	 */
	public function get_terms_hierarchical( $taxonomy ) {

		// Build args for fetching Top Level Terms that don't have parents.
		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'parent'     => 0,
		);

		// If logged in as an Administrator, prevent PublishPress Permissions from attempting to filter Term counts,
		// otherwise they will display as zero for Administrators (other User Roles are unaffected).
		if ( is_user_logged_in() && 'administrator' === wp_get_current_user()->roles[0] ) {
			$args['pp_no_filter'] = true;
		}

		// Get Top Level Terms that don't have parents.
		$terms = get_terms( $args );

		// Bail if this fails.
		if ( is_wp_error( $terms ) ) {
			return false;
		}
		if ( empty( $terms ) ) {
			return false;
		}

		// Get hierarchy of Terms.
		// We don't use _get_term_hierarchy(), as this is a private WordPress function that returns child term IDs ordered by ID, not name.
		$hierarchy = $this->get_term_hierarchy( $taxonomy );

		// Build final term array, comprising of top level terms and all children.
		$hierarchical_terms = array();
		foreach ( $terms as $term ) {
			$hierarchical_terms[] = $term;
			$hierarchical_terms   = $this->add_child_terms_recursive( $taxonomy, $hierarchical_terms, $hierarchy, $term->term_id, 1 );
		}

		/**
		 * Defines the available hierarchical terms for the given Taxonomy.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $hierarchical_terms     Hierarchical Terms.
		 * @param   string  $taxonomy               Taxonomy.
		 * @param   array   $hierarchy              Hierarchy of child Term IDs ordered by Term Name.
		 */
		$hierarchical_terms = apply_filters( 'media_library_organizer_common_get_terms_hierarchical', $hierarchical_terms, $taxonomy, $hierarchy );

		// Return filtered results.
		return $hierarchical_terms;
	}

	/**
	 * Modified version of _get_term_hierarchy(), which returns Term IDs ordered by Term Name.
	 *
	 * @since   1.4.5
	 *
	 * @param   string $taxonomy   Taxonomy.
	 * @return  array               Child Terms
	 */
	private function get_term_hierarchy( $taxonomy ) {

		// Bail if the taxonomy is not hierarchical.
		if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
			return array();
		}

		$children = array();
		$terms    = get_terms(
			array(
				'taxonomy'               => $taxonomy,
				'get'                    => 'all',
				'orderby'                => 'name',
				'fields'                 => 'id=>parent',
				'update_term_meta_cache' => false,
			)
		);
		foreach ( $terms as $term_id => $parent ) {
			if ( $parent > 0 ) {
				$children[ $parent ][] = $term_id;
			}
		}

		return $children;
	}

	/**
	 * Recursive function to keep adding child terms through all depths until they are exhausted
	 *
	 * @since   1.0.0
	 *
	 * @param   string $taxonomy               Taxonomy.
	 * @param   array  $hierarchical_terms     Hierarchical Terms.
	 * @param   array  $hierarchy              Term ID / Child ID Hierarchy.
	 * @param   int    $current_term_id        Current Term ID.
	 * @param   int    $current_depth          Current Depth.
	 * @return  array                          Hierarchical Terms
	 */
	private function add_child_terms_recursive( $taxonomy, $hierarchical_terms, $hierarchy, $current_term_id, $current_depth ) {

		// Bail if no Children exist for the current term.
		if ( ! isset( $hierarchy[ $current_term_id ] ) ) {
			return $hierarchical_terms;
		}

		// Iterate through Child Term IDs, adding them to the array.
		foreach ( $hierarchy[ $current_term_id ] as $child_term_id ) {
			// Get the Child Term.
			$child_term = get_term( $child_term_id, $taxonomy );

			// Ignore this child term if it could not be found.
			if ( is_wp_error( $child_term ) ) {
				continue;
			}

			// Depending on its depth, pad the label.
			$child_term->name = str_pad( '', $current_depth, '-', STR_PAD_LEFT ) . ' ' . $child_term->name;

			// Assign to the flat array of hierarchical terms.
			$hierarchical_terms[] = $child_term;

			// Add Child Terms.
			$hierarchical_terms = $this->add_child_terms_recursive( $taxonomy, $hierarchical_terms, $hierarchy, $child_term_id, ( $current_depth + 1 ) );
		}

		// If here, we've finished.
		return $hierarchical_terms;
	}

	/**
	 * Returns a string to indicate the current Media View the user is on (either list or grid)
	 *
	 * @since   1.0.0
	 *
	 * @return  string  View (list|grid)
	 */
	public function get_media_view() {

		$media_view = ( get_user_option( 'media_library_mode', get_current_user_id() ) ? get_user_option( 'media_library_mode', get_current_user_id() ) : 'grid' );

		/**
		 * Defines the current Media View the user is on (either list or grid).
		 *
		 * @since   1.0.7
		 *
		 * @param   string  $media_view     Media View.
		 */
		$media_view = apply_filters( 'media_library_organizer_common_get_media_view', $media_view );

		// Return filtered result.
		return $media_view;
	}

	/**
	 * Insert an array value after the given key for the given array
	 *
	 * @since   1.1.4
	 *
	 * @param   array  $array_items Current Array.
	 * @param   string $key         Key (new array will be inserted after this key).
	 * @param   array  $new_item    Array data to insert.
	 * @return  array               New Array
	 */
	public function array_insert_after( array $array_items, $key, array $new_item ) {

		$keys  = array_keys( $array_items );
		$index = array_search( $key, $keys, true );
		$pos   = false === $index ? count( $array_items ) : $index + 1;
		return array_merge( array_slice( $array_items, 0, $pos ), $new_item, array_slice( $array_items, $pos ) );
	}

	/**
	 * Determines if the WordPress URL is a local, non-web accessible URL.
	 *
	 * @since   1.1.0
	 *
	 * @return  bool    Locally Hosted Site
	 */
	public function is_local_host() {

		// Get URL of site and its information.
		$url = wp_parse_url( get_bloginfo( 'url' ) );

		// Iterate through local host addresses to check if they exist
		// in part of the site's URL host.
		foreach ( $this->get_local_hosts() as $local_host ) {
			if ( strpos( $url['host'], $local_host ) !== false ) {
				return true;
			}
		}

		// If here, we're not on a local host.
		return false;
	}

	/**
	 * Returns an array of domains and IP addresses that are non-web accessible
	 *
	 * @since   1.1.0
	 *
	 * @return  array   Non-web accessible Domains and IP addresses
	 */
	private function get_local_hosts() {

		// If domain is 127.0.0.1, localhost or .dev, don't count it towards the domain limit
		// The user has a valid license key if they're here, so that's enough.
		// See: https://www.sqa.org.uk/e-learning/WebTech01CD/page_12.htm.
		$local_hosts = array(
			'localhost',
			'127.0.0.1',
			'10.0.',
			'192.168.',
			'.dev',
			'.local',
			'.localhost',
			'.test',
		);

		// Add 172.16.0.* to 172.16.31.*.
		for ( $i = 0; $i <= 31; $i++ ) {
			$local_hosts[] = '172.16.' . $i . '.';
		}

		return $local_hosts;
	}
}
