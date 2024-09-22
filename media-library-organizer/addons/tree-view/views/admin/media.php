<?php
/**
 * Outputs the Tree View in the Media Library.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="media-library-organizer-tree-view">
	<form class="media-library-organizer-tree-view-inner">
		<h2 class="wp-heading-inline"><?php echo esc_html( $taxonomy->label ); ?></h2>

		<div class="wp-filter">
		<?php
		if ( current_user_can( 'manage_categories' ) ) {
			?>
			<div class="search-form">
				<button class="button media-library-organizer-tree-view-add"><?php esc_html_e( 'Add', 'media-library-organizer' ); ?></button>
				<button class="button media-library-organizer-tree-view-edit" disabled><?php esc_html_e( 'Edit', 'media-library-organizer' ); ?></button>
				<button class="button media-library-organizer-tree-view-delete" disabled><?php esc_html_e( 'Delete', 'media-library-organizer' ); ?></button>
			</div>
			<?php
		}
		?>
		</div>

		<div id="media-library-organizer-tree-view-list"<?php echo ( $jstree_enabled ? ' class="media-library-organizer-tree-view-enabled"' : '' ); ?>>
			<?php echo wp_kses_post( $output ); ?>
		</div>
	</form>
</div>
