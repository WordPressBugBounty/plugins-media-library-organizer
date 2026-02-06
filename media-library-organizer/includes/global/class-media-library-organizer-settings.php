<?php
/**
 * Settings class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Handles fetching, defining defaults and saving settings in the options table.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Settings {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.5
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * The key prefix to use for settings
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	private $key_prefix = '_mlo';

	/**
	 * Constructor
	 *
	 * @since   1.0.5
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;
	}

	/**
	 * Returns a setting for the given Type
	 *
	 * @since   1.0.0
	 *
	 * @param   string $type       Type.
	 * @param   string $key        Setting Key.
	 * @return  mixed               Setting Value
	 */
	public function get_setting( $type, $key ) {

		// Get settings.
		$settings = $this->get_settings( $type );

		// Get setting.
		$setting = ( isset( $settings[ $key ] ) ? $settings[ $key ] : '' );

		/**
		 * Filters the settings data for the given setting type and key before it is returned.
		 *
		 * @since   1.0.7
		 *
		 * @param   mixed   $setting        Settings Data (string, array, object).
		 * @param   string  $type           Setting Type (option key).
		 * @param   string  $key            Setting Type Key (setting key within option value).
		 */
		$setting = apply_filters( 'media_library_organizer_settings_get_setting', $setting, $type, $key );

		// Return.
		return $setting;
	}

	/**
	 * Returns all settings for the given Type
	 *
	 * @since   1.0.0
	 *
	 * @param    string $type   Type.
	 * @return   array              Settings
	 */
	public function get_settings( $type ) {

		// Get settings.
		$settings = get_option( $this->key_prefix . '_' . $type );

		// Get default settings.
		$defaults = $this->get_default_settings( $type );

		// If we couldn't fetch any defaults, we can only return the settings.
		if ( empty( $defaults ) ) {
			return $settings;
		}

		// If no settings exists, fallback to the defaults.
		if ( ! $settings ) {
			$settings = $defaults;
		} else {
			// Iterate through the defaults, checking if the settings have the same key
			// If not, add the setting key with the default value.
			// This ensures that on a Plugin upgrade where new defaults are introduced,
			// they are immediately available for use without the user needing to save their
			// settings.
			foreach ( $defaults as $default_key => $default_value ) {
				if ( ! isset( $settings[ $default_key ] ) ) {
					$settings[ $default_key ] = $default_value;
				}
			}
		}

		/**
		 * Filters the settings data for the given setting type before it is returned.
		 *
		 * @since   1.0.7
		 *
		 * @param   mixed   $settings       Settings Data (string, array, object).
		 * @param   string  $type           Setting Type (option key).
		 */
		$settings = apply_filters( 'media_library_organizer_settings_get_settings', $settings, $type );

		// Return.
		return $settings;
	}

	/**
	 * Saves a single setting for the given Type and Key
	 *
	 * @since   1.0.0
	 *
	 * @param   string $type   Type.
	 * @param   string $key    Setting Key.
	 * @param   mixed  $value  Setting Value.
	 * @return  bool            Success
	 */
	public function update_setting( $type, $key, $value ) {

		// Get settings.
		$settings = $this->get_settings( $type );

		/**
		 * Defines the setting data just before it is saved for the given type and key.
		 *
		 * @since   1.0.7
		 *
		 * @param   mixed   $value        Settings Data (string, array, object).
		 * @param   string  $type         Setting Type (option key).
		 * @param   string  $key          Setting Type Key (setting key within option value).
		 */
		$value = apply_filters( 'media_library_organizer_settings_update_setting', $value, $type, $key );

		// Update single setting.
		$settings[ $key ] = $value;

		// Update settings.
		return $this->update_settings( $type, $settings );
	}

	/**
	 * Saves all settings for the given Type
	 *
	 * @since 1.0.0
	 *
	 * @param    string $type       Type.
	 * @param    array  $settings   Settings.
	 * @return   bool                Success
	 */
	public function update_settings( $type, $settings ) {

		/**
		 * Defines the settings data just before it is saved for the given type.
		 *
		 * @since   1.0.7
		 *
		 * @param   mixed   $settings     Settings Data (string, array, object).
		 * @param   string  $type         Setting Type (option key).
		 */
		$settings = apply_filters( 'media_library_organizer_settings_update_settings', $settings, $type );

		// Update settings.
		update_option( $this->key_prefix . '_' . $type, $settings );

		return true;
	}

	/**
	 * Deletes a single setting for the given Type and Key
	 *
	 * @since   1.0.0
	 *
	 * @param   string $type   Type.
	 * @param   string $key    Key.
	 * @return  bool            Success
	 */
	public function delete_setting( $type, $key ) {

		// Get settings.
		$settings = $this->get_settings( $type );

		// Delete single setting.
		if ( isset( $settings[ $key ] ) ) {
			unset( $settings[ $key ] );
		}

		/**
		 * Filters the setting data just before it is deleted.
		 *
		 * @since   1.0.7
		 *
		 * @param   mixed   $value        Settings Data (string, array, object).
		 * @param   string  $type         Setting Type (option key).
		 * @param   string  $key          Setting Type Key (setting key within option value).
		 */
		$settings = apply_filters( 'media_library_organizer_settings_delete_setting', $settings, $type, $key );

		// Update settings.
		return $this->update_settings( $type, $settings );
	}

	/**
	 * Deletes all settings for the given Type
	 *
	 * @since   1.0.0
	 *
	 * @param   string $type   Type.
	 * @return  bool            Success
	 */
	public function delete_settings( $type ) {

		// Delete settings.
		delete_option( $this->key_prefix . '_' . $type );

		/**
		 * Runs actions after a Settings Type is deleted.
		 *
		 * @since   1.0.7
		 *
		 * @param   string  $type         Setting Type (option key).
		 */
		do_action( 'media_library_organizer_settings_delete_settings', $type );

		return true;
	}

	/**
	 * Converts multidimensional form data, that is stored in a JSON string using JS' JSON.stringify(),
	 * into a PHP multidimensional array.
	 *
	 * Typically used on settings screens where form data is submitted using AJAX.
	 *
	 * @since   1.1.6
	 *
	 * @param   string $json   JSON String.
	 * @return  array           Multidimensional array
	 */
	public function convert_multidimensional_form_data_json_string_to_array( $json ) {

		$string = '';
		foreach ( json_decode( wp_unslash( $json ) ) as $key => $setting ) {
			$string .= $setting->name . '=' . $setting->value . '&';
		}
		parse_str( rtrim( $string, '&' ), $settings );

		// Drop some keys that aren't setting related.
		unset( $settings['media-library-organizer_nonce'], $settings['_wp_http_referer'] );

		// Return.
		return $settings;
	}

	/**
	 * Returns the default settings for the given Type
	 *
	 * @since   1.0.0
	 *
	 * @param   string $type    Type.
	 * @return  mixed           Default Settings | empty string
	 */
	private function get_default_settings( $type ) {

		// Define defaults.
		$defaults = array(
			// General.
			'general'      => array(
				'mlo-category_enabled' => 1,
				'orderby_enabled'      => 1,
				'order_enabled'        => 1,
			),

			// User Options.
			'user-options' => array(
				'orderby_enabled' => 0,
				'order_enabled'   => 0,
			),
		);

		/**
		 * Defines the default settings for the given type.
		 *
		 * @since   1.0.7
		 *
		 * @param   array   $defaults       Setting Defaults.
		 * @param   string  $type           Setting Type (option key).
		 */
		$defaults = apply_filters( 'media_library_organizer_settings_get_default_settings', $defaults, $type );

		// Return.
		return ( isset( $defaults[ $type ] ) ? $defaults[ $type ] : '' );
	}
}
