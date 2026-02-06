<?php
/**
 * Outputs a TinyMCE tabbed form for a shortcode.
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

	<!-- Vertical Tabbed UI -->
	<div class="wpzinc-vertical-tabbed-ui">
		<!-- Tabs -->
		<ul class="wpzinc-nav-tabs wpzinc-js-tabs" 
			data-panels-container="#<?php echo esc_attr( $shortcode['name'] ); ?>-container"
			data-panel=".<?php echo esc_attr( $shortcode['name'] ); ?>"
			data-active="wpzinc-nav-tab-vertical-active"
			data-match-height="#wpzinc-tinymce-modal-body">

			<?php
			// data-match-height="#wpzinc-tinymce-modal-body" removed from above.
			// Output each Tab.
			$first_tab = true;
			foreach ( $shortcode['tabs'] as $modal_tab_name => $modal_tab ) {
				?>
				<li class="wpzinc-nav-tab<?php echo esc_attr( ( isset( $modal_tab['class'] ) ? ' ' . $modal_tab['class'] : '' ) ); ?>">
					<a href="#<?php echo esc_attr( $shortcode['name'] ) . '-' . esc_attr( $modal_tab_name ); ?>"<?php echo ( $first_tab ? ' class="wpzinc-nav-tab-vertical-active"' : '' ); ?>>
						<?php echo esc_html( $modal_tab['label'] ); ?>
					</a>
				</li>
				<?php
				$first_tab = false;
			}
			?>
		</ul>

		<!-- Content -->
		<div id="<?php echo esc_attr( $shortcode['name'] ); ?>-container" class="wpzinc-nav-tabs-content no-padding">
			<?php
			// Output each Tab Panel.
			foreach ( $shortcode['tabs'] as $modal_tab_name => $modal_tab ) {
				?>
				<div id="<?php echo esc_attr( $shortcode['name'] ) . '-' . esc_attr( $modal_tab_name ); ?>" class="<?php echo esc_attr( $shortcode['name'] ); ?>">
					<div class="postbox">
						<header>
							<h3><?php echo esc_html( $modal_tab['label'] ); ?></h3>
							<?php
							if ( isset( $modal_tab['description'] ) && ! empty( $modal_tab['description'] ) ) {
								?>
								<p class="description">
									<?php esc_html( $modal_tab['description'] ); ?>
								</p>
								<?php
							}
							?>
						</header>

						<?php
						// Iterate through this tab's field names.
						foreach ( $modal_tab['fields'] as $field_name ) {
							// Skip if this field doesn't exist.
							if ( ! isset( $shortcode['fields'][ $field_name ] ) ) {
								continue;
							}

							// Fetch the field properties.
							$field = $shortcode['fields'][ $field_name ];

							// Output Field.
							include 'fields/row.php';
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</form>
