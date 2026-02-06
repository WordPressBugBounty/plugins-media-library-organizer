<?php
/**
 * Output the Settings screen.
 *
 * @since   1.0.0
 *
 * @package Media_Library_Organizer
 * @author  Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="wrap">
	<h1 class="wp-heading-inline dashicons-before dashicons-admin-media">
		<?php echo esc_html( $this->base->plugin->displayName ); ?> 

		<span>
			<?php echo esc_html( $screen['label'] ); ?>
		</span>
	</h1>

	<?php
	// Output notices.
	echo $this->base->get_class( 'notices' )->output_notices(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>

	<div class="wrap-inner">
		<?php
		// Add a Documentation Tab, if a Documentation link exists.
		if ( isset( $screen['documentation'] ) && ! empty( $screen['documentation'] ) ) {
			?>
			<!-- Tabs -->
			<h2 class="nav-tab-wrapper wpzinc-horizontal-tabbed-ui">
				<a href="<?php echo esc_url( $screen['documentation'] ); ?>" class="nav-tab last" target="_blank">
					<?php esc_html_e( 'Documentation', 'media-library-organizer' ); ?>
					<span class="dashicons dashicons-admin-page"></span>
				</a>
			</h2>
			<?php
		}
		?>

		<form name="post" method="post" action="" id="<?php echo esc_attr( $this->base->plugin->name ); ?>">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<!-- Content -->
					<div id="post-body-content">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable publishing-defaults">  
							<?php
							// Load sub view.
							require_once $screen['view'];

							wp_nonce_field( $this->base->plugin->name . '_' . $screen['name'], $this->base->plugin->name . '_nonce' );
							?>
						</div>
						<!-- /normal-sortables -->
					</div>
					<!-- /post-body-content -->
				</div>
			</div> 
			<!-- /poststuff -->
		</form>
	</div><!-- ./wrap-inner -->       
</div>
