<?php
/**
 * Outputs an error message in the TinyMCE modal telling the user that a shortcode
 * could not be found i.e. it was not registered.
 *
 * @since   1.4.9
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<form class="wpzinc-tinymce-popup">
	<div class="notice error" style="display:block;">
		<?php esc_html_e( 'The shortcode could not be found. Check it is registered and its class initialized.', 'media-library-organizer' ); ?>
	</div>

	<div class="wpzinc-option buttons has-wpzinc-vertical-tabbed-ui">
		<div class="left">
			<button type="button" class="close button"><?php esc_html_e( 'Cancel', 'media-library-organizer' ); ?></button>
		</div>
	</div>
</form>
