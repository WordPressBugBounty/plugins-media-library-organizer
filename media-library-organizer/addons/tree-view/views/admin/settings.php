<?php
/**
 * Outputs the Tree View Settings at Media Library Organizer > Settings.
 *
 * @package Media_Library_Organizer
 * @author WP Media Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="tree-view" class="panel">
	<div class="postbox">
		<header>
			<h3><?php esc_html_e( 'Tree View Settings', 'media-library-organizer' ); ?></h3>

			<p class="description">
				<?php esc_html_e( 'Display a Category Tree sidebar when viewing the Media Library.', 'media-library-organizer' ); ?>
			</p>
		</header>

		<div class="wpzinc-option">
			<div class="left">
				<strong><?php esc_html_e( 'Enabled', 'media-library-organizer' ); ?></strong>
			</div>
			<div class="right">
				<select name="tree-view[enabled]" size="1" data-conditional="tree-view-enabled">
					<option value="1"<?php selected( $this->get_setting( 'tree-view', 'enabled' ), 1 ); ?>>
						<?php esc_attr_e( 'Enabled', 'media-library-organizer' ); ?>
					</option>
					<option value="0"<?php selected( $this->get_setting( 'tree-view', 'enabled' ), 0 ); ?>>
						<?php esc_attr_e( 'Disabled', 'media-library-organizer' ); ?>
					</option>
				</select>
			</div>
		</div>

		<div id="tree-view-enabled">
			<div class="wpzinc-option">
				<div class="left">
					<strong><?php esc_html_e( 'Expand/Collapse', 'media-library-organizer' ); ?></strong>
				</div>
				<div class="right">
					<select name="tree-view[jstree_enabled]" size="1">
						<option value="1"<?php selected( $this->get_setting( 'tree-view', 'jstree_enabled' ), 1 ); ?>>
							<?php esc_attr_e( 'Enabled', 'media-library-organizer' ); ?>
						</option>
						<option value="0"<?php selected( $this->get_setting( 'tree-view', 'jstree_enabled' ), 0 ); ?>>
							<?php esc_attr_e( 'Disabled', 'media-library-organizer' ); ?>
						</option>
					</select>

					<p class="description">
						<?php esc_html_e( 'If enabled, only top level Categories are displayed in the Tree View. Clicking the icon next to them will reveal Subcategories.', 'media-library-organizer' ); ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
