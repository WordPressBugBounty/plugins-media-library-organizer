/**
 * Defines default values for wp.media.view.Settings.AttachmentDisplay, for Backbone views
 *
 * @package Media_Library_Organizer_Defaults
 * @author WP Media Library
 */

/**
 * Defines default values for wp.media.view.Settings.AttachmentDisplay, for Backbone views
 *
 * @since 	1.0.0
 */
( function () {

	var AttachmentDisplay                    = wp.media.view.Settings.AttachmentDisplay;
	wp.media.view.Settings.AttachmentDisplay = wp.media.view.Settings.AttachmentDisplay.extend(
		{
			updateLinkTo: function () {

				// Get link to, attachment and form fields.
				var linkTo        = this.model.get( 'link' ),
					attachment    = this.options.attachment,
					$alignment    = this.$( '.alignment' ),
					$linkTo       = this.$( '.link-to' ),
					$size         = this.$( '.size' ),
					$linkToCustom = this.$( '.link-to-custom' );

				// Determine the file type.
				var attachmentFileType = 'other';
				var attachmentMimeType = attachment.get( 'type' ) + '/' + attachment.get( 'subtype' );
				for ( var fileType in media_library_organizer_defaults.file_types ) {
					if ( media_library_organizer_defaults.file_types[ fileType ].indexOf( attachmentMimeType ) > -1 ) {
						attachmentFileType = fileType;
						break;
					}
				}

				// Build object of default values.
				var attachmentDisplaySettingDefaults = {
					'alignment': media_library_organizer_defaults.settings[ attachmentFileType + '_attachment_display_alignment' ],
					'link_to': media_library_organizer_defaults.settings[ attachmentFileType + '_attachment_display_link_to' ],
					'size': media_library_organizer_defaults.settings[ attachmentFileType + '_attachment_display_size' ]
				}

				// Set defaults on the form fields.
				$alignment.val( attachmentDisplaySettingDefaults.alignment );
				$linkTo.val( attachmentDisplaySettingDefaults.link_to );
				$size.val( attachmentDisplaySettingDefaults.size );

				// Rest of the code below is the same as wp-includes/js/media-view.js.
				if ( 'none' === linkTo || 'embed' === linkTo || ( ! attachment && 'custom' !== linkTo ) ) {
					$linkToCustom.addClass( 'hidden' );
					return;
				}

				if ( attachment ) {
					if ( 'post' === linkTo ) {
						$linkToCustom.val( attachment.get( 'link' ) );
					} else if ( 'file' === linkTo ) {
						$linkToCustom.val( attachment.get( 'url' ) );
					} else if ( ! this.model.get( 'linkUrl' ) ) {
						$linkToCustom.val( 'http://' );
					}

					$linkToCustom.prop( 'readonly', 'custom' !== linkTo );
				}

				$linkToCustom.removeClass( 'hidden' );

				// If the linkToCustom is visible, focus and select its contents.
				if ( ! wp.media.isTouchDevice && $linkToCustom.is( ':visible' ) ) {
					$linkToCustom.focus()[0].select();
				}
			}
		}
	);

} )( jQuery, _ );
