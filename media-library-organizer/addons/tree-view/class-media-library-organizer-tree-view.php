<?php
/**
 * Media Library Organizer Tree View class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Library Organizer Tree View Addon
 *
 * @since   1.2.7
 */
class Media_Library_Organizer_Tree_View {

	/**
	 * Holds the class object.
	 *
	 * @since   1.1.1
	 *
	 * @var     object|null
	 */
	public static $instance = null;

	/**
	 * Holds the plugin information object.
	 *
	 * @since   1.1.1
	 *
	 * @var     object
	 */
	public $plugin;

	/**
	 * Classes
	 *
	 * @since   1.5.1
	 *
	 * @var     object
	 */
	public $classes;

	/**
	 * Flag denoting whether this Addon is enabled
	 *
	 * @since   1.1.1
	 *
	 * @var     bool
	 */
	public $enabled = false;

	/**
	 * Constructor. Acts as a bootstrap to load the rest of the plugin
	 *
	 * @since   1.1.1
	 */
	public function __construct() {

		// Plugin Details.
		$this->plugin                    = new stdClass();
		$this->plugin->name              = 'media-library-organizer-tree-view';
		$this->plugin->displayName       = __( 'Tree View', 'media-library-organizer' );
		$this->plugin->folder            = plugin_dir_path( __FILE__ );
		$this->plugin->url               = plugin_dir_url( __FILE__ );
		$this->plugin->documentation_url = 'https://wpmedialibrary.com/documentation/tree-view';

		// Defer loading of Plugin Classes.
		add_action( 'init', array( $this, 'initialize' ), 2 );
	}

	/**
	 * Initializes required and licensed classes
	 *
	 * @since   1.1.1
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
	 * @since   1.1.1
	 */
	private function initialize_admin() {

		// Bail if this request isn't for the WordPress Administration interface.
		if ( ! is_admin() ) {
			return;
		}

		$this->classes->admin = new Media_Library_Organizer_Tree_View_Admin( self::$instance );
	}

	/**
	 * Initialize classes for the frontend web site
	 *
	 * @since   1.1.1
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
	 * @since   1.1.1
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
	 * @since   1.1.1
	 */
	private function initialize_admin_or_frontend_editor() {

		// Bail if this request isn't for the WordPress Administration interface and isn't for a frontend Page Builder.
		if ( ! Media_Library_Organizer()->is_admin_or_frontend_editor() ) {
			return;
		}

		$this->classes->ajax     = new Media_Library_Organizer_Tree_View_AJAX( self::$instance );
		$this->classes->media    = new Media_Library_Organizer_Tree_View_Media( self::$instance );
		$this->classes->settings = new Media_Library_Organizer_Tree_View_Settings( self::$instance );
	}

	/**
	 * Initialize classes used everywhere
	 *
	 * @since   1.1.1
	 */
	private function initialize_global() {
	}

	/**
	 * Returns the given class
	 *
	 * @since   1.1.1
	 *
	 * @param   string $name   Class Name.
	 */
	public function get_class( $name ) {

		// If the class hasn't been loaded, throw a WordPress die screen
		// to avoid a PHP fatal error.
		if ( ! isset( $this->classes->{ $name } ) ) {
			// Define the error.
			$error = new WP_Error(
				'media_library_organizer_tree_view_get_class',
				sprintf(
					/* translators: PHP class name */
					__( 'Media Library Organizer: Tree View: Error: Could not load Plugin class <strong>%s</strong>', 'media-library-organizer' ),
					$name
				)
			);

			// Depending on the request, return or display an error.
			// Admin UI.
			if ( is_admin() ) {
				wp_die(
					esc_html( $error->get_error_message() ),
					esc_html__( 'Media Library Organizer: Tree View: Error', 'media-library-organizer' ),
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
	 * @since   1.1.1
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

/**
 * Define the autoloader for this Plugin
 *
 * @since   1.1.1
 *
 * @param string $class_name The class to load.
 */
function media_library_organizer_tree_view_autoloader( $class_name ) {

	// Define the required start of the class name.
	$class_start_name = 'Media_Library_Organizer_Tree_View';

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
}
spl_autoload_register( 'media_library_organizer_tree_view_autoloader' );

/**
 * Main function to return Plugin instance.
 *
 * @since   1.1.1
 */
function Media_Library_Organizer_Tree_View() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName

	return Media_Library_Organizer_Tree_View::get_instance();
}

// Finally, initialize the Plugin.
$media_library_organizer_tree_view = Media_Library_Organizer_Tree_View();
