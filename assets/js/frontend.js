(function () {
	'use strict';

	function setLoading(directory, isLoading) {
		var loading = directory.querySelector('.ddt-loading');
		if (loading) {
			loading.hidden = !isLoading;
		}
		directory.classList.toggle('is-loading', isLoading);
	}

	document.addEventListener('click', function (event) {
		var tab = event.target.closest('.ddt-tab');
		if (!tab || !tab.closest('.ddt-directory') || !window.fetch || !window.ddtFrontend) {
			return;
		}
		event.preventDefault();
		var directory = tab.closest('.ddt-directory');
		var results = directory.querySelector('.ddt-results');
		var tabs = directory.querySelectorAll('.ddt-tab');
		var specialty = tab.getAttribute('data-specialty') || '';
		var body = new window.FormData();
		body.append('action', 'ddt_filter_doctors');
		body.append('nonce', window.ddtFrontend.nonce);
		body.append('specialty', specialty);
		body.append('group', directory.getAttribute('data-group') || '');
		body.append('columns', directory.getAttribute('data-columns') || '3');
		body.append('image_size', directory.getAttribute('data-image-size') || 'medium');
		body.append('show_all', directory.getAttribute('data-show-all') || 'true');
		setLoading(directory, true);
		window.fetch(window.ddtFrontend.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (response) { return response.json(); })
			.then(function (json) {
				if (!json || !json.success) {
					throw new Error(window.ddtFrontend.error);
				}
				results.innerHTML = json.data.html;
				tabs.forEach(function (item) {
					var active = item === tab;
					item.classList.toggle('is-active', active);
					item.setAttribute('aria-selected', active ? 'true' : 'false');
				});
			})
			.catch(function () {
				results.innerHTML = '<p class="ddt-error">' + window.ddtFrontend.error + '</p>';
			})
			.finally(function () { setLoading(directory, false); });
	});
}());
