<?php
/**
 * Output the Upgrade banner on the Settings General (Filters) panel.
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

<div class="wpzinc-option highlight">
	<div class="full">
		<p>
			<?php
			echo esc_html(
				sprintf(
				/* translators: Plugin Name */
					__( 'Filter by mutiple Categories and specific File Types with %s Pro', 'media-library-organizer' ),
					$this->base->plugin->displayName
				)
			);
			?>
		</p>

		<a href="<?php echo esc_attr( $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ) ); ?>" class="button button-primary" target="_blank">
			<?php esc_html_e( 'Upgrade', 'media-library-organizer' ); ?>
		</a>
	</div>
</div>
