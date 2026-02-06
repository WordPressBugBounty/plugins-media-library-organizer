<?php
/**
 * Output the Settings tabs and panels.
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

<div class="postbox wpzinc-vertical-tabbed-ui">
	<!-- Second level tabs -->
	<ul class="wpzinc-nav-tabs wpzinc-js-tabs" data-panels-container="#settings-container" data-panel=".panel" data-active="wpzinc-nav-tab-vertical-active">
		<?php
		// Iterate through this screen's tabs.
		foreach ( (array) $tabs as $index => $tab_item ) {
			$css_class = ( ( $tab_item['name'] === $tab['name'] ) ? 'wpzinc-nav-tab-vertical-active' : '' );
			?>
			<li class="wpzinc-nav-tab <?php echo esc_attr( isset( $tab_item['menu_icon'] ) ? $tab_item['menu_icon'] : 'default' ); ?>">
				<a href="#<?php echo esc_attr( $tab_item['name'] ); ?>" class="<?php echo esc_attr( $css_class ); ?>" <?php echo esc_attr( isset( $tab_item['documentation'] ) ? ' data-documentation="' . $tab_item['documentation'] . '"' : '' ); ?>>
					<?php
					echo esc_html( $tab_item['label'] );
					?>
				</a>
			</li>
			<?php
		}

		// Iterate through this screen's addon tabs.
		foreach ( (array) $addon_tabs as $addon_name => $tab_item ) {
			$css_class = ( ( $tab_item['name'] === $tab['name'] ) ? 'wpzinc-nav-tab-vertical-active' : '' );
			?>
			<li class="wpzinc-nav-tab <?php echo esc_attr( isset( $tab_item['menu_icon'] ) ? $tab_item['menu_icon'] : 'default' ); ?>">
				<a href="#<?php echo esc_attr( $tab_item['name'] ); ?>" class="<?php echo esc_attr( $css_class ); ?>" <?php echo esc_attr( isset( $tab_item['documentation'] ) ? ' data-documentation="' . $tab_item['documentation'] . '"' : '' ); ?>>
					<?php
					echo esc_html( $tab_item['label'] );

					if ( isset( $tab_item['is_pro'] ) && $tab_item['is_pro'] ) {
						?>
						<span class="tag"><?php esc_html_e( 'Pro', 'media-library-organizer' ); ?></span>
						<?php
					}
					?>
				</a>
			</li>
			<?php
		}
		?>
	</ul>

	<!-- Content -->
	<div id="settings-container" class="wpzinc-nav-tabs-content no-padding">
		<?php
		do_action( 'media_library_organizer_admin_output_settings_panels' );
		?>
	</div>
</div>

<!-- Save -->
<div>
	<input type="submit" name="submit" value="<?php esc_html_e( 'Save', 'media-library-organizer' ); ?>" class="button button-primary" />
</div>
