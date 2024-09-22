<?php
/**
 * Deactivation Modal view, displayed when a Plugin is deactivated.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div id="wpzinc-deactivation-modal-overlay" class="wpzinc-modal-overlay"></div>
<div id="wpzinc-deactivation-modal" class="wpzinc-modal">
	<h2 class="title">
		<?php echo esc_html( $this->plugin->displayName ); ?>
	</h2>

	<p class="message">
		<?php
		printf(
			/* Translators: Plugin Name */
			esc_html__( 'Optional: We\'d be super grateful if you could take a moment to let us know why you\'re deactivating %s', 'media-library-organizer' ),
			esc_html( $this->plugin->displayName )
		);
		?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" id="wpzinc-deactivation-modal-form">
		<ul>
			<?php
			if ( is_array( $reasons ) && count( $reasons ) > 0 ) {
				foreach ( $reasons as $key => $label ) {
					?>
					<li>
						<label>
							<span><input type="radio" name="reason" value="<?php echo esc_attr( $key ); ?>" /></span>
							<span><?php echo esc_html( $label ); ?></span>
						</label>
					</li>
					<?php
				}
			}
			?>
		</ul>

		<div class="additional-information">
			<p>
				<label for="reason_text">
					<?php esc_html_e( 'Optional: Was there a problem, any feedback or something we could do better?', 'media-library-organizer' ); ?>
				</label>
				<input type="text" id="reason_text" name="reason_text" value="" placeholder="<?php esc_attr_e( 'e.g. XYZ Plugin because it has this feature...', 'media-library-organizer' ); ?>" class="widefat" />
			</p>

			<p>
				<label for="reason_email"><?php esc_html_e( 'Optional: Email Address', 'media-library-organizer' ); ?></label>
				<input type="email" id="reason_email" name="reason_email" value="" class="widefat" />
				<small>
					<?php
					esc_html_e( 'If you\'d like further discuss the problem / feature, enter your email address above and we\'ll be in touch.  This will *never* be used for any marketing.', 'media-library-organizer' );
					?>
				</small>
			</p>
		</div>

		<input type="submit" name="submit" value="<?php esc_attr_e( 'Deactivate', 'media-library-organizer' ); ?>" class="button button-primary" />
	</form>
</div>
