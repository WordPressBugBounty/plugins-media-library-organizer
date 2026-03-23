<?php
/**
 * MLO Attachment Edit class.
 *
 * Main controller for the Replace Media feature.
 *
 * @package Media_Library_Organizer
 */

/**
 * Media_Library_Organizer_Edit
 *
 * Adds the Replace Media sidebar metabox, AJAX handler, and media row quick link.
 */
class Media_Library_Organizer_Edit {

	/**
	 * Initialize the attachment edit class.
	 *
	 * @return void
	 */
	public function __construct() {
		// Initialize Replace Media feature only if Optimole's media_rename is not active.
		if ( class_exists( 'Optml_Attachment_Edit' ) ) {
			return;
		}

		add_action( 'add_meta_boxes_attachment', array( $this, 'add_replace_media_metabox' ) );

		add_action( 'wp_ajax_mlo_replace_file', array( $this, 'replace_file' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'media_row_actions', array( $this, 'add_replace_action' ), 10, 2 );
	}

	/**
	 * Register the Replace Media metabox on the attachment edit screen.
	 *
	 * @param WP_Post $post The attachment post object.
	 */
	public function add_replace_media_metabox( $post ) {
		$attachment = new Media_Library_Organizer_Attachment_Model( $post->ID );

		if ( ! $attachment->can_be_replaced() ) {
			return;
		}

		add_meta_box(
			'mlo-replace-media',
			__( 'Replace Media', 'media-library-organizer' ),
			array( $this, 'render_replace_media_metabox' ),
			'attachment',
			'side',
			'core'
		);
	}

	/**
	 * Render the Replace Media metabox content.
	 *
	 * @param WP_Post $post The attachment post object.
	 */
	public function render_replace_media_metabox( $post ) {
		$attachment = new Media_Library_Organizer_Attachment_Model( $post->ID );
		echo '<div class="mlo-replace-media-metabox">';
		echo '<p class="mlo-description">' . esc_html__( 'Replace this file while keeping all metadata, categories, and references intact.', 'media-library-organizer' ) . '</p>';
		echo $this->get_replace_field( $attachment ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Add Replace action in media library list view.
	 *
	 * @param string[] $actions Array of row action links.
	 * @param WP_Post  $post    The post object.
	 * @return string[]
	 */
	public function add_replace_action( $actions, $post ) {
		if ( get_post_type( $post->ID ) !== 'attachment' ) {
			return $actions;
		}

		$edit_url               = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
		$actions['mlo_replace'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $edit_url ),
			esc_attr__( 'Replace Media', 'media-library-organizer' ),
			esc_html__( 'Replace Media', 'media-library-organizer' )
		);

		return $actions;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( $hook !== 'post.php' && $hook !== 'upload.php' ) {
			return;
		}

		if ( $hook === 'post.php' ) {
			$id = isset( $_GET['post'] ) ? (int) sanitize_text_field( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $id ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $id ) ) {
				return;
			}

			if ( get_post_type( $id ) !== 'attachment' ) {
				return;
			}

			$mlo       = Media_Library_Organizer();
			$ext       = ( $mlo->dashboard->should_load_minified_js() ? 'min' : '' );
			$mime_type = get_post_mime_type( $id );

			$max_file_size = wp_max_upload_size();
			// translators: %s is the max file size in MB.
			$max_file_size_error = sprintf( __( 'File size is too large. Max file size is %sMB', 'media-library-organizer' ), $max_file_size / 1024 / 1024 );

			wp_enqueue_style( $mlo->plugin->name . '-attachment-edit', $mlo->plugin->url . 'assets/css/single-attachment.css', array(), $mlo->plugin->version );

			wp_register_script( $mlo->plugin->name . '-attachment-edit', $mlo->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'single-attachment' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'jquery' ), $mlo->plugin->version, true );
			wp_localize_script(
				$mlo->plugin->name . '-attachment-edit',
				'MLOAttachmentEdit',
				array(
					'ajaxURL'      => admin_url( 'admin-ajax.php' ),
					'maxFileSize'  => $max_file_size,
					'attachmentId' => $id,
					'mimeType'     => $mime_type,
					'nonce'        => wp_create_nonce( 'mlo_replace_file' ),
					'i18n'         => array(
						'maxFileSizeError' => $max_file_size_error,
						'replaceFileError' => __( 'Error replacing file', 'media-library-organizer' ),
						'mimeTypeError'    => __( 'The uploaded file\'s MIME type is not supported.', 'media-library-organizer' ),
					),
				)
			);
			wp_enqueue_script( $mlo->plugin->name . '-attachment-edit' );
		} elseif ( $hook === 'upload.php' ) {
			$mlo = Media_Library_Organizer();
			$ext = ( $mlo->dashboard->should_load_minified_js() ? 'min' : '' );

			wp_enqueue_script(
				$mlo->plugin->name . '-modal-attachment',
				$mlo->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'modal-attachment' . ( $ext ? '-' . $ext : '' ) . '.js',
				array( 'jquery', 'media-views', 'media-models' ),
				$mlo->plugin->version,
				true
			);

			wp_localize_script(
				$mlo->plugin->name . '-modal-attachment',
				'MLOModalAttachment',
				array(
					'editPostURL' => admin_url( 'post.php' ),
					'i18n'        => array(
						'replaceMedia' => __( 'Replace Media', 'media-library-organizer' ),
					),
				)
			);
		}
	}

	/**
	 * Get the replace field HTML.
	 *
	 * @param \Media_Library_Organizer_Attachment_Model $attachment The attachment model.
	 *
	 * @return string The HTML.
	 */
	private function get_replace_field( \Media_Library_Organizer_Attachment_Model $attachment ) {
		$file_ext = $attachment->get_extension();
		$file_ext = in_array( $file_ext, array( 'jpg', 'jpeg' ), true ) ? array( '.jpg', '.jpeg' ) : array( '.' . $file_ext );

		$supported_text = sprintf(
			/* translators: %s is a comma-separated list of file extensions */
			__( 'Supported: %s', 'media-library-organizer' ),
			strtoupper( str_replace( '.', '', implode( ', ', $file_ext ) ) )
		);

		$html  = '<div class="mlo-replace-section">';
		$html .= '<div class="mlo-replace-input">';
		$html .= '<label for="mlo-replace-file-field" id="mlo-file-drop-area">';
		$html .= '<span class="mlo-drop-icon dashicons dashicons-media-default"></span>';
		$html .= '<span class="label-text">' . esc_html__( 'Drop file here or click to select', 'media-library-organizer' ) . '</span>';
		$html .= '<span class="mlo-supported-text">' . esc_html( $supported_text ) . '</span>';
		$html .= '<div class="mlo-replace-file-preview"></div>';
		$html .= '</label>';

		$html .= '<input type="file" class="hidden" id="mlo-replace-file-field" name="mlo-replace-file-field" accept="' . esc_attr( implode( ',', $file_ext ) ) . '">';

		$html .= '<div class="mlo-replace-file-actions">';
		$html .= '<button disabled type="button" class="button button-primary" id="mlo-replace-file-btn">' . esc_html__( 'Replace', 'media-library-organizer' ) . '</button>';
		$html .= '<button disabled type="button" class="button" id="mlo-replace-clear-btn">' . esc_html__( 'Cancel', 'media-library-organizer' ) . '</button>';
		$html .= $this->get_svg_loader();
		$html .= '</div>';

		$html .= '<div class="mlo-replace-file-error hidden"></div>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Replace the file.
	 */
	public function replace_file() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mlo_replace_file' ) ) {
			wp_send_json_error( __( 'Security check failed', 'media-library-organizer' ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? (int) sanitize_text_field( $_POST['attachment_id'] ) : 0;

		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( __( 'You are not allowed to replace this file', 'media-library-organizer' ) );
		}

		if ( ! isset( $_FILES['file'] ) ) {
			wp_send_json_error( __( 'No file was selected. Please choose a file and try again.', 'media-library-organizer' ) );
		}

		$replacer = new Media_Library_Organizer_Replace( $id, $_FILES['file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$replaced = $replacer->replace();

		$is_error = is_wp_error( $replaced );

		$response = array(
			'success' => ! $is_error,
			'message' => $is_error ? $replaced->get_error_message() : __( 'File replaced successfully', 'media-library-organizer' ),
		);

		wp_send_json( $response );
	}

	/**
	 * Get the SVG loader.
	 *
	 * @return string The SVG loader.
	 */
	private function get_svg_loader() {
		return '<svg style="display: none;" class="mlo-svg-loader" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>';
	}
}
