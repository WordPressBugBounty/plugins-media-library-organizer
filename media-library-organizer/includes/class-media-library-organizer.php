<?php
/**
 * Media Library Organizer class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Main Media Library Organizer class, used to load the Plugin.
 *
 * @package   Media_Library_Organizer
 * @author    WP Media Library
 * @version   1.0.0
 */
class Media_Library_Organizer {

	/**
	 * Holds the class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object|null
	 */
	public static $instance = null;

	/**
	 * Plugin
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $plugin;

	/**
	 * Dashboard
	 *
	 * @since   1.0.5
	 *
	 * @var     object
	 */
	public $dashboard;

	/**
	 * Licensing
	 *
	 * @since   1.5.1
	 *
	 * @var     object
	 */
	public $licensing;

	/**
	 * Classes
	 *
	 * @since   1.0.5
	 *
	 * @var     object
	 */
	public $classes;

	/**
	 * Constructor. Acts as a bootstrap to load the rest of the plugin
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Plugin Details.
		$this->plugin                    = new stdClass();
		$this->plugin->name              = 'media-library-organizer';
		$this->plugin->displayName       = 'Media Library Organizer';
		$this->plugin->author_name       = 'Media Library Organizer';
		$this->plugin->version           = MEDIA_LIBRARY_ORGANIZER_PLUGIN_VERSION;
		$this->plugin->buildDate         = MEDIA_LIBRARY_ORGANIZER_PLUGIN_BUILD_DATE;
		$this->plugin->requires          = '5.0';
		$this->plugin->tested            = '5.9.3';
		$this->plugin->folder            = MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH;
		$this->plugin->url               = MEDIA_LIBRARY_ORGANIZER_PLUGIN_URL;
		$this->plugin->documentation_url = 'https://wpmedialibrary.com/documentation';
		$this->plugin->support_url       = 'https://wpmedialibrary.com/support';
		$this->plugin->upgrade_url       = 'https://wpmedialibrary.com/pricing';
		$this->plugin->review_name       = 'media-library-organizer';
		$this->plugin->review_notice     = sprintf(
			/* translators: Plugin Name */
			__( 'Thanks for using %s to organize your Media Library!', 'media-library-organizer' ),
			$this->plugin->displayName
		);

		// Dashboard Submodule.
		if ( ! class_exists( 'WPZincDashboardWidget' ) ) {
			require_once $this->plugin->folder . '_modules/dashboard/class-wpzincdashboardwidget.php';
		}
		$this->dashboard = new WPZincDashboardWidget( $this->plugin );

		// License Submodule.
		// If the Pro version is installed, make its licensing object available here for screens.
		if ( function_exists( 'Media_Library_Organizer_Pro' ) && class_exists( 'LicensingUpdateManager' ) ) {
			$this->licensing = Media_Library_Organizer_Pro()->licensing;
		}

		// Initialize Free Addons.
		$this->initialize_free_addons();

		// Defer loading of Plugin Classes.
		add_action( 'init', array( $this, 'initialize' ), 1 );
		add_action( 'init', array( $this, 'upgrade' ), 2 );

		// Localization.
		add_action( 'plugins_loaded', array( $this, 'load_language_files' ) );
	}

	/**
	 * Initialize Free Addons
	 *
	 * @since   1.1.4
	 */
	private function initialize_free_addons() {

		// Define Addons Directory.
		$addons_dir = MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . '/addons/';
		if ( ! is_dir( $addons_dir ) ) {
			return;
		}
		// Iterate through Addons Directory.
		$files_dirs = scandir( $addons_dir );
		foreach ( $files_dirs as $file_dir ) {
			// Skip dot folders.
			if ( '.' === $file_dir || '..' === $file_dir ) {
				continue;
			}

			// Skip if Addon directory doesn't exist.
			if ( ! is_dir( $addons_dir . $file_dir ) ) {
				continue;
			}

			// Skip if Addon bootstrap file doesn't exist.
			$file = $addons_dir . $file_dir . '/class-media-library-organizer-' . $file_dir . '.php';
			if ( ! file_exists( $file ) ) {
				continue;
			}

			// Load Addon.
			require_once $file;
		}
	}

	/**
	 * Initializes classes and Free Addons.
	 *
	 * @since   1.0.5
	 */
	public function initialize() {

		$this->classes = new stdClass();

		$this->initialize_admin();
		$this->initialize_frontend();
		$this->initialize_admin_or_frontend_editor();
		$this->initialize_cli();
		$this->initialize_global();
	}

	/**
	 * Initialize classes for the WordPress Administration interface
	 *
	 * @since   1.0.9
	 */
	private function initialize_admin() {

		// Bail if this request isn't for the WordPress Administration interface.
		if ( ! is_admin() ) {
			return;
		}

		$this->classes->admin      = new Media_Library_Organizer_Admin( self::$instance );
		$this->classes->admin_ajax = new Media_Library_Organizer_Admin_AJAX( self::$instance );
		$this->classes->export     = new Media_Library_Organizer_Export( self::$instance );
		$this->classes->import     = new Media_Library_Organizer_Import( self::$instance );
	}

	/**
	 * Initialize classes for the frontend web site
	 *
	 * @since   1.0.9
	 */
	private function initialize_frontend() {

		// Bail if this request isn't for the frontend web site.
		if ( is_admin() ) {
			return;
		}

		$this->classes->frontend = new Media_Library_Organizer_Frontend( self::$instance );
	}

	/**
	 * Initialize classes for WP-CLI
	 *
	 * @since   1.0.9
	 */
	private function initialize_cli() {

		// Bail if WP-CLI isn't installed on the server.
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		// In CLI mode, is_admin() is not called, so we need to require the classes that
		// the CLI commands may use.
		$this->classes->cli = new Media_Library_Organizer_CLI( self::$instance );
	}

	/**
	 * Initialize classes for the WordPress Administration interface or a frontend Page Builder
	 *
	 * @since   1.0.9
	 */
	private function initialize_admin_or_frontend_editor() {

		// Bail if this request isn't for the WordPress Administration interface and isn't for a frontend Page Builder.
		if ( ! $this->is_admin_or_frontend_editor() ) {
			return;
		}

		$this->classes->ajax          = new Media_Library_Organizer_AJAX( self::$instance );
		$this->classes->editor        = new Media_Library_Organizer_Editor( self::$instance );
		$this->classes->page_builders = new Media_Library_Organizer_Page_Builders( self::$instance );
		$this->classes->tinymce       = new Media_Library_Organizer_TinyMCE( self::$instance );
	}

	/**
	 * Initialize classes used everywhere
	 *
	 * @since   1.0.9
	 */
	private function initialize_global() {

		$this->classes->common       = new Media_Library_Organizer_Common( self::$instance );
		$this->classes->dynamic_tags = new Media_Library_Organizer_Dynamic_Tags( self::$instance );
		$this->classes->filesystem   = new Media_Library_Organizer_Filesystem( self::$instance );
		$this->classes->install      = new Media_Library_Organizer_Install( self::$instance );
		$this->classes->media        = new Media_Library_Organizer_Media( self::$instance );
		$this->classes->mime         = new Media_Library_Organizer_MIME( self::$instance );
		$this->classes->notices      = new Media_Library_Organizer_Notices( self::$instance );
		$this->classes->settings     = new Media_Library_Organizer_Settings( self::$instance );
		$this->classes->shortcode    = new Media_Library_Organizer_Shortcode( self::$instance );
		$this->classes->taxonomies   = new Media_Library_Organizer_Taxonomies( self::$instance );
		$this->classes->upload       = new Media_Library_Organizer_Upload( self::$instance );
		$this->classes->user_option  = new Media_Library_Organizer_User_Option( self::$instance );
	}

	/**
	 * Improved version of WordPress' is_admin(), which includes whether we're
	 * editing on the frontend using a Page Builder, or a developer / Addon
	 * wants to load Editor, Media Management and Upload classes on the frontend
	 * of the site.
	 *
	 * @since   1.0.7
	 *
	 * @return  bool    Is Admin or Frontend Editor Request
	 */
	public function is_admin_or_frontend_editor() {

		// If we're in the wp-admin, return true.
		if ( is_admin() ) {
			return true;
		}

		// Pro.
		$request_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( strpos( $request_url, '/pro/' ) !== false ) {
			return true;
		}
		if ( strpos( $request_url, '/x/' ) !== false ) {
			return true;
		}
		if ( strpos( $request_url, 'cornerstone-endpoint' ) !== false ) {
			return true;
		}

		// If the request global exists, check for specific request keys which tell us
		// that we're using a frontend editor.
		if ( ! empty( $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Sanitize request.
			$request = map_deep( $_REQUEST, 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification

			// Beaver Builder.
			if ( array_key_exists( 'fl_builder', $request ) ) {
				return true;
			}

			// Cornerstone (AJAX).
			if ( array_key_exists( '_cs_nonce', $request ) ) {
				return true;
			}

			// Divi.
			if ( array_key_exists( 'et_fb', $request ) ) {
				return true;
			}

			// Elementor.
			if ( array_key_exists( 'action', $request ) && 'elementor' === $request['action'] ) {
				return true;
			}

			// Kallyas.
			if ( array_key_exists( 'zn_pb_edit', $request ) ) {
				return true;
			}

			// Oxygen.
			if ( array_key_exists( 'ct_builder', $request ) ) {
				return true;
			}

			// Themify Builder.
			if ( array_key_exists( 'tb-preview', $request ) && array_key_exists( 'tb-id', $request ) ) {
				return true;
			}

			// Thrive Architect.
			if ( array_key_exists( 'tve', $request ) ) {
				return true;
			}

			// Visual Composer.
			if ( array_key_exists( 'vcv-editable', $request ) ) {
				return true;
			}

			// WPBakery Page Builder.
			if ( array_key_exists( 'vc_editable', $request ) ) {
				return true;
			}
		} else {
			$request = false;
		}

		// Assume we're not in the Administration interface.
		$is_admin_or_frontend_editor = false;

		/**
		 * Filters whether the current request is a WordPress Administration / Frontend Editor request or not.
		 *
		 * Page Builders can set this to true to allow Media Library Organizer and its Addons to load its
		 * functionality.
		 *
		 * @since   1.0.7
		 *
		 * @param   bool    $is_admin_or_frontend_editor    Is WordPress Administration / Frontend Editor request.
		 * @param   array   $request                        Sanitized request data.
		 */
		$is_admin_or_frontend_editor = apply_filters( 'media_library_organizer_is_admin_or_frontend_editor', $is_admin_or_frontend_editor, $request );

		// Return filtered result.
		return $is_admin_or_frontend_editor;
	}

	/**
	 * Runs the upgrade routine once the plugin has loaded
	 *
	 * @since   1.0.5
	 */
	public function upgrade() {

		// Bail if we're not in the WordPress Admin.
		if ( ! is_admin() ) {
			return;
		}

		// Run upgrade routine.
		$this->get_class( 'install' )->upgrade();
	}

	/**
	 * Loads plugin textdomain
	 *
	 * @since   1.2.6
	 */
	public function load_language_files() {

		load_plugin_textdomain( 'media-library-organizer', false, 'media-library-organizer/languages/' );
	}

	/**
	 * Returns the given class
	 *
	 * @since   1.0.5
	 *
	 * @param   string $name   Class Name.
	 */
	public function get_class( $name ) {

		// If the class hasn't been loaded, throw a WordPress die screen
		// to avoid a PHP fatal error.
		if ( ! isset( $this->classes->{ $name } ) ) {
			// Define the error.
			$error = new WP_Error(
				'media_library_organizer_get_class',
				sprintf(
					/* translators: %1$s: Plugin Name, %2$s: PHP class name */
					__( '%1$s: Error: Could not load Plugin class %2$s', 'media-library-organizer' ),
					$this->plugin->displayName,
					$name
				)
			);

			// Depending on the request, return or display an error.
			// Admin UI.
			if ( is_admin() ) {
				wp_die(
					esc_html( $error->get_error_message() ),
					sprintf(
						/* translators: Plugin Name */
						esc_html__( '%s: Error', 'media-library-organizer' ),
						esc_html( $this->plugin->displayName )
					),
					array(
						'back_link' => true,
					)
				);
			}

			// Cron / CLI.
			return $error;
		}

		// Return the class object.
		return $this->classes->{ $name };
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since   1.0.0
	 *
	 * @return  object Class.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
