<?php
/**
 * Notices class.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

/**
 * Persists success and error messages
 * across Admin Screens, primarily for Bulk actions
 * on the Media List View.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Notices {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds success and error notices to be displayed
	 *
	 * @since   1.0.0
	 *
	 * @var     array
	 */
	public $notices = array(
		'success' => array(),
		'error'   => array(),
	);

	/**
	 * Whether to store notices for displaying on the next page load.
	 *
	 * Set using enable_store() and disable_store(),
	 * useful for the Media Library's Grid view
	 *
	 * @since   1.0.0
	 *
	 * @var     bool
	 */
	private $store = false;

	/**
	 * The key prefix to use for stored notices
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	private $key_prefix = '_mlo_notices';

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

		add_action( 'admin_notices', array( $this, 'output_notices' ) );
	}

	/**
	 * Enable persistence on notices
	 *
	 * @since   1.0.5
	 */
	public function enable_store() {

		$this->store = true;
	}

	/**
	 * Disable persistence on notices
	 *
	 * @since   1.0.5
	 */
	public function disable_store() {

		$this->store = false;
	}

	/**
	 * Defines the key prefix to use for setting and getting notices
	 *
	 * @since   1.0.5
	 *
	 * @param   string $key_prefix     Key Prefix.
	 */
	public function set_key_prefix( $key_prefix ) {

		$this->key_prefix = $key_prefix;
	}

	/**
	 * Returns all Success Notices that need to be displayed.
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Notices
	 */
	public function get_success_notices() {

		// Get notices from store, if required.
		if ( $this->store ) {
			$this->notices = $this->get_notices();
		}

		// Get success notices.
		$success_notices = ( isset( $this->notices['success'] ) ? $this->notices['success'] : array() );

		/**
		 * Filters the success notices to return.
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $success_notices    Success Notices.
		 */
		$success_notices = apply_filters( 'media_library_organizer_notices_get_success_notices', $success_notices );

		// Return.
		return $success_notices;
	}

	/**
	 * Add a single Success Notice
	 *
	 * @since   1.0.5
	 *
	 * @param   string $value       Message.
	 * @return  bool                Success
	 */
	public function add_success_notice( $value ) {

		// Get notices from store, if required.
		if ( $this->store ) {
			$this->notices = $this->get_notices();
		}

		// Add success notice.
		if ( isset( $this->notices['success'] ) ) {
			// Bail if the notice already exists.
			if ( in_array( $value, $this->notices['success'], true ) ) {
				return true;
			}

			$this->notices['success'][] = $value;
		} else {
			$this->notices['success'] = array( $value );
		}

		// Remove any duplicates.
		$this->notices['success'] = array_values( array_unique( $this->notices['success'] ) );

		// Store notices, if required.
		if ( $this->store ) {
			$this->save_notices( $this->notices );
		}

		return true;
	}

	/**
	 * Returns all Error Notices that need to be displayed.
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Notices
	 */
	public function get_error_notices() {

		// Get notices from store, if required.
		if ( $this->store ) {
			$this->notices = $this->get_notices();
		}

		// Get error notices.
		$error_notices = ( isset( $this->notices['error'] ) ? $this->notices['error'] : array() );

		/**
		 * Filters the error notices to return.
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $error_notices  Error Notices.
		 */
		$error_notices = apply_filters( 'media_library_organizer_notices_get_error_notices', $error_notices );

		// Return.
		return $error_notices;
	}

	/**
	 * Add a single Error Notice.
	 *
	 * @since   1.0.5
	 *
	 * @param   string $value       Message.
	 */
	public function add_error_notice( $value ) {

		// Get notices from store, if required.
		if ( $this->store ) {
			$this->notices = $this->get_notices();
		}

		// Add success notice.
		if ( isset( $this->notices['error'] ) ) {
			$this->notices['error'][] = $value;
		} else {
			$this->notices['error'] = array( $value );
		}

		// Remove any duplicates.
		$this->notices['error'] = array_values( array_unique( $this->notices['error'] ) );

		// Store notices, if required.
		if ( $this->store ) {
			$this->save_notices( $this->notices );
		}
	}

	/**
	 * Returns all Success and Error notices
	 *
	 * @since   1.0.5
	 *
	 * @return  array   Notices
	 */
	private function get_notices() {

		// Get notices.
		$notices = get_transient( $this->key_prefix );

		/**
		 * Filters the success and error notices to return.
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $notices    Success and Error Notices.
		 */
		$notices = apply_filters( 'media_library_organizer_notices_get_notices', $notices );

		// If not an array, setup.
		if ( ! is_array( $notices ) ) {
			$notices = array(
				'success' => array(),
				'error'   => array(),
			);
		}

		// If some keys aren't set, define them now.
		if ( ! isset( $notices['success'] ) ) {
			$notices['success'] = array();
		}
		if ( ! isset( $notices['error'] ) ) {
			$notices['error'] = array();
		}

		// Return.
		return $notices;
	}

	/**
	 * Saves the given notices array.
	 *
	 * @since    1.0.5
	 *
	 * @param    array $notices     Notices.
	 * @return   bool               Success
	 */
	private function save_notices( $notices ) {

		/**
		 * Filters the success and error notices to save.
		 *
		 * @since   1.0.5
		 *
		 * @param   array   $notices    Success and Error Notices.
		 */
		$notices = apply_filters( 'media_library_organizer_notices_save', $notices );

		// Update settings.
		set_transient( $this->key_prefix, $notices, 60 );

		return true;
	}

	/**
	 * Deletes all notices
	 *
	 * @since   1.0.5
	 */
	public function delete_notices() {

		// Delete notices.
		delete_transient( $this->key_prefix );

		/**
		 * Runs actions immediately after all notices are deleted / cleared from the transients data.
		 *
		 * @since   1.0.7
		 */
		do_action( 'media_library_organizer_notices_delete_notices' );

		return true;
	}

	/**
	 * Output any success and error notices.
	 *
	 * @since   1.0.5
	 */
	public function output_notices() {

		// If no notices exist in the class, check the storage.
		if ( count( $this->notices['success'] ) === 0 &&
			count( $this->notices['error'] ) === 0 ) {
			$this->notices = $this->get_notices();
		}

		// Success.
		if ( count( $this->notices['success'] ) > 0 ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					foreach ( $this->notices['success'] as $notice ) {
						echo esc_html( $notice ) . '<br />';
					}
					?>
				</p>
			</div>
			<?php
		}

		// Error.
		if ( count( $this->notices['error'] ) > 0 ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php
					foreach ( $this->notices['error'] as $notice ) {
						echo esc_html( $notice ) . '<br />';
					}
					?>
				</p>
			</div>
			<?php
		}

		// Clear storage if it's not enabled.
		// This prevents notices stored in this page request from being immediately destroyed.
		if ( ! $this->store ) {
			$this->delete_notices();
		}
	}
}
