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

	// GA4/GTM: fire form_start once, on first interaction (funnel start).
	var ptStarted = false;
	form.addEventListener('focusin', function () {
		if (ptStarted) { return; }
		ptStarted = true;
		try { window.dataLayer = window.dataLayer || []; window.dataLayer.push({ event: 'form_start', form_name: 'contact', form_id: 'contact-form' }); } catch (e) {}
	});

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
					// GA4/GTM: explicit success event (these forms submit via fetch, so
					// GA4's automatic form tracking never sees them). Trigger on `form_submit`.
					try { window.dataLayer = window.dataLayer || []; window.dataLayer.push({ event: 'form_submit', form_name: 'contact', form_id: 'contact-form' }); } catch (e) {}
					// Meta conversion (browser copy) — same event_id as the server sent,
					// so they deduplicate. Held automatically if consent isn't granted.
					if (res.data && res.data.fb && typeof window.fbq === 'function') {
						try {
							fbq('track', res.data.fb.event, res.data.fb.custom_data || {}, { eventID: res.data.fb.event_id });
						} catch (e) {}
					}
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
