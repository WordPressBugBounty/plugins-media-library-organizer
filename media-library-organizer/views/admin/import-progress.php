<?php
/**
 * Output the Import button, and the progress of a background Import, for an Import Source.
 *
 * @package Media_Library_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="wpzinc-option mlo-import" data-source="<?php echo esc_attr( $import_source['name'] ); ?>" style="display: none;">
	<p class="mlo-import-message" role="status" aria-live="polite"></p>
	<progress class="mlo-import-progress" value="0" max="100" aria-label="<?php esc_attr_e( 'Import progress', 'media-library-organizer' ); ?>" style="width: 100%;"></progress>
	<ul class="mlo-import-errors" style="display: none;"></ul>
</div>

<div class="wpzinc-option">
	<button type="button" class="button button-primary mlo-import-start" data-source="<?php echo esc_attr( $import_source['name'] ); ?>">
		<?php esc_html_e( 'Import', 'media-library-organizer' ); ?>
	</button>
	<button type="button" class="button mlo-import-cancel" data-source="<?php echo esc_attr( $import_source['name'] ); ?>" style="display: none;">
		<?php esc_html_e( 'Cancel', 'media-library-organizer' ); ?>
	</button>

	<noscript>
		<input name="<?php echo esc_attr( $import_source['name'] ); ?>" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Import', 'media-library-organizer' ); ?>" />
	</noscript>
</div>
