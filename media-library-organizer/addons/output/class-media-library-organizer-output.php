<?php
/**
 * Output class.
 *
 * @package   Media_Library_Organizer_Output
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Output' ) ) {

	/**
	 * Media Library Organizer: Output Addon.
	 * Acts as a bootstrap to load the rest of the Addon.
	 *
	 * @package   Media_Library_Organizer_Output
	 * @author    WP Media Library
	 * @version   1.1.3
	 */
	class Media_Library_Organizer_Output {

		/**
		 * Holds the class object.
		 *
		 * @since   1.1.3
		 *
		 * @var     object
		 */
		public static $instance;

		/**
		 * Holds the plugin information object.
		 *
		 * @since   1.1.3
		 *
		 * @var     object
		 */
		public $plugin;

		/**
		 * Classes.
		 *
		 * @var object $classes
		 */
		public $classes;

		/**
		 * Constructor. Acts as a bootstrap to load the rest of the plugin
		 *
		 * @since   1.1.3
		 */
		public function __construct() {

			// Plugin Details.
			$this->plugin                    = new stdClass();
			$this->plugin->name              = 'media-library-organizer-output';
			$this->plugin->folder            = plugin_dir_path( __FILE__ );
			$this->plugin->url               = plugin_dir_url( __FILE__ );
			$this->plugin->documentation_url = 'https://wpmedialibrary.com/documentation/output';

			// Defer loading of Plugin Classes.
			add_action( 'init', array( $this, 'initialize' ), 2 );
		}

		/**
		 * Initializes required and licensed classes
		 *
		 * @since   1.1.3
		 */
		public function initialize() {

			// Bail if the main Plugin isn't active.
			if ( ! function_exists( 'Media_Library_Organizer' ) ) {
				return;
			}

			$this->plugin->displayName = __( 'Output', 'media-library-organizer' );

			$this->classes = new stdClass();

			$this->initialize_admin();
			$this->initialize_frontend();
			$this->initialize_admin_or_frontend_editor();
			$this->initialize_cli();
		}

		/**
		 * Initialize classes for the WordPress Administration interface
		 *
		 * @since   1.1.3
		 */
		private function initialize_admin() {

			// If this request is for rest api then initialize admin class.
			if ( Media_Library_Organizer()->is_rest ) {
				$this->classes->admin = new Media_Library_Organizer_Output_Admin( self::$instance );
				return;
			}

			// Bail if this request isn't for the WordPress Administration interface.
			if ( ! is_admin() ) {
				return;
			}

			$this->classes->admin  = new Media_Library_Organizer_Output_Admin( self::$instance );
			$this->classes->export = new Media_Library_Organizer_Output_Export( self::$instance );
		}

		/**
		 * Initialize classes for the frontend web site
		 *
		 * @since   1.1.3
		 */
		private function initialize_frontend() {

			// Bail if this request isn't for the frontend web site.
			if ( is_admin() ) {
				return;
			}
		}

		/**
		 * Initialize classes for WP-CLI
		 *
		 * @since   1.1.3
		 */
		private function initialize_cli() {

			// Bail if WP-CLI isn't installed on the server.
			if ( ! class_exists( 'WP_CLI' ) ) {
				return;
			}

			// In CLI mode, is_admin() is not called, so we need to require the classes that
			// the CLI commands may use.
		}

		/**
		 * Initialize classes for the WordPress Administration interface or a frontend Page Builder
		 *
		 * @since   1.1.3
		 */
		private function initialize_admin_or_frontend_editor() {

			// Bail if this request isn't for the WordPress Administration interface and isn't for a frontend Page Builder.
			if ( ! Media_Library_Organizer()->is_admin_or_frontend_editor() ) {
				return;
			}

			$this->classes->media    = new Media_Library_Organizer_Output_Media( self::$instance );
			$this->classes->settings = new Media_Library_Organizer_Output_Settings( self::$instance );
		}

		/**
		 * Returns the given class
		 *
		 * @since   1.1.3
		 *
		 * @param   string $name   Class Name.
		 */
		public function get_class( $name ) {

			// If the class hasn't been loaded, throw a WordPress die screen.
			// to avoid a PHP fatal error.
			if ( ! isset( $this->classes->{ $name } ) ) {
				// Define the error.
				$error = new WP_Error(
					'media_library_organizer_output_get_class',
					sprintf(
						/* translators: %1$s, %2$s: PHP class name */
						__( 'Media Library Organizer Pro: %1$s: Error: Could not load Plugin class <strong>%2$s</strong>', 'media-library-organizer' ),
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
							esc_html__( 'Media Library Organizer Pro: %s: Error', 'media-library-organizer' ),
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
		 * @since   1.1.3
		 *
		 * @return  object Class.
		 */
		public static function get_instance() {

			if ( ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

/**
 * Define the autoloader for this Plugin
 *
 * @since   1.1.3
 *
 * @param   string $class_name     The class to load.
 */
function media_library_organizer_output_autoloader( $class_name ) {

	// Define the required start of the class name.
	$class_start_name = 'Media_Library_Organizer_Output';

	// Get the number of parts the class start name has.
	$class_parts_count = count( explode( '_', $class_start_name ) );

	// Break the class name into an array.
	$class_path = explode( '_', $class_name );

	// Bail if it's not a minimum length (i.e. doesn't potentially have Media_Library_Organizer_Tree_View).
	if ( count( $class_path ) < $class_parts_count ) {
		return;
	}

	// Build the base class path for this class.
	$base_class_path = '';
	for ( $i = 0; $i < $class_parts_count; $i++ ) {
		$base_class_path .= $class_path[ $i ] . '_';
	}
	$base_class_path = trim( $base_class_path, '_' );

	// Bail if the first parts don't match what we expect.
	if ( $base_class_path !== $class_start_name ) {
		return;
	}

	// Define the file name.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

	// Define the paths with file name we need to include.
	$include_paths = array(
		__DIR__ . '/includes/admin/' . $file_name,
		__DIR__ . '/includes/global/' . $file_name,
	);

	// Iterate through the include paths to find the file.
	foreach ( $include_paths as $path_file ) {
		if ( file_exists( $path_file ) ) {
			require_once $path_file;
			return;
		}
	}

	// If here, we couldn't find the file!
}
spl_autoload_register( 'media_library_organizer_output_autoloader' );

/**
 * Main function to return Plugin instance.
 *
 * @since   1.1.3
 */
function Media_Library_Organizer_Output() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName

	return Media_Library_Organizer_Output::get_instance();
}

// Finally, initialize the Plugin.
$media_library_organizer_output = Media_Library_Organizer_Output();
