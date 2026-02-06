/**
 * Displays the Tree View in the Media Library, and handles
 * its functionality, such as creating/editing/deleting Terms,
 * drag and drop categorization etc.
 *
 * @since   1.0.0
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

var mediaLibraryOrganizerTreeViewGridSelectedAttachments,
	mediaLibraryOrganizerTreeViewGridModified;

/**
 * Assign Attachment(s) to the given Category.
 *
 * @since   1.1.1
 *
 * @param   array   attachment_ids  Attachment IDs.
 * @param   int     term_id         Category ID to assign Attachment(s) to.
 */
function mediaLibraryOrganizerTreeViewAssignAttachmentsToCategory( attachment_ids, term_id ) {

	( function ( $ ) {

		// Bail if no Attachment IDs or Term ID.
		if ( ! attachment_ids ) {
			return;
		}
		if ( ! term_id ) {
			return;
		}

		$.post(
			media_library_organizer_tree_view.ajaxurl,
			{
				'action':                media_library_organizer_tree_view.actions.categorize_attachments.action,
				'nonce':                 media_library_organizer_tree_view.actions.categorize_attachments.nonce,
				'taxonomy_name': 		 media_library_organizer_tree_view.taxonomy.name,
				'attachment_ids':        attachment_ids,
				'term_id':               term_id
			},
			function ( response ) {

				// Bail if an error occured.
				if ( ! response.success ) {
					wpzinc_notification_show_error_message( response.data );
					return;
				}

				// Show notification.
				wpzinc_notification_show_success_message( media_library_organizer_tree_view.labels.categorized_attachments.replace( '%s', response.data.attachments.length ) );

				// Build attributes to send to wp.media.events.
				var atts           = response.data;
				atts.selected_term = media_library_organizer_tree_view.selected_term;
				atts.media_view    = media_library_organizer_tree_view.media_view;

				// Fire the mlo:grid:tree-view:assigned:attachments:term event that Addons can hook into and listen.
				wp.media.events.trigger( 'mlo:grid:tree-view:assigned:attachments:term', atts );

			}
		);

	} )( jQuery );

}

/**
 * Fetch the Tree View HTML, injecting it into the container
 *
 * @since   1.1.1
 *
 * @param 	string 	taxonomy_name 	Taxonomy Name.
 * @param   int   	current_term   	The current Term ID or Slug that is selected.
 */
function mediaLibraryOrganizerTreeViewGet( taxonomy_name, current_term ) {

	( function ( $ ) {

		$.post(
			media_library_organizer_tree_view.ajaxurl,
			{
				'action':             media_library_organizer_tree_view.actions.get_tree_view.action,
				'nonce':              media_library_organizer_tree_view.actions.get_tree_view.nonce,
				'taxonomy_name': 	  taxonomy_name,
				'current_term':       current_term
			},
			function ( response ) {

				if ( ! response.success ) {
					return false;
				}

				// Destroy JSTree.
				mediaLibraryOrganizerTreeViewDestroyJsTree();

				// Trigger event to update the tree-view.
				const event = new CustomEvent('mlo:tree-view:updated', {detail: response });
                window.dispatchEvent(event);

				// Init JSTree.
				mediaLibraryOrganizerTreeViewInitJsTree();

				// Rebind Droppable.
				mediaLibraryOrganizerTreeViewInitDroppable();

				// Fire the mlo:grid:tree-view:loaded event that Addons can hook into and listen.
				wp.media.events.trigger( 'mlo:grid:tree-view:loaded' );

			}
		);

	} )( jQuery );

}

/**
 * Initialize the Tree View JSTree.
 *
 * @since   1.2.7
 */
function mediaLibraryOrganizerTreeViewInitJsTree() {

	( function ( $ ) {

		if ( $( '.media-library-organizer-tree-view-enabled' ).length ) {
			// If a subcategory was selected, open all .current-cat-ancestor list items,
			// so the user can see the subcategory.
			$( 'li.current-cat-ancestor', $( '.media-library-organizer-tree-view-enabled' ) ).each(
				function () {
					$( this ).addClass( 'jstree-open' );
				}
			);

			// Init JSTree.
			$( '.media-library-organizer-tree-view-enabled' ).jstree()
				.bind(
					'select_node.jstree',
					function ( e, data ) {
						document.location.href = data.node.a_attr.href;
					}
				)
				.bind(
					'open_node.jstree',
					function ( e, data ) {
						// Re-init droppable targets as new categories are displayed in the Tree View.
						mediaLibraryOrganizerTreeViewInitDroppable();
					}
				);
		}

	} )( jQuery );

}

/**
 * Destroys the Tree View JSTree
 *
 * @since   1.2.7
 */
function mediaLibraryOrganizerTreeViewDestroyJsTree() {

	( function ( $ ) {

		if ( $( '.media-library-organizer-tree-view-enabled' ).length ) {
			$( '.media-library-organizer-tree-view-enabled' ).jstree( 'destroy' );
		}

	} )( jQuery );

}

/**
 * Initialize the Tree View Draggable on the List View
 *
 * @since   1.1.1
 */
function mediaLibraryOrganizerTreeViewListInitDraggable() {

	( function ( $ ) {

		$( 'td.title.column-title strong.has-media-icon, td.tree-view-move span.dashicons-move' ).draggable(
			{
				appendTo: 'body', // Ensure dragging div is above all other elements.
				revert: true,
				cursorAt: {
					top: 10,
					left: 10
				},
				helper: function () {
					var attachment_id  = $( this ).closest( 'tr' ).attr( 'id' ).split( '-' )[1],
						attachment_ids = [ attachment_id ];

					// See if any Media Library items' checkboxes have been checked.
					// If so, include them.
					if ( $( 'table.media tbody input:checked' ).length > 0 ) {
						// Get Attachment IDs.
						$( 'table.media tbody input:checked' ).each(
							function () {
								// Skip if this Attachment is the one we're dragging, to avoid duplicates.
								if ( $( this ).val() == attachment_id ) {
									return;
								}

								attachment_ids.push( $( this ).val() );
							}
						);
					}

					// Define label.
					var label = '';
					if ( attachment_ids.length > 1 ) {
						label = media_library_organizer_tree_view.labels.categorize_attachments.replace( '%s', attachment_ids.length );
					} else {
						label = media_library_organizer_tree_view.labels.categorize_attachment;
					}

					return $( '<div id="media-library-organizer-tree-view-draggable" data-attachment-ids="' + attachment_ids.join( ',' ) + '">' + label + '</div>' );
				}
			}
		);

	} )( jQuery );

}

/**
 * Initialize the Tree View Draggable on the Grid View
 *
 * @since   1.1.1
 */
function mediaLibraryOrganizerTreeViewGridInitDraggable() {

	( function ( $ ) {

		$( 'li.attachment' ).draggable(
			{
				appendTo: 'body', // Ensure dragging div is above all other elements.
				revert: true,
				cursorAt: {
					top: 40,
					left: 10
				},
				helper: function () {
					var attachment_id  = $( this ).data( 'id' ),
						attachment_ids = [ attachment_id ];

					// Add Bulk Selected Attachments, if defined.
					if ( mediaLibraryOrganizerTreeViewGridSelectedAttachments.length > 0 ) {
						var length = mediaLibraryOrganizerTreeViewGridSelectedAttachments.length;
						for ( var i = 0; i < length; i++ ) {
							// Skip if this attachment is already selected.
							if ( mediaLibraryOrganizerTreeViewGridSelectedAttachments.models[ i ].id == attachment_id ) {
								continue;
							}

							attachment_ids.push( mediaLibraryOrganizerTreeViewGridSelectedAttachments.models[ i ].id );
						}
					}

					// Define label.
					var label = '';
					if ( attachment_ids.length > 1 ) {
						label = media_library_organizer_tree_view.labels.categorize_attachments.replace( '%s', attachment_ids.length );
					} else {
						label = media_library_organizer_tree_view.labels.categorize_attachment;
					}

					return $( '<div id="media-library-organizer-tree-view-draggable" data-attachment-ids="' + attachment_ids.join( ',' ) + '">' + label + '</div>' );
				}
			}
		);

	} )( jQuery );

}

/**
 * Initialize the Tree View Droppable.
 *
 * @since   1.1.1
 */
function mediaLibraryOrganizerTreeViewInitDroppable() {

	( function ( $ ) {

		$( '#media-library-organizer-tree-view-list .cat-item, #media-library-organizer-tree-view-list .cat-item-unassigned' ).droppable(
			{
				hoverClass: 'media-library-organizer-tree-view-droppable-hover',
				drop: function ( event, ui ) {
					// Get Attachment IDs from helper.
					var attachment_ids = $( ui.helper ).data( 'attachment-ids' );
					if ( attachment_ids.toString().search( ',' ) ) {
						attachment_ids = attachment_ids.toString().split( ',' );
					}

					// Get Term ID we dropped the items on.
					var term_id = mediaLibraryOrganizerTreeViewGetTermIDFromElement( $( event.target ) );

					// Assign Attachments to Category.
					mediaLibraryOrganizerTreeViewAssignAttachmentsToCategory( attachment_ids, term_id );
				}
			}
		);

	} )( jQuery );

}

/**
 * Extracts the Term ID from the given <li> Tree View element.
 *
 * @since 	1.2.7
 *
 * @param 	DOMElement 	element 	The <li> element.
 * @return 	mixed 					false | Term ID (-1 = unassigned, any other number = Term ID)
 */
function mediaLibraryOrganizerTreeViewGetTermIDFromElement( element ) {

	// Bail if no CSS classes exist on the element.
	if ( typeof element[0] === 'undefined' ) {
		return false;
	}
	if ( typeof element[0].className === 'undefined' ) {
		return false;
	}

	var css_classes = element[0].className.split( ' ' ),
		length      = css_classes.length;

	for ( var i = 0; i < length; i++ ) {
		// Skip if this isn't the class we're looking for.
		if ( css_classes[ i ].search( 'cat-item-' ) == -1 ) {
			continue;
		}

		// Extract number.
		var term_id = css_classes[ i ].replace( 'cat-item-', '' );

		// If the Term ID is 'unassigned', return -1.
		if ( term_id == 'unassigned' ) {
			return -1;
		}

		return term_id;

	}

	return false;

}

/**
 * Extracts the Term Name from the given <a> Tree View element
 *
 * @since 	1.3.1
 *
 * @param 	DOMElement 	element 	The <a> element.
 * @return 	string 					Term Name.
 */
function mediaLibraryOrganizerTreeViewGetTermNameFromElement( element ) {

	return jQuery( element ).contents().filter(
		function () {
			return this.nodeType == 3;
		}
	) [0].nodeValue.trim();

}

/**
 * Draggable: Grid View: Reinitialize Drag and Drop Categorization when the Attachments Browser contents change
 * e.g.
 * - Filters are applied
 * - Search is changed
 * - Bulk Selection is activated, changed or deactivated
 * - Attachments are dragged and dropped into the Tree View for categorization
 */
if ( media_library_organizer_tree_view.media_view == 'grid' ) {

	// (Re)initialize Drag and Drop Categorization when the Attachments Browser contents change.
	jQuery( document ).ready(
		function ( $ ) {
			var mediaLibraryOrganizerTreeViewObserver = new MutationObserver( mediaLibraryOrganizerTreeViewGridInitDraggable );
			mediaLibraryOrganizerTreeViewObserver.observe(
				document.querySelector( '.attachments-browser ul.attachments' ),
				{
					childList: true
				}
			);
		}
	);

	/**
	 * Fetch the selected attachments, storing them in a local var
	 */
	( function ( $, _ ) {

		// Called on load and when Bulk Select is cancelled.
		_.extend(
			wp.media.view.AttachmentFilters.prototype,
			{

				select: function () {

					mediaLibraryOrganizerTreeViewGridSelectedAttachments = this.controller.state().get( 'selection' );

				}

			}
		);

		// Called when Bulk Select is used and an attachment is selected/deselected.
		_.extend(
			wp.media.controller.Library.prototype,
			{

				refreshContent: function () {

					mediaLibraryOrganizerTreeViewGridSelectedAttachments = this.get( 'selection' );

				},

			}
		);

	} )( jQuery, _ );

}

jQuery( document ).ready(
	function ( $ ) {

		// Media Library Screen.
		if ( $( 'body' ).hasClass( 'upload-php' ) ) {
			// Move tree view into the wrapper.
			let containerSelector = '';
			if ( media_library_organizer_tree_view.media_view == 'grid' ) {
				containerSelector = '.wrap:has(.media-frame.mode-grid)';
			} else if ( media_library_organizer_tree_view.media_view == 'list' ) {
				containerSelector = '.wrap:has(#posts-filter)';
			}

			$(containerSelector).wrap( '<div class="media-library-organizer-tree-view"></div>' );

			$( '.media-library-organizer-tree-view' ).prepend( $( '#media-library-organizer-tree-view' ) );
			$( '#media-library-organizer-tree-view' ).show();

			// Make Sidebar Sticky.
			var mediaLibraryOrganizerTreeViewSidebar = new StickySidebar(
				'#media-library-organizer-tree-view',
				{
					containerSelector: '.media-library-organizer-tree-view',
					innerWrapperSelector: '.media-library-organizer-tree-view-inner',
				}
			);

			// JSTree.
			mediaLibraryOrganizerTreeViewInitJsTree();

			// Draggable.
			mediaLibraryOrganizerTreeViewListInitDraggable();

			// Droppable.
			const mediaLibraryOrganizerCategorizedAttachmentObserver = new MutationObserver( mediaLibraryOrganizerTreeViewInitDroppable );
			mediaLibraryOrganizerCategorizedAttachmentObserver.observe(
				document.querySelector('#media-library-organizer-tree-view'),
				{
					childList: true,
					subtree: true,
				}
			);
		}

	}
);

/**
 * Tree View: When a Taxonomy Term is added, refresh the Taxonomy Dropdown Filter
 *
 * @since   1.3.3
 *
 * @param   obj   atts 	Attributes.
 */
wp.media.events.on(
	'mlo:grid:tree-view:added:term',
	function ( atts ) {

		( function ( $ ) {

			switch ( atts.media_view ) {
				/**
				 * List View
				 */
				case 'list':
					// Replace <select> Taxonomy dropdown to reflect changes.
					mediaLibraryOrganizerListViewReplaceTaxonomyFilter(
						atts.taxonomy.name,
						atts.dropdown_filter,
						atts.selected_term
					);
					break;

				/**
				 * Grid View
				 */
				case 'grid':
					// Replace Taxonomy Filter to reflect changes, if we're not in Bulk Select mode
					// (otherwise the Taxonomy Filters display between the 'Delete permanently' and 'Cancel' buttons).
					if ( ! MediaLibraryOrganizerAttachmentsBrowser.controller.isModeActive( 'select' ) ) {
						mediaLibraryOrganizerGridViewReplaceTaxonomyFilter(
							atts.taxonomy.name,
							atts.terms,
							atts.taxonomy.labels.all_items,
							media_library_organizer_media.labels.unassigned
						);
					}
					break;
			}

		} )( jQuery );

	}
);

/**
 * Tree View: When a Taxonomy Term is edited, refresh the Taxonomy Dropdown Filter
 *
 * @since   1.3.3
 *
 * @param   obj   atts 	Attributes.
 */
wp.media.events.on(
	'mlo:grid:tree-view:edited:term',
	function ( atts ) {

		( function ( $ ) {

			// Update this Taxonomy for any Attachments in the Media Library View that are assigned to it.
			switch ( atts.media_view ) {
				/**
				 * List View
				 */
				case 'list':
					// Replace <select> Taxonomy dropdown to reflect changes.
					mediaLibraryOrganizerListViewReplaceTaxonomyFilter(
						atts.taxonomy.name,
						atts.dropdown_filter,
						atts.selected_term
					);

					// Iterate through all Terms listed in the WP_List_Table for each Attachment,
					// replacing the old Term with the New Term.
					mediaLibraryOrganizerListViewUpdateAttachmentTerms(
						atts.taxonomy.name,
						atts.old_term,
						atts.term
					);
					break;

				/**
				 * Grid View
				 */
				case 'grid':
					// Replace Taxonomy Filter to reflect changes, if we're not in Bulk Select mode
					// (otherwise the Taxonomy Filters display between the 'Delete permanently' and 'Cancel' buttons).
					if ( ! MediaLibraryOrganizerAttachmentsBrowser.controller.isModeActive( 'select' ) ) {
						mediaLibraryOrganizerGridViewReplaceTaxonomyFilter(
							atts.taxonomy.name,
							atts.terms,
							atts.taxonomy.labels.all_items,
							media_library_organizer_media.labels.unassigned
						);
					}

					// Refresh the Library to reflect the changed Term Name.
					if ( typeof wp.media.frame.library !== 'undefined' ) {
						wp.media.frame.library.props.set( {ignore: (+ new Date())} );
					} else {
						wp.media.frame.content.get().collection.props.set( {ignore: (+ new Date())} );
						wp.media.frame.content.get().options.selection.reset();
					}
					break;
			}

		} )( jQuery );

	}
);

/**
 * Tree View: When a Taxonomy Term is deleted, refresh the Taxonomy Dropdown Filter
 *
 * @since   1.3.3
 *
 * @param   obj   atts 	Attributes.
 */
wp.media.events.on(
	'mlo:grid:tree-view:deleted:term',
	function ( atts ) {

		( function ( $ ) {

			// Remove this Term from any Attachments in the Media Library View.
			switch ( atts.media_view ) {
				/**
				 * List View
				 */
				case 'list':
					// If we're viewing the Term we just deleted, reset the view.
					if ( atts.selected_term == atts.term.slug ) {
						window.location.href = 'upload.php?mode=list';
						return;
					}

					// Replace <select> Taxonomy dropdown to reflect changes.
					mediaLibraryOrganizerListViewReplaceTaxonomyFilter(
						atts.taxonomy.name,
						atts.dropdown_filter,
						atts.selected_term
					);

					// Iterate through all Terms listed in the WP_List_Table for each Attachment, remov the Term.
					mediaLibraryOrganizerListViewUpdateAttachmentTerms(
						atts.taxonomy.name,
						atts.term,
						false
					);
					break;

				/**
				 * Grid View.
				 */
				case 'grid':
					// If we're viewing the Category we just deleted, reset the view.
					if ( atts.selected_term == atts.term.slug ) {
						window.location.href = 'upload.php?mode=grid';
						return;
					}

					// Replace Taxonomy Filter to reflect changes, if we're not in Bulk Select mode
					// (otherwise the Taxonomy Filters display between the 'Delete permanently' and 'Cancel' buttons).
					if ( ! MediaLibraryOrganizerAttachmentsBrowser.controller.isModeActive( 'select' ) ) {
						mediaLibraryOrganizerGridViewReplaceTaxonomyFilter(
							atts.taxonomy.name,
							atts.terms,
							atts.taxonomy.labels.all_items,
							media_library_organizer_media.labels.unassigned
						);
					}

					// Refresh the Library to reflect the deleted Term.
					if ( typeof wp.media.frame.library !== 'undefined' ) {
						wp.media.frame.library.props.set( {ignore: (+ new Date())} );
					} else {
						wp.media.frame.content.get().collection.props.set( {ignore: (+ new Date())} );
						wp.media.frame.content.get().options.selection.reset();
					}
					break;
			}

		} )( jQuery );

	}
);

/**
 * List or Grid View: When attachment(s) are dragged and dropped onto a Category in the Tree View:
 * -
 * -
 *
 * @since   1.3.3
 *
 * @param   obj   atts  Attributes.
 */
wp.media.events.on(
	'mlo:grid:tree-view:assigned:attachments:term',
	function ( atts ) {

		( function ( $ ) {

			switch ( atts.media_view ) {
				/**
				 * List View
				 */
				case 'list':
					// Replace <select> Taxonomy dropdown to reflect changes.
					mediaLibraryOrganizerListViewReplaceTaxonomyFilter(
						atts.taxonomy.name,
						atts.dropdown_filter,
						media_library_organizer_tree_view.selected_term
					);

					// For each Attachment, build Term Links.
					for ( let attachment in atts.attachments ) {
						var terms  = [],
							length = atts.attachments[ attachment ].terms.length;
						for ( j = 0; j < length; j++ ) {
							terms.push( '<a href="upload.php?taxonomy=' + atts.attachments[ attachment ].terms[ j ].taxonomy + '&term=' + atts.attachments[ attachment ].terms[ j ].slug + '">' + atts.attachments[ attachment ].terms[ j ].name + '</a>' );
						}

						// Set HTML in Terms column of this Attachment's row.
						$( 'tr#post-' + atts.attachments[ attachment ].id + ' td.taxonomy-' + atts.taxonomy.name ).html( terms.join( ', ' ) );
					}
					break;

				/**
				 * Grid View
				 */
				case 'grid':
					// Cancel Bulk Select mode if active and was just used to categorize multiple Attachments.
					if ( MediaLibraryOrganizerAttachmentsBrowser.controller.isModeActive( 'select' ) ) {
						MediaLibraryOrganizerAttachmentsBrowser.controller.deactivateMode( 'select' ).activateMode( 'edit' );
					}

					// Replace Taxonomy Filter to reflect changes.
					mediaLibraryOrganizerGridViewReplaceTaxonomyFilter(
						atts.taxonomy.name,
						atts.terms,
						atts.taxonomy.labels.all_items,
						media_library_organizer_media.labels.unassigned
					);

					// Refresh Grid View.
					mediaLibraryOrganizerGridViewRefresh();
					break;
			}

			// Don't reload Tree View if the Term that was added isn't for the Taxonomy displayed in the Tree View.
			if ( atts.taxonomy.name != media_library_organizer_tree_view.taxonomy.name ) {
				return;
			}

			// Reload Tree View.
			mediaLibraryOrganizerTreeViewGet( atts.taxonomy.name, atts.selected_term );

		} )( jQuery );

	}
);

/**
 * Grid View: When an attachment is edited in the Grid View, and has a Taxonomy Term added to it using the
 * inline 'Add New' option, reload the Tree View to reflect the new Taxonomy Term
 *
 * @since   1.3.3
 *
 * @param   obj   atts  Attributes.
 */
wp.media.events.on(
	'mlo:grid:edit-attachment:added:term',
	function ( atts ) {

		// Don't reload Tree View if the Term that was added isn't for the Taxonomy displayed in the Tree View.
		if ( atts.taxonomy.name != media_library_organizer_tree_view.taxonomy.name ) {
			return;
		}

		// Reload Tree View.
		mediaLibraryOrganizerTreeViewGet( atts.taxonomy.name, media_library_organizer_tree_view.selected_term );

	}
);

/**
 * Grid View: When Taxonomy Term(s) are assigned or unassigned to an Attachment in the Grid View
 * reload the Tree View for the Taxonomy where changes were made.
 *
 * @since   1.3.3
 *
 * @param   obj   atts  Attributes.
 */
wp.media.events.on(
	'mlo:grid:edit-attachment:edited',
	function ( atts ) {

		( function ( $ ) {

			// Bail if no Taxonomy Terms were changed in the Attachment.
			if ( ! atts.taxonomy_term_changed ) {
				return;
			}

			// Reload Tree View.
			mediaLibraryOrganizerTreeViewGet( media_library_organizer_tree_view.taxonomy.name, media_library_organizer_tree_view.selected_term );

		} )( jQuery );

	}
);

/**
 * Grid View: When the Grid View's Taxonomy Filter's value is changed, reflect the change
 * of selected Category in the Tree View.
 *
 * @since   1.2.2
 *
 * @param   obj   atts  Filter Attributes.
 *                        slug: term-slug.
 */
wp.media.events.on(
	'mlo:grid:filter:change:term',
	function ( atts ) {

		// Don't reload Tree View if the Term that was changed isn't the Taxonomy displayed in the Tree View.
		if ( atts.taxonomy_name != media_library_organizer_tree_view.taxonomy.name ) {
			return;
		}

		// Update the selected term.
		media_library_organizer_tree_view.selected_term = atts.slug;

		// Reload Tree View.
		mediaLibraryOrganizerTreeViewGet( atts.taxonomy_name, atts.slug );

	}
);

/**
 * Grid View: When an attachment completes successful upload to the Grid View, reload the Tree View
 * to show the updated Category counts
 *
 * @since   1.3.1
 *
 * @param   obj   attachment  Uploaded Attachment.
 */
wp.media.events.on(
	'mlo:grid:attachment:upload:success',
	function ( attachment ) {

		mediaLibraryOrganizerTreeViewGet( media_library_organizer_tree_view.taxonomy.name, media_library_organizer_tree_view.selected_term );

	}
);

/**
 * Grid View: When an attachment is deleted in the Grid View > Edit Attachment modal,
 * refresh the Tree View to get the new Term Counts.
 *
 * @since   1.3.3
 */
wp.media.events.on(
	'mlo:grid:edit-attachment:deleted',
	function ( atts ) {

		mediaLibraryOrganizerTreeViewGet( media_library_organizer_tree_view.taxonomy.name, media_library_organizer_tree_view.selected_term );

	}
);

/**
 * Grid View: When Bulk Actions complete on Attachments in the Grid View,
 * refresh the Tree View to get the new Term counts
 *
 * @since   1.2.3
 */
wp.media.events.on(
	'mlo:grid:attachments:bulk_actions:done',
	function () {

		mediaLibraryOrganizerTreeViewGet( media_library_organizer_tree_view.taxonomy.name, media_library_organizer_tree_view.selected_term );

	}
);
