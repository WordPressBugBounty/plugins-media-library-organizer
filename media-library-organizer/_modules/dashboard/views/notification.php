<?php
/**
 * Outputs a fixed overlay toast-style notification.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div id="<?php echo esc_attr( $this->base->plugin->name ); ?>-notification" class="wpzinc-notification"></div>
