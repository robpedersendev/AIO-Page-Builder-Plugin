/**
 * Template compare: add/remove without full-page navigation (delegated clicks on .aio-compare-action).
 */
(function () {
	'use strict';

	function getCfg() {
		return typeof aioTemplateCompare === 'object' && aioTemplateCompare !== null ? aioTemplateCompare : null;
	}

	document.addEventListener('click', function (e) {
		var link = e.target && e.target.closest ? e.target.closest('a.aio-compare-action') : null;
		if (!link || !link.getAttribute('href')) {
			return;
		}
		var cfg = getCfg();
		if (!cfg || !cfg.ajaxUrl || !cfg.nonce || !cfg.action) {
			return;
		}
		e.preventDefault();
		var op = link.getAttribute('data-aio-compare-op') || '';
		var type = link.getAttribute('data-aio-compare-type') || 'section';
		var key = link.getAttribute('data-aio-compare-key') || '';
		if (op !== 'add' && op !== 'remove' || !key) {
			return;
		}
		var fd = new FormData();
		fd.append('action', cfg.action);
		fd.append('nonce', cfg.nonce);
		fd.append('compare_op', op);
		fd.append('template_type', type);
		fd.append('template_key', key);
		link.setAttribute('aria-busy', 'true');
		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: fd,
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				link.removeAttribute('aria-busy');
				if (!data || !data.success || !data.data) {
					var msg = (cfg.i18n && cfg.i18n.error) ? cfg.i18n.error : 'Error';
					if (window.alert) {
						window.alert(msg);
					}
					return;
				}
				var d = data.data;
				var onCompareTab = /[?&]aio_tab=compare(&|$)/.test(window.location.search);
				if (onCompareTab) {
					window.location.reload();
					return;
				}
				var inList = !!d.in_compare;
				var addUrl = d.add_url || link.getAttribute('href');
				var removeUrl = d.remove_url || link.getAttribute('href');
				if (inList) {
					link.setAttribute('data-aio-compare-op', 'remove');
					link.textContent = (cfg.i18n && cfg.i18n.remove) ? cfg.i18n.remove : 'Remove from compare';
					link.setAttribute('href', removeUrl);
				} else {
					link.setAttribute('data-aio-compare-op', 'add');
					link.textContent = (cfg.i18n && cfg.i18n.add) ? cfg.i18n.add : 'Add to compare';
					link.setAttribute('href', addUrl);
				}
			})
			.catch(function () {
				link.removeAttribute('aria-busy');
				var msg = (cfg.i18n && cfg.i18n.error) ? cfg.i18n.error : 'Error';
				if (window.alert) {
					window.alert(msg);
				}
			});
	});
})();
