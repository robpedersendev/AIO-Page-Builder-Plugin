/**
 * Batches delegated clicks/submits on [data-aio-ux-action] to admin-ajax (WP_DEBUG builds only).
 *
 * Uses application/x-www-form-urlencoded for compatibility with strict proxies/WAFs that mishandle
 * multipart FormData or sendBeacon to admin-ajax.php. Unload uses fetch(..., { keepalive: true });
 * periodic and debounced flushes use ordinary fetch. If unload delivery fails, the 5s interval
 * still drains the queue while the document stays visible.
 */
(function () {
	'use strict';
	if ( typeof window.AioAdminUxTrace === 'undefined' ) {
		return;
	}
	var cfg = window.AioAdminUxTrace;
	var queue = [];
	var seq = 0;
	var maxBatch = 25;
	var flushTimer = null;
	var FORM_CT = 'application/x-www-form-urlencoded; charset=UTF-8';

	function tagsFromElement( el ) {
		var tags = [];
		var sec = el.getAttribute( 'data-aio-ux-section' );
		if ( sec ) {
			tags.push( 'section:' + sec );
		}
		var hub = el.getAttribute( 'data-aio-ux-hub' );
		if ( hub ) {
			tags.push( 'hub:' + hub );
		}
		var tab = el.getAttribute( 'data-aio-ux-tab' );
		if ( tab ) {
			tags.push( 'tab:' + tab );
		}
		return tags;
	}

	function buildPayload( kind, el ) {
		seq += 1;
		var action = el.getAttribute( 'data-aio-ux-action' ) || 'unknown';
		var row = {
			severity: 'flow',
			facet: 'client_interaction',
			detail: kind + ':' + action,
			tags: tagsFromElement( el ),
			client_sequence: seq
		};
		var hub = cfg.hub || '';
		var tab = cfg.tab || '';
		if ( hub ) {
			row.hub = hub;
		}
		if ( tab ) {
			row.tab = tab;
		}
		return row;
	}

	function encodeBatchBody( chunk ) {
		var p = new URLSearchParams();
		p.set( 'action', cfg.action );
		p.set( 'nonce', cfg.nonce );
		p.set( 'batch', JSON.stringify( chunk ) );
		return p.toString();
	}

	function postBatch( body, keepalive ) {
		if ( typeof fetch === 'undefined' || ! cfg.ajaxUrl ) {
			return;
		}
		var opts = {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': FORM_CT },
			body: body
		};
		if ( keepalive ) {
			opts.keepalive = true;
		}
		fetch( cfg.ajaxUrl, opts ).catch( function () {} );
	}

	/**
	 * @param {boolean} keepalive Use for document unload (best-effort; host/proxy may still drop).
	 */
	function flush( keepalive ) {
		if ( ! queue.length || ! cfg.ajaxUrl ) {
			return;
		}
		var chunk = queue.splice( 0, maxBatch );
		postBatch( encodeBatchBody( chunk ), !! keepalive );
	}

	function flushAllKeepalive() {
		while ( queue.length && cfg.ajaxUrl ) {
			flush( true );
		}
	}

	function scheduleFlush() {
		if ( flushTimer ) {
			clearTimeout( flushTimer );
		}
		flushTimer = setTimeout( function () {
			flushTimer = null;
			flush( false );
		}, 400 );
	}

	document.addEventListener(
		'click',
		function ( ev ) {
			var t = ev.target && ev.target.closest ? ev.target.closest( '[data-aio-ux-action]' ) : null;
			if ( ! t ) {
				return;
			}
			queue.push( buildPayload( 'click', t ) );
			if ( queue.length >= 10 ) {
				flush( false );
			} else {
				scheduleFlush();
			}
		},
		true
	);

	document.addEventListener(
		'submit',
		function ( ev ) {
			var f = ev.target;
			if ( ! f || ! f.closest ) {
				return;
			}
			if ( f.tagName && f.tagName.toLowerCase() !== 'form' ) {
				return;
			}
			var marker = null;
			if ( ev.submitter && ev.submitter.getAttribute && ev.submitter.getAttribute( 'data-aio-ux-action' ) ) {
				marker = ev.submitter;
			} else if ( f.querySelector ) {
				marker = f.querySelector( '[data-aio-ux-action]' );
			}
			if ( ! marker ) {
				return;
			}
			queue.push( buildPayload( 'submit', marker ) );
			flush( false );
		},
		true
	);

	function onPageLifecycleUnload() {
		flushAllKeepalive();
	}

	window.addEventListener( 'pagehide', onPageLifecycleUnload );
	window.addEventListener( 'beforeunload', onPageLifecycleUnload );

	setInterval( function () {
		flush( false );
	}, 5000 );
} )();
