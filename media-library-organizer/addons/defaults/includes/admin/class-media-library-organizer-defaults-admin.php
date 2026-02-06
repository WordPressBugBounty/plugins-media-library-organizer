<?php
/**
 * Administration class.
 *
 * @package   Media_Library_Organizer_Defaults
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Admin' ) ) {

	/**
	 * Outputs and saves options in the Plugin's settings for this Addon.
	 *
	 * @package   Media_Library_Organizer_Defaults
	 * @author    WP Media Library
	 * @version   1.1.0
	 */
	class Media_Library_Organizer_Defaults_Admin {

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

			add_action( 'media_library_organizer_admin_scripts_js_general', array( $this, 'enqueue_scripts' ), 10, 4 );
			add_action( 'media_library_organizer_admin_scripts_js_general', array( $this, 'enqueue_css' ), 10, 0 );
			add_filter( 'media_library_organizer_admin_save_settings', array( $this, 'save_settings' ), 10, 2 );
			add_filter( 'media_library_organizer_localize_settings', array( $this, 'localize_settings' ) );
			add_filter( 'media_library_organizer_defaults_fields', array( $this, 'defaults_fields' ) );
		}

		/**
		 * Enqueues JS if we're on the Plugin Settings screen
		 *
		 * @since   1.1.5
		 *
		 * @param   object $screen     get_current_screen().
		 * @param   array  $screens    Available Plugin Screens.
		 * @param   string $mode       Media View Mode (list|grid).
		 * @param   string $ext        If defined, loads minified JS.
		 */
		public function enqueue_scripts( $screen, $screens, $mode, $ext ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

			// Tags.
			wp_enqueue_script( 'wpzinc-admin-tags' );

			// Autocomplete.
			wp_enqueue_script( 'wpzinc-admin-autocomplete' );
			wp_localize_script(
				'wpzinc-admin-autocomplete',
				'wpzinc_autocomplete',
				array(
					array(
						'fields'   => Media_Library_Organizer()->get_class( 'common' )->get_autocomplete_enabled_fields(),
						'triggers' => array(
							array(
								'trigger' => '{',
								'values'  => Media_Library_Organizer()->get_class( 'dynamic_tags' )->get_key_value_pairs(),
							),
						),
					),
				)
			);

			// Settings.
			wp_enqueue_script( $this->base->plugin->name . '-settings', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'settings' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'jquery' ), Media_Library_Organizer()->plugin->version, true );
			wp_localize_script(
				$this->base->plugin->name . '-settings',
				'media_library_organizer_defaults_settings',
				array(
					'delete_ruleset_message' => __( 'Are you sure you want to delete this ruleset?', 'media-library-organizer' ),
				)
			);
		}

		/**
		 * Enqueue CSS, if we're on the Plugin Settings screen
		 *
		 * @since   1.1.5
		 */
		public function enqueue_css() {

			// WP Zinc.
			wp_enqueue_style( 'wpzinc-admin' );

			// Enqueue CSS.
			wp_enqueue_style( $this->base->plugin->name . '-settings', $this->base->plugin->url . '/assets/css/settings.css', array(), Media_Library_Organizer()->plugin->version );
		}

		/**
		 * Save Settings for this Addon.
		 *
		 * @since   1.1.0
		 *
		 * @param   mixed $result     Result (WP_Error|true).
		 * @param   array $settings   Settings.
		 */
		public function save_settings( $result, $settings ) {

			// Bail if no settings for this Addon were posted.
			if ( ! isset( $settings['defaults'] ) ) {
				return $result;
			}

			// For each file type, make the rulesets associative.
			$file_types = Media_Library_Organizer()->get_class( 'mime' )->get_file_types();
			foreach ( $file_types as $file_type => $label ) {
				// Skip if no settings exist for the file type.
				if ( ! isset( $settings['defaults'][ $file_type ] ) ) {
					continue;
				}
				if ( ! isset( $settings['defaults'][ $file_type ]['rulesets'] ) ) {
					continue;
				}
			}

			// Save Settings.
			return Media_Library_Organizer()->get_class( 'settings' )->update_settings( 'defaults', $settings['defaults'] );
		}

		/**
		 * Helper method to get the setting value from the Plugin settings
		 *
		 * @since   1.1.0
		 *
		 * @param   string $screen   Screen.
		 * @param   string $key      Setting Key.
		 * @return  mixed               Value
		 */
		public function get_setting( $screen = '', $key = '' ) {

			return Media_Library_Organizer()->get_class( 'settings' )->get_setting( $screen, $key );
		}

		/**
		 * Add Defaults data to the localize.
		 *
		 * @param  array $settings settings array.
		 * @return array
		 */
		public function localize_settings( $settings ) {
			$settings['defaults'] = Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'defaults' );
			return $settings;
		}

		/**
		 * Get default fields.
		 *
		 * @param array $fields Current fields array.
		 * @return array
		 */
		public function defaults_fields( $fields ) {
			$file_types = Media_Library_Organizer()->get_class( 'mime' )->get_file_types();

			foreach ( $file_types as $file_type => $file_type_label ) {
				$fields[ $file_type ]['rulesets']['fields'] = $this->default_fields( $file_type );
				$fields[ $file_type ]['rulesets']['rules']  = $this->default_rules( $file_type );

				foreach ( $this->default_attachement_display( $file_type ) as $display_options => $_option ) {
					$fields[ $file_type ][ 'attachment_display_' . $display_options ] = $_option;
				}

				/**
				 * Default fields.
				 *
				 * @param array $fields Fields
				 */
				$fields = apply_filters( 'media_library_organizer_defaults_settings_output_' . $file_type, $fields );
			}

			$fields['comparison_operators'] = $this->base->get_class( 'common' )->get_attribute_comparison_operators();
			$fields['pass_rules']           = $this->base->get_class( 'common' )->get_attribute_pass_rules();
			return $fields;
		}

		/**
		 * Get default rules.
		 *
		 * @param string $file_type File type.
		 * @return array
		 */
		private function default_rules( $file_type ) {
			$rules = array();

			if ( 'image' === $file_type ) {
				$rules['alt_text'] = __( 'Alt Text', 'media-library-organizer' );
			}

			$rules['title']       = __( 'Title', 'media-library-organizer' );
			$rules['caption']     = __( 'Caption', 'media-library-organizer' );
			$rules['description'] = __( 'Description', 'media-library-organizer' );
			$rules['filename']    = __( 'Media Folders', 'media-library-organizer' );

			return $rules;
		}

		/**
		 * Get default fiels.
		 *
		 * @param string $file_type File type.
		 * @return array
		 */
		private function default_fields( $file_type ) {
			$fields = array();

			if ( 'image' === $file_type ) {
				$fields['alt_text'] = __( 'Alt Text', 'media-library-organizer' );
			}

			$fields['title']       = __( 'Title', 'media-library-organizer' );
			$fields['caption']     = __( 'Caption', 'media-library-organizer' );
			$fields['description'] = __( 'Description', 'media-library-organizer' );

			$taxonomies = Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies();

			foreach ( $taxonomies as $key => $value ) {
				if ( 'mlo-category' === $key ) {
					$fields['taxonomy'][ $key ] = array(
						'title'   => __( 'Media Folder', 'media-library-organizer' ),
						'options' => $this->get_taxonomy_terms( $key ),
					);
				} else {
					$fields['taxonomy'][ $key ] = array(
						'title'   => $value['plural_name'],
						'options' => $this->get_taxonomy_terms( $key ),
					);
				}
			}

			return $fields;
		}

		/**
		 * Get default attachment display options.
		 *
		 * @param string $file_type File type.
		 */
		private function default_attachement_display( $file_type ) {
			$attachment_display_options = array();
			if ( 'image' === $file_type ) {
				$attachment_display_options = array(
					'alignment' => array(
						'title'   => __( 'Alignment', 'media-library-organizer' ),
						'options' => Media_Library_Organizer()->get_class( 'common' )->get_attachment_display_settings_alignment( $file_type ),
					),
					'link_to'   => array(
						'title'   => __( 'Link To', 'media-library-organizer' ),
						'options' => Media_Library_Organizer()->get_class( 'common' )->get_attachment_display_settings_link_to( $file_type ),
					),
					'size'      => array(
						'title'   => __( 'Size', 'media-library-organizer' ),
						'options' => Media_Library_Organizer()->get_class( 'common' )->get_attachment_display_settings_size( $file_type ),
					),
				);
			} else {
				$attachment_display_options = array(
					'link_to' => array(
						'title'   => __( 'Link To', 'media-library-organizer' ),
						'options' => Media_Library_Organizer()->get_class( 'common' )->get_attachment_display_settings_link_to( $file_type ),
					),
				);
			}

			return $attachment_display_options;
		}

		/**
		 * Get taxonomy terms.
		 *
		 * @param string $taxonomy Taxonomy slug.
		 * @return array
		 */
		private function get_taxonomy_terms( $taxonomy ) {
			$options = array();
			$terms   = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			foreach ( $terms as $_term ) {
				$options[ $_term->term_id ] = $_term->name;
			}

			return $options;
		}
	}
}
