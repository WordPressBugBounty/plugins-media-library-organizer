/**
 * Handles add/edit/delete of rulesets in settings UI.
 *
 * @package Media_Library_Organizer_Defaults
 * @author WP Media Library
 */

/**
 * Reindexes rulesets when a ruleset is added or deleted
 *
 * @since 	1.1.5
 */
function mediaLibraryOrganizerDefaultsReindexRulesets( container ) {

	( function ( $ ) {

		// Find all sortable options in the ruleset container (these are individual rulesets)
		// and reindex them from 1.
		$( 'div.wpzinc-option', $( container ) ).each(
			function ( i ) {
				// Display the index number.
				$( 'div.number a.count ', $( this ) ).html( '#' + ( i + 1 ) );

				// Change the ID and Name attributes so they remain associated with the correct ruleset.
				$( 'table tbody tr', $( this ) ).each(
					function ( j ) {
						$( 'label', $( this ) ).each(
							function ( k ) {
								if ( typeof $( this ).data( 'for' ) !== 'undefined' ) {
									$( this ).attr( 'for', $( this ).data( 'for' ).replace( 'index', i ) );
								}
							}
						);
						$( 'input, select', $( this ) ).each(
							function ( k ) {
								if ( typeof $( this ).data( 'id' ) !== 'undefined' ) {
									$( this ).attr( 'id', $( this ).data( 'id' ).replace( 'index', i ) );
								}

								// Only for Custom Fields.
								if ( typeof $( this ).data( 'name' ) !== 'undefined' ) {
									$( this ).attr( 'name', $( this ).data( 'name' ).replace( 'index', i ) );
								}
							}
						);
					}
				);

				// Set 'first' class.
				if ( i == 0 ) {
					$( this ).addClass( 'first' );
				} else {
					$( this ).removeClass( 'first' );
				}
			}
		);

	} )( jQuery );

}

jQuery( document ).ready(
	function ( $ ) {

		/**
		 * Add Ruleset
		 */
		$( 'body' ).on(
			'click',
			'div#defaults-container button.add-ruleset',
			function ( e ) {

				e.preventDefault();

				// Destroy selectize instances.
				mediaLibraryOrganizerSelectizeDestroy( '#defaults-container' );

				// // Setup vars.
				// var button       = $( this ),
				// button_container = $( button ).parent(),
				// container        = $( button ).closest( 'div.rulesets' );

				// // Clone ruleset element, removing the existing selectize instance.
				// var ruleset = $( button_container ).prev().clone();
				// ruleset.find( 'div.wpzinc-selectize' ).remove();

				// // Add cloned ruleset.
				// $( button_container ).before( '<div class="wpzinc-option">' + $( ruleset ).html() + '</div>' );

				// // Reindex rulesets.
				// mediaLibraryOrganizerDefaultsReindexRulesets( $( container ) );

				// Reinit selectize instances.
				mediaLibraryOrganizerSelectizeInit( '#defaults-container' );

			}
		);

		/**
		 * Delete Ruleset
		 */
		$( 'body' ).on(
			'click',
			'div.rulesets a.delete',
			function ( e ) {

				e.preventDefault();

				// Confirm deletion.
				var result = confirm( media_library_organizer_defaults_settings.delete_ruleset_message );
				if ( ! result ) {
					return;
				}

				// Get ruleset and container.
				var ruleset = $( this ).closest( 'div.wpzinc-option' ),
				container   = $( ruleset ).closest( 'div.rulesets' );

				// Delete ruleset.
				$( ruleset ).remove();

				// Reindex rulesets.
				mediaLibraryOrganizerDefaultsReindexRulesets( $( container ) );

			}
		);
	}
);
