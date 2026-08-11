/* Project Timber — Intercom Messenger trigger wiring (global).

   Ported from the old theTimber theme, where the "Chat to us" link (#chat-us)
   opened the Intercom Messenger via Intercom("show"). The Messenger itself is
   booted by the official Intercom WordPress plugin; this file opens it from our
   support widget's "Chat to Us" option, positions it just above our support
   button, and keeps the launcher (X icon) in sync.

   Enqueued site-wide because the support widget is global chrome present in the
   footer on every page. The click is always intercepted (no #-anchor scroll);
   it only calls Intercom('show') when the Messenger is actually booted. */
(function () {
	'use strict';

	var support = document.getElementById('support');
	var launch = support ? support.querySelector('.launch') : null;

	// Position the Messenger just ABOVE our bottom-left support button — same
	// corner, lifted clear so their frames don't overlap (overlap is what left the
	// button unclickable after closing). vertical_padding is the gap from the
	// viewport bottom; the button spans ~18–74px, so 90 sits the Messenger above it.
	function placeIntercom() {
		if (typeof window.Intercom === 'function') {
			window.Intercom('update', {
				alignment: 'left',
				horizontal_padding: 18,
				vertical_padding: 94
			});
		}
	}

	// Bind Intercom('onHide') once, lazily — both this script and the plugin's boot
	// snippet print in the footer, so Intercom may not exist at initial run and an
	// eager bind would silently no-op, leaving the launcher stuck on the X after
	// the Messenger is closed from its own control. Binding on first open (Intercom
	// guaranteed present) makes the reset reliable.
	var hideBound = false;
	function bindHideSync() {
		if (hideBound || typeof window.Intercom !== 'function') { return; }
		hideBound = true;
		window.Intercom('onHide', function () {
			if (support) { support.classList.remove('chat-open'); }
		});
	}

	function openChat(e) {
		// Always stop the link's default #-anchor jump (which scrolls the page up).
		if (e) { e.preventDefault(); }
		// The boot snippet defines window.Intercom as a queueing stub immediately,
		// so 'show' is safe even before the widget finishes loading. If Intercom
		// isn't present at all (not configured / blocked) we simply do nothing.
		if (typeof window.Intercom !== 'function') { return; }
		bindHideSync();
		// Collapse the support panel but keep the launcher as an X (chat-open) so
		// the user keeps a clear control to close the chat again.
		if (support) {
			support.classList.remove('open');
			support.classList.add('chat-open');
		}
		placeIntercom();
		window.Intercom('show');
	}

	function closeChat() {
		if (support) { support.classList.remove('chat-open'); }
		if (typeof window.Intercom === 'function') { window.Intercom('hide'); }
	}

	// Triggers: support widget "Chat to Us", plus any other [data-intercom] and the
	// legacy #chat-us id (kept for parity with the old theme markup).
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

	// If Intercom is already loaded at run time, bind close-sync + placement now
	// too; otherwise the first openChat() handles it (see bindHideSync above).
	if (typeof window.Intercom === 'function') {
		bindHideSync();
		placeIntercom();
	}
})();
