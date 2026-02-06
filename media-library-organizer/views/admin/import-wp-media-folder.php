<?php
/**
 * Output Import from WP Media Folder options.
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

<!-- Import from WP Media Folder -->
<div id="import_wp_media_folder" class="panel">
	<div class="postbox">
		<header>
			<h3><?php esc_html_e( 'Import from WP Media Folder', 'media-library-organizer' ); ?></h3>
		</header>

		<div class="wpzinc-option">	
			<p class="description">
				<?php
				esc_html_e( 'WP Media Folder\'s folders (categories) will be imported into Media Library Organizer.', 'media-library-organizer' );
				?>
				<br />
				<?php
				esc_html_e( 'Attachments assigned to WP Media Folder\'s folders will be reassigned to the equivalent Categories imported into Media Library Organizer.', 'media-library-organizer' );
				?>
			</p>
		</div>

		<div class="wpzinc-option">
			<input name="import_wp_media_folder" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Import', 'media-library-organizer' ); ?>" />              
		</div>
	</div>
</div>
