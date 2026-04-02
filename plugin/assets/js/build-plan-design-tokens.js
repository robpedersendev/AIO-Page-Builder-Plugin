/**
 * Searchable catalog pickers for build plan design token add/edit forms.
 */
(function () {
	'use strict';

	var root = document.querySelector('.aio-dt-token-tools');
	if (!root) {
		return;
	}
	var jsonEl = document.getElementById('aio-dt-catalog-json');
	var catalog = [];
	if (jsonEl && jsonEl.textContent) {
		try {
			catalog = JSON.parse(jsonEl.textContent.trim());
		} catch (e) {
			catalog = [];
		}
	}
	if (!Array.isArray(catalog)) {
		catalog = [];
	}

	function bindSearch(searchId, hiddenId, pickedId) {
		var search = document.getElementById(searchId);
		var hidden = document.getElementById(hiddenId);
		var picked = document.getElementById(pickedId);
		if (!search || !hidden || !picked) {
			return;
		}
		var ul = document.createElement('ul');
		ul.className = 'aio-dt-suggest-list';
		ul.setAttribute('hidden', 'hidden');
		search.parentNode.appendChild(ul);

		function findRowById(id) {
			var i;
			for (i = 0; i < catalog.length; i++) {
				if (catalog[i] && catalog[i].id === id) {
					return catalog[i];
				}
			}
			return null;
		}

		function render(q) {
			var n = 0;
			q = (q || '').toLowerCase().trim();
			ul.innerHTML = '';
			catalog.forEach(function (row) {
				var hay;
				var li;
				if (n >= 80) {
					return;
				}
				if (!row || !row.id) {
					return;
				}
				hay = (String(row.label || '') + ' ' + String(row.purpose || '') + ' ' + String(row.group || '') + ' ' + String(row.name || '')).toLowerCase();
				if (q && hay.indexOf(q) === -1) {
					return;
				}
				li = document.createElement('li');
				li.textContent = String(row.label || row.id) + ' — ' + String(row.purpose || '');
				li.setAttribute('tabindex', '0');
				li.addEventListener('mousedown', function (ev) {
					ev.preventDefault();
					hidden.value = row.id;
					picked.textContent = String(row.label || row.id);
					search.value = '';
					ul.setAttribute('hidden', 'hidden');
				});
				ul.appendChild(li);
				n++;
			});
			if (n === 0) {
				ul.setAttribute('hidden', 'hidden');
			} else {
				ul.removeAttribute('hidden');
			}
		}

		search.addEventListener('input', function () {
			render(search.value);
		});
		search.addEventListener('focus', function () {
			render(search.value);
		});
		document.addEventListener('click', function (e) {
			if (!root.contains(e.target)) {
				ul.setAttribute('hidden', 'hidden');
			}
		});

		if (hidden.value) {
			var sel = findRowById(hidden.value);
			if (sel) {
				picked.textContent = String(sel.label || sel.id);
			}
		}
	}

	bindSearch('aio-dt-add-search', 'aio-dt-add-catalog-id', 'aio-dt-add-picked');
	bindSearch('aio-dt-edit-search', 'aio-dt-edit-catalog-id', 'aio-dt-edit-picked');
}());
