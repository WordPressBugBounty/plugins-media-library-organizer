<?php
/**
 * Attachment class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Represents an existing Attachment in the Media Library.
 *
 * @since   1.0.5
 */
class Media_Library_Organizer_Attachment {

	/**
	 * Holds the Attachment ID
	 *
	 * @since   1.0.6
	 *
	 * @var     int
	 */
	private $attachment_id;

	/**
	 * Holds the Attachment Title
	 *
	 * @since   1.0.6
	 *
	 * @var     string
	 */
	private $title;

	/**
	 * Holds the Attachment Alt Text
	 *
	 * @since   1.0.6
	 *
	 * @var     string
	 */
	private $alt_text;

	/**
	 * Holds the Attachment Caption
	 *
	 * @since   1.0.6
	 *
	 * @var     string
	 */
	private $caption;

	/**
	 * Holds the Attachment Description
	 *
	 * @since   1.0.6
	 *
	 * @var     string
	 */
	private $description;

	/**
	 * Holds the Attachment Terms
	 *
	 * @since   1.0.6
	 *
	 * @var     array
	 */
	private $terms;

	/**
	 * Holds the filename.
	 *
	 * @since   1.5.1
	 *
	 * @var     string
	 */
	private $filename;

	/**
	 * Creates a new Attachment object, representing the given Attachment ID.
	 *
	 * @since   1.0.5
	 *
	 * @param   int $attachment_id  Attachment ID.
	 */
	public function __construct( $attachment_id ) {

		// Get attachment.
		$attachment = get_post( $attachment_id );

		// Store attachment data in class.
		$this->attachment_id = $attachment_id;
		$this->set_title( $attachment->post_title );
		$this->set_alt_text( get_post_meta( $this->attachment_id, '_wp_attachment_image_alt', true ) );
		$this->set_caption( $attachment->post_excerpt );
		$this->set_description( $attachment->post_content );
		$this->set_filename( basename( get_attached_file( $attachment_id ) ) );

		// Iterate through Registered Taxonomies.
		foreach ( Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			$this->set_terms(
				$taxonomy_name,
				wp_get_object_terms(
					$this->attachment_id,
					$taxonomy_name,
					array(
						'fields' => 'ids',
					)
				)
			);
		}
	}

	/**
	 * Returns the ID for the current Attachment
	 *
	 * @since   1.1.6
	 *
	 * @return  int  ID
	 */
	public function get_attachment_id() {

		return $this->attachment_id;
	}

	/**
	 * Returns the Title for the current Attachment
	 *
	 * @since   1.0.5
	 *
	 * @return  string  Title
	 */
	public function get_title() {

		return $this->title;
	}

	/**
	 * Returns the Alt Text for the current Attachment
	 *
	 * @since   1.0.5
	 *
	 * @return  string  Alt Text
	 */
	public function get_alt_text() {

		return $this->alt_text;
	}

	/**
	 * Returns the Caption for the current Attachment
	 *
	 * @since   1.0.5
	 *
	 * @return  string  Caption
	 */
	public function get_caption() {

		return $this->caption;
	}

	/**
	 * Returns the Description for the current Attachment
	 *
	 * @since   1.0.5
	 *
	 * @return  string  Description
	 */
	public function get_description() {

		return $this->description;
	}

	/**
	 * Returns the Terms for the current Attachment
	 *
	 * @since   1.0.5
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @return  array                   Terms
	 */
	public function get_terms( $taxonomy_name ) {

		return $this->terms[ $taxonomy_name ];
	}

	/**
	 * Returns the Filename for the current Attachment
	 *
	 * @since   1.1.6
	 *
	 * @return  string  Filename
	 */
	public function get_filename() {

		return $this->filename;
	}

	/**
	 * Sets the Title for the current Attachment
	 * To save the Title, subsequently call update().
	 *
	 * @since   1.0.5
	 *
	 * @param   string $title  Title.
	 */
	public function set_title( $title ) {

		$this->title = sanitize_text_field( $title );
	}

	/**
	 * Sets the Alt Text for the current Attachment
	 * To save the Alt Text, subsequently call update().
	 *
	 * @since   1.0.5
	 *
	 * @param   string $alt_text   Alt Text.
	 */
	public function set_alt_text( $alt_text ) {

		$this->alt_text = sanitize_text_field( $alt_text );
	}

	/**
	 * Sets the Caption for the current Attachment
	 * To save the Caption, subsequently call update().
	 *
	 * @since   1.0.5
	 *
	 * @param   string $caption    Caption.
	 */
	public function set_caption( $caption ) {

		$this->caption = sanitize_text_field( $caption );
	}

	/**
	 * Sets the Description for the current Attachment
	 * To save the Description, subsequently call update().
	 *
	 * @since   1.0.5
	 *
	 * @param   string $description    Description.
	 */
	public function set_description( $description ) {

		$this->description = sanitize_text_field( $description );
	}

	/**
	 * Sets the Media Categories for the current Attachment
	 * To save the Media Categories, subsequently call update().
	 *
	 * @since   1.0.5
	 *
	 * @param   string $taxonomy_name       Taxonomy Name.
	 * @param   array  $terms               Media Categories.
	 */
	public function set_terms( $taxonomy_name, $terms ) {

		// Set this Taxonomy's Terms array if it hasn't been set.
		if ( ! isset( $this->terms[ $taxonomy_name ] ) ) {
			$this->terms[ $taxonomy_name ] = array();
		}

		// Bail if no Terms were assigned.
		if ( ! is_array( $terms ) ) {
			$this->terms[ $taxonomy_name ] = array();
			return;
		}
		if ( ! count( $terms ) ) {
			$this->terms[ $taxonomy_name ] = array();
			return;
		}

		// Cast Terms as Term IDs.
		foreach ( $terms as $index => $term_id ) {
			$terms[ $index ] = absint( sanitize_text_field( $term_id ) );
		}

		// Assign Term IDs.
		$this->terms[ $taxonomy_name ] = $terms;
	}

	/**
	 * Sets the Filename for the current Attachment
	 *
	 * @since   1.1.6
	 *
	 * @param   string $filename    Filename.
	 */
	public function set_filename( $filename ) {

		$this->filename = $filename;
	}

	/**
	 * Appends Terms for the current Attachment, preseving
	 * any existing Terms.
	 *
	 * To save the Terms, subsequently call update().
	 *
	 * @since   1.1.1
	 *
	 * @param   string $taxonomy_name   Taxonomy Name.
	 * @param   array  $terms          Terms.
	 */
	public function append_terms( $taxonomy_name, $terms ) {

		// Set this Taxonomy's Terms array if it hasn't been set.
		if ( ! isset( $this->terms[ $taxonomy_name ] ) ) {
			$this->terms[ $taxonomy_name ] = array();
		}

		// Bail if no Terms were assigned.
		if ( ! is_array( $terms ) ) {
			return;
		}
		if ( ! count( $terms ) ) {
			return;
		}

		// Cast Terms as Term IDs.
		foreach ( $terms as $index => $term_id ) {
			$terms[ $index ] = absint( sanitize_text_field( $term_id ) );
		}

		// Append.
		$this->terms[ $taxonomy_name ] = array_merge( $this->terms[ $taxonomy_name ], $terms );
	}

	/**
	 * Removes all Terms for the current Attachment.
	 *
	 * To save changes, subsequently call update().
	 *
	 * @since   1.4.0
	 *
	 * @param   string $taxonomy_name   Taxonomy Name.
	 */
	public function remove_terms( $taxonomy_name ) {

		$this->terms[ $taxonomy_name ] = array( -1 );
	}

	/**
	 * Determines if the current Attachment has a Title
	 *
	 * @since   1.0.5
	 *
	 * @return  bool    Has Title
	 */
	public function has_title() {

		return ( ! empty( $this->title ) );
	}

	/**
	 * Determines if the current Attachment has Alt Text
	 *
	 * @since   1.0.5
	 *
	 * @return  bool    Has Alt Text
	 */
	public function has_alt_text() {

		return ( ! empty( $this->alt_text ) );
	}

	/**
	 * Determines if the current Attachment has a Caption
	 *
	 * @since   1.0.5
	 *
	 * @return  bool    Has Caption
	 */
	public function has_caption() {

		return ( ! empty( $this->caption ) );
	}

	/**
	 * Determines if the current Attachment has a Description
	 *
	 * @since   1.0.5
	 *
	 * @return  bool    Has Description
	 */
	public function has_description() {

		return ( ! empty( $this->description ) );
	}

	/**
	 * Determines if the current Attachment has Media Categories
	 *
	 * @since   1.0.5
	 *
	 * @param   string $taxonomy_name  Taxonomy Name.
	 * @return  bool                    Has Terms
	 */
	public function has_terms( $taxonomy_name ) {

		if ( ! isset( $this->terms[ $taxonomy_name ] ) ) {
			return false;
		}
		if ( ! count( $this->terms[ $taxonomy_name ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Updates the Attachment in the WordPress database.
	 *
	 * @since   1.0.5
	 *
	 * @return  mixed   WP_Error | bool
	 */
	public function update() {

		// Update the Post (Attachment).
		$result = wp_update_post(
			array(
				'ID'           => (int) $this->attachment_id,
				'post_title'   => $this->title,
				'post_excerpt' => $this->caption,
				'post_content' => $this->description,
			),
			true
		);

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update the Alt Tag.
		update_post_meta( $this->attachment_id, '_wp_attachment_image_alt', $this->alt_text );

		// Iterate through Registered Taxonomies.
		foreach ( Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
			// Skip if no Terms to assign to this Taxonomy.
			if ( ! $this->has_terms( $taxonomy_name ) ) {
				continue;
			}

			// If there is only a single Term assigned to this Attachment and it is -1, we need to remove all Terms
			// assigned to this Attachment.
			if ( 1 === count( $this->terms[ $taxonomy_name ] ) && -1 === $this->terms[ $taxonomy_name ][0] ) {
				// Delete Terms.
				$result = wp_set_object_terms( $this->attachment_id, '', $taxonomy_name, false );
			} else {
				// Update Terms.
				$result = wp_set_object_terms( $this->attachment_id, $this->terms[ $taxonomy_name ], $taxonomy_name, false );
			}

			// Bail if an error occured.
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}
}
