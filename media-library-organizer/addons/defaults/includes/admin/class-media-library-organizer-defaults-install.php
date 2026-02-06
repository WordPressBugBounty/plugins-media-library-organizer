<?php
/**
 * Installation class.
 *
 * @package   Media_Library_Organizer_Defaults
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Install' ) ) {

	/**
	 * Runs migration routines when the Plugin is updated.
	 *
	 * @package   Media_Library_Organizer_Defaults
	 * @author    WP Media Library
	 * @version   1.1.6
	 */
	class Media_Library_Organizer_Defaults_Install {

		/**
		 * Holds the base class object.
		 *
		 * @since   1.1.6
		 *
		 * @var     object
		 */
		public $base;

		/**
		 * Constructor
		 *
		 * @since   1.1.6
		 *
		 * @param   object $base    Base Plugin Class.
		 */
		public function __construct( $base ) {

			// Store base class.
			$this->base = $base;
		}

		/**
		 * Runs migration routines when the plugin is updated
		 *
		 * @since   1.1.6
		 */
		public function upgrade() {

			if ( ! function_exists( 'Media_Library_Organizer_Pro' ) ) {
				return;
			}

			global $wpdb;

			// Get current installed version number.
			// false | 1.1.6.
			$installed_version = get_option( $this->base->plugin->name . '-version' );

			// If the version number matches the plugin version, bail.
			if ( $installed_version === Media_Library_Organizer_Pro()->plugin->version ) {
				return;
			}

			/**
			 * 1.1.6: Migrate Defaults to Rulesets
			 */
			if ( ! $installed_version || $installed_version < '1.1.6' ) {
				$this->migrate_defaults_to_rulesets();
			}

			// Update the version number.
			update_option( $this->base->plugin->name . '-version', Media_Library_Organizer_Pro()->plugin->version );
		}

		/**
		 * Migrates 1.1.5 and earlier settings for this Addon to the new rulesets structure for
		 * 1.1.6 and higher
		 *
		 * @since   1.1.6
		 */
		private function migrate_defaults_to_rulesets() {

			// Get settings.
			$settings = Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'defaults' );

			// If no settings are defined, bail.
			if ( ! $settings ) {
				return;
			}

			// Get taxonomy name.
			$taxonomy_name = 'mlo-category';

			// Get file types.
			$file_types = Media_Library_Organizer()->get_class( 'mime' )->get_file_types();

			// Iterate through file types.
			foreach ( $file_types as $file_type => $file_type_label ) {
				// Skip if no settings exist for this file type.
				if ( empty( $settings[ $file_type ] ) ) {
					continue;
				}

				// If settings already contain the 'rulesets' key, skip.
				// This shouldn't ever happen, but it's a useful sanity check.
				if ( isset( $settings['rulesets'] ) ) {
					continue;
				}

				// Migrate the settings to the first ruleset.
				$settings[ $file_type ]['rulesets'] = array(
					array(
						'title'        => ! empty( $settings[ $file_type ]['title'] ) ? $settings[ $file_type ]['title'] : '',
						'caption'      => ! empty( $settings[ $file_type ]['caption'] ) ? $settings[ $file_type ]['caption'] : '',
						'description'  => ! empty( $settings[ $file_type ]['description'] ) ? $settings[ $file_type ]['description'] : '',
						'alt_text'     => ! empty( $settings[ $file_type ]['alt_text'] ) ? $settings[ $file_type ]['alt_text'] : '',
						$taxonomy_name => ! empty( $settings[ $file_type ][ $taxonomy_name ] ) ? $settings[ $file_type ][ $taxonomy_name ] : '',
						'rules'        => false,
					),
				);

				// Remove the old settings.
				unset(
					$settings[ $file_type ]['alt_text'],
					$settings[ $file_type ]['title'],
					$settings[ $file_type ]['caption'],
					$settings[ $file_type ]['description'],
					$settings[ $file_type ][ $taxonomy_name ]
				);
			}

			// Save settings.
			Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'defaults', $settings );
		}
	}
}
