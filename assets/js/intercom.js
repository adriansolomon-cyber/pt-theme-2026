/* Project Timber — Intercom Messenger trigger wiring (global).

   Ported from the old theTimber theme, where the "Chat to us" link (#chat-us)
   opened the Intercom Messenger via Intercom("show"). The Messenger itself is
   booted by the official Intercom WordPress plugin; this file only opens it from
   our support widget's "Chat to Us" option and keeps the launcher in sync.

   Enqueued site-wide because the support widget is global chrome present in the
   footer on every page. The click is always intercepted (no #-anchor scroll);
   it only calls Intercom('show') when the Messenger is actually booted. */
(function () {
	'use strict';

	var support = document.getElementById('support');
	var launch = support ? support.querySelector('.launch') : null;

	// The workspace aligns the Messenger to the bottom-left — the same corner as
	// our support button. Lift the Messenger off the bottom so the two don't
	// crowd each other (vertical_padding is the gap from the viewport bottom).
	function applyPadding() {
		if (typeof window.Intercom === 'function') {
			window.Intercom('update', { vertical_padding: 100 });
		}
	}

	function openChat(e) {
		if (e) { e.preventDefault(); }
		// The boot snippet defines window.Intercom as a queueing stub immediately,
		// so 'show' is safe even before the widget finishes loading. If Intercom
		// isn't present at all (not configured / blocked) we just do nothing.
		if (typeof window.Intercom !== 'function') { return; }
		// Collapse the support panel but keep the launcher as an X (chat-open) so
		// the user keeps a clear control to close the chat again.
		if (support) {
			support.classList.remove('open');
			support.classList.add('chat-open');
		}
		applyPadding();
		window.Intercom('show');
	}

	function closeChat() {
		if (support) { support.classList.remove('chat-open'); }
		if (typeof window.Intercom === 'function') { window.Intercom('hide'); }
	}

	// Triggers: support widget "Chat to Us", plus any other [data-intercom] and
	// the legacy #chat-us id (kept for parity with the old theme markup).
	document.querySelectorAll('[data-intercom], #chat-us').forEach(function (t) {
		if (t.getAttribute('data-pt-intercom-bound')) { return; }
		t.setAttribute('data-pt-intercom-bound', '1');
		t.addEventListener('click', openChat);
	});

	// While the chat is open the launcher shows an X — clicking it closes the chat
	// instead of re-opening our support panel. Capture phase + stopImmediatePropagation
	// pre-empts the panel-toggle handler bound elsewhere (header.js / per-page JS).
	if (launch) {
		launch.addEventListener('click', function (e) {
			if (support && support.classList.contains('chat-open')) {
				e.preventDefault();
				e.stopImmediatePropagation();
				closeChat();
			}
		}, true);
	}

	// Sync our launcher state when the chat is closed from Intercom's own controls,
	// and apply the spacing once the Messenger is ready.
	if (typeof window.Intercom === 'function') {
		window.Intercom('onHide', function () {
			if (support) { support.classList.remove('chat-open'); }
		});
		applyPadding();
	}
})();
