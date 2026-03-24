/**
 * Live template preview: viewport toggles, iframe height postMessage, reduced-motion reload, a11y status.
 */
(function () {
	'use strict';

	function setStatus(el, msg) {
		if (el) {
			el.textContent = msg;
		}
	}

	function initWrap(wrap) {
		if (!wrap || wrap.getAttribute('data-aio-live-preview-init') === '1') {
			return;
		}
		wrap.setAttribute('data-aio-live-preview-init', '1');
		var iframe = wrap.querySelector('.aio-live-preview-frame');
		var loading = wrap.querySelector('.aio-live-preview-loading');
		var section = wrap.closest('.aio-preview-section');
		var status =
			(section && section.querySelector('[id^="aio-live-preview-status"]')) || null;
		var origin = wrap.getAttribute('data-aio-live-origin') || '';
		function showLoading() {
			if (loading) {
				loading.hidden = false;
			}
		}
		function hideLoading() {
			if (loading) {
				loading.hidden = true;
			}
		}
		if (iframe) {
			showLoading();
			iframe.addEventListener('load', function () {
				hideLoading();
				setStatus(status, 'Live preview loaded.');
			});
		}
		window.addEventListener('message', function (ev) {
			if (origin && ev.origin !== origin) {
				return;
			}
			var d = ev.data;
			if (!d || d.source !== 'aio_tpl_live_preview' || d.type !== 'height') {
				return;
			}
			var h = parseInt(d.height, 10) || 0;
			if (h > 0 && iframe) {
				iframe.style.height = Math.min(h, 12000) + 'px';
			}
		});
		var toolbar = section ? section.querySelector('.aio-live-preview-toolbar') : null;
		if (toolbar) {
			var btns = toolbar.querySelectorAll('.aio-live-preview-view-btn');
			for (var i = 0; i < btns.length; i++) {
				btns[i].addEventListener(
					'click',
					(function (btn) {
						return function () {
							var v = btn.getAttribute('data-aio-viewport');
							wrap.classList.remove(
								'aio-live-preview-viewport--desktop',
								'aio-live-preview-viewport--tablet',
								'aio-live-preview-viewport--mobile'
							);
							wrap.classList.add('aio-live-preview-viewport--' + v);
							var all = toolbar.querySelectorAll('.aio-live-preview-view-btn');
							for (var j = 0; j < all.length; j++) {
								all[j].classList.remove('is-active');
							}
							btn.classList.add('is-active');
						};
					})(btns[i])
				);
			}
			var openTab = toolbar.querySelector('.aio-live-preview-open-tab');
			if (openTab && iframe) {
				openTab.addEventListener('click', function () {
					window.open(iframe.src, '_blank', 'noopener,noreferrer');
				});
			}
			var regen = toolbar.querySelector('.aio-live-preview-regenerate');
			if (regen) {
				regen.addEventListener('click', function () {
					window.location.reload();
				});
			}
			var focusBtn = toolbar.querySelector('.aio-live-preview-focus-frame');
			if (focusBtn && iframe) {
				focusBtn.addEventListener('click', function () {
					iframe.focus();
				});
			}
			var rm = toolbar.querySelector('.aio-live-preview-rm-input');
			if (rm) {
				rm.addEventListener('change', function () {
					var u = new URL(window.location.href);
					if (rm.checked) {
						u.searchParams.set('reduced_motion', '1');
					} else {
						u.searchParams.delete('reduced_motion');
					}
					window.location.href = u.toString();
				});
			}
		}
	}

	var wraps = document.querySelectorAll('[data-aio-live-preview-wrap]');
	for (var k = 0; k < wraps.length; k++) {
		initWrap(wraps[k]);
	}
})();
