<?php
/**
 * Output Backbone template for a modal window comprising of content area with sidebar.
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
<script type="text/html" id="tmpl-media-library-organizer-content-view">
	<div class="media-frame-title">
		<h1>{{data.title}}</h1>
	</div>
	<div class="media-frame-content">
		<!-- Content -->
		<div class="media-content has-sidebar">
		</div>

		<!-- Sidebar -->
		<div class="media-sidebar">
		</div>
	</div>

	<!-- Footer Bar -->
	<div class="media-frame-toolbar">
		<div class="media-toolbar">
			<div class="media-toolbar-primary search-form">
				<button type="button" class="button media-button button-primary button-large media-button-insert">
					{{data.buttonLabel}}
				</button>
			</div>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-media-library-organizer-sidebar-view">
</script>
