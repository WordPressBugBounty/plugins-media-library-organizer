/**
 * Modify the Edit Attachment Backbone Modal's ideal column
 * width, depending on the grid size setting in the Addon.
 *
 * @package Media_Library_Organizer_Output
 * @author WP Media Library
 */

( function ( $ ) {

	/**
	 * Extend and override wp.media.view.Attachments to modify the ideal
	 * column width, depending on the grid size setting
	 */
	if ( media_library_organizer_output.settings.grid_size.length > 0 ) {

		// Works but initialize takes over wp.media.view.Attachments and breaks things.
		wp.media.view.Attachments = wp.media.view.Attachments.extend(
			{

				initialize: function () {

					switch ( media_library_organizer_output.settings.grid_size ) {

						/**
						 * Extra Large
						 * 2x
						 */
						case 'xlarge':
							this.options.idealColumnWidth = $( window ).width() < 640 ? 270 : 300;
							break;

						/**
						 * Large
						 * 1.5x
						 */
						case 'large':
							this.options.idealColumnWidth = $( window ).width() < 640 ? 200 : 225;
							break;

						/**
						 * Small
						 * 0.75x
						 */
						case 'small':
							this.options.idealColumnWidth = $( window ).width() < 640 ? 90 : 110;
							break;

						/**
						 * Extra Small
						 * 0.5x
						 */
						case 'xsmall':
							this.options.idealColumnWidth = $( window ).width() < 640 ? 60 : 75;
							break;

					}

					// Call parent initialize function.
					this.constructor.__super__.initialize.apply( this, arguments );

				}

			}
		);

	}

} )( jQuery );
