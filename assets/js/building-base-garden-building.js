/* Project Timber — Building a Base: method tabs (Concrete / Paving / Bearers).
   Keyboard accessible (arrow keys + Home/End). The mobile menu + support +
   search are wired site-wide by header.js, so only the tabs live here. */
(function () {
	'use strict';

	var tabs = Array.prototype.slice.call(document.querySelectorAll('.mtab'));
	var panels = Array.prototype.slice.call(document.querySelectorAll('.mpanel'));
	if (!tabs.length || tabs.length !== panels.length) {
		return;
	}

	function select(i) {
		tabs.forEach(function (t, j) {
			var on = (j === i);
			t.setAttribute('aria-selected', on ? 'true' : 'false');
			t.tabIndex = on ? 0 : -1;
			panels[j].classList.toggle('show', on);
			if (on) {
				panels[j].removeAttribute('hidden');
			} else {
				panels[j].setAttribute('hidden', '');
			}
		});
	}

	tabs.forEach(function (t, i) {
		t.addEventListener('click', function () { select(i); });
		t.addEventListener('keydown', function (e) {
			var n = null;
			if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { n = (i + 1) % tabs.length; }
			else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { n = (i - 1 + tabs.length) % tabs.length; }
			else if (e.key === 'Home') { n = 0; }
			else if (e.key === 'End') { n = tabs.length - 1; }
			if (n !== null) { e.preventDefault(); select(n); tabs[n].focus(); }
		});
	});
})();
