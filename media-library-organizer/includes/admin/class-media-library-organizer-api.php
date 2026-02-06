<?php
/**
 * API class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * API class, used by other classes to perform POST and GET requests.
 *
 * @since 1.0.9
 */
class Media_Library_Organizer_API {

	/**
	 * Holds the base object.
	 *
	 * @since   1.0.9
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the API endpoint
	 *
	 * @since   1.0.9
	 *
	 * @var     string
	 */
	public $api_endpoint;

	/**
	 * Holds the API key
	 *
	 * @since   1.0.9
	 *
	 * @var     string
	 */
	public $api_key;

	/**
	 * Holds HTTP Headers to include in requests
	 *
	 * @since   1.0.9
	 *
	 * @var     array
	 */
	public $headers = array();

	/**
	 * Constructor.
	 *
	 * @since   1.0.9
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;
	}

	/**
	 * Overrides the default API Endpoint with the supplied API Endpoint
	 *
	 * @since   1.0.9
	 *
	 * @param   string $api_endpoint        API Endpoint.
	 */
	public function set_api_endpoint( $api_endpoint ) {

		$this->api_endpoint = $api_endpoint;
	}

	/**
	 * Overrides the default API Key with the supplied API Key
	 *
	 * @since   1.0.9
	 *
	 * @param   string $api_key        API Key.
	 */
	public function set_api_key( $api_key ) {

		$this->api_key = $api_key;
	}

	/**
	 * Performs a GET request
	 *
	 * @since  1.0.9
	 *
	 * @param  string $cmd        Command (required).
	 * @param  array  $params     Params (optional).
	 * @return mixed               WP_Error | object
	 */
	public function get( $cmd, $params = array() ) {

		return $this->request( $cmd, 'get', $params );
	}

	/**
	 * Performs a POST request
	 *
	 * @since  1.0.9
	 *
	 * @param  string $cmd        Command (required).
	 * @param  array  $params     Params (optional).
	 * @return mixed               WP_Error | object
	 */
	public function post( $cmd, $params = array() ) {

		return $this->request( $cmd, 'post', $params );
	}

	/**
	 * Sets the full URL to request
	 *
	 * @since   1.0.9
	 *
	 * @param   string $cmd    Command.
	 * @return  string          Full URL
	 */
	private function set_url( $cmd ) {

		return $this->api_endpoint . '/' . $cmd;
	}

	/**
	 * Sets the maximum amount of time to wait for
	 * a response to the request before exiting
	 *
	 * @since   1.0.9
	 *
	 * @return  int     Timeout, in seconds
	 */
	private function set_timeout() {

		$timeout = 5;

		/**
		 * Defines the maximum time to allow the API request to run.
		 *
		 * @since   1.0.9
		 *
		 * @param   int     $timeout    Timeout, in seconds.
		 */
		$timeout = apply_filters( 'media_library_organizer_api_set_timeout', $timeout );

		return $timeout;
	}

	/**
	 * Main function which handles sending requests to an API using WordPress functions
	 *
	 * @since   1.0.9
	 *
	 * @param   string     $cmd        Command.
	 * @param   string     $method     Method (get|post).
	 * @param   bool|array $params     Parameters (optional).
	 * @return  mixed                   WP_Error | object
	 */
	private function request( $cmd, $method = 'get', $params = false ) {

		// Send request.
		switch ( $method ) {

			/**
			 * POST
			 */
			case 'post':
				$result = wp_remote_post(
					$this->set_url( $cmd ),
					array(
						'headers' => $this->headers,
						'body'    => ( false !== $params ? wp_json_encode( $params ) : '' ),
						'timeout' => $this->set_timeout(),
					)
				);
				break;

			/**
			 * PUT
			 */
			case 'put':
				$result = wp_remote_post(
					$this->set_url( $cmd ),
					array(
						'headers' => $this->headers,
						'method'  => 'PUT',
						'body'    => ( false !== $params ? wp_json_encode( $params ) : '' ),
						'timeout' => $this->set_timeout(),
					)
				);
				break;

			/**
			 * GET
			 */
			case 'get':
			default:
				$result = wp_remote_get(
					$this->set_url( $cmd ),
					array(
						'headers' => $this->headers,
						'body'    => ( false !== $params ? $params : '' ),
						'timeout' => $this->set_timeout(),
					)
				);
				break;
		}

		// If an error occured, return it now.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Fetch HTTP response code and body.
		$http_response_code = wp_remote_retrieve_response_code( $result );
		$body               = wp_remote_retrieve_body( $result );

		// Maybe return an error, depending on the HTTP response code.
		switch ( $http_response_code ) {
			case 400:
			case 403:
				return new WP_Error(
					'media_library_organizer_api_request_error',
					sprintf(
						/* translators: HTTP response code */
						__( 'HTTP Code %s', 'media-library-organizer' ),
						$http_response_code
					)
				);
		}

		// Decode JSON.
		$body = json_decode( $body );

		// Bail if an error occured.
		if ( isset( $body->error ) ) {
			return new WP_Error( 'media_library_organizer_api_request_error', $body->error );
		}

		// All OK, return response.
		return $body;
	}
}
