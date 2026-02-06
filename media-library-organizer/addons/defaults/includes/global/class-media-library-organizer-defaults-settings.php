<?php
/**
 * Settings class.
 *
 * @package   Media_Library_Organizer_Defaults
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Settings' ) ) {

	/**
	 * Gets default settings for this Addon.
	 *
	 * @package   Media_Library_Organizer_Defaults
	 * @author    WP Media Library
	 * @version   1.1.0
	 */
	class Media_Library_Organizer_Defaults_Settings {

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

			add_filter( 'media_library_organizer_settings_get_default_settings', array( $this, 'get_default_settings' ), 10, 1 );
		}

		/**
		 * Defines default settings for this Plugin
		 *
		 * @since   1.1.0
		 *
		 * @param   array $defaults   Default Settings.
		 * @return  array               Default Settings
		 */
		public function get_default_settings( $defaults ) {

			// Get file types.
			$file_types = Media_Library_Organizer()->get_class( 'mime' )->get_file_types();

			// Build defaults array.
			$defaults['defaults'] = array(
				'enabled' => 0,
			);

			// Build default rule.
			$rule = array(
				'title'               => '',
				'caption'             => '',
				'description'         => '',
				'alt_text'            => '',
				'all_rules_must_pass' => 0,
				'rules'               => array(
					'title'       => array(
						'comparison' => '',
						'value'      => '',
					),
					'caption'     => array(
						'comparison' => '',
						'value'      => '',
					),
					'description' => array(
						'comparison' => '',
						'value'      => '',
					),
					'alt_text'    => array(
						'comparison' => '',
						'value'      => '',
					),
					'filename'    => array(
						'comparison' => '',
						'value'      => '',
					),
				),
			);

			// Add Taxonomies to default rule.
			foreach ( Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
				$rule[ $taxonomy_name ] = array();
			}

			// Add rule to each File Type.
			foreach ( $file_types as $file_type => $label ) {
				$defaults['defaults'][ $file_type ] = array(
					'rulesets'                     => array(
						$rule,
					),
					'attachment_display_alignment' => 'none',
					'attachment_display_link_to'   => ( in_array( $file_type, array( 'video', 'audio', 'other' ), true ) ? 'embed' : 'none' ),
					'attachment_display_size'      => 'full',
				);
			}

			/**
			 * Filter the default settings for the Defaults Addon
			 *
			 * @since   1.1.0
			 *
			 * @param   array   $defaults['defaults']   Defaults.
			 */
			$defaults['defaults'] = apply_filters( 'media_library_organizer_defaults_settings_get_default_settings', $defaults['defaults'] );

			// Return.
			return $defaults;
		}

		/**
		 * Returns all settings for the given File Type
		 *
		 * @since   1.1.0
		 *
		 * @param   string $file_type  File Type (image|video|audio|document).
		 * @return  array               Settings
		 */
		public function get_settings_by_file_type( $file_type ) {

			// Fetch all settings for this Addon.
			$settings = Media_Library_Organizer()->get_class( 'settings' )->get_settings( 'defaults' );

			// If the file type doesn't have any settings, use 'other'.
			if ( ! isset( $settings[ $file_type ] ) ) {
				$file_type = 'other';
			}

			// Return defaults.
			return $settings[ $file_type ];
		}

		/**
		 * Makes the given File Type Rulesets associative e.g.
		 * $rulesets[alt_text][0] --> $rulesets[0][alt_text]
		 *
		 * @since   1.1.6
		 *
		 * @param   array  $rulesets   Rulesets.
		 * @param   string $file_type  File Type.
		 * @return  array               Associative Rulesets
		 */
		public function make_rulesets_associative( $rulesets, $file_type ) {

			// Define empty array to hold associative rulesets.
			$associative_rulesets = array();

			// Iterate through POSTed rulesets, converting them into an associative ruleset array.
			foreach ( $rulesets['title'] as $index => $value ) {

				// Build this ruleset, adding it to the associative rulesets array.
				$associative_ruleset = array(
					'alt_text'            => ( isset( $rulesets['alt_text'][ $index ] ) ? $rulesets['alt_text'][ $index ] : '' ),
					'title'               => ( isset( $rulesets['title'][ $index ] ) ? $rulesets['title'][ $index ] : '' ),
					'caption'             => ( isset( $rulesets['caption'][ $index ] ) ? $rulesets['caption'][ $index ] : '' ),
					'description'         => ( isset( $rulesets['description'][ $index ] ) ? $rulesets['description'][ $index ] : '' ),
					'all_rules_must_pass' => ( isset( $rulesets['all_rules_must_pass'][ $index ] ) ? $rulesets['all_rules_must_pass'][ $index ] : 0 ),
					'rules'               => array(
						'alt_text'    => array(
							'comparison' => ( isset( $rulesets['rules']['alt_text']['comparison'][ $index ] ) ? $rulesets['rules']['alt_text']['comparison'][ $index ] : '' ),
							'value'      => ( isset( $rulesets['rules']['alt_text']['value'][ $index ] ) ? $rulesets['rules']['alt_text']['value'][ $index ] : '' ),
						),
						'title'       => array(
							'comparison' => ( isset( $rulesets['rules']['title']['comparison'][ $index ] ) ? $rulesets['rules']['title']['comparison'][ $index ] : '' ),
							'value'      => ( isset( $rulesets['rules']['title']['value'][ $index ] ) ? $rulesets['rules']['title']['value'][ $index ] : '' ),
						),
						'caption'     => array(
							'comparison' => ( isset( $rulesets['rules']['caption']['comparison'][ $index ] ) ? $rulesets['rules']['caption']['comparison'][ $index ] : '' ),
							'value'      => ( isset( $rulesets['rules']['caption']['value'][ $index ] ) ? $rulesets['rules']['caption']['value'][ $index ] : '' ),
						),
						'description' => array(
							'comparison' => ( isset( $rulesets['rules']['description']['comparison'][ $index ] ) ? $rulesets['rules']['description']['comparison'][ $index ] : '' ),
							'value'      => ( isset( $rulesets['rules']['description']['value'][ $index ] ) ? $rulesets['rules']['description']['value'][ $index ] : '' ),
						),
						'filename'    => array(
							'comparison' => ( isset( $rulesets['rules']['filename']['comparison'][ $index ] ) ? $rulesets['rules']['filename']['comparison'][ $index ] : '' ),
							'value'      => ( isset( $rulesets['rules']['filename']['value'][ $index ] ) ? $rulesets['rules']['filename']['value'][ $index ] : '' ),
						),
					),
				);

				// Add Taxonomies' Terms.
				foreach ( Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
					// Bail if this Taxonomy isn't specified at all.
					if ( ! isset( $rulesets[ $taxonomy_name ] ) ) {
						$associative_ruleset[ $taxonomy_name ] = array();
						continue;
					}
					if ( empty( $rulesets[ $taxonomy_name ] ) ) {
						$associative_ruleset[ $taxonomy_name ] = array();
						continue;
					}

					// Build Term IDs, casting as integers.
					$terms = array();
					foreach ( explode( ',', $rulesets[ $taxonomy_name ][ $index ] ) as $term_id ) {
						$terms[] = absint( $term_id );
					}

					// Add to associative ruleset.
					$associative_ruleset[ $taxonomy_name ] = $terms;
				}

				/**
				 * Makes the given Defaults' File Type Rulesets associative e.g.
				 * $rulesets[alt_text][0] --> $rulesets[0][alt_text]
				 *
				 * @since   1.2.2
				 *
				 * @param   array   $associative_ruleset    Associative Ruleset.
				 * @param   array   $rulesets               POSTed Ruleset from Settings Form.
				 * @param   int     $index                  POSTed Ruleset Index.
				 */
				$associative_ruleset = apply_filters( 'media_library_organizer_defaults_settings_make_rulesets_associative_' . $file_type, $associative_ruleset, $rulesets, $index );

				// Assign to associative rulesets array.
				$associative_rulesets[ $index ] = $associative_ruleset;
			}

			// Return.
			return $associative_rulesets;
		}
	}
}
