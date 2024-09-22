<?php
/**
 * Filesystem class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Wraps WP_Filesystem with some simpler convenience functions.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Filesystem {

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
	 * Adds the given full path and file as an Attachment Post to the Media Library
	 *
	 * This also copies the full path and file to WordPress' managed uploads folder.
	 *
	 * Removes all actions that might hook to upload processes (e.g. ZIP Addon),
	 * so that the resulting Attachment isn't then uncompressed again.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $file       Full Path and Filename to add.
	 * @param   int    $post_id    Post ID to attach the Attachment to (optional).
	 * @return  mixed               WP_Error | Attachment ID
	 */
	public function add_file_as_attachment( $file, $post_id = 0 ) {

		/**
		 * Action hook to remove filters / actions that would interfere with sideloading
		 * (e.g. unzipping a sideloaded zip file upload, which we don't want to do).
		 *
		 * @since   1.0.7
		 */
		do_action( 'media_library_organizer_filesystem_add_file_as_attachment_remove_actions', $file, $post_id );

		// Sideload ZIP file as an Attachment in the Media Library.
		$file_data = array(
			'name'     => basename( $file ),
			'type'     => mime_content_type( $file ),
			'tmp_name' => $file,
			'error'    => 0,
			'size'     => filesize( $file ),
		);
		$overrides = array(
			'test_form' => false,
			'test_size' => true,
		);

		// Adds a file to the WordPress Media Library in the same way as the uploader, except
		// we supply the data.
		$result = media_handle_sideload( $file_data, $post_id );

		/**
		 * Action hook to restore filters / actions that might have been disabled on the
		 * media_library_organizer_filesystem_add_file_as_attachment_remove_actions action.
		 *
		 * @since   1.0.7
		 */
		do_action( 'media_library_organizer_filesystem_add_file_as_attachment_restore_actions', $file, $post_id, $result );

		// Return Result.
		return $result;
	}

	/**
	 * Returns the path to the temporary folder, creating it if
	 * it does not exist.
	 *
	 * @since   1.0.0
	 *
	 * @param   bool|string $sub_folder     Sub Folder to include, optional.
	 * @return  WP_Error|string             WP_Error object or Temporary Folder Path
	 */
	public function get_tmp_folder( $sub_folder = false ) {

		global $wp_filesystem;

		// Initialize WP Filesystem, if it hasn't yet been initialized.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Get upload folder.
		$upload_dir = wp_upload_dir();

		// Define the temporary folder to use.
		$temporary_folder = $upload_dir['basedir'] . '/media-library-organizer-temp';

		/**
		 * Defines the temporary server folder location to use for file operations.
		 *
		 * @since   1.0.7
		 *
		 * @param   string  $temporary_folder   Full Path to Temporary Folder
		 * @param   string  $sub_folder         Relative Path to optional Sub Folder (relative to Temporary Folder)
		 */
		$temporary_folder = apply_filters( 'media_library_organizer_common_get_tmp_folder', $temporary_folder, $sub_folder );

		// Create temporary folder, if it doesn't exist.
		if ( ! $wp_filesystem->is_dir( $temporary_folder ) ) {
			$result = $wp_filesystem->mkdir( $temporary_folder );

			// If an error occured, bail.
			if ( ! $result ) {
				return new WP_Error(
					'media_library_organizer_common_get_tmp_folder',
					sprintf(
						/* translators: Folder path and name */
						__( 'Could not create temporary folder at %s', 'media-library-organizer' ),
						$temporary_folder
					)
				);
			}
		}

		// If no requirement for a subfolder, return the temporary folder name now.
		if ( ! $sub_folder ) {
			return $temporary_folder;
		}

		// Add the sub folder to the temporary folder path.
		$temporary_folder = $temporary_folder . '/' . $sub_folder;

		// Create temporary sub folder, if it doesn't exist.
		if ( ! $wp_filesystem->is_dir( $temporary_folder ) ) {
			$result = $wp_filesystem->mkdir( $temporary_folder );

			// If an error occured, bail.
			if ( ! $result ) {
				return new WP_Error(
					'media_library_organizer_common_get_tmp_folder',
					sprintf(
						/* translators: Folder path and name */
						__( 'Could not create temporary folder at %s', 'media-library-organizer' ),
						$temporary_folder
					)
				);
			}
		}

		// Return temporary folder path.
		return $temporary_folder;
	}

	/**
	 * Deletes the given folder, including all contents.
	 *
	 * Mainly used for deleting temporary folders created using get_tmp_folder() above.
	 *
	 * If the folder path doesn't point to somewhere side the wp-content/uploads folder,
	 * the deletion won't happen - this protects malicious code deleting core WordPress files.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $folder     Folder to delete.
	 * @return  mixed               WP_Error | bool
	 */
	public function delete_folder( $folder ) {

		global $wp_filesystem;

		// Initialize WP Filesystem, if it hasn't yet been initialized.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Get upload folder.
		$upload_dir = wp_upload_dir();

		// If the folder for deletion's path isn't within wp-content/uploads, bail.
		if ( strpos( $folder, $upload_dir['basedir'] ) === false ) {
			return new WP_Error(
				'media_library_organizer_common_delete_folder',
				sprintf(
					/* translators: %1$s: Folder Name, %2$s: WordPress Uploads Folder Path */
					__( 'Cannot delete %1$s, as it is not within %2$s.', 'media-library-organizer' ),
					$folder,
					$upload_dir['basedir']
				)
			);
		}

		// Delete the folder.
		$result = $wp_filesystem->rmdir( $folder, true );
		if ( ! $result ) {
			return new WP_Error(
				'media_library_organizer_common_delete_folder',
				sprintf(
					/* translators: Folder Name */
					__( 'Unable to delete %s', 'media-library-organizer' ),
					$folder
				)
			);
		}

		// OK.
		return true;
	}
}
