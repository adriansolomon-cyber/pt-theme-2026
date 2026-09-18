/* Project Timber — Intercom Messenger trigger wiring + position sync (global).

   The Messenger itself is booted by the official Intercom WordPress plugin.
   This file:
   - opens it from our bottom-left support widget's "Chat to Us" option;
   - hides Intercom's own default launcher and anchors the Messenger, the in-app
     notification card and the unread badge to our #support button, measured at
     RUNTIME (so it survives breakpoint changes, rotation and any future
     restyling of the button — nothing is hardcoded);
   - keeps our launcher (the X icon) in sync when the Messenger closes.

   Paired with the #intercom-container rules in base.css, which read the
   --pt-ic-clear variable this script sets. On mobile Intercom ignores
   vertical_padding, so that CSS is what actually lifts the card off the button.

   Enqueued site-wide because the support widget is global chrome present in the
   footer on every page. Safe to run before or after the Intercom boot code — it
   waits for window.Intercom and re-runs on load/resize/orientation. */
(function () {
	'use strict';

	var support = document.getElementById('support');
	var launch  = support ? support.querySelector('.launch') : null;

	var SEL  = '#support'; // the custom yellow launcher
	var GAP  = 16;         // breathing room above the button
	var BOX  = 48;         // Intercom's own launcher box size
	var root = document.documentElement;
	var raf, last = '';

	// Measure #support and drive Intercom's padding + the --pt-ic-clear CSS var
	// from it, so the Messenger/card/badge anchor to our button at any size.
	function placeIntercom() {
		var btn = document.querySelector( SEL );
		if ( ! btn ) { return; }

		var r = btn.getBoundingClientRect();
		if ( ! r.height ) { return; } // not laid out yet

		var left  = Math.round( r.left );
		var clear = Math.round( window.innerHeight - r.bottom ) + Math.round( r.height ) + GAP;

		var key = left + ':' + clear;
		if ( key !== last ) { // skip the Intercom round-trip when nothing moved
			last = key;
			root.style.setProperty( '--pt-ic-clear', clear + 'px' );
			if ( typeof window.Intercom === 'function' ) {
				window.Intercom( 'update', {
					hide_default_launcher: true,
					alignment: 'left',
					horizontal_padding: left,
					vertical_padding: Math.max( 0, clear - BOX - 8 )
				} );
			}
		}

		// Re-measure if the button itself ever changes size.
		if ( window.ResizeObserver && ! btn.__ptRO ) {
			btn.__ptRO = new ResizeObserver( queue );
			btn.__ptRO.observe( btn );
		}
	}

	function queue() {
		cancelAnimationFrame( raf );
		raf = requestAnimationFrame( placeIntercom );
	}

	// Bind Intercom('onHide') once, lazily — both this script and the plugin's boot
	// snippet print in the footer, so Intercom may not exist at initial run and an
	// eager bind would silently no-op, leaving the launcher stuck on the X after
	// the Messenger is closed from its own control. Binding on first open (Intercom
	// guaranteed present) makes the reset reliable.
	var hideBound = false;
	function bindHideSync() {
		if ( hideBound || typeof window.Intercom !== 'function' ) { return; }
		hideBound = true;
		window.Intercom( 'onHide', function () {
			if ( support ) { support.classList.remove( 'chat-open' ); }
		} );
	}

	function openChat( e ) {
		// Always stop the link's default #-anchor jump (which scrolls the page up).
		if ( e ) { e.preventDefault(); }
		// The boot snippet defines window.Intercom as a queueing stub immediately,
		// so 'show' is safe even before the widget finishes loading. If Intercom
		// isn't present at all (not configured / blocked) we simply do nothing.
		if ( typeof window.Intercom !== 'function' ) { return; }
		bindHideSync();
		// Collapse the support panel but keep the launcher as an X (chat-open) so
		// the user keeps a clear control to close the chat again.
		if ( support ) {
			support.classList.remove( 'open' );
			support.classList.add( 'chat-open' );
		}
		placeIntercom();
		window.Intercom( 'show' );
	}

	function closeChat() {
		if ( support ) { support.classList.remove( 'chat-open' ); }
		if ( typeof window.Intercom === 'function' ) { window.Intercom( 'hide' ); }
	}

	// Triggers: support widget "Chat to Us", plus any other [data-intercom] and the
	// legacy #chat-us id (kept for parity with the old theme markup).
	document.querySelectorAll( '[data-intercom], #chat-us' ).forEach( function ( t ) {
		if ( t.getAttribute( 'data-pt-intercom-bound' ) ) { return; }
		t.setAttribute( 'data-pt-intercom-bound', '1' );
		t.addEventListener( 'click', openChat );
	} );

	// While the chat is open the launcher shows an X — clicking it closes the chat
	// instead of re-opening our support panel. Capture phase + stopImmediatePropagation
	// pre-empts the panel-toggle handler bound elsewhere (header.js / per-page JS).
	if ( launch ) {
		launch.addEventListener( 'click', function ( e ) {
			if ( support && support.classList.contains( 'chat-open' ) ) {
				e.preventDefault();
				e.stopImmediatePropagation();
				closeChat();
			}
		}, true );
	}

	// Position on ready, again after Intercom boots (load), and on any reflow.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', placeIntercom );
	} else {
		placeIntercom();
	}
	window.addEventListener( 'load', function () { bindHideSync(); placeIntercom(); } );
	window.addEventListener( 'resize', queue );
	window.addEventListener( 'orientationchange', queue );
})();
