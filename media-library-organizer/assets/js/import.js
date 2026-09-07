/**
 * Media Library Organizer - Import
 *
 * Starts and monitors background imports on the Import & Export screen.
 *
 * @package Media_Library_Organizer
 * @author  Themeisle
 */

( function () {
	'use strict';

	const settings = window.media_library_organizer_import;

	/**
	 * Returns the progress panel for the given Import Source.
	 *
	 * @param {string} source Import Source name.
	 * @return {Element|null} Progress panel.
	 */
	const panel = ( source ) => document.querySelector( '.mlo-import[data-source="' + source + '"]' );

	/**
	 * Returns the Import Source's own options, such as the taxonomies to import from
	 * Enhanced Media Library.
	 *
	 * Every Import Source shares one form, so only the fields inside the submitted Source's
	 * own panel are collected.
	 *
	 * @param {Element} button Import button.
	 * @return {Object} Options, keyed by field name.
	 */
	function options( button ) {
		const container = button.closest( '.panel' );
		const args      = {};

		if ( ! container ) {
			return args;
		}

		container.querySelectorAll( 'input, select, textarea' ).forEach( ( field ) => {
			// Skip fields that wouldn't be submitted with a form.
			if ( ! field.name || field.disabled ) {
				return;
			}

			if ( ( 'checkbox' === field.type || 'radio' === field.type ) && ! field.checked ) {
				return;
			}

			// Fields named e.g. taxonomies[] are sent as an array.
			const isMultiple = field.name.endsWith( '[]' ) || field.multiple;
			const name       = field.name.replace( '[]', '' );

			if ( ! isMultiple ) {
				args[ name ] = field.value;
				return;
			}

			if ( ! args[ name ] ) {
				args[ name ] = [];
			}

			if ( field.multiple && 'SELECT' === field.tagName ) {
				Array.from( field.selectedOptions ).forEach( ( option ) => args[ name ].push( option.value ) );
				return;
			}

			args[ name ].push( field.value );
		} );

		return args;
	}

	/**
	 * Outputs the given messages as list items, hiding the list when there are none.
	 *
	 * @param {Element} list  List element.
	 * @param {Array}   items Messages.
	 */
	function messages( list, items ) {
		if ( ! list ) {
			return;
		}

		list.textContent = '';

		if ( ! items || ! items.length ) {
			list.style.display = 'none';
			return;
		}

		items.forEach( ( item ) => {
			const listItem       = document.createElement( 'li' );
			listItem.textContent = item;
			list.append( listItem );
		} );

		list.style.display = '';
	}

	/**
	 * Displays the given message in an Import Source's panel.
	 *
	 * @param {Element} container Progress panel.
	 * @param {string}  message   Message.
	 */
	function setMessage( container, message ) {
		const element = container ? container.querySelector( '.mlo-import-message' ) : null;

		if ( element ) {
			element.textContent = message;
		}
	}

	/**
	 * Displays the given Import's progress, and disables the Import buttons of every other
	 * source while it runs.
	 *
	 * @param {Object} status Import status.
	 */
	function render( status ) {
		// Only one import can run at a time, so every other source's button is unavailable
		// until this one finishes.
		document.querySelectorAll( '.mlo-import-start' ).forEach( ( button ) => {
			button.disabled = status.running;
		} );

		const container = status.source ? panel( status.source ) : null;

		if ( ! container || ! status.message ) {
			return;
		}

		container.style.display = '';
		setMessage( container, status.message );

		const progress = container.querySelector( '.mlo-import-progress' );
		if ( progress ) {
			progress.value = status.percentage;
		}

		document.querySelectorAll( '.mlo-import-cancel[data-source="' + status.source + '"]' ).forEach( ( button ) => {
			button.style.display = status.running ? '' : 'none';
		} );

		messages( container.querySelector( '.mlo-import-errors' ), status.errors.concat( status.failed_batches ) );
	}

	/**
	 * Adds a value to the request body, expanding arrays into the repeated keys PHP reads
	 * back as an array.
	 *
	 * @param {URLSearchParams} body  Request body.
	 * @param {string}          key   Parameter name.
	 * @param {*}               value Parameter value.
	 */
	function appendValue( body, key, value ) {
		if ( Array.isArray( value ) ) {
			value.forEach( ( item ) => appendValue( body, key + '[]', item ) );
			return;
		}

		body.append( key, value );
	}

	/**
	 * Sends a request to the given Import AJAX action, and returns its response.
	 *
	 * @param {string} action Action name.
	 * @param {Object} data   Request data.
	 * @return {Promise<Object>} Response.
	 */
	async function request( action, data ) {
		const body = new URLSearchParams();

		body.append( 'action', action );
		body.append( 'nonce', settings.nonce );

		Object.keys( data || {} ).forEach( ( key ) => {
			if ( 'args' === key ) {
				Object.keys( data.args ).forEach( ( name ) => appendValue( body, 'args[' + name + ']', data.args[ name ] ) );
				return;
			}

			appendValue( body, key, data[ key ] );
		} );

		const response = await fetch( window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body,
		} );

		if ( ! response.ok ) {
			throw new Error( 'Request failed with status ' + response.status );
		}

		return response.json();
	}

	let timer = null;

	/**
	 * Asks the server for the Import's progress, until the Import finishes.
	 */
	async function poll() {
		window.clearTimeout( timer );

		try {
			const response = await request( settings.actions.status );

			if ( ! response.success ) {
				return;
			}

			render( response.data );

			if ( response.data.running ) {
				timer = window.setTimeout( poll, settings.interval );
			}
		} catch ( error ) {
			// The import keeps running in the background, so keep asking.
			timer = window.setTimeout( poll, settings.interval );
		}
	}

	/**
	 * Starts an Import.
	 *
	 * @param {Element} button Import button.
	 */
	async function start( button ) {
		const container = panel( button.dataset.source );
		const progress  = container ? container.querySelector( '.mlo-import-progress' ) : null;

		button.disabled = true;

		if ( container ) {
			container.style.display = '';
		}

		setMessage( container, settings.strings.starting );
		messages( container ? container.querySelector( '.mlo-import-errors' ) : null, [] );

		if ( progress ) {
			progress.value = 0;
		}

		try {
			const response = await request( settings.actions.start, {
				source: button.dataset.source,
				args: options( button ),
			} );

			if ( ! response.success ) {
				button.disabled = false;
				setMessage( container, response.data );
				return;
			}

			render( response.data );
			poll();
		} catch ( error ) {
			button.disabled = false;
			setMessage( container, settings.strings.request_failed );
		}
	}

	/**
	 * Cancels a running Import.
	 *
	 * @param {Element} button Cancel button.
	 */
	async function cancel( button ) {
		if ( ! window.confirm( settings.strings.confirm_cancel ) ) {
			return;
		}

		const container = panel( button.dataset.source );

		window.clearTimeout( timer );
		setMessage( container, settings.strings.cancelling );

		try {
			const response = await request( settings.actions.cancel );

			if ( ! response.success ) {
				setMessage( container, response.data );
				poll();
				return;
			}

			render( response.data );
		} catch ( error ) {
			// The import may not have been cancelled, and may still be running, so go back to
			// reporting whatever the site says its state is.
			setMessage( container, settings.strings.request_failed );
			poll();
		}
	}

	/**
	 * Binds the Import and Cancel buttons, and picks up an Import that was already running
	 * when this screen loaded.
	 */
	function init() {
		if ( ! settings || ! document.querySelector( '.mlo-import' ) ) {
			return;
		}

		document.querySelectorAll( '.mlo-import-start' ).forEach( ( button ) => {
			button.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				start( button );
			} );
		} );

		document.querySelectorAll( '.mlo-import-cancel' ).forEach( ( button ) => {
			button.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				cancel( button );
			} );
		} );

		render( settings.status );

		if ( settings.status.running ) {
			poll();
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
