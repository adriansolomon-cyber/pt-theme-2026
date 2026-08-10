/* Project Timber — Intercom Messenger trigger wiring (global).

   Ported from the old theTimber theme, where the "Chat to us" link (#chat-us)
   opened the Intercom Messenger via Intercom("show") — see
   theTimber/assets/js/mainscript.js. The Messenger itself is booted separately
   (by functions.php pt_intercom_snippet, or the official Intercom WP plugin);
   this file only opens it from our support widget's "Chat to Us" option.

   Enqueued site-wide (only when Intercom is configured) because the support
   widget is global chrome present in the footer on every page. */
(function () {
	'use strict';

	var support = document.getElementById('support');

	function openIntercom(e) {
		// Always stop the link's default #-anchor jump (which scrolls the page up).
		if (e) { e.preventDefault(); }
		// The boot snippet defines window.Intercom as a queueing stub immediately,
		// so 'show' is safe to call even before the widget script has finished
		// loading — it queues and opens once ready. If Intercom isn't present at
		// all (not configured / blocked) we simply do nothing rather than scroll.
		if (typeof window.Intercom !== 'function') {
			return;
		}
		if (support) { support.classList.remove('open'); } // close the support panel
		window.Intercom('show');
	}

	// Support-widget "Chat to Us", plus any other [data-intercom] trigger and the
	// legacy #chat-us id (kept for parity with the old theme markup).
	document.querySelectorAll('[data-intercom], #chat-us').forEach(function (t) {
		if (t.getAttribute('data-pt-intercom-bound')) { return; }
		t.setAttribute('data-pt-intercom-bound', '1');
		t.addEventListener('click', openIntercom);
	});
})();
