<?php
/**
 * Media class.
 *
 * @package   Media_Library_Organizer_Output
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Output_Media' ) ) {

	/**
	 * Modifies output in the Media Library's list and grid views,
	 * based on the Addon's settings.
	 *
	 * @package   Media_Library_Organizer_Output
	 * @author    WP Media Library
	 * @version   1.1.3
	 */
	class Media_Library_Organizer_Output_Media {

		/**
		 * Holds the base class object.
		 *
		 * @since   1.1.3
		 *
		 * @var     object
		 */
		public $base;

		/**
		 * Constructor
		 *
		 * @since   1.1.3
		 *
		 * @param   object $base    Base Plugin Class.
		 */
		public function __construct( $base ) {

			// Store base class.
			$this->base = $base;

			add_action( 'media_library_organizer_media_enqueue_js_css', array( $this, 'enqueue_js_css' ) );

			add_filter( 'media_library_infinite_scrolling', array( $this, 'maybe_enable_infinite_scroll' ) );

			// Use Output Settings to determine which List View Columns to display.
			// Low priority so this runs last.
			add_filter( 'media_library_organizer_media_define_list_view_columns', array( $this, 'define_list_view_columns' ), 999, 1 );

			// Define data to output on List View Columns.
			add_filter( 'media_library_organizer_media_define_list_view_columns_output', array( $this, 'define_list_view_columns_output' ), 999, 3 );
		}

		/**
		 * Enqueues JS and CSS when loading a media view
		 *
		 * @since   1.1.3
		 *
		 * @param   string $ext    If defined, output minified JS and CSS.
		 */
		public function enqueue_js_css( $ext ) {

			// JS.
			wp_enqueue_script(
				$this->base->plugin->name . '-media',
				$this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'media' . ( $ext ? '-' . $ext : '' ) . '.js',
				array( 'media-editor', 'media-views' ),
				Media_Library_Organizer()->plugin->version,
				true
			);
			wp_localize_script(
				$this->base->plugin->name . '-media',
				'media_library_organizer_output',
				array(
					'settings' => array(
						'grid_size'     => Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'output', 'grid_size' ),
						'preview_hover' => Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'output', 'preview_hover' ),
					),
				)
			);

			// CSS.
			wp_enqueue_style( $this->base->plugin->name . '-media', $this->base->plugin->url . '/assets/css/media.css', array(), Media_Library_Organizer()->plugin->version );
		}

		/**
		 * For WordPress 5.8 and higher, enables or disables infinite scroll for the Media Library Grid View,
		 * depending on the Addon settings
		 *
		 * @since   1.3.2
		 *
		 * @return  bool    Infinite Scroll
		 */
		public function maybe_enable_infinite_scroll() {

			if ( Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'output', 'infinte_scroll' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Returns output size options for Media Library Grids
		 *
		 * @since   1.1.3
		 *
		 * @return  array   Output Grid Sizes
		 */
		public function get_output_grid_sizes() {

			return array(
				'xsmall' => __( 'Extra Small', 'media-library-organizer' ),
				'small'  => __( 'Small', 'media-library-organizer' ),
				''       => __( 'Medium (WordPress Default)', 'media-library-organizer' ),
				'large'  => __( 'Large', 'media-library-organizer' ),
				'xlarge' => __( 'Extra Large', 'media-library-organizer' ),
			);
		}

		/**
		 * Returns List View columns that are supported for the Media Library List View
		 *
		 * @since   1.2.4
		 *
		 * @return  array   List View Columns
		 */
		public function get_supported_list_view_columns() {

			// Define List View columns that can be enabled or disabled through the Plugin Settings.
			$columns = array(
				'tree-view-move' => __( 'Tree View: Categorize / Move Icon', 'media-library-organizer' ),
				'author'         => __( 'Author', 'media-library-organizer' ),
			);

			// Add Taxonomies.
			foreach ( Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
				$columns[ 'taxonomy-' . $taxonomy_name ] = $taxonomy['plural_name'];
			}

			// Add other Columns.
			$columns = array_merge(
				$columns,
				array(
					'parent'             => __( 'Uploaded to', 'media-library-organizer' ),
					'comments'           => __( 'Comments', 'media-library-organizer' ),
					'date'               => __( 'Date', 'media-library-organizer' ),
					'mlo_alt_text'       => __( 'Alt Text', 'media-library-organizer' ),
					'mlo_caption'        => __( 'Caption', 'media-library-organizer' ),
					'mlo_description'    => __( 'Description', 'media-library-organizer' ),
					'mlo_slug'           => __( 'Slug', 'media-library-organizer' ),
					'mlo_file_extension' => __( 'File Extension', 'media-library-organizer' ),
					'mlo_file_type'      => __( 'File Type', 'media-library-organizer' ),
					'mlo_file_mime'      => __( 'File MIME', 'media-library-organizer' ),
					'mlo_file_size'      => __( 'File Size', 'media-library-organizer' ),
					'mlo_dimensions'     => __( 'Dimensions', 'media-library-organizer' ),
					'mlo_width'          => __( 'Width', 'media-library-organizer' ),
					'mlo_height'         => __( 'Height', 'media-library-organizer' ),
					'mlo_attachment_id'  => __( 'Attachment ID', 'media-library-organizer' ),
					'mlo_url'            => __( 'URL', 'media-library-organizer' ),
				)
			);

			/**
			 * Returns List View columns that are supported for the Media Library List View,
			 * which the user can enable or disable in the Plugin's Settings.
			 *
			 * @since   1.2.4
			 *
			 * @return  array   List View Columns
			 */
			$columns = apply_filters( 'media_library_organizer_output_media_get_supported_list_view_columns', $columns );

			// Return.
			return $columns;
		}

		/**
		 * Defines the Columns to display in the List View WP_List_Table
		 *
		 * @since   1.2.4
		 *
		 * @param   array $columns        Columns.
		 * @return  array                   Columns
		 */
		public function define_list_view_columns( $columns ) {

			// Get Settings.
			$supported_list_view_columns = $this->get_supported_list_view_columns();
			$enabled_list_view_columns   = Media_Library_Organizer()->get_class( 'settings' )->get_setting( 'output', 'list_view_columns' );

			// Add any other enabled columns.
			foreach ( $supported_list_view_columns as $column => $label ) {
				// Skip if this column isn't enabled for display.
				if ( ! in_array( $column, $enabled_list_view_columns, true ) ) {
					continue;
				}

				// Skip if the column already exists, so we don't overwrite WordPress' efforts.
				if ( isset( $columns[ $column ] ) ) {
					continue;
				}

				// Add column.
				$columns[ $column ] = $label;
			}

			return $columns;
		}

		/**
		 * Defines the data to display in the List View WP_List_Table Column, for the given column
		 * and Attachment
		 *
		 * @since   1.2.4
		 *
		 * @param   string $output         Output.
		 * @param   string $column_name    Column Name.
		 * @param   int    $id             Attachment ID.
		 * @return  string|int             Output
		 */
		public function define_list_view_columns_output( $output, $column_name, $id ) {

			// Get file information.
			$file       = get_attached_file( $id );
			$path_parts = pathinfo( $file );
			$meta       = wp_get_attachment_metadata( $id );
			$attachment = new Media_Library_Organizer_Attachment( $id );

			switch ( $column_name ) {

				case 'mlo_alt_text':
					return $attachment->get_alt_text();

				case 'mlo_caption':
					return $attachment->get_caption();

				case 'mlo_description':
					return $attachment->get_description();

				case 'mlo_slug':
					return get_permalink( $id );

				case 'mlo_file_extension':
					return $path_parts['extension'];

				case 'mlo_file_type':
					return Media_Library_Organizer()->get_class( 'mime' )->get_file_type( $id );

				case 'mlo_file_mime':
					return Media_Library_Organizer()->get_class( 'mime' )->get_file_type( $id ) . '/' . $path_parts['extension'];

				case 'mlo_file_size':
					return size_format( filesize( $file ) );

				case 'mlo_dimensions':
					return $meta ? $meta['width'] . ' x ' . $meta['height'] . ' ' . __( 'pixels', 'media-library-organizer' ) : '';

				case 'mlo_width':
					return ( isset( $meta['width'] ) ? $meta['width'] . ' ' . __( 'pixels', 'media-library-organizer' ) : '' );

				case 'mlo_height':
					return ( isset( $meta['height'] ) ? $meta['height'] . ' ' . __( 'pixels', 'media-library-organizer' ) : '' );

				case 'mlo_attachment_id':
					return $id;

				case 'mlo_url':
					return wp_get_attachment_url( $id );

				default:
					return $output;
			}
		}
	}
}
