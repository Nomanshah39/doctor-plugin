(function () {
	'use strict';
	document.addEventListener('click', function (event) {
		var upload = event.target.closest('.ddt-upload-image');
		var remove = event.target.closest('.ddt-remove-image');
		if (upload) {
			event.preventDefault();
			var frame = wp.media({ title: 'Select doctor image', button: { text: 'Use this image' }, multiple: false });
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var input = document.getElementById('ddt_image_id');
				var preview = document.querySelector('.ddt-image-preview');
				input.value = attachment.id;
				preview.innerHTML = '<img src="' + (attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="" />';
			});
			frame.open();
		}
		if (remove) {
			event.preventDefault();
			document.getElementById('ddt_image_id').value = '';
			var preview = document.querySelector('.ddt-image-preview');
			preview.textContent = preview.getAttribute('data-placeholder') || 'No image selected';
		}
	});
}());
