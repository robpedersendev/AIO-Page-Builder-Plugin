/**
 * Build Plan workspace: sync "select all" with row checkboxes inside bulk+list forms.
 */
(function () {
	'use strict';

	document.addEventListener('change', function (event) {
		var target = event.target;
		if (!target || !target.classList) {
			return;
		}
		var form = target.form;
		if (!form || !form.classList.contains('aio-step-bulk-and-list-form')) {
			return;
		}
		if (target.classList.contains('aio-row-select-all')) {
			var checked = target.checked;
			var rows = form.querySelectorAll('input.aio-row-select[type="checkbox"]');
			for (var i = 0; i < rows.length; i++) {
				rows[i].checked = checked;
			}
			return;
		}
		if (target.classList.contains('aio-row-select')) {
			var selectAll = form.querySelector('input.aio-row-select-all[type="checkbox"]');
			if (!selectAll) {
				return;
			}
			var allRows = form.querySelectorAll('input.aio-row-select[type="checkbox"]');
			var total = allRows.length;
			var n = 0;
			for (var j = 0; j < allRows.length; j++) {
				if (allRows[j].checked) {
					n++;
				}
			}
			selectAll.checked = total > 0 && n === total;
			selectAll.indeterminate = n > 0 && n < total;
		}
	});
}());
