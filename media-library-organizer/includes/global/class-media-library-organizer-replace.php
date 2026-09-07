<?php
/**
 * MLO Attachment Replace class.
 *
 * @package Media_Library_Organizer
 */

/**
 * Media_Library_Organizer_Replace
 *
 * Handles replacing an attachment file while preserving metadata and updating references.
 */
class Media_Library_Organizer_Replace {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * File.
	 *
	 * @var array
	 */
	private $file;

	/**
	 * Attachment.
	 *
	 * @var \Media_Library_Organizer_Attachment_Model
	 */
	private $attachment;

	/**
	 * New attachment.
	 *
	 * @var \Media_Library_Organizer_Attachment_Model
	 */
	private $new_attachment;

	/**
	 * Constructor.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $file          File.
	 */
	public function __construct( $attachment_id, $file ) {
		$this->attachment_id = $attachment_id;
		$this->file          = $file;
		$this->attachment    = new Media_Library_Organizer_Attachment_Model( $attachment_id );

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
	}

	/**
	 * Replace the attachment.
	 *
	 * @return bool|WP_Error
	 */
	public function replace() {
		// Check for upload errors.
		if ( isset( $this->file['error'] ) && UPLOAD_ERR_OK !== $this->file['error'] ) {
			return new WP_Error( 'upload_error', __( 'File upload error.', 'media-library-organizer' ) );
		}

		if ( ! file_exists( $this->file['tmp_name'] ) ) {
			return new WP_Error( 'file_error', __( 'Error uploading file.', 'media-library-organizer' ) );
		}

		$original_file  = $this->attachment->get_source_file_path();
		$old_sizes_urls = $this->attachment->get_all_image_sizes_urls();

		if ( ! file_exists( $original_file ) ) {
			return new WP_Error( 'file_error', __( 'Original file does not exist.', 'media-library-organizer' ) );
		}

		// Get the original attachment's mime type from the database.
		$original_mime = get_post_mime_type( $this->attachment_id );
		if ( ! $original_mime ) {
			return new WP_Error( 'file_error', __( 'Could not determine original file type.', 'media-library-organizer' ) );
		}

		// Validate the uploaded file's actual content and extension.
		$uploaded_filetype = wp_check_filetype_and_ext( $this->file['tmp_name'], $this->file['name'] );

		// Check if the file type validation failed.
		if ( ! $uploaded_filetype['type'] || ! $uploaded_filetype['ext'] ) {
			return new WP_Error( 'file_error', __( 'Invalid file type.', 'media-library-organizer' ) );
		}

		// Ensure the uploaded file's actual type matches the original attachment's mime type.
		if ( $uploaded_filetype['type'] !== $original_mime ) {
			return new WP_Error( 'file_error', __( 'The uploaded file type does not match the original file type.', 'media-library-organizer' ) );
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem->move( $this->file['tmp_name'], $original_file, true ) ) {
			return new WP_Error( 'file_error', __( 'Could not move file.', 'media-library-organizer' ) );
		}

		// Let plugins hooked to `wp_delete_file` (image optimizers, WebP converters) remove
		// companion files of the old original after it has been overwritten in place.
		apply_filters( 'wp_delete_file', $original_file );

		// Also notify for the old scaled version, which the metadata generation
		// below overwrites in place when the replacement is also scaled.
		if ( $this->attachment->is_scaled() ) {
			$scaled_original_file = str_replace(
				$this->attachment->get_filename_with_ext(),
				$this->attachment->get_filename_with_ext( true ),
				$original_file
			);

			if ( file_exists( $scaled_original_file ) ) {
				apply_filters( 'wp_delete_file', $scaled_original_file );
			}
		}

		$wp_filesystem->chmod( $original_file, FS_CHMOD_FILE );

		$this->remove_all_image_sizes();

		clean_attachment_cache( $this->attachment_id );

		$metadata = wp_generate_attachment_metadata( $this->attachment_id, $original_file );

		if ( isset( $metadata['sizes'] ) ) {
			$this->replace_image_sizes_links( $metadata['sizes'], $old_sizes_urls );
		}

		wp_update_attachment_metadata( $this->attachment_id, $metadata );
		$this->new_attachment = new Media_Library_Organizer_Attachment_Model( $this->attachment_id );

		$this->handle_scaled_images();

		/**
		 * Fires after an attachment file has been replaced.
		 *
		 * @since 2.1.0
		 *
		 * @param int $attachment_id Attachment ID.
		 */
		do_action( 'mlo_attachment_replaced', $this->attachment_id );

		return true;
	}

	/**
	 * Remove all image sizes files.
	 *
	 * @return void
	 */
	private function remove_all_image_sizes() {
		$all_image_sizes_paths = $this->attachment->get_all_image_sizes_paths();

		foreach ( $all_image_sizes_paths as $path ) {
			if ( file_exists( $path ) ) {
				// Use wp_delete_file() so plugins hooked to `wp_delete_file` (image optimizers,
				// WebP converters) can clean up companion files, matching core deletion behavior.
				wp_delete_file( $path );
			}
		}
	}

	/**
	 * Handle scaled images.
	 *
	 * @return bool
	 */
	private function handle_scaled_images() {
		$old_scaled = $this->attachment->is_scaled();
		$new_scaled = $this->new_attachment->is_scaled();
		$replacer   = new Media_Library_Organizer_Renamer( true );

		$new_file_path = $this->new_attachment->get_source_file_path();

		// New is scaled, but old is not scaled. We don't replace anything.
		if ( $old_scaled === $new_scaled || ( ! $old_scaled && $new_scaled ) ) {
			return true;
		}

		// Delete the old scaled version and replace scaled URLs with non-scaled URLs.
		if ( $old_scaled && ! $new_scaled ) {
			$main_file_url   = $this->attachment->get_main_url();
			$unscaled_file   = $this->attachment->get_filename_with_ext();
			$old_scaled_file = $this->attachment->get_filename_with_ext( true );
			$old_scaled_url  = str_replace( $unscaled_file, $old_scaled_file, $main_file_url );

			$replacer->replace( $old_scaled_url, $main_file_url );

			$scaled_path = str_replace( $unscaled_file, $old_scaled_file, $this->attachment->get_source_file_path() );
			if ( file_exists( $scaled_path ) ) {
				wp_delete_file( $scaled_path );
			}

			update_attached_file( $this->attachment_id, sprintf( '%s/%s', $this->attachment->get_metadata_prefix_path(), $unscaled_file ) );
		}

		return true;
	}

	/**
	 * Replace image sizes links.
	 *
	 * @param array $new_sizes      New sizes.
	 * @param array $old_sizes_urls Old sizes URLs.
	 *
	 * @return void
	 */
	private function replace_image_sizes_links( $new_sizes, $old_sizes_urls ) {
		$replacer = new Media_Library_Organizer_Renamer( true );

		foreach ( $old_sizes_urls as $size => $old_url ) {
			// If the size is not in the new sizes, we need to use the original URL.
			if ( ! isset( $new_sizes[ $size ], $new_sizes[ $size ]['file'] ) ) {
				$replacer->replace( $old_url, $this->attachment->get_main_url() );
				continue;
			}

			// If the size is in the new sizes, we need to use the new URL.
			$new_url = str_replace( $this->attachment->get_filename_with_ext(), $new_sizes[ $size ]['file'], $this->attachment->get_main_url() );
			$replacer->replace( $old_url, $new_url );
		}
	}
}
