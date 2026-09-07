<?php
/**
 * Output Import from Media Library Assistant options.
 *
 * @package Media_Library_Organizer
 * @author  Themeisle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!-- Import from Media Library Assistant -->
<div id="import_mla" class="panel">
	<div class="postbox">
		<header>
			<h3>
				<?php
				printf(
					// translators: %s is the plugin name.
					esc_html__( 'Import from %s', 'media-library-organizer' ),
					'Media Library Assistant'
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
					'Media Library Assistant'
				);
				?>
				<br />
				<?php
				printf(
					// translators: %s is the plugin name.
					esc_html__( 'Attachments assigned to %s folders will be reassigned to the equivalent Categories imported into Media Library Organizer.', 'media-library-organizer' ),
					'Media Library Assistant'
				);
				?>
			</p>
		</div>

		<?php
		require MEDIA_LIBRARY_ORGANIZER_PLUGIN_PATH . 'views/admin/import-progress.php';
		?>
	</div>
</div>