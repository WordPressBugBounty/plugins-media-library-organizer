<?php
/**
 * TinyMCE class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Registers TinyMCE Plugins.
 *
 * @since 1.4.9
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */
class Media_Library_Organizer_TinyMCE {

	/**
	 * Holds the base object.
	 *
	 * @since   1.4.9
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.4.9
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Outputs the TinyMCE and QuickTag Modal.
		add_action( 'wp_ajax_media_library_organizer_tinymce_output_modal', array( $this, 'output_modal' ) );

		// Add filters to register QuickTag Plugins.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_quicktags' ) ); // WordPress Admin.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_quicktags' ) ); // Frontend Editors.

		// Add filters to register TinyMCE Plugins.
		// Low priority ensures this works with Frontend Page Builders.
		add_filter( 'mce_external_plugins', array( $this, 'register_tinymce_plugins' ), 99999 );
		add_filter( 'mce_buttons', array( $this, 'register_tinymce_buttons' ), 99999 );
	}

	/**
	 * Loads the view for a shortcode's modal in the TinyMCE and Text Editors.
	 *
	 * @since   1.4.9
	 */
	public function output_modal() {

		// Check nonce.
		check_ajax_referer( 'media_library_organizer_tinymce', 'nonce' );

		// Get shortcodes.
		$shortcodes = $this->base->get_class( 'shortcode' )->get();

		// Get requested shortcode name.
		$shortcode_name = isset( $_REQUEST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['shortcode'] ) ) : '';
		$editor_type    = isset( $_REQUEST['editor_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['editor_type'] ) ) : '';

		// If the shortcode is not registered, return a view in the modal to tell the user.
		if ( ! isset( $shortcodes[ $shortcode_name ] ) ) {
			require_once $this->base->plugin->folder . '/views/admin/tinymce/modal-missing.php';
			die();
		}

		// Define shortcode.
		$shortcode = $shortcodes[ $shortcode_name ];

		// If we have less than two tabs defined in the shortcode properties, output a basic modal.
		if ( count( $shortcode['tabs'] ) < 2 ) {
			require_once $this->base->plugin->folder . '/views/admin/tinymce/modal.php';
			die();
		}

		// Output tabbed view.
		require_once $this->base->plugin->folder . '/views/admin/tinymce/modal-tabbed.php';
		die();
	}

	/**
	 * Registers QuickTags JS for the TinyMCE Text (non-Visual) Editor
	 *
	 * @since   1.4.9
	 */
	public function register_quicktags() {

		// Get shortcodes.
		$shortcodes = $this->base->get_class( 'shortcode' )->get();

		// Bail if no shortcode are available.
		if ( ! is_array( $shortcodes ) || ! count( $shortcodes ) ) {
			return;
		}

		// Determine whether to load minified JS.
		$ext = ( $this->base->dashboard->should_load_minified_js() ? 'min' : '' );

		// Enqueue Quicktag JS.
		wp_enqueue_script( $this->base->plugin->name . '-quicktags', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'quicktags' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );

		// Make shortcodes available as media_library_organizer_quicktags JS variable.
		wp_localize_script( $this->base->plugin->name . '-quicktags', 'media_library_organizer_quicktags', $shortcodes );

		// Register JS variable media_library_organizer_tinymce with labels and nonce for AJAX calls.
		wp_localize_script(
			$this->base->plugin->name . '-quicktags',
			'media_library_organizer_tinymce',
			array(
				'labels' => array(
					'insert' => __( 'Insert', 'media-library-organizer' ),
					'cancel' => __( 'Cancel', 'media-library-organizer' ),
				),
				'nonce'  => wp_create_nonce( 'media_library_organizer_tinymce' ),
			)
		);

		// Enqueue Quicktag CSS.
		// @TODO Do we need this?

		// Output Backbone View Template.
		add_action( 'wp_print_footer_scripts', array( $this, 'output_quicktags_modal' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'output_quicktags_modal' ) );
	}

	/**
	 * Register JS plugins for the TinyMCE Editor
	 *
	 * @since   1.4.9
	 *
	 * @param   array $plugins    JS Plugins.
	 * @return  array             JS Plugins
	 */
	public function register_tinymce_plugins( $plugins ) {

		// Get shortcodes.
		$shortcodes = $this->base->get_class( 'shortcode' )->get();

		// Bail if no shortcodes are available.
		if ( ! is_array( $shortcodes ) || ! count( $shortcodes ) ) {
			return $plugins;
		}

		// Determine whether to load minified JS.
		$ext = ( $this->base->dashboard->should_load_minified_js() ? 'min' : '' );

		// Enqueue TinyMCE CSS and JS.
		wp_enqueue_script( 'wpzinc-admin-tinymce-modal' );
		wp_enqueue_script( 'wpzinc-admin-tabs' );
		wp_enqueue_script( 'wpzinc-admin-tables' );
		wp_enqueue_script( $this->base->plugin->name . '-tinymce', $this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'tinymce' . ( $ext ? '-' . $ext : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );

		// Register JS variable media_library_organizer_tinymce with labels and nonce for AJAX calls.
		wp_localize_script(
			$this->base->plugin->name . '-tinymce',
			'media_library_organizer_tinymce',
			array(
				'labels' => array(
					'insert' => __( 'Insert', 'media-library-organizer' ),
					'cancel' => __( 'Cancel', 'media-library-organizer' ),
				),
				'nonce'  => wp_create_nonce( 'media_library_organizer_tinymce' ),
			)
		);

		// Make shortcodes available as media_library_organizer_shortcodes JS variable.
		wp_localize_script( $this->base->plugin->name . '-tinymce', 'media_library_organizer_shortcodes', $shortcodes );

		// Register TinyMCE Javascript Plugin.
		foreach ( $shortcodes as $shortcode => $properties ) {
			if ( ! isset( $properties['tinymce_script'] ) ) {
				continue;
			}

			$plugins[ 'media_library_organizer_' . $shortcode ] = $properties['tinymce_script'];
		}

		return $plugins;
	}

	/**
	 * Registers buttons in the TinyMCE Editor
	 *
	 * @since   1.4.9
	 *
	 * @param   array $buttons    Buttons.
	 * @return  array             Buttons
	 */
	public function register_tinymce_buttons( $buttons ) {

		// Get shortcodes.
		$shortcodes = $this->base->get_class( 'shortcode' )->get();

		// Bail if no shortcodes are available.
		if ( ! is_array( $shortcodes ) || ! count( $shortcodes ) ) {
			return $buttons;
		}

		// Register each Shortcode as a TinyMCE Button.
		foreach ( $shortcodes as $shortcode => $properties ) {
			$buttons[] = 'media_library_organizer_' . $shortcode;
		}

		return $buttons;
	}

	/**
	 * Outputs the QuickTags modal view in the footer of the site, which is
	 * used when using the Text editor button to insert a shortcode.
	 *
	 * @since   1.4.9
	 */
	public function output_quicktags_modal() {

		?>
		<script type="text/template" id="tmpl-wpzinc-quicktags-modal">
			<div id="wpzinc-quicktags-modal">
				<div class="media-frame-title"><h1></h1></div>
				<div class="media-frame-content"></div>
				<div class="media-frame-toolbar">
					<div class="media-toolbar">
						<div class="media-toolbar-secondary">
							<button type="button" class="button button-large cancel"><?php esc_html_e( 'Cancel', 'media-library-organizer' ); ?></button>
						</div>
						<div class="media-toolbar-primary">
							<button type="button" class="button button-primary button-large insert"><?php esc_html_e( 'Insert', 'media-library-organizer' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</script>
		<?php
	}
}
