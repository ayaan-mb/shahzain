(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('speedx-reset-form');
		var input = document.getElementById('speedx-reset-confirm');
		var submit = document.getElementById('speedx-reset-submit');

		if (!form || !input || !submit) {
			return;
		}

		var toggleButtonState = function () {
			submit.disabled = input.value !== 'reset';
		};

		input.addEventListener('input', toggleButtonState);
		toggleButtonState();

		form.addEventListener('submit', function (event) {
			if (input.value !== 'reset') {
				event.preventDefault();
				return;
			}

			var confirmText =
				typeof speedxSiteResetConfig !== 'undefined' && speedxSiteResetConfig.confirmText
					? speedxSiteResetConfig.confirmText
					: 'Are you absolutely sure? This will permanently delete website data.';

			if (!window.confirm(confirmText)) {
				event.preventDefault();
			}
		});
	});
})();
