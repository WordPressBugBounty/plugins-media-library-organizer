<?php
/**
 * Output Order By and Order Filters in the List View.
 *
 * @since   1.0.0
 *
 * @package Media_Library_Organizer
 * @author  Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Order By Filter.
if ( $this->base->get_class( 'settings' )->get_setting( 'general', 'orderby_enabled' ) ) {
	$mlo_orderby = $this->base->get_class( 'common' )->get_orderby_options();
	if ( ! empty( $mlo_orderby ) ) {
		?>
		<select name="orderby" id="mlo-orderby" size="1">
			<?php
			foreach ( $mlo_orderby as $key => $value ) {
				?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $current_orderby, $key ); ?>><?php echo esc_html( $value ); ?></option>
				<?php
			}
			?>
		</select>
		<?php
	}
}

// Order Filter.
if ( $this->base->get_class( 'settings' )->get_setting( 'general', 'order_enabled' ) ) {
	$mlo_order = $this->base->get_class( 'common' )->get_order_options();
	if ( ! empty( $mlo_order ) ) {
		?>
		<select name="order" id="mlo-order" size="1">
			<?php
			foreach ( $mlo_order as $key => $value ) {
				?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $current_order, $key ); ?>><?php echo esc_html( $value ); ?></option>
				<?php
			}
			?>
		</select>
		<?php
	}
}
