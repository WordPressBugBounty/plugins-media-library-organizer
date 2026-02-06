/**
 * Registers shortcodes in the text editor as QuickTag Buttons.
 *
 * @since 	1.4.9
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

for ( const block in media_library_organizer_quicktags ) {

	mediaLibraryOrganizerQuickTagRegister( media_library_organizer_quicktags[ block ] );

}

/**
 * Registers the given shortcode as a Quick Tag, with a button in
 * the Text Editor toolbar.
 *
 * @since 	1.4.9
 *
 * @param 	object 	block 	Block
 */
function mediaLibraryOrganizerQuickTagRegister( block ) {

	( function ( $ ) {

		// Bail if Quicktags isn't available.
		if ( typeof QTags === 'undefined' ) {
			return;
		}

		QTags.addButton(
			block.prefix + '_' + block.name,
			block.title,
			function () {

				// Perform an AJAX call to load the modal's view.
				$.post(
					ajaxurl,
					{
						'action': 		'media_library_organizer_tinymce_output_modal',
						'nonce':  		media_library_organizer_tinymce.nonce,
						'editor_type':  'quicktags',
						'shortcode': 	block.name

					},
					function ( response ) {

						// Show Modal.
						wpZincQuickTagsModal.open();

						// Set Title.
						$( '#wpzinc-quicktags-modal .media-frame-title h1' ).text( block.title );

						// Inject HTML into modal.
						$( '#wpzinc-quicktags-modal .media-frame-content' ).html( response );

						// Resize Modal height to prevent whitespace below form.
						$( 'div.wpzinc-quicktags-modal div.media-modal.wp-core-ui' ).css(
							{
								height: ( $( 'div.wpzinc-quicktags-modal div.media-frame-title h1' ).outerHeight() + $( 'div.wpzinc-quicktags-modal form.convertkit-tinymce-popup' ).height() + 6 ) + 'px' // Additional 6px prevents a vertical scroll bar.
							}
						);

						// Initialize tabbed interface.
						wp_zinc_tabs_init();

						// Initialize any Selectize instances now.
						mediaLibraryOrganizerSelectizeInit();

					}
				);

			}
		);

	} )( jQuery );

}
