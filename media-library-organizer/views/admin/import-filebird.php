<?php
/**
 * Output Import from Filebird options.
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

<!-- Import from FileBird -->
<div id="import_filebird" class="panel">
	<div class="postbox">
		<header>
			<h3><?php esc_html_e( 'Import from FileBird', 'media-library-organizer' ); ?></h3>
		</header>

		<div class="wpzinc-option">	
			<p class="description">
				<?php
				esc_html_e( 'FileBird\'s folders (categories) will be imported into Media Library Organizer.', 'media-library-organizer' );
				?>
				<br />
				<?php
				esc_html_e( 'Attachments assigned to FileBird folders will be reassigned to the equivalent Categories imported into Media Library Organizer.', 'media-library-organizer' );
				?>
			</p>
		</div>

		<?php
		require MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'views/admin/import-progress.php';
		?>
	</div>
</div>
