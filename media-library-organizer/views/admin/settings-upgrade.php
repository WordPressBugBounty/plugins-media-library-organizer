<?php
/**
 * Output Settings Panels with short descriptions and upgrade buttons for functionality
 * available in the Pro version.
 *
 * @since   1.0.0
 *
 * @package Media_Library_Organizer
 * @author  Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

foreach ( $panels as $setting_name => $panel ) {
	?>
	<div id="<?php echo esc_attr( $setting_name ); ?>" class="panel">
		<div class="postbox">
			<header>
				<h3><?php echo esc_html( $panel['title'] ); ?>
			</header>

			<div class="wpzinc-option highlight">
				<div class="full">
					<p>
						<?php
						echo esc_html( $panel['description'] );
						?>
					</p>
					<?php if ( $setting_name !== 'optimizer' ) : ?>
						<a href="<?php echo esc_attr( $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ) ); ?>" class="button button-primary" target="_blank">
							<?php esc_html_e( 'Upgrade', 'media-library-organizer' ); ?>
						</a>
					<?php else : ?>
						<a target="_blank" href="<?php echo esc_url( admin_url( '/plugin-install.php?tab=plugin-information&plugin=optimole-wp&section=description' ) ); ?>" class="button button-primary" target="_blank">
							<?php esc_html_e( 'Install', 'media-library-organizer' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
