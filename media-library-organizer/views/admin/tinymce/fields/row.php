<?php
/**
 * View to output a setting field row in a TinyMCE modal
 *
 * @since   1.4.9
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Output Field.
if ( $field['type'] === 'repeater' ) {
	include 'repeater.php';
} else {
	$condition = '';
	if ( isset( $field['condition'] ) ) {
		if ( is_array( $field['condition']['value'] ) ) {
			$condition = implode( ' ', $field['condition']['value'] );
		} else {
			$condition = $field['condition']['value'];
		}
	}
	?>
	<div class="wpzinc-option">
		<div class="left">
			<label for="<?php echo esc_attr( $field_name ); ?>">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
		</div>
		<div class="right <?php echo esc_attr( $condition ); ?>">
			<?php
			include 'field.php';
			?>
		</div>
	</div>
	<?php
}
