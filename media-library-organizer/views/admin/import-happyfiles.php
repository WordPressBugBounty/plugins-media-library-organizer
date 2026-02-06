<?php
/**
 * Output Import from HappyFiles options.
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

<!-- Import from HappyFiles -->
<div id="import_happyfiles" class="panel">
	<div class="postbox">
		<header>
			<h3><?php esc_html_e( 'Import from HappyFiles', 'media-library-organizer' ); ?></h3>
		</header>

		<div class="wpzinc-option">	
			<p class="description">
				<?php
				esc_html_e( 'HappyFiles\'s folders (categories) will be imported into Media Library Organizer.', 'media-library-organizer' );
				?>
				<br />
				<?php
				esc_html_e( 'Attachments assigned to HappyFiles folders will be reassigned to the equivalent Categories imported into Media Library Organizer.', 'media-library-organizer' );
				?>
			</p>
		</div>

		<div class="wpzinc-option">
			<input name="import_happyfiles" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Import', 'media-library-organizer' ); ?>" />              
		</div>
	</div>
</div>
