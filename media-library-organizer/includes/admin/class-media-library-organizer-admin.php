<?php
/**
 * Admin class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Handles the settings screen.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Admin {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Maybe request review.
		add_action( 'wp_loaded', array( $this, 'maybe_request_review' ) );

		// Admin CSS, JS and Menu.
		add_filter( 'wpzinc_admin_body_class', array( $this, 'admin_body_class' ) ); // WordPress Admin.
		add_filter( 'body_class', array( $this, 'body_class' ) ); // Frontend Editors.

		// Actions.
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_css' ) ); // WordPress Admin.
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_css' ) ); // Frontend Editors.

		// Menu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Settings Screen.
		add_action( 'media_library_organizer_admin_scripts_js_general', array( $this, 'enqueue_js_settings' ), 10, 4 );
		add_action( 'media_library_organizer_admin_scripts_css_general', array( $this, 'enqueue_css_settings' ) );

		// Addon Screens.
		add_action( 'media_library_organizer_admin_output_settings_panel_general', array( $this, 'output_addon_settings_panel_general' ) );
		add_action( 'media_library_organizer_admin_output_settings_panels', array( $this, 'output_addon_panels' ) );
	}

	/**
	 * Maybe request a review
	 *
	 * Won't do this if Pro with Whitelabelling is enabled
	 *
	 * The review notice will display 3 days after this request
	 *
	 * @since   1.2.4
	 */
	public function maybe_request_review() {

		if ( ! function_exists( 'Media_Library_Organizer_Pro' ) ) {
			Media_Library_Organizer()->dashboard->request_review();
		} elseif ( ! Media_Library_Organizer_Pro()->licensing->has_feature( 'whitelabelling' ) ) {
			Media_Library_Organizer()->dashboard->request_review();
		}
	}

	/**
	 * Registers screen names that should add the wpzinc class to the <body> tag
	 *
	 * @since   1.1.0
	 *
	 * @param   array $screens    Screen Names.
	 * @return  array               Screen Names
	 */
	public function admin_body_class( $screens ) {

		/**
		 * Registers screen names that should add the wpzinc class to the <body> tag
		 *
		 * @since   2.5.7
		 *
		 * @param   array   $screens    Screen Names.
		 * @return  array               Screen Names.
		 */
		$screens = apply_filters( 'media_library_organizer_admin_body_class', $screens );

		// Return.
		return $screens;
	}

	/**
	 * Defines CSS classes for the frontend output
	 *
	 * @since   1.1.0
	 *
	 * @param   array $classes    CSS Classes.
	 * @return  array               CSS Classes
	 */
	public function body_class( $classes ) {

		$classes[] = 'wpzinc';

		return $classes;
	}

	/**
	 * Enqueues JS and CSS depending on the screen that's being viewed
	 *
	 * @since   1.0.0
	 */
	public function scripts_css() {

		// Bail if we can't get the current admin screen, or we're not viewing a screen
		// belonging to this plugin.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		// Determine whether to load minified JS.
		$ext = ( $this->base->dashboard->should_load_minified_js() ? 'min' : '' );

		// JS: Register Selectize.
		$this->base->get_class( 'media' )->register_selectize_js_css( $ext );

		// Get current screen, registered plugin screens and the media view (list or grid).
		$screen  = get_current_screen();
		$screens = $this->get_screens();
		$mode    = $this->base->get_class( 'common' )->get_media_view();

		// If we're on the Media screen, enqueue.
		if ( 'upload' === $screen->id || 'media' === $screen->id ) {
			// Add New.
			if ( 'add' === $screen->action ) {
				$this->enqueue_scripts_css( 'media_add_new', $screen, $screens, $mode, $ext );
				return;
			}

			// List or Grid View.
			$this->enqueue_scripts_css( 'media', $screen, $screens, $mode, $ext );
			return;
		}

		// If we're on the Edit Attachment screen, enqueue.
		if ( 'attachment' === $screen->id ) {
			$this->enqueue_scripts_css( 'attachment', $screen, $screens, $mode, $ext );
			return;
		}

		// If we're on the top level Plugin screen, enqueue.
		if ( 'toplevel_page_' . $this->base->plugin->name === $screen->base ) {
			$this->enqueue_scripts_css( 'general', $screen, $screens, $mode, $ext );
			return;
		}

		// Iterate through the registered screens, to see if we're viewing that screen.
		foreach ( $screens as $registered_screen ) {
			if ( 'media-library-organizer_page_media-library-organizer-' . $registered_screen['name'] === $screen->id ) {
				// We're on a plugin screen.
				$this->enqueue_scripts_css( $registered_screen['name'], $screen, $screens, $mode, $ext );
				return;
			}
		}
	}

	/**
	 * Enqueues scripts and CSS.
	 *
	 * @since   1.0.0
	 *
	 * @param   string       $plugin_screen_name     Plugin Screen Name (general|media).
	 * @param   WP_Screen    $screen                 Current WordPress Screen object.
	 * @param   string|array $screens                Registered Plugin Screens (optional).
	 * @param   string       $mode                   Media View Mode (list|grid).
	 * @param   string       $ext                    If defined, load minified JS.
	 */
	public function enqueue_scripts_css( $plugin_screen_name, $screen, $screens = '', $mode = 'list', $ext = '' ) {

		global $post;

		// Enqueue JS.
		// These scripts are registered in the Dashboard module.
		wp_enqueue_script( 'wpzinc-admin-conditional' );
		wp_enqueue_script( 'wpzinc-admin-tabs' );
		wp_enqueue_script( 'wpzinc-admin' );

		/**
		 * Enqueue Javascript for the given screen and Media View mode.
		 *
		 * @since   1.0.7
		 *
		 * @param   WP_Screen       $screen                 Current WordPress Screen object.
		 * @param   string|array    $screens                Registered Plugin Screens (optional).
		 * @param   string          $mode                   Media View Mode (list|grid).
		 * @param   string          $ext                    If defined, load minified JS.
		 */
		do_action( 'media_library_organizer_admin_scripts_js', $screen, $screens, $mode, $ext );

		/**
		 * Enqueue Javascript for the given screen and Media View mode by Plugin
		 * Screen Name.
		 *
		 * @since   1.0.7
		 *
		 * @param   WP_Screen       $screen                 Current WordPress Screen object.
		 * @param   string|array    $screens                Registered Plugin Screens (optional).
		 * @param   string          $mode                   Media View Mode (list|grid).
		 * @param   string          $ext                    If defined, load minified JS
		 */
		do_action( 'media_library_organizer_admin_scripts_js_' . $plugin_screen_name, $screen, $screens, $mode, $ext );

		/**
		 * Enqueue Stylesheets (CSS) for the given screen and Media View mode.
		 *
		 * @since   1.0.7
		 *
		 * @param   WP_Screen   $screen                     Current WordPress Screen object.
		 * @param   string|array    $screens                Registered Plugin Screens (optional).
		 * @param   string      $mode                       Media View Mode (list|grid).
		 */
		do_action( 'media_library_organizer_admin_scripts_css', $screen, $screens, $mode );

		/**
		 * Enqueue Stylesheets (CSS) for the given screen and Media View mode.
		 *
		 * @since   1.0.7
		 *
		 * @param   WP_Screen       $screen                 Current WordPress Screen object.
		 * @param   string|array    $screens                Registered Plugin Screens (optional).
		 * @param   string          $mode                   Media View Mode (list|grid).
		 */
		do_action( 'media_library_organizer_admin_scripts_css_' . $plugin_screen_name, $screen, $screens, $mode );
	}

	/**
	 * Enqueues JS for the Settings screen.
	 *
	 * @since   1.1.6
	 *
	 * @param   WP_Screen $screen     get_current_screen().
	 * @param   array     $screens    Available Plugin Screens.
	 * @param   string    $mode       Media View Mode (list|grid).
	 * @param   string    $ext        If defined, loads minified JS.
	 */
	public function enqueue_js_settings( $screen, $screens, $mode, $ext ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// JS.
		wp_enqueue_script( 'wpzinc-admin-modal' );

		// Plugin JS.
		wp_enqueue_script( $this->base->plugin->name . '-selectize' );
		wp_enqueue_script( $this->base->plugin->name . '-settings', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'settings' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );

		// Localize.
		wp_localize_script(
			$this->base->plugin->name . '-settings',
			'media_library_organizer_settings',
			array(
				'save_settings_action' => 'media_library_organizer_save_settings',
				'save_settings_nonce'  => wp_create_nonce( $this->base->plugin->name . '-save-settings' ),
				'save_settings_modal'  => array(
					'title'         => __( 'Saving', 'media-library-organizer' ),
					'title_success' => __( 'Saved!', 'media-library-organizer' ),
				),
			)
		);

		// CSS.
		wp_enqueue_style( 'wpzinc-admin-selectize' );
	}

	/**
	 * Enqueues CSS for the Settings screen.
	 *
	 * @since   1.0.3
	 */
	public function enqueue_css_settings() {

		// Enqueue CSS.
		wp_enqueue_style( $this->base->plugin->name . '-admin', $this->base->plugin->url . '/assets/css/admin.css', array(), $this->base->plugin->version );
	}

	/**
	 * Adds menu and sub menu items to the WordPress Administration.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {

		// Bail if we cannot access any menus.
		if ( function_exists( 'Media_Library_Organizer_Access' ) && ! Media_Library_Organizer_Access()->can_access( 'show_menu' ) ) {
			return;
		}

		// Get the registered screens.
		$screens = $this->get_screens();

		// Define the minimum capability required to access the Media Library Organizer Menu and Sub Menus.
		$minimum_capability = 'manage_options';

		/**
		 * Defines the minimum capability required to access the Media Library Organizer
		 * Menu and Sub Menus.
		 *
		 * @since   1.2.4
		 *
		 * @param   string  $capability     Minimum Required Capability.
		 * @return  string                  Minimum Required Capability
		 */
		$minimum_capability = apply_filters( 'media_library_organizer_admin_admin_menu_minimum_capability', $minimum_capability );

		// Create the top level screen.
		add_menu_page( $this->base->plugin->displayName, $this->base->plugin->displayName, $minimum_capability, $this->base->plugin->name, array( $this, 'admin_screen' ), 'dashicons-admin-media' );

		// Iterate through screens, adding as submenu items.
		foreach ( (array) $screens as $screen ) {
			// The settings screen doesn't need to append the page slug.
			$slug = ( ( 'settings' === $screen['name'] ) ? $this->base->plugin->name : $this->base->plugin->name . '-' . $screen['name'] );

			// Define ACL name.
			$access = str_replace( '-', '_', str_replace( $this->base->plugin->name, '', $slug ) );
			if ( empty( $access ) ) {
				$access = 'settings';
			}

			// Skip if access isn't permitted, but always allow licensing and settings.
			if ( 'settings' !== $access && function_exists( 'Media_Library_Organizer_Access' ) && '_pro' !== $access && ! Media_Library_Organizer_Access()->can_access( 'show_menu_' . $access ) ) {
				continue;
			}

			// Add submenu page.
			add_submenu_page( $this->base->plugin->name, $screen['label'], $screen['label'], $minimum_capability, $slug, array( $this, 'admin_screen' ) );
		}

		// Import and Export.
		if ( ! function_exists( 'Media_Library_Organizer_Access' ) || Media_Library_Organizer_Access()->can_access( 'show_menu_import_export' ) ) {
			do_action( 'media_library_organizer_admin_menu_import_export' );
		}

		// Support.
		if ( ! function_exists( 'Media_Library_Organizer_Access' ) || Media_Library_Organizer_Access()->can_access( 'show_menu_support' ) ) {
			do_action( 'media_library_organizer_admin_menu_support' );
		}
	}

	/**
	 * Returns an array of screens for the plugin's admin.
	 *
	 * @since   1.0.0
	 *
	 * @return  array Sections
	 */
	private function get_screens() {

		// Define the settings screen.
		$screens = array(
			'settings' => array(
				'name'          => 'settings',
				'label'         => __( 'Settings', 'media-library-organizer' ),
				'description'   => __( 'Defines Plugin-wide settings for Media Library Organizer.', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/settings-general.php',
				'columns'       => 2,
				'data'          => array(),
				'documentation' => 'https://wpmedialibrary.com/documentation/media-library-organizer/setup/',
			),
		);

		/**
		 * Define sections in the Plugin's Settings
		 *
		 * @since   1.0.7
		 *
		 * @param   array       $screens                Registered Plugin Screens.
		 */
		$screens = apply_filters( 'media_library_organizer_admin_get_screens', $screens );

		// Return.
		return $screens;
	}

	/**
	 * Gets the current admin screen the user is on.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool|WP_Error|array    false|WP_Error|Screen name and label
	 */
	public function get_current_screen() {

		// Bail if no page given.
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		// Get current screen name.
		$screen = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// Get registered screens.
		$screens = $this->get_screens();

		// Remove the plugin name from the screen.
		$screen = str_replace( $this->base->plugin->name . '-', '', $screen );

		// If the screen is the plugin name, it's the settings screen.
		if ( $screen === $this->base->plugin->name ) {
			$screen = 'settings';
		}

		// Check if the screen exists.
		if ( ! isset( $screens[ $screen ] ) ) {
			return new WP_Error( 'screen_missing', __( 'The requested administration screen does not exist', 'media-library-organizer' ) );
		}

		/**
		 * Adjust the screen data immediately before returning.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $screens[ $screen ] Screen Data.
		 * @param   string  $screen             Screen Name.
		 */
		$screens[ $screen ] = apply_filters( 'media_library_organizer_admin_get_current_screen_' . $screen, $screens[ $screen ], $screen );

		// Return the screen.
		return $screens[ $screen ];
	}

	/**
	 * Gets the current admin screen tab the user is on.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $tabs   Screen Tabs.
	 * @return  bool|array          Tab name and label
	 */
	private function get_current_screen_tab( $tabs ) {

		// If the supplied tabs are an empty array, return false.
		if ( empty( $tabs ) ) {
			return false;
		}

		// If no tab defined, get the first tab name from the tabs array.
		if ( ! isset( $_REQUEST['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			foreach ( $tabs as $tab ) {
				return $tab;
			}
		}

		// Return the requested tab, if it exists.
		if ( isset( $tabs[ $_REQUEST['tab'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$tab = $tabs[ sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) ]; // phpcs:ignore WordPress.Security.NonceVerification
			return $tab;
		} else {
			foreach ( $tabs as $tab ) {
				return $tab;
			}
		}
	}

	/**
	 * Returns an array of tabs, depending on the Plugin Screen being viewed.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $screen     Screen.
	 * @return  array               Tabs
	 */
	private function get_screen_tabs( $screen ) {

		// Define tabs array.
		$tabs = array();

		// Define the tabs depending on which screen is specified.
		switch ( $screen ) {

			/**
			 * Settings
			 */
			case 'settings':
				$tabs = array(
					'settings'     => array(
						'name'          => 'general',
						'label'         => __( 'Filters', 'media-library-organizer' ),
						'documentation' => $this->base->plugin->documentation_url . '/settings/#general',
						'menu_icon'     => 'general',
					),
					'user-options' => array(
						'name'          => 'user-options',
						'label'         => __( 'User Options', 'media-library-organizer' ),
						'documentation' => $this->base->plugin->documentation_url . '/settings/#user-options',
						'menu_icon'     => 'user',
					),
				);
				break;

		}

		/**
		 * Define tabs in the Plugin Settings section.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $tabs       Settings Tabs.
		 * @param   string  $screen     Current Screen Name to define Tabs for.
		 */
		$tabs = apply_filters( 'media_library_organizer_admin_get_screen_tabs', $tabs, $screen );

		// Return.
		return $tabs;
	}

	/**
	 * Returns an array of Addon tabs, depending on the Plugin Screen being viewed.
	 *
	 * @since   1.1.0
	 *
	 * @param   string $screen     Screen.
	 * @return  array               Tabs
	 */
	private function get_screen_addon_tabs( $screen ) {

		// Define additional tabs.
		$addon_tabs = array(
			'auto-categorization' => array(
				'name'          => 'auto-categorization',
				'label'         => __( 'Auto Categorization', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/auto-categorization/setup',
				'menu_icon'     => 'tag',
				'is_pro'        => true,
			),
			'bulk-quick-edit'     => array(
				'name'          => 'bulk-quick-edit',
				'label'         => __( 'Bulk and Quick Edit', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/bulk-quick-edit/setup',
				'menu_icon'     => 'edit',
				'is_pro'        => true,
			),
			'defaults'            => array(
				'name'          => 'defaults',
				'label'         => __( 'Defaults', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/defaults/setup',
				'is_pro'        => true,
			),
			'exif'                => array(
				'name'          => 'exif',
				'label'         => __( 'EXIF', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/exif/setup',
				'menu_icon'     => 'camera',
				'is_pro'        => true,
			),
			'iptc'                => array(
				'name'          => 'iptc',
				'label'         => __( 'IPTC', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/iptc/setup',
				'menu_icon'     => 'camera',
				'is_pro'        => true,
			),
			'optimizer'           => array(
				'name'          => 'optimizer',
				'label'         => __( 'Optimizer', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/optimizer/setup',
				'menu_icon'     => 'image',
				'is_pro'        => true,
			),
			'output'              => array(
				'name'          => 'output',
				'label'         => __( 'Output', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/output/setup',
				'menu_icon'     => 'general',
				'is_pro'        => true,
			),
			'taxonomy-manager'    => array(
				'name'          => 'taxonomy-manager',
				'label'         => __( 'Taxonomies', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/taxonomy-manager/setup',
				'is_pro'        => true,
			),
			'zip'                 => array(
				'name'          => 'zip',
				'label'         => __( 'ZIP and Unzip', 'media-library-organizer' ),
				'documentation' => $this->base->plugin->documentation_url . '/zip-unzip/setup',
				'is_pro'        => true,
			),
		);

		/**
		 * Define Addon tabs in the Plugin Settings section.
		 *
		 * @since   1.1.0
		 *
		 * @param   array   $tabs       Settings Tabs.
		 * @param   string  $screen     Current Screen Name to define Tabs for.
		 */
		$addon_tabs = apply_filters( 'media_library_organizer_admin_get_screen_addon_tabs', $addon_tabs, $screen );

		// Sort additional tabs alphabetically.
		ksort( $addon_tabs );

		// Return.
		return $addon_tabs;
	}

	/**
	 * Output the Settings screen.
	 * Save POSTed data from the Administration Panel into a WordPress option.
	 *
	 * @since 1.0.0
	 */
	public function admin_screen() {

		// Get the current screen.
		$screen = $this->get_current_screen();
		if ( ! $screen || is_wp_error( $screen ) ) {
			require_once $this->base->plugin->folder . '/views/admin/error.php';
			return;
		}

		// Maybe save settings.
		$this->save_settings( $screen['name'] );

		// Hacky; get the current screen again, so its data is refreshed post save and actions.
		$screen = $this->get_current_screen();
		if ( ! $screen || is_wp_error( $screen ) ) {
			require_once $this->base->plugin->folder . '/views/admin/error.php';
			return;
		}

		// Get the tabs for the given screen.
		$tabs       = $this->get_screen_tabs( $screen['name'] );
		$addon_tabs = $this->get_screen_addon_tabs( $screen['name'] );

		// Get the current tab.
		// If no tab specified, get the first tab.
		$tab = $this->get_current_screen_tab( $tabs );

		// Get Taxonomies.
		$taxonomies = $this->base->get_class( 'taxonomies' )->get_taxonomies();

		// Load View.
		require_once $this->base->plugin->folder . '/views/admin/settings.php';

		// Add footer action to output overlay modal markup.
		add_action( 'admin_footer', array( $this, 'output_modal' ) );
	}

	/**
	 * Outputs the hidden Javascript Modal and Overlay in the Footer.
	 *
	 * @since   1.1.6
	 */
	public function output_modal() {

		// Load view.
		require_once $this->base->plugin->folder . '_modules/dashboard/views/modal.php';
	}

	/**
	 * Outputs General Settings for Addons.
	 *
	 * @since   1.1.1
	 */
	public function output_addon_settings_panel_general() {

		// Load View.
		require_once $this->base->plugin->folder . '/views/admin/settings-general-upgrade.php';
	}

	/**
	 * Outputs Settings Panel(s) for Addons.
	 *
	 * @since   1.1.0
	 */
	public function output_addon_panels() {

		// Define Setting Panel(s) Titles and Descriptions.
		$panels = array(
			'auto-categorization' => array(
				'title'       => __( 'Auto Categorization Settings', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Automatically categorize images uploaded through WordPress using image recognition with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'bulk-quick-edit'     => array(
				'title'       => __( 'Bulk and Quick Edit Settings', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Bulk and Quick Edit Titles, Alt Tags, Captions, Descriptions, Categories, EXIF and IPTC metadata from the List and Grid Media Library Views with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'defaults'            => array(
				'title'       => __( 'Defaults Settings', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Define Default Titles, Alt Tags, Captions, Descriptions and Categories for newly uploaded files where no data is specified with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'exif'                => array(
				'title'       => __( 'EXIF Settings', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Read, write and display EXIF image data with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'iptc'                => array(
				'title'       => __( 'IPTC Settings', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Read, write and display IPTC image data, compatible with Google Image\'s Image Licenses, in %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'optimizer'           => array(
				'title'       => __( 'Optimizer', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Effectively optimizes your images, resulting in faster loading times and better overall website performance with Optimole.', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'output'              => array(
				'title'       => __( 'Output', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Display image previews on hover and determine the thumbnail image size the Media Library with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'taxonomy-manager'    => array(
				'title'       => __( 'Taxonomy Manager', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Define additional Taxonomies that can be used for Attachments in the Media Library with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),

			'zip'                 => array(
				'title'       => __( 'ZIP Settings', 'media-library-organizer' ),
				'description' => sprintf(
					/* translators: Plugin Name */
					__( 'Automatically unzip files when uploaded to the Media Library, and zip multiple Media Library files with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				),
			),
		);

		// Load View.
		require_once $this->base->plugin->folder . '/views/admin/settings-upgrade.php';
	}

	/**
	 * Save settings for the given screen
	 *
	 * @since 1.0
	 *
	 * @param string $screen     Screen Name.
	 */
	public function save_settings( $screen = 'settings' ) {

		// Check that some data was submitted in the request.
		if ( ! isset( $_REQUEST[ $this->base->plugin->name . '_nonce' ] ) ) {
			return;
		}

		// Invalid nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ $this->base->plugin->name . '_nonce' ] ) ), 'media-library-organizer_' . $screen ) ) {
			$this->base->get_class( 'notices' )->add_error_notice( __( 'Invalid nonce specified. Settings NOT saved.', 'media-library-organizer' ) );
			return false;
		}

		$postdata = array_map(
			function ( $data ) {
				if ( is_array( $data ) ) {
					return map_deep( $data, 'sanitize_text_field' );
				}
				return sanitize_text_field( wp_unslash( $data ) );
			},
			$_POST
		);

		// Depending on the screen we're on, save the data and perform some actions.
		switch ( $screen ) {

			/**
			 * Settings
			 */
			case 'settings':
				// General.
				$result = $this->base->get_class( 'settings' )->update_settings( 'general', $postdata['general'] );
				if ( is_wp_error( $result ) ) {
					$this->base->get_class( 'notices' )->add_error_notice( $result->get_error_message() );
					return;
				}

				// User Options.
				$result = $this->base->get_class( 'settings' )->update_settings( 'user-options', $postdata['user-options'] );
				if ( is_wp_error( $result ) ) {
					$this->base->get_class( 'notices' )->add_error_notice( $result->get_error_message() );
					return;
				}

				/**
				 * Save POSTed data on a Settings Screen
				 *
				 * @since   1.0.7
				 *
				 * @param   mixed   $result    Result of saving data (true or WP_Error)
				 * @param   array   $postdata     Unfiltered $postdata data
				 */
				$result = apply_filters( 'media_library_organizer_admin_save_settings', true, $postdata );
				break;

			/**
			 * Other Screens
			 */
			default:
				/**
				 * Saves Settings for a non-setting screen.
				 *
				 * @since   1.0.7
				 *
				 * @param   mixed   $result     Result of importing data (true or WP_Error).
				 * @param   array   $postdata      Unfiltered $postdata data to save.
				 */
				$result = apply_filters( 'media_library_organizer_admin_save_settings_' . $screen, '', $postdata );
				break;
		}

		// Check the result.
		if ( isset( $result ) && is_wp_error( $result ) ) {
			$this->base->get_class( 'notices' )->add_error_notice( $result->get_error_message() );
			return;
		}

		// OK.
		$this->base->get_class( 'notices' )->add_success_notice( __( 'Settings saved.', 'media-library-organizer' ) );
		return true;
	}

	/**
	 * Helper method to get the setting value from the Plugin settings
	 *
	 * @since 1.0.0
	 *
	 * @param   string $screen   Screen.
	 * @param   string $key      Setting Key.
	 * @return  mixed               Value
	 */
	public function get_setting( $screen = '', $key = '' ) {

		return $this->base->get_class( 'settings' )->get_setting( $screen, $key );
	}
}
