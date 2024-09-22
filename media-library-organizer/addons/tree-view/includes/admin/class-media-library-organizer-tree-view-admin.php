<?php
/**
 * Tree View Admin class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Tree View class
 *
 * @since   1.1.1
 */
class Media_Library_Organizer_Tree_View_Admin {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.1.1
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.1.1
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Settings.
		add_filter( 'media_library_organizer_admin_get_screen_addon_tabs', array( $this, 'get_screen_addon_tabs' ), 10, 2 );
		add_action( 'media_library_organizer_admin_output_settings_panels', array( $this, 'output_settings_panels' ) );
		add_filter( 'media_library_organizer_admin_save_settings', array( $this, 'save_settings' ), 10, 2 );
	}

	/**
	 * Adds this Addon as a tab to the Settings screen.
	 *
	 * @since   1.1.1
	 *
	 * @param   array  $tabs       Tabs.
	 * @param   string $screen     Screen.
	 * @return  array               Tabs
	 */
	public function get_screen_addon_tabs( $tabs, $screen ) {

		switch ( $screen ) {

			/**
			 * Settings
			 */
			case 'settings':
				// Define tab.
				$tabs['tree-view'] = array(
					'name'          => 'tree-view',
					'label'         => $this->base->plugin->displayName,
					'documentation' => $this->base->plugin->documentation_url . '/setup',
					'menu_icon'     => 'list',
				);
				break;

		}

		return $tabs;
	}

	/**
	 * Outputs Settings Panel(s) for this Addon.
	 *
	 * @since   1.1.1
	 */
	public function output_settings_panels() {

		// Load View.
		require_once $this->base->plugin->folder . '/views/admin/settings.php';
	}

	/**
	 * Save Settings for this Addon.
	 *
	 * @since   1.1.1
	 *
	 * @param   mixed $result     Result (WP_Error|true).
	 * @param   array $settings   Settings.
	 * @return  bool              Success
	 */
	public function save_settings( $result, $settings ) {

		// Bail if no settings for this Addon were posted.
		if ( ! isset( $settings['tree-view'] ) ) {
			return $result;
		}

		// Save Settings.
		return Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'tree-view', $settings['tree-view'] );
	}

	/**
	 * Helper method to get the setting value from the Plugin settings
	 *
	 * @since   1.1.1
	 *
	 * @param   string $screen   Screen.
	 * @param   string $key      Setting Key.
	 * @return  mixed               Value
	 */
	public function get_setting( $screen = '', $key = '' ) {

		return Media_Library_Organizer()->get_class( 'settings' )->get_setting( $screen, $key );
	}
}
