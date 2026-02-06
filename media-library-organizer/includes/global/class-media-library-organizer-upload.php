<?php
/**
 * Upload class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Registers actions and filters for uploading to the Media Library.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Upload {

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

		// Before the UI.
		add_action( 'pre-upload-ui', array( $this, 'output_before_upload_ui' ) );

		// After the UI.
		add_action( 'post-upload-ui', array( $this, 'output_after_upload_ui' ) );

		// Define the pluploader's options.
		add_filter( 'plupload_init', array( $this, 'plupload_options' ) );

		// Run actions just before an attachment is added.
		add_filter( 'wp_insert_attachment_data', array( $this, 'filter_attachment_data_before_save' ), 10, 2 );

		// Run actions when an attachment is uploaded.
		add_action( 'add_attachment', array( $this, 'add_attachment' ) );
	}

	/**
	 * Allows Addons to output before the HTML and JS uploaders at Media > Add New
	 *
	 * @since   1.0.5
	 */
	public function output_before_upload_ui() {

		/**
		 * Allows Addons to output before the HTML and JS uploaders at Media > Add New
		 *
		 * @since   1.0.5
		 */
		do_action( 'media_library_organizer_upload_output_before_upload_ui' );
	}

	/**
	 * Allows Addons to output after the HTML and JS uploaders at Media > Add New
	 */
	public function output_after_upload_ui() {

		/**
		 * Allows Addons to output after the HTML and JS uploaders at Media > Add New
		 */
		do_action( 'media_library_organizer_upload_output_after_upload_ui' );
	}

	/**
	 * Define the pluploader's options.
	 *
	 * @since   1.0.5
	 *
	 * @param   array $options    Plupload Options.
	 * @return  array               Plupload Options
	 */
	public function plupload_options( $options ) {

		// Iterate through Registered Taxonomies.
		$fields = array();
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Define form fields to send with file uploads.
			// The field values must be updated when the user updates the form, which is done at assets/js/upload.js.
			$fields[ $taxonomy_name ] = $this->base->get_class( 'media' )->get_selected_terms_slugs( $taxonomy_name );
		}

		// Define a multipart_params array if not defined.
		if ( ! is_array( $options['multipart_params'] ) ) {
			$options['multipart_params'] = array();
		}

		// Assign fields to multipart_params.
		$options['multipart_params']['media_library_organizer'] = $fields;

		/**
		 * Define the pluploader's options.
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $options    Plupload Options.
		 */
		$options = apply_filters( 'media_library_organizer_upload_plupload_options', $options );

		// Return.
		return $options;
	}

	/**
	 * Filters attachment post data before it is saved to the WordPress Media Library, for new
	 * and existing Attachments.
	 *
	 * @since   1.1.9
	 *
	 * @param   array $data               Slashed, sanitized, and processed attachment post data.
	 * @param   array $unprocessed_data   Slashed and sanitized attachment post data, but not processed.
	 * @return  array                       Attachment Post Data
	 */
	public function filter_attachment_data_before_save( $data, $unprocessed_data ) {

		// Determine if we're saving Attachment data for a new upload or an existing file.
		if ( isset( $data['ID'] ) && ! empty( $data['ID'] ) ) {

			/**
			 * Filters attachment post data for an existing Attachment before it is saved to the WordPress Media Library.
			 *
			 * @since   1.1.9
			 *
			 * @param   array   $data               Slashed, sanitized, and processed attachment post data.
			 * @param   array   $unprocessed_data   Slashed and sanitized attachment post data, but not processed.
			 * @param   int     $id                 Existing Attachment ID.
			 */
			$data = apply_filters( 'media_library_organizer_upload_filter_existing_attachment_data_before_save', $data, $unprocessed_data, $data['ID'] );

		} else {

			/**
			 * Filters attachment post data before a new Attachment is saved to the WordPress Media Library.
			 *
			 * @since   1.1.9
			 *
			 * @param   array   $data               Slashed, sanitized, and processed attachment post data.
			 * @param   array   $unprocessed_data   Slashed and sanitized attachment post data, but not processed.
			 */
			$data = apply_filters( 'media_library_organizer_upload_filter_new_attachment_data_before_save', $data, $unprocessed_data );

		}

		return $data;
	}

	/**
	 * Allows Addons to run actions on an attachment that has just been
	 * uploaded to the WordPress Media Library.
	 *
	 * @since   1.0.5
	 *
	 * @param   int $attachment_id  Attachment ID.
	 */
	public function add_attachment( $attachment_id ) {

		// Get Attachment.
		$attachment = new Media_Library_Organizer_Attachment( $attachment_id );

		// Iterate through Registered Taxonomies.
		foreach ( $this->base->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Conditionally set Media Categories, as they won't be included in the request if no checkboxes were selected.
			if ( ! isset( $_REQUEST['media_library_organizer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				continue;
			}

			// Fetch request.
			$request = array_map(
				function ( $r ) {
					if ( is_array( $r ) ) {
						return map_deep( $r, 'sanitize_text_field' );
					}
					return sanitize_text_field( wp_unslash( $r ) );
				},
				$_REQUEST // phpcs:ignore WordPress.Security.NonceVerification
			);
			$request = $request['media_library_organizer'];

			// Skip if the Taxonomy isn't specified.
			if ( ! isset( $request[ $taxonomy_name ] ) ) {
				continue;
			}
			if ( empty( $request[ $taxonomy_name ] ) ) {
				continue;
			}

			// Set Terms.
			$term = get_term_by( 'slug', sanitize_text_field( $request[ $taxonomy_name ] ), $taxonomy_name );
			if ( ! $term ) {
				continue;
			}

			$attachment->set_terms( $taxonomy_name, array( $term->term_id ) );
		}

		// Update the Attachment.
		$attachment->update();

		// Destroy the class.
		unset( $attachment );

		/**
		 * Allows Addons to run actions on an attachment that has just been
		 * uploaded to the WordPress Media Library
		 *
		 * @since   1.0.5
		 *
		 * @param   int     $attachment_id  Attachment ID.
		 */
		do_action( 'media_library_organizer_upload_add_attachment', $attachment_id );
	}
}
