/* Project Timber — Intercom Messenger trigger wiring (global).

   Ported from the old theTimber theme, where the "Chat to us" link (#chat-us)
   opened the Intercom Messenger via Intercom("show"). The Messenger itself is
   booted by the official Intercom WordPress plugin; this file only opens it from
   our support widget's "Chat to Us" option.

   Enqueued site-wide because the support widget is global chrome present in the
   footer on every page. The click is always intercepted (no #-anchor scroll);
   it only calls Intercom('show') when the Messenger is actually booted. */
(function () {
	'use strict';

	var support = document.getElementById('support');

	// Pin the Intercom Messenger to the bottom-RIGHT — the opposite corner from
	// our bottom-left support button. The workspace aligns it bottom-left by
	// default, i.e. the SAME corner as the support button, and Intercom leaves an
	// invisible frame/click-catcher over that corner. That overlay is what left
	// the support button unclickable after the Messenger was closed. Moving the
	// Messenger to the other corner removes the collision entirely.
	function placeIntercom() {
		if (typeof window.Intercom === 'function') {
			window.Intercom('update', {
				alignment: 'right',
				horizontal_padding: 20,
				vertical_padding: 20
			});
		}
	}

	function openIntercom(e) {
		// Always stop the link's default #-anchor jump (which scrolls the page up).
		if (e) { e.preventDefault(); }
		// The boot snippet defines window.Intercom as a queueing stub immediately,
		// so 'show' is safe even before the widget finishes loading. If Intercom
		// isn't present at all (not configured / blocked) we simply do nothing.
		if (typeof window.Intercom !== 'function') { return; }
		if (support) { support.classList.remove('open'); } // close the support panel
		placeIntercom();
		window.Intercom('show');
	}

	// Support-widget "Chat to Us", plus any other [data-intercom] trigger and the
	// legacy #chat-us id (kept for parity with the old theme markup).
	document.querySelectorAll('[data-intercom], #chat-us').forEach(function (t) {
		if (t.getAttribute('data-pt-intercom-bound')) { return; }
		t.setAttribute('data-pt-intercom-bound', '1');
		t.addEventListener('click', openIntercom);
	});

	// Apply the placement as soon as the Messenger is ready (in case it renders
	// anything before the first open).
	placeIntercom();
})();
