<?php
/**
 * MIME class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Defines various file types for easy identification,
 * such as images, video, audio and documents.
 *
 * @since   1.0.5
 */
class Media_Library_Organizer_MIME {

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
	 * Returns the file type (image, video, audio, document)
	 * of the given Attachment ID
	 *
	 * @since   1.0.5
	 *
	 * @param   int $attachment_id  Attachment ID.
	 * @return  bool|string         File Type
	 */
	public function get_file_type( $attachment_id ) {

		// Get all file types.
		$file_types = $this->get_all_file_types();

		// Determine file type.
		$filename = get_attached_file( $attachment_id );

		// Bail if the file doesn't exist.
		if ( ! $filename ) {
			return false;
		}

		// Get MIME type.
		$mime = mime_content_type( $filename );

		// Iterate through the file types until we match the mime.
		foreach ( $file_types as $file_type => $mimes ) {
			if ( in_array( $mime, $mimes, true ) ) {
				return $file_type;
			}
		}

		// If here, we couldn't determine the file type.
		return false;
	}

	/**
	 * Defines top level file types, such as Images, Videos,
	 * Audio and Documents.
	 *
	 * @since   1.0.5
	 *
	 * @return  array   File Types
	 */
	public function get_file_types() {

		// Define File Types.
		$file_types = array(
			'image'        => __( 'Images', 'media-library-organizer' ),
			'video'        => __( 'Videos', 'media-library-organizer' ),
			'audio'        => __( 'Audio', 'media-library-organizer' ),
			'text'         => __( 'Text', 'media-library-organizer' ),
			'document'     => __( 'Documents', 'media-library-organizer' ),
			'spreadsheet'  => __( 'Spreadsheets', 'media-library-organizer' ),
			'presentation' => __( 'Presentations', 'media-library-organizer' ),
			'archive'      => __( 'Archives', 'media-library-organizer' ),
			'other'        => __( 'Other', 'media-library-organizer' ),
		);

		/**
		 * Defines top level file types, such as Images, Videos,
		 * Audio and Documents.
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines all supported file types, grouped by file type
	 *
	 * @since   1.0.5
	 *
	 * @return  array   File Types
	 */
	public function get_all_file_types() {

		// Get all available file types.
		$file_types = array(
			'image'        => $this->get_image_file_types(),
			'video'        => $this->get_video_file_types(),
			'audio'        => $this->get_audio_file_types(),
			'text'         => $this->get_text_file_types(),
			'document'     => $this->get_document_file_types(),
			'spreadsheet'  => $this->get_spreadsheet_file_types(),
			'presentation' => $this->get_presentation_file_types(),
			'archive'      => $this->get_archive_file_types(),
		);

		/**
		 * Defines all file types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     All File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_all_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines image file types
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Image File Types
	 */
	public function get_image_file_types() {

		// Define File Types.
		$file_types = array(
			'image/jpg',
			'image/jpeg',
			'image/jpe',
			'image/gif',
			'image/png',
			'image/bmp',
			'image/tiff',
			'image/tif',
			'image/ico',
		);

		/**
		 * Defines image file types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     Image File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_image_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines video file types
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Video File Types
	 */
	public function get_video_file_types() {

		// Define File Types.
		$file_types = array(
			'video/x-ms-asf',
			'video/x-ms-wmv',
			'video/x-ms-wmx',
			'video/x-ms-wm',
			'video/avi',
			'video/divx',
			'video/x-flv',
			'video/quicktime',
			'video/mpeg',
			'video/mp4',
			'video/ogg',
			'video/webm',
			'video/x-matroska',
			'video/3gpp',  // Can also be audio.
			'video/3gpp2', // Can also be audio.
		);

		/**
		 * Defines video file types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     Video File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_video_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines text file types
	 *
	 * @since   1.1.1
	 *
	 * @return  array   Text File Types
	 */
	public function get_text_file_types() {

		// Define File Types.
		$file_types = array(
			'text/plain',
			'text/csv',
			'text/tab-separated-values',
			'text/calendar',
			'text/richtext',
			'text/css',
			'text/html',
			'text/vtt',
			'application/ttaf+xml',
		);

		/**
		 * Defines text file types
		 *
		 * @since   1.1.1
		 *
		 * @param   array   $file_types     Text File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_text_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines audio file types
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Audio File Types
	 */
	public function get_audio_file_types() {

		// Define File Types.
		$file_types = array(
			'audio/mpeg',
			'audio/aac',
			'audio/x-realaudio',
			'audio/wav',
			'audio/ogg',
			'audio/flac',
			'audio/midi',
			'audio/x-ms-wma',
			'audio/x-ms-wax',
			'audio/x-matroska',
		);

		/**
		 * Defines audio file types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     Audio File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_audio_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines document file types
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Document File Types
	 */
	public function get_document_file_types() {

		// Define File Types.
		$file_types = array(
			'application/msword',
			'application/vnd.ms-write',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-word.document.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'application/vnd.ms-word.template.macroEnabled.12',

			'application/onenote',
			'application/oxps',
			'application/vnd.ms-xpsdocument',

			'application/vnd.oasis.opendocument.text',

			'application/wordperfect',

			'application/vnd.apple.pages',
		);

		/**
		 * Defines image types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     Document File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_document_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines spreadsheet file types
	 *
	 * @since   1.1.1
	 *
	 * @return  array   Spreadsheet File Types
	 */
	public function get_spreadsheet_file_types() {

		// Define File Types.
		$file_types = array(
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-excel.sheet.macroEnabled.12',
			'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'application/vnd.ms-excel.template.macroEnabled.12',
			'application/vnd.ms-excel.addin.macroEnabled.12',
			'application/vnd.oasis.opendocument.spreadsheet',
			'application/vnd.apple.numbers',
		);

		/**
		 * Defines image types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     Spreadsheet File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_spreadsheet_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines presentation file types
	 *
	 * @since   1.1.1
	 *
	 * @return  array   Presentation File Types
	 */
	public function get_presentation_file_types() {

		// Define File Types.
		$file_types = array(
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.template',
			'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'application/vnd.ms-powerpoint.slide.macroEnabled.12',
			'application/vnd.oasis.opendocument.presentation',
			'application/vnd.apple.keynote',
		);

		/**
		 * Defines image types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     Presentation File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_presentation_file_types', $file_types );

		// Return.
		return $file_types;
	}

	/**
	 * Defines archive file types
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Archive File Types
	 */
	public function get_archive_file_types() {

		// Define File Types.
		$file_types = array(
			'application/x-tar',
			'application/zip',
			'application/x-gzip',
			'application/rar',
			'application/x-7z-compressed',
		);

		/**
		 * Defines archive file types
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $file_types     Archive File Types.
		 */
		$file_types = apply_filters( 'media_library_organizer_mime_get_archive_file_types', $file_types );

		// Return.
		return $file_types;
	}
}
