<?php
/**
 * Output Import from Wicked Folder options.
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

<!-- Import from Wicked Folders -->
<div id="import_wicked_folders" class="panel">
	<div class="postbox">
		<header>
			<h3><?php esc_html_e( 'Import from Wicked Folders', 'media-library-organizer' ); ?></h3>
		</header>

		<div class="wpzinc-option">	
			<p class="description">
				<?php
				esc_html_e( 'Wicked Folder\'s folders (categories) will be imported into Media Library Organizer.', 'media-library-organizer' );
				?>
				<br />
				<?php
				esc_html_e( 'Attachments assigned to Wicked Folder\'s folders will be reassigned to the equivalent Categories imported into Media Library Organizer.', 'media-library-organizer' );
				?>
			</p>
		</div>

		<?php
		require MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'views/admin/import-progress.php';
		?>
	</div>
</div>
