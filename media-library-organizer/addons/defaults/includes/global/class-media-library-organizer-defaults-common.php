<?php
/**
 * Common class.
 *
 * @package   Media_Library_Organizer_Defaults
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Common' ) ) {

	/**
	 * Helper functions that don't fit into other Addon clases.
	 *
	 * @package   Media_Library_Organizer_Defaults
	 * @author    WP Media Library
	 * @version   1.1.5
	 */
	class Media_Library_Organizer_Defaults_Common {

		/**
		 * Holds the class object.
		 *
		 * @since   1.1.5
		 *
		 * @var     object
		 */
		public $base;

		/**
		 * Constructor
		 *
		 * @since   1.1.5
		 *
		 * @param   object $base    Base Plugin Class.
		 */
		public function __construct( $base ) {

			// Store base class.
			$this->base = $base;
		}

		/**
		 * Helper method to retrieve Attachment Attribute comparison operators, that can be used
		 * to compare an Attachment's Attribute to a value.
		 *
		 * @since   1.1.5
		 *
		 * @return  array   Meta Compare options
		 */
		public function get_attribute_comparison_operators() {

			// Define meta compare options.
			$comparison_operators = array(
				''          => __( 'No Comparison (Skip)', 'media-library-organizer' ),
				'='         => __( 'Equals', 'media-library-organizer' ),
				'!='        => __( 'Does not Equal', 'media-library-organizer' ),
				'>'         => __( 'Greater Than', 'media-library-organizer' ),
				'>='        => __( 'Greater Than or Equal To', 'media-library-organizer' ),
				'<'         => __( 'Less Than', 'media-library-organizer' ),
				'<='        => __( 'Less Than or Equal To', 'media-library-organizer' ),
				'LIKE'      => __( 'Contains', 'media-library-organizer' ),
				'NOT LIKE'  => __( 'Does Not Contain', 'media-library-organizer' ),
				'EMPTY'     => __( 'Empty (Value Ignored)', 'media-library-organizer' ),
				'NOT EMPTY' => __( 'Not Empty (Value Ignored)', 'media-library-organizer' ),
			);

			/**
			 * Defines the available Attachment Attribute comparison operators
			 *
			 * @since   1.1.5
			 *
			 * @param   array   $comparison_operators    Comparison Operators.
			 */
			$comparison_operators = apply_filters( 'media_library_organizer_auto_categorization_common_get_attribute_comparison_operators', $comparison_operators );

			// Return filtered results.
			return $comparison_operators;
		}

		/**
		 * Helper method to retrieve rule comparison operators, that can be used
		 * to treat rules in a ruleset together or separately.
		 *
		 * @since   1.1.5
		 *
		 * @return  array   Comparison Operators
		 */
		public function get_rule_comparison_operators() {

			// Define meta compare options.
			$comparison_operators = array(
				'AND' => __( 'ALL Rules must be met', 'media-library-organizer' ),
				'OR'  => __( 'ANY Rule must be met', 'media-library-organizer' ),
			);

			/**
			 * Defines the available Rule Comparison Operator
			 *
			 * @since   1.1.5
			 *
			 * @param   array   $comparison_operators    Comparison Operators.
			 */
			$comparison_operators = apply_filters( 'media_library_organizer_auto_categorization_common_get_rule_comparison_operators', $comparison_operators );

			// Return filtered results.
			return $comparison_operators;
		}

		/**
		 * Helper method to retrieve pass rule conditions.
		 *
		 * @return  array
		 */
		public function get_attribute_pass_rules() {
			$pass_rules = array(
				'1'  => __( 'All Rules must Pass', 'media-library-organizer' ),
				'0'  => __( 'Any Rule can Pass', 'media-library-organizer' ),
				'-1' => __( 'Ignore Rules (just apply Attributes)', 'media-library-organizer' ),
			);

			/**
			 * Defines the available pass rule operator.
			 *
			 * @param   array   $pass_rules    Pass Rules..
			 */
			$pass_rules = apply_filters( 'media_library_organizer_auto_categorization_common_get_pass_rules', $pass_rules );

			// Return filtered results.
			return $pass_rules;
		}
	}
}
