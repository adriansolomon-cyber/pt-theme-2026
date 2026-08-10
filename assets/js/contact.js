/* Project Timber — Contact form (templates/page-contact.php).
   Progressive enhancement: the form works without JS (posts to admin-post.php
   and redirects back with ?pt_contact=sent). With JS we submit via fetch and
   swap in the inline "Thanks" confirmation, no reload. */
(function () {
	'use strict';

	var form = document.getElementById('cForm');
	var done = document.getElementById('cDone');
	if (!form) {
		return;
	}

	var btn = form.querySelector('.fbtn');
	// NOTE: read the action via getAttribute — the form has a hidden
	// <input name="action"> (required by admin-post.php), and a named control
	// shadows the form's .action property (which would return that input).
	var actionUrl = form.getAttribute('action');

	form.addEventListener('submit', function (e) {
		// Let the browser handle native required-field validation first.
		if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
			return; // invalid — browser shows its messages, no submit
		}

		e.preventDefault();

		var data = new FormData(form);
		data.append('pt_ajax', '1');

		if (btn) {
			btn.disabled = true;
			btn.dataset.label = btn.textContent;
			btn.textContent = 'Sending…';
		}

		fetch(actionUrl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin',
			headers: { Accept: 'application/json' }
		})
			.then(function (r) { return r.json().catch(function () { return null; }); })
			.then(function (res) {
				if (res && res.success) {
					form.style.display = 'none';
					if (done) {
						done.classList.add('show');
						done.scrollIntoView({ behavior: 'smooth', block: 'center' });
					}
					return;
				}
				restore(res && res.data && res.data.message);
			})
			.catch(function () { restore(); });
	});

	function restore(message) {
		if (btn) {
			btn.disabled = false;
			btn.textContent = btn.dataset.label || 'Send message';
		}
		alert(message || "Sorry — we couldn't send your message. Please call us on 01777 801214.");
	}
})();
