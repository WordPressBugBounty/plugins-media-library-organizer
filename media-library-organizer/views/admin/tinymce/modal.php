<?php
/**
 * Outputs a TinyMCE form for a shortcode.
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
<!-- .wp-core-ui ensures styles are applied on frontend editors for e.g. buttons.css -->
<form class="wpzinc-tinymce-popup wp-core-ui">
	<input type="hidden" name="shortcode" value="<?php echo esc_attr( $shortcode['prefix'] ); ?>_<?php echo esc_attr( $shortcode['name'] ); ?>" />
	<input type="hidden" name="editor_type" value="<?php echo esc_attr( $editor_type ); // quicktags|tinymce. ?>" />

	<?php
	// Output each Field.
	foreach ( $shortcode['fields'] as $field_name => $field ) {
		include 'fields/row.php';
	}
	?>
</form>
