<?php
/**
 * Admin class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
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

		add_action( 'created_term', array( $this, 'after_term_created' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'after_term_created' ), 10, 3 );
		add_action( 'pre_delete_term', array( $this, 'after_term_deleted' ) );

		// Survey data.
		add_filter( 'themeisle-sdk/survey/mlo', array( $this, 'get_survey_data' ), 10, 2 );
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

		// If any page load media-editor, enqueue.
		if ( wp_script_is( 'media-editor' ) ) {
			$this->enqueue_scripts_css( 'media_editor', $screen, $screens, $mode, $ext );
			return;
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

		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		// Plugin JS.
		wp_enqueue_script( $this->base->plugin->name . '-selectize' );
		wp_enqueue_script( $this->base->plugin->name . '-settings', $this->base->plugin->url . 'assets/build/settings.js', array(), $this->base->plugin->version, true );

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
				'is_pro'               => function_exists( 'Media_Library_Organizer_Pro' ) && Media_Library_Organizer_Pro()->check_license_key_valid(),
				'settings'             => $this->get_settings(),
				'api'                  => rest_url( $this->base->plugin->namespace ),
				'rest_nonce'           => wp_create_nonce( 'wp_rest' ),
				'defaults'             => Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'defaults' ),
				'defaults_fields'      => apply_filters( 'media_library_organizer_defaults_fields', array() ),
				'optimole_data'        => $this->get_optimole_data(),
			)
		);
		wp_set_script_translations( $this->base->plugin->name . '-settings', 'media-library-organizer' );

		// CSS.
		wp_enqueue_style( 'wpzinc-admin-selectize' );
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		$settings = array(
			'general'          => $this->base->get_class( 'settings' )->get_settings( 'general' ),
			'user-options'     => $this->base->get_class( 'settings' )->get_settings( 'user-options' ),
			'taxonomy-manager' => $this->base->get_class( 'taxonomies' )->get_taxonomies(),
		);

		return apply_filters( 'media_library_organizer_localize_settings', $settings );
	}

	/**
	 * Get optimole data.
	 *
	 * @return array
	 */
	private function get_optimole_data() {
		$data = get_transient( 'mlo_optimole_data' );

		if ( empty( $data ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$data = plugins_api( 'plugin_information', array( 'slug' => 'optimole-wp' ) );

			if ( ! is_wp_error( $data ) ) {
				set_transient( 'mlo_optimole_data', $data, 12 * HOUR_IN_SECONDS );
			}
		}

		if ( ! is_object( $data ) ) {
			$data->num_ratings     = 612;
			$data                  = (object) array();
			$data->rating          = 94;
			$data->active_installs = 200000;
		}

		$rating          = (int) $data->rating * 5 / 100;
		$rating          = number_format( $rating, 1 );
		$active_installs = number_format( $data->active_installs );

		$installed = file_exists( WP_PLUGIN_DIR . '/optimole-wp/optimole-wp.php' );

		return array(
			'installed'      => $installed,
			'active'         => is_plugin_active( 'optimole-wp/optimole-wp.php' ),
			'logoURL'        => $this->base->plugin->url . 'assets/images/optimole-logo.png',
			// translators: %1$s: rating, %2$d: number of reviews.
			'ratingByline'   => sprintf( __( '%1$s out of 5 stars (%2$d reviews)', 'media-library-organizer' ), $rating, $data->num_ratings ),
			// translators: %s: number of active installations.
			'activeInstalls' => sprintf( __( '%s+ Active installations', 'media-library-organizer' ), $active_installs ),
			'cta'            => $installed ? __( 'Activate Optimole', 'media-library-organizer' ) : __( 'Install Optimole', 'media-library-organizer' ),
			'thickboxURL'    => add_query_arg(
				array(
					'tab'       => 'plugin-information',
					'plugin'    => 'optimole-wp',
					'TB_iframe' => 'true',
					'width'     => '600',
					'height'    => '500',
				),
				network_admin_url( 'plugin-install.php' )
			),
		);
	}

	/**
	 * Enqueues CSS for the Settings screen.
	 *
	 * @since   1.0.3
	 */
	public function enqueue_css_settings() {

		// Enqueue CSS.
		wp_enqueue_style( $this->base->plugin->name . '-admin', $this->base->plugin->url . '/assets/build/settings.css', array(), $this->base->plugin->version );
	}

	/**
	 * Adds menu and sub menu items to the WordPress Administration.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {

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
		$hook = add_menu_page( $this->base->plugin->displayName, $this->base->plugin->displayName, $minimum_capability, $this->base->plugin->name, array( $this, 'admin_screen' ), 'dashicons-admin-media' );

		add_action(
			'load-' . $hook,
			function () {
				do_action( 'themeisle_internal_page', 'mlo', 'dashboard' );
			}
		);

		// Iterate through screens, adding as submenu items.
		foreach ( (array) $screens as $screen ) {
			// The settings screen doesn't need to append the page slug.
			$slug = ( ( 'settings' === $screen['name'] ) ? $this->base->plugin->name : $this->base->plugin->name . '-' . $screen['name'] );

			// Define ACL name.
			$access = str_replace( '-', '_', str_replace( $this->base->plugin->name, '', $slug ) );
			if ( empty( $access ) ) {
				$access = 'settings';
			}

			// Add submenu page.
			add_submenu_page( $this->base->plugin->name, $screen['label'], $screen['label'], $minimum_capability, $slug, array( $this, 'admin_screen' ) );
		}

		do_action( 'media_library_organizer_admin_menu_import_export' );

		do_action( 'media_library_organizer_admin_menu_support' );
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
				'columns'       => 2,
				'data'          => array(),
				'documentation' => 'https://wpmedialibrary.com/documentation/media-library-organizer/setup/',
				'type'          => 'settings',
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
		$tabs = $this->get_screen_tabs( $screen['name'] );

		// Get the current tab.
		// If no tab specified, get the first tab.
		$tab = $this->get_current_screen_tab( $tabs );

		// Get Taxonomies.
		$taxonomies = $this->base->get_class( 'taxonomies' )->get_taxonomies();

		if ( isset( $screen['type'] ) ) {
			echo '<div id="mlo-' . esc_attr( $screen['type'] ) . '"></div>';
		} else {
			// Load other View pages.
			require_once $this->base->plugin->folder . '/views/admin/settings.php';
		}

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

	/**
	 * Add created_at and modified_at after term create.
	 *
	 * @param int    $term_id       Term ID.
	 * @param int    $taxonomy_id   Taxonomy ID.
	 * @param string $taxonomy_name Taxonomy slug.
	 */
	public function after_term_created( $term_id, $taxonomy_id, $taxonomy_name ) {
		$taxonomies     = $this->base->get_class( 'taxonomies' )->get_taxonomies();
		$taxonomies_key = array_keys( $taxonomies );

		if ( ! in_array( $taxonomy_name, $taxonomies_key, true ) ) {
			return;
		}

		if ( ! get_term_meta( $term_id, '_created_at', true ) ) {
			update_term_meta( $term_id, '_created_at', current_time( 'mysql' ) );
		}

		update_term_meta( $term_id, '_modified_at', current_time( 'mysql' ) );
	}

	/**
	 * Run after term delete.
	 *
	 * @param int $term_id Term ID.
	 */
	public function after_term_deleted( $term_id ) {
		$startup_folder = $this->base->get_class( 'settings' )->get_setting( 'output', 'startup_folder' );
		$term           = get_term( $term_id );
		if ( $startup_folder === $term->slug ) {
			$this->base->get_class( 'settings' )->update_setting( 'output', 'startup_folder', '' );
		}
	}

	/**
	 * Get the data used for the survey.
	 *
	 * @param array  $data Existing survey data.
	 * @param string $page_slug Current page slug.
	 *
	 * @return array
	 * @see survey.js
	 */
	public function get_survey_data( $data, $page_slug ) {
		if ( $page_slug !== 'dashboard' ) {
			return $data;
		}

		$license_data        = get_option( 'media_library_organizer_pro_license_data', array() );
		$user_id             = 'mlo_' . ( ! empty( $license_data->key ) ? $license_data->key : preg_replace( '/[^\w\d]*/', '', get_site_url() ) );
		$product_key         = str_replace( '-', '_', $this->base->plugin->name );
		$current_time        = time();
		$install_date        = get_option( $product_key . '_install', $current_time );
		$install_days_number = intval( ( $current_time - $install_date ) / DAY_IN_SECONDS );
		$plan                = apply_filters( 'product_mlo_license_plan', 0 );
		$plugin_version      = $this->base->plugin->version;

		$attributes = array(
			'free_version'        => $plugin_version,
			'install_days_number' => $install_days_number,
			'license_status'      => ! empty( $license_data->license ) ? $license_data->license : 'invalid',
		);

		if ( defined( 'MEDIA_LIBRARY_ORGANIZER_PRO_PLUGIN_VERSION' ) ) {
			$attributes['pro_version'] = MEDIA_LIBRARY_ORGANIZER_PRO_PLUGIN_VERSION;
		}

		if ( ! empty( $plan ) ) {
			$attributes['plan'] = $plan;
		}

		return array(
			'environmentId' => 'cmg0it51g29x1x501gmd4bl58',
			'userId'        => $user_id,
			'attributes'    => $attributes,
		);
	}
}
