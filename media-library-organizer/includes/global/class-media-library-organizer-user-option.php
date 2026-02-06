<?php
/**
 * User option class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Read and write user preferences in the user options table.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_User_Option {

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
	 * Returns a User's order by option.
	 *
	 * @since   1.4.5
	 *
	 * @param   int $user_id        User ID.
	 * @return  string                 Value
	 */
	public function get_orderby( $user_id ) {

		// Get user option.
		$value = $this->get_option( $user_id, 'orderby' );

		// If the value isn't a permitted order by option, return the default.
		if ( ! in_array( $value, array_keys( $this->base->get_class( 'common' )->get_orderby_options() ), true ) ) {
			return $this->get_default_option( 'orderby' );
		}

		// Return user's order by option.
		return $value;
	}

	/**
	 * Returns a User's order option.
	 *
	 * @since   1.4.5
	 *
	 * @param   int $user_id        User ID.
	 * @return  string                 Value
	 */
	public function get_order( $user_id ) {

		// Get user option.
		$value = $this->get_option( $user_id, 'order' );

		// If the value isn't a permitted order by option, return the default.
		if ( ! in_array( $value, array_keys( $this->base->get_class( 'common' )->get_order_options() ), true ) ) {
			return $this->get_default_option( 'order' );
		}

		// Return user's order by option.
		return $value;
	}

	/**
	 * Returns a User option.
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $user_id        User ID.
	 * @param   string $option_name    Option.
	 * @return  mixed                   Value
	 */
	public function get_option( $user_id, $option_name ) {

		// Get option.
		$value = get_user_option( $this->key_prefix . '_' . $option_name, $user_id );

		// If no option exists, fall back to the default.
		if ( ! $value ) {
			$value = $this->get_default_option( $option_name );
		}

		/**
		 * Filter the value returned for a User Option.
		 *
		 * @since   1.0.7
		 *
		 * @param   string      $value          Option Value
		 * @param   string      $option_name    Option Name
		 * @param   int         $user_id        User ID
		 */
		$value = apply_filters( 'media_library_organizer_user_option_get_option', $value, $option_name, $user_id );

		// Return.
		return $value;
	}

	/**
	 * Saves a single User option.
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $user_id        User ID.
	 * @param   string $option_name    Option Name.
	 * @param   mixed  $value          Value.
	 * @return  bool                    Success
	 */
	public function update_option( $user_id, $option_name, $value ) {

		/**
		 * Filter the value just before saving a User Option.
		 *
		 * @since   1.0.7
		 *
		 * @param   string      $value          Option Value.
		 * @param   string      $option_name    Option Name.
		 * @param   int         $user_id        User ID.
		 */
		$value = apply_filters( 'media_library_organizer_user_option_update_option', $value, $option_name, $user_id );

		// Get option.
		return update_user_option( $user_id, $this->key_prefix . '_' . $option_name, $value );
	}


	/**
	 * Deletes a single User option.
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $user_id        User ID.
	 * @param   string $option_name    Option.
	 * @return  bool                    Success
	 */
	public function delete_option( $user_id, $option_name ) {

		/**
		 * Runs actions before a User option is deleted.
		 *
		 * @since   1.0.7
		 *
		 * @param   int     $user_id        User ID.
		 * @param   string  $option_name    Option Name.
		 */
		do_action( 'media_library_organizer_user_options_delete_option', $user_id, $option_name );

		return delete_user_option( $user_id, $this->key_prefix . '_' . $option_name );
	}

	/**
	 * Returns the default User option
	 *
	 * @since   1.0.0
	 *
	 * @param   string $option_name    Option Name.
	 */
	public function get_default_option( $option_name ) {

		// Get default options.
		$defaults = $this->get_default_options();

		// Get default option.
		$default = ( isset( $defaults[ $option_name ] ) ? $defaults[ $option_name ] : '' );

		/**
		 * Filter the default value returned for a User Option, when no saved option value exists.
		 *
		 * @since   1.0.7
		 *
		 * @param   mixed       $default        Default Value.
		 * @param   string      $option_name    Option Name.
		 */
		$default = apply_filters( 'media_library_organizer_user_option_get_default_option', $default, $option_name );

		// Return.
		return $default;
	}

	/**
	 * Returns the default User options.
	 *
	 * @since   1.0.0
	 *
	 * @return  array      Default Options
	 */
	private function get_default_options() {

		// Define defaults.
		$defaults = array(
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		/**
		 * Filter the default options to be applied where no saved option value exists.
		 *
		 * @since   1.0.7
		 *
		 * @param   array       $defaults          Default Option Key/Value Pairs.
		 */
		$defaults = apply_filters( 'media_library_organizer_user_option_get_default_options', $defaults );

		// Return.
		return $defaults;
	}
}
