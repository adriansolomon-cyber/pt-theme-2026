/* Project Timber — Mediahawk Call-To-Action conversion tracking.

   Fires Mediahawk CTA conversions from the site:
     - WhatsApp Us  -> mhct.trigger('dm3')   (any wa.me / whatsapp link; delegated)
     - Live Chat    -> mhct.trigger('dm5')   (Fin / Intercom Messenger onShow; once per page)

   The 'dm1' (eCommerce checkout) CTA is intentionally NOT implemented — Mediahawk
   confirmed it is legacy and not in use. Left for a later decision.

   The Mediahawk loader is inline in header.php (defines window.mhct via mhct.min.js);
   this file is enqueued in the footer, so it runs after it. Everything is guarded:
   it never throws when Mediahawk is missing/ad-blocked and never changes layout or
   link behaviour.

   Conflict notes:
   - Intercom: this uses Intercom('onShow'); the theme's assets/js/intercom.js uses
     Intercom('onHide') — different events, so neither clobbers the other. Intercom's
     default launcher is hidden, so onShow catches every open path (our "Chat to Us"
     link calls Intercom('show'), which fires onShow).
   - WhatsApp: a delegated capture-phase listener, independent of intercom.js's
     [data-intercom]/#chat-us click handlers (the WhatsApp link is a plain wa.me <a>).
   - A one-time init guard stops a double-enqueue from binding twice.

   Task: "Add Mediahawk call-to-action tracking (WhatsApp + Live Chat)",
   Mediahawk thread 191156 (Amber Jarvis-Brown, 1 Oct 2026). */
(function () {
	'use strict';

	if ( window.__ptMhCtaInit ) { return; } // never double-bind
	window.__ptMhCtaInit = true;

	// Fire a Mediahawk CTA conversion, guarded — never breaks the page.
	function mh( code ) {
		if ( typeof window.mhct !== 'undefined' && typeof window.mhct.trigger === 'function' ) {
			try { window.mhct.trigger( code ); } catch ( e ) { /* never break the page */ }
		}
	}

	// --- WhatsApp Us -> dm3 --------------------------------------------------
	// Delegated + capture phase: covers any WhatsApp entry point (now or future)
	// without editing buttons, and fires once per click before navigation.
	document.addEventListener( 'click', function ( e ) {
		var t = e.target;
		var a = ( t && t.closest ) ? t.closest( 'a[href*="wa.me"], a[href*="api.whatsapp.com"], a[href^="whatsapp:"], [data-mh-cta="whatsapp"]' ) : null;
		if ( a ) { mh( 'dm3' ); }
	}, true );

	// --- Live Chat (Fin / Intercom) -> dm5, once per page --------------------
	var chatFired   = false;
	var onShowBound = false;

	function bindIntercom() {
		if ( onShowBound || typeof window.Intercom !== 'function' ) { return onShowBound; }
		onShowBound = true;
		window.Intercom( 'onShow', function () {
			if ( ! chatFired ) { chatFired = true; mh( 'dm5' ); }
		} );
		return true;
	}

	// window.Intercom is a queueing stub as soon as the boot snippet runs, so this
	// usually binds immediately; the interval is a fallback if it loads late.
	if ( ! bindIntercom() ) {
		var tries = 0;
		var iv = setInterval( function () {
			if ( bindIntercom() || ++tries > 40 ) { clearInterval( iv ); }
		}, 500 );
	}
})();
