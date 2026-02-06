<?php
/**
 * View to output a setting field in a TinyMCE modal.
 *
 * @since   1.4.9
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Build a string of data- attributes.
$data_attributes                   = '';
$data_attributes_shortcode_defined = false;
if ( isset( $field['data'] ) ) {
	foreach ( $field['data'] as $data_attribute => $data_attribute_value ) {
		// If the data attribute's value contains quotation marks, use single quotes.
		if ( strpos( $data_attribute_value, '"' ) !== false ) {
			$data_attributes .= ' data-' . esc_html( $data_attribute ) . '=\'' . esc_attr( $data_attribute_value ) . '\'';
		} else {
			$data_attributes .= ' data-' . esc_html( $data_attribute ) . '="' . esc_attr( $data_attribute_value ) . '"';
		}

		if ( 'shortcode' === $data_attribute ) {
			$data_attributes_shortcode_defined = true;
		}
	}
}
if ( ! $data_attributes_shortcode_defined ) {
	$data_attributes .= ' data-shortcode="' . esc_attr( $field_name ) . '"';
}

switch ( $field['type'] ) {
	/**
	 * Autocomplete
	 */
	case 'autocomplete':
		?>
		<input type="text" 
				id="<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				value="<?php echo esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ); ?>" 
				<?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				placeholder="<?php echo esc_attr( ( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) ); ?>"
				class="widefat wpzinc-autocomplete <?php echo esc_attr( ( isset( $field['class'] ) ? $field['class'] : '' ) ); ?>" />
		<?php
		break;

	/**
	 * Text
	 */
	case 'text':
	case 'text_multiple':
		?>
		<input type="text" 
				id="<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				value="<?php echo esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ); ?>" 
				<?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				placeholder="<?php echo esc_attr( ( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) ); ?>"
				class="widefat <?php echo esc_attr( ( isset( $field['class'] ) ? $field['class'] : '' ) ); ?>" />
		<?php
		break;

	/**
	 * Number
	 */
	case 'number':
		?>
		<input type="number" 
				id="<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>" 
				value="<?php echo esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ); ?>" 
				<?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				min="<?php echo esc_attr( $field['min'] ); ?>" 
				max="<?php echo esc_attr( $field['max'] ); ?>" 
				step="<?php echo esc_attr( $field['step'] ); ?>"
				class="widefat <?php echo esc_attr( ( isset( $field['class'] ) ? $field['class'] : '' ) ); ?>" />
		<?php
		break;

	/**
	 * Select
	 */
	case 'select':
		?>
		<select name="<?php echo esc_attr( $field_name ); ?>"
				id="<?php echo esc_attr( $field_name ); ?>"
				<?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				size="1"
				class="widefat <?php echo esc_attr( ( isset( $field['class'] ) ? $field['class'] : '' ) ); ?>">
			<?php
			$field['default_value'] = ( isset( $field['default_value'] ) ? $field['default_value'] : '' );
			foreach ( $field['values'] as $value => $label ) {
				?>
				<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $field['default_value'], $value ); ?>>
					<?php echo esc_attr( $label ); ?>
				</option>
				<?php
			}
			?>
		</select>
		<?php
		break;

	/**
	 * Multiple Select
	 */
	case 'select_multiple':
		?>
		<select name="<?php echo esc_attr( $field_name ); ?>[]"
				id="<?php echo esc_attr( $field_name ); ?>"
				<?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				size="1"
				multiple="multiple"
				class="widefat <?php echo esc_attr( ( isset( $field['class'] ) ? $field['class'] : '' ) ); ?>">
			<?php
			$field['default_value'] = ( isset( $field['default_value'] ) ? $field['default_value'] : '' );
			if ( isset( $field['values'] ) && is_array( $field['values'] ) && count( $field['values'] ) > 0 ) {
				foreach ( $field['values'] as $value => $label ) {
					?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php echo esc_attr( in_array( $value, (array) $field['default_value'], true ) ? ' selected' : '' ); ?>>
						<?php echo esc_attr( $label ); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
		<?php
		break;

	/**
	 * Toggle
	 */
	case 'toggle':
		?>
		<select name="<?php echo esc_attr( $field_name ); ?>"
				id="<?php echo esc_attr( $field_name ); ?>"
				<?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				size="1"
				class="widefat <?php echo esc_attr( ( isset( $field['class'] ) ? $field['class'] : '' ) ); ?>">
			<?php
			$field['default_value'] = ( isset( $field['default_value'] ) ? $field['default_value'] : '' );
			?>
			<option value="0"<?php selected( $field['default_value'], 0 ); ?>><?php esc_html_e( 'No', 'media-library-organizer' ); ?></option>
			<option value="1"<?php selected( $field['default_value'], 1 ); ?>><?php esc_html_e( 'Yes', 'media-library-organizer' ); ?></option>
		</select>
		<?php
		break;
}

if ( isset( $field['description'] ) ) {
	?>
	<p class="description">
		<?php echo $field['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>
	<?php
}
