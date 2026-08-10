/* Project Timber — Request-a-callback modal (global).
   Opens from any [data-callback] trigger (the support widget's "Request a
   Callback" option). Submits the form via fetch to admin-post.php and shows an
   inline confirmation. Works without JS too (native POST + redirect). */
(function () {
	'use strict';

	var ov = document.getElementById('pcbOverlay');
	if (!ov) {
		return;
	}
	var closeBtn = document.getElementById('pcbClose');
	var form = document.getElementById('pcbForm');
	var done = document.getElementById('pcbDone');
	var support = document.getElementById('support');
	var lastFocus = null;

	function open() {
		lastFocus = document.activeElement;
		if (support) { support.classList.remove('open'); } // close the support widget
		ov.classList.add('open');
		ov.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
		var n = document.getElementById('pcbName');
		if (n) { n.focus(); }
	}

	function close() {
		ov.classList.remove('open');
		ov.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	// Triggers (support widget "Request a Callback", or any [data-callback]).
	document.querySelectorAll('[data-callback]').forEach(function (b) {
		b.addEventListener('click', function (e) { e.preventDefault(); open(); });
	});
	if (closeBtn) { closeBtn.addEventListener('click', close); }
	ov.addEventListener('click', function (e) { if (e.target === ov) { close(); } });
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && ov.classList.contains('open')) { close(); }
	});

	if (!form) { return; }
	var btn = form.querySelector('.pcb-btn');
	// Read the action via getAttribute — the hidden <input name="action"> shadows form.action.
	var actionUrl = form.getAttribute('action');

	form.addEventListener('submit', function (e) {
		if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
			return;
		}
		e.preventDefault();

		var data = new FormData(form);
		data.append('pt_ajax', '1');

		if (btn) { btn.disabled = true; btn.dataset.label = btn.textContent; btn.textContent = 'Sending…'; }

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
					if (done) { done.classList.add('show'); }
					return;
				}
				restore(res && res.data && res.data.message);
			})
			.catch(function () { restore(); });
	});

	function restore(message) {
		if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Send request'; }
		alert(message || "Sorry — we couldn't send your request. Please call us on 01777 553392.");
	}
})();
