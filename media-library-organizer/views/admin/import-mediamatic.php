<?php
/**
 * Output Import from Mediamatic options.
 *
 * @package Media_Library_Organizer
 * @author  Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!-- Import from Mediamatic -->
<div id="import_mediamatic" class="panel">
	<div class="postbox">
		<header>
			<h3>
				<?php
				printf(
					// translators: %s is the plugin name.
					esc_html__( 'Import from %s', 'media-library-organizer' ),
					'Mediamatic'
				);
				?>
			</h3>
		</header>

		<div class="wpzinc-option">	
			<p class="description">
				<?php
				printf(
					// translators: %s is the plugin name.
					esc_html__( '%s\'s folders (categories) will be imported into Media Library Organizer.', 'media-library-organizer' ),
					'Mediamatic'
				);
				?>
				<br />
				<?php
				printf(
					// translators: %s is the plugin name.
					esc_html__( 'Attachments assigned to %s folders will be reassigned to the equivalent Categories imported into Media Library Organizer.', 'media-library-organizer' ),
					'Mediamatic'
				);
				?>
			</p>
		</div>

		<div class="wpzinc-option">
			<input name="import_mediamatic" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Import', 'media-library-organizer' ); ?>" />
		</div>
	</div>
</div>