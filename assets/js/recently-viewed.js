/**
 * Recently-viewed recommendations.
 *
 * On every product page: record the current (parent) product ID in localStorage,
 * then — if the visitor has viewed other products before — replace the
 * "You might also like" rail with their recently-viewed products (rendered by
 * the pt/v1/recommended REST endpoint, padded with the default up-sells/related).
 *
 * First-time visitors (no history) keep the server-rendered default rail.
 * If anything fails, the default rail is left untouched.
 */
(function () {
	'use strict';

	var KEY = 'pt_recently_viewed';
	var MAX = 12; // how much history to keep

	if (typeof PT_RV === 'undefined' || !PT_RV.current) {
		return;
	}
	var current = parseInt(PT_RV.current, 10);
	if (!current) {
		return;
	}

	// --- read existing history ------------------------------------------------
	var list = [];
	try {
		list = JSON.parse(localStorage.getItem(KEY)) || [];
	} catch (e) {
		list = [];
	}
	if (!Array.isArray(list)) {
		list = [];
	}
	list = list
		.map(function (id) { return parseInt(id, 10); })
		.filter(function (id) { return id > 0; });

	// History to render = everything except the product we're on.
	var viewed = list.filter(function (id) { return id !== current; });

	// --- record the current product (most-recent first, deduped, capped) ------
	var updated = [current].concat(viewed).slice(0, MAX);
	try {
		localStorage.setItem(KEY, JSON.stringify(updated));
	} catch (e) {}

	// No prior history → leave the server-rendered default rail as-is.
	if (!viewed.length) {
		return;
	}

	var rail = document.querySelector('.recommend .rec-rail');
	if (!rail) {
		return;
	}

	// We know synchronously (from localStorage) that the visitor has history, so
	// show a skeleton straight away instead of letting the default cards flash
	// and then get swapped. Preserve the default markup to restore if the
	// request fails or returns nothing.
	var fallbackHTML = rail.innerHTML;
	rail.innerHTML = ptRvSkeleton(4);

	var url = PT_RV.rest +
		(PT_RV.rest.indexOf('?') === -1 ? '?' : '&') +
		'current=' + encodeURIComponent(current) +
		'&viewed=' + encodeURIComponent(viewed.join(','));

	fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
		.then(function (r) { return r.ok ? r.json() : null; })
		.then(function (data) {
			rail.innerHTML = (data && typeof data.html === 'string' && data.html.trim() !== '')
				? data.html
				: fallbackHTML;
		})
		.catch(function () { rail.innerHTML = fallbackHTML; });

	function ptRvSkeleton(n) {
		var card = '<span class="rec-card is-skeleton" aria-hidden="true">' +
			'<span class="rec-img sk"></span>' +
			'<span class="rec-body">' +
			'<span class="sk sk-rng"></span>' +
			'<span class="sk sk-title"></span>' +
			'<span class="sk sk-price"></span>' +
			'</span></span>';
		var out = '';
		for (var i = 0; i < n; i++) { out += card; }
		return out;
	}
})();
