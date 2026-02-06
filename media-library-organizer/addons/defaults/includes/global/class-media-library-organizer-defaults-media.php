<?php
/**
 * Media class.
 *
 * @package   Media_Library_Organizer_Defaults
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Media' ) ) {

	/**
	 * Enqueues scripts to set the Attachment Display Setting Defaults,
	 * whenever wp_enqueue_media() is called.
	 *
	 * @package   Media_Library_Organizer_Defaults
	 * @author    WP Media Library
	 * @version   1.1.0
	 */
	class Media_Library_Organizer_Defaults_Media {

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

			// Enqueue scripts.
			add_action( 'wp_enqueue_media', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Enqueues JS whenever wp_enqueue_media() is called, which is used for
		 * any media upload, management or selection screens / views.
		 *
		 * @since   1.1.0
		 */
		public function enqueue_scripts() {

			// If SCRIPT_DEBUG is enabled, load unminified versions.
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				$ext = '';
			} else {
				$ext = 'min';
			}

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
				'media_library_organizer_defaults',
				array(
					'file_types' => Media_Library_Organizer()->get_class( 'mime' )->get_all_file_types(),
					'settings'   => Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'defaults' ),
				)
			);
		}
	}

}
