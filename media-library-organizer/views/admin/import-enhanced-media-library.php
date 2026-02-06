<?php
/**
 * Output Import from Enhanced Media Library options.
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

<!-- Import from Enhanced Media Library-->
<div id="import_enhanced_media_library" class="panel">
	<div class="postbox">
		<header>
			<h3><?php esc_html_e( 'Import from Enhanced Media Library', 'media-library-organizer' ); ?></h3>
		</header>

		<div class="wpzinc-option">
			<div class="left">
				<strong><?php esc_html_e( 'Taxonomies', 'media-library-organizer' ); ?></strong>
			</div>
			<div class="right">
				<?php
				foreach ( $import_source['data']['taxonomies'] as $taxonomy_name => $eml_taxonomy ) {
					// Skip non-EML categories.
					if ( ! $eml_taxonomy['eml_media'] ) {
						continue;
					}
					?>
					<label for="taxonomies_<?php echo esc_attr( $taxonomy_name ); ?>">
						<input type="checkbox" name="taxonomies[]" id="taxonomies_<?php echo esc_attr( $taxonomy_name ); ?>" value="<?php echo esc_attr( $taxonomy_name ); ?>" />
						<?php echo esc_html( $eml_taxonomy['labels']['name'] ); ?>
					</label><br />
					<?php
				}
				?>

				<p class="description">
					<?php esc_html_e( 'Select the Taxonomies to import.  The Terms from the chosen Enhanced Media Library Taxonomies above will be imported into Media Library Organizer\'s Media Categories Taxonomy.', 'media-library-organizer' ); ?>
				</p>
			</div>
		</div>

		<div class="wpzinc-option">
			<input name="import_enhanced_media_library" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Import', 'media-library-organizer' ); ?>" />              
		</div>
	</div>
</div>
