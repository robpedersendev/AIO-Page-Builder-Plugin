/**
 * AI provider routing: model selects follow provider picks; guidance panel under each model select.
 */
( function () {
	'use strict';

	var cfg = window.aioAiRouting || {};
	var catalog = cfg.catalog || {};
	var i18n = cfg.i18n || {};

	function escHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function escAttr( s ) {
		return escHtml( s ).replace( /'/g, '&#039;' );
	}

	function buildOptions( providerId, currentValue, allowEmpty ) {
		var models = catalog[ providerId ] || [];
		var html = '';
		var seen = {};
		if ( allowEmpty ) {
			html += '<option value="">' + escHtml( i18n.inheritOption || '' ) + '</option>';
		}
		var i;
		for ( i = 0; i < models.length; i++ ) {
			var m = models[ i ];
			if ( ! m || ! m.id ) {
				continue;
			}
			seen[ m.id ] = true;
			var sel = m.id === currentValue ? ' selected="selected"' : '';
			html += '<option value="' + escAttr( m.id ) + '"' + sel + '>' + escHtml( m.id ) + '</option>';
		}
		if ( currentValue && ! seen[ currentValue ] ) {
			html += '<option value="' + escAttr( currentValue ) + '" selected="selected">' +
				escHtml( ( i18n.savedModel || '' ) + ': ' + currentValue ) + '</option>';
		}
		return html;
	}

	function modelMeta( providerId, modelId ) {
		if ( ! modelId ) {
			return null;
		}
		var models = catalog[ providerId ] || [];
		var i;
		for ( i = 0; i < models.length; i++ ) {
			if ( models[ i ].id === modelId ) {
				return models[ i ];
			}
		}
		return null;
	}

	function updateModelPanel( selectEl ) {
		var cell = selectEl.closest( 'td' );
		if ( ! cell ) {
			return;
		}
		var panel = cell.querySelector( '.aio-routing-model-panel' );
		if ( ! panel ) {
			return;
		}
		var pid = '';
		if ( selectEl.id === 'aio_route_fallback_model' ) {
			var fb = document.getElementById( 'aio_route_fallback' );
			pid = fb && fb.value ? fb.value : '';
		} else if ( selectEl.classList.contains( 'aio-routing-primary-model' ) ) {
			var rowP = selectEl.closest( 'tr.aio-routing-task-row' );
			var pp = rowP ? rowP.querySelector( 'select.aio-routing-primary-provider' ) : null;
			pid = pp && pp.value ? pp.value : '';
			if ( pid === '' ) {
				var siteP = document.getElementById( 'aio_route_primary' );
				pid = siteP && siteP.value ? siteP.value : '';
			}
		} else if ( selectEl.classList.contains( 'aio-routing-fallback-model' ) ) {
			var rowF = selectEl.closest( 'tr.aio-routing-task-row' );
			var fp = rowF ? rowF.querySelector( 'select.aio-routing-fallback-provider' ) : null;
			pid = fp && fp.value ? fp.value : '';
			if ( pid === '' ) {
				var siteF = document.getElementById( 'aio_route_fallback' );
				pid = siteF && siteF.value ? siteF.value : '';
			}
		}
		var mid = selectEl.value;
		if ( ! mid ) {
			panel.innerHTML = '';
			return;
		}
		if ( ! pid ) {
			panel.innerHTML = '<p class="description">' + escHtml( i18n.chooseProviderFirst || '' ) + '</p>';
			return;
		}
		var info = modelMeta( pid, mid );
		if ( ! info ) {
			panel.innerHTML = '<p class="description">' + escHtml( i18n.customModelHint || '' ) + '</p>';
			return;
		}
		panel.innerHTML =
			'<p class="description" style="margin:0.35em 0 0.15em;"><strong>' + escHtml( i18n.goodFor || '' ) +
			':</strong> ' + escHtml( info.good_for ) + '</p>' +
			'<p class="description" style="margin:0;"><strong>' + escHtml( i18n.notIdeal || '' ) +
			':</strong> ' + escHtml( info.not_ideal_for ) + '</p>';
	}

	function refreshPrimaryModelInRow( row ) {
		var ms = row.querySelector( 'select.aio-routing-primary-model' );
		if ( ! ms ) {
			return;
		}
		var ps = row.querySelector( 'select.aio-routing-primary-provider' );
		var pid = ps && ps.value ? ps.value : '';
		if ( pid === '' ) {
			var siteP = document.getElementById( 'aio_route_primary' );
			pid = siteP && siteP.value ? siteP.value : '';
		}
		var cur = ms.value;
		ms.innerHTML = buildOptions( pid, cur, true );
		updateModelPanel( ms );
	}

	function refreshFallbackModelInRow( row ) {
		var ms = row.querySelector( 'select.aio-routing-fallback-model' );
		if ( ! ms || ms.disabled ) {
			return;
		}
		var ps = row.querySelector( 'select.aio-routing-fallback-provider' );
		var pid = ps && ps.value ? ps.value : '';
		if ( pid === '' ) {
			var siteF = document.getElementById( 'aio_route_fallback' );
			pid = siteF && siteF.value ? siteF.value : '';
		}
		var cur = ms.value;
		ms.innerHTML = buildOptions( pid, cur, true );
		updateModelPanel( ms );
	}

	function refreshAllPrimaryModels() {
		document.querySelectorAll( 'tr.aio-routing-task-row' ).forEach( function ( row ) {
			refreshPrimaryModelInRow( row );
		} );
	}

	function refreshAllFallbackModels() {
		document.querySelectorAll( 'tr.aio-routing-task-row' ).forEach( function ( row ) {
			refreshFallbackModelInRow( row );
		} );
		var g = document.getElementById( 'aio_route_fallback_model' );
		if ( g && g.tagName === 'SELECT' ) {
			var fs = document.getElementById( 'aio_route_fallback' );
			var pid = fs && fs.value ? fs.value : '';
			var cur = g.value;
			g.innerHTML = buildOptions( pid, cur, true );
			updateModelPanel( g );
		}
	}

	function bind() {
		document.querySelectorAll( 'select.aio-routing-model-select' ).forEach( function ( sel ) {
			sel.addEventListener( 'change', function () {
				updateModelPanel( sel );
			} );
			updateModelPanel( sel );
		} );

		var sitePri = document.getElementById( 'aio_route_primary' );
		if ( sitePri ) {
			sitePri.addEventListener( 'change', refreshAllPrimaryModels );
		}
		var siteFb = document.getElementById( 'aio_route_fallback' );
		if ( siteFb ) {
			siteFb.addEventListener( 'change', refreshAllFallbackModels );
		}

		document.querySelectorAll( 'select.aio-routing-primary-provider' ).forEach( function ( ps ) {
			ps.addEventListener( 'change', function () {
				var row = ps.closest( 'tr.aio-routing-task-row' );
				if ( row ) {
					refreshPrimaryModelInRow( row );
				}
			} );
		} );
		document.querySelectorAll( 'select.aio-routing-fallback-provider' ).forEach( function ( ps ) {
			ps.addEventListener( 'change', function () {
				var row = ps.closest( 'tr.aio-routing-task-row' );
				if ( row ) {
					refreshFallbackModelInRow( row );
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', bind );
	} else {
		bind();
	}
}() );
