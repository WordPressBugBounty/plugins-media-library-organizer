<?php
/**
 * Upload class.
 *
 * @package   Media_Library_Organizer_BQE
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Upload' ) ) {

	/**
	 * Apply default settings to uploaded Attachments, if no
	 * values are specified for a given Attachment field.
	 *
	 * @package   Media_Library_Organizer_BQE
	 * @author    WP Media Library
	 * @version   1.1.0
	 */
	class Media_Library_Organizer_Defaults_Upload {

		/**
		 * Holds the base class object.
		 *
		 * @since   1.1.0
		 *
		 * @var     object
		 */
		public $base;

		/**
		 * Constructor
		 *
		 * @since   1.1.0
		 *
		 * @param   object $base    Base Plugin Class.
		 */
		public function __construct( $base ) {

			// Store base class.
			$this->base = $base;

			add_filter( 'media_library_organizer_upload_filter_new_attachment_data_before_save', array( $this, 'maybe_remove_attachment_title_on_new_attachment' ), 10, 1 );
			add_action( 'media_library_organizer_upload_add_attachment', array( $this, 'maybe_apply_defaults' ), 999 );
		}

		/**
		 * Removes the Attachment Post's Title immediately before the Attachment is saved or updated
		 * in the Media Library, if the Title is just the filename
		 *
		 * This ensures that later functions e.g. maybe_apply_defaults() can apply a Title
		 *
		 * @since   1.2.0
		 *
		 * @param   array $data               Slashed, sanitized, and processed attachment post data.
		 * @return  array                       Attachment Post Data
		 */
		public function maybe_remove_attachment_title_on_new_attachment( $data ) {

			// Bail if there's no Post Title.
			if ( empty( $data['post_title'] ) ) {
				return $data;
			}

			// If the Post Title was generated from the filename, make it blank.
			$post_title = strtolower( str_replace( ' ', '-', $data['post_title'] ) ); // Spaces are replaced by hyphens by WP in the uploaded file.
			$filename   = strtolower( wp_basename( $data['guid'] ) );
			$filename   = substr( $filename, 0, strrpos( $filename, '.' ) );

			if ( strpos( $filename, $post_title ) !== false ) {
				$data['post_title'] = '';
			}

			return $data;
		}

		/**
		 * Applies default settings to the newly uploaded file if defined and required,
		 * after all other Addons have run their actions on the attachment.
		 *
		 * @since   1.1.0
		 *
		 * @param   int $attachment_id    Attachment ID.
		 */
		public function maybe_apply_defaults( $attachment_id ) {

			// Get file type.
			$file_type = Media_Library_Organizer()->get_class( 'mime' )->get_file_type( $attachment_id );

			// Bail if the file type couldn't be identified.
			if ( ! $file_type ) {
				return;
			}

			// Fetch defaults for this File Type.
			$defaults = $this->base->get_class( 'settings' )->get_settings_by_file_type( $file_type );

			// If the defaults are empty, we don't need to do anything else.
			if ( ! array_filter( $defaults ) ) {
				return;
			}

			// If no rulesets are defined, we don't need to do anything else.
			if ( ! isset( $defaults['rulesets'] ) ) {
				return;
			}

			// Get attachment.
			$attachment = new Media_Library_Organizer_Attachment( $attachment_id );

			// Iterate through rulesets until one applies.
			foreach ( $defaults['rulesets'] as $ruleset ) {
				if ( -1 === $ruleset['all_rules_must_pass'] ) {
					// Apply the values in this ruleset to the attachment and exit.
					$this->apply_defaults( $ruleset, $attachment, $file_type );
					break;
				}

				// If no rules exist, apply the values in this ruleset to the attachment and exit.
				if ( ! is_array( $ruleset['rules'] ) || ! count( $ruleset['rules'] ) ) {
					$this->apply_defaults( $ruleset, $attachment, $file_type );
					break;
				}

				// Test ruleset.
				$ruleset_passed = $this->base->get_class( 'ruleset' )->ruleset_passed( $ruleset['rules'], $attachment, $attachment_id, $ruleset['all_rules_must_pass'] );
				if ( $ruleset_passed ) {
					// Apply the values in this ruleset to the attachment and exit.
					$this->apply_defaults( $ruleset, $attachment, $file_type );
					break;
				}
			}

			// Destroy the class.
			unset( $attachment );
		}

		/**
		 * Applies the given default values to the given Attachment
		 *
		 * @since   1.1.6
		 *
		 * @param   array                              $defaults       Default Values.
		 * @param   Media_Library_Organizer_Attachment $attachment     Attachment.
		 * @param   string                             $file_type      File Type.
		 */
		private function apply_defaults( $defaults, $attachment, $file_type ) {

			// Define attachment data.
			if ( ! $attachment->has_title() ) {
				$attachment->set_title( Media_Library_Organizer()->get_class( 'dynamic_tags' )->parse( $defaults['title'], $attachment ) );
			}
			if ( ! $attachment->has_caption() ) {
				$attachment->set_caption( Media_Library_Organizer()->get_class( 'dynamic_tags' )->parse( $defaults['caption'], $attachment ) );
			}
			if ( ! $attachment->has_description() ) {
				$attachment->set_description( Media_Library_Organizer()->get_class( 'dynamic_tags' )->parse( $defaults['description'], $attachment ) );
			}
			if ( ! $attachment->has_alt_text() && $file_type === 'image' ) {
				$attachment->set_alt_text( Media_Library_Organizer()->get_class( 'dynamic_tags' )->parse( $defaults['alt_text'], $attachment ) );
			}

			// Define attachment Taxonomy Terms.
			foreach ( Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
				// If no defaults exist, skip.
				if ( ! isset( $defaults[ $taxonomy_name ] ) ) {
					continue;
				}
				if ( empty( $defaults[ $taxonomy_name ] ) ) {
					continue;
				}

				// If the Attachment already has Term(s) specified for this Taxonomy e.g. through the Bulk and Quick Edit Form,
				// don't apply default Terms.
				if ( $attachment->has_terms( $taxonomy_name ) ) {
					continue;
				}

				// No Terms have yet been specified for this Taxonomy, so apply the default Terms now.
				$attachment->set_terms( $taxonomy_name, $defaults[ $taxonomy_name ] );
			}

			// Update the Attachment.
			$attachment->update();

			/**
			 * Applies the given default values to the given Attachment
			 *
			 * @since   1.2.2
			 *
			 * @param   array                               $defaults       Default Values.
			 * @param   Media_Library_Organizer_Attachment  $attachment     Attachment.
			 */
			do_action( 'media_library_organizer_defaults_upload_apply_defaults_' . $file_type, $defaults, $attachment );

			// Destroy the class.
			unset( $attachment );
		}
	}
}
