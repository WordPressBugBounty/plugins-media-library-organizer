<?php
/**
 * Ruleset class.
 *
 * @package   Media_Library_Organizer_Defaults
 * @author    WP Media Library
 */

if ( ! class_exists( 'Media_Library_Organizer_Defaults_Ruleset' ) ) {

	/**
	 * Tests whether a given ruleset passes or fails for a given attachment.
	 *
	 * @package   Media_Library_Organizer_Defaults
	 * @author    WP Media Library
	 * @version   1.1.6
	 */
	class Media_Library_Organizer_Defaults_Ruleset {

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
		 * Tests the given array of rules against the given Media Library Organizer Attachment
		 * object.
		 *
		 * @since   1.1.6
		 *
		 * @param   array                              $rules                  Rules.
		 * @param   Media_Library_Organizer_Attachment $attachment             Attachment.
		 * @param   int                                $attachment_id          Attachment ID.
		 * @param   int                                $all_rules_must_pass    If true, all rules must be true for the ruleset to pass.
		 *                                                                     If false, any rule must be true for the ruleset to pass.
		 * @return  bool                                                        Rules Pass
		 */
		public function ruleset_passed( $rules, $attachment, $attachment_id, $all_rules_must_pass = 0 ) {

			foreach ( $rules as $field => $rule ) {

				unset( $value );

				// Skip if there is no comparison defined.
				if ( empty( $rule['comparison'] ) ) {
					continue;
				}

				// Get field value.
				switch ( $field ) {
					case 'alt_text':
						$value = $attachment->get_alt_text();
						break;

					case 'title':
						$value = $attachment->get_title();
						break;

					case 'caption':
						$value = $attachment->get_caption();
						break;

					case 'description':
						$value = $attachment->get_description();
						break;

					case 'filename':
						$value = $attachment->get_filename();
						break;

					default:
						// Might be a taxonomy.
						foreach ( Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies() as $taxonomy_name => $taxonomy ) {
							if ( $field !== $taxonomy_name ) {
								continue;
							}

							$value = $attachment->get_terms( $taxonomy_name );
							break;
						}
						if ( isset( $value ) ) {
							break;
						}

						/**
						 * Fetch the attachment value to test against the rule.
						 *
						 * @since   1.1.6
						 *
						 * @param   mixed                               $value          Attachment Value.
						 * @param   string                              $field          Field.
						 * @param   array                               $rule           Rule.
						 * @param   Media_Library_Organizer_Attachment  $attachment     Attachment.
						 * @param   int                                 $attachment_id  Attachment ID.
						 * @return  mixed                                               Attachment Value.
						 */
						$value = apply_filters( 'media_library_organizer_defaults_ruleset_ruleset_passed_field_value', '', $field, $rule, $attachment, $attachment_id );
						break;
				}

				// Test rule.
				$rule_passed = $this->rule_passed( $rule['comparison'], $value, $rule['value'] );

				// If any rule must pass and this rule passes, return.
				if ( ! $all_rules_must_pass && $rule_passed ) {
					return true;
				}

				// If all rules must pass and this rule failed, bail.
				if ( $all_rules_must_pass && ! $rule_passed ) {
					return false;
				}
			}

			// If here, all rules have passed.
			return true;
		}

		/**
		 * Determines if a rule passes or fails.
		 *
		 * @since   1.1.6
		 *
		 * @param   string $rule_comparison    Rule Comparison Method.
		 * @param   string $value              Attachment Value.
		 * @param   string $rule_value         Rule Value.
		 * @return  bool                        Rule Passed
		 */
		private function rule_passed( $rule_comparison, $value, $rule_value ) {

			switch ( $rule_comparison ) {
				case '=':
					if ( $value == $rule_value ) { // phpcs:ignore Universal.Operators.StrictComparisons
						return true;
					}

					return false;

				case '!=':
					if ( $value != $rule_value ) { // phpcs:ignore Universal.Operators.StrictComparisons
						return true;
					}

					return false;

				case '>':
					if ( $value > $rule_value ) {
						return true;
					}

					return false;

				case '>=':
					if ( $value >= $rule_value ) {
						return true;
					}

					return false;

				case '<':
					if ( $value < $rule_value ) {
						return true;
					}

					return false;

				case '<=':
					if ( $value <= $rule_value ) {
						return true;
					}

					return false;

				case 'LIKE':
					if ( stripos( $value, $rule_value ) !== false ) {
						return true;
					}

					return false;

				case 'NOT LIKE':
					if ( stripos( $value, $rule_value ) === false ) {
						return true;
					}

					return false;

				case 'EMPTY':
					if ( empty( $value ) ) {
						return true;
					}

					return false;

				case 'NOT EMPTY':
					if ( ! empty( $value ) ) {
						return true;
					}

					return false;

				case 'start_with':
					if ( str_starts_with( $rule_value, $value ) ) {
						return true;
					}

					return false;

				case 'end_with':
					if ( str_ends_with( $rule_value, $value ) ) {
						return true;
					}

					return false;
				default:
					/**
					 * Determines if a rule passes or fails.
					 *
					 * @since 1.1.6
					 *
					 * @param bool   $passed          Whether the rule passed.
					 * @param string $rule_comparison The rule comparison method.
					 * @param string $value           The attachment value.
					 * @param string $rule_value      The rule value.
					 * @return bool  True if the rule passed, false otherwise.
					 */
					return apply_filters( 'media_library_organizer_defaults_ruleset_rule_passed', true, $rule_comparison, $value, $rule_value );
			}
		}
	}

}
