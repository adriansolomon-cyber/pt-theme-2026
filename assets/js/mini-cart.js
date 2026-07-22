/* ============================================================
   Project Timber — mini-cart drawer (shared across pages).
   Ported from scripts-to-migrate/cart-modal-pt-v3.html: drives the .ptc-* drawer
   live from the WooCommerce Store API and handles open/close.

   The cart is per-visitor SESSION state, so it uses the cookie + Nonce Store API
   (credentials:'include'), NOT the consumer-key proxy. That only works when the
   page is served same-origin on the WordPress site; anywhere else (loose file /
   different origin) the fetch fails and the drawer shows the empty state.

   Runs after include.js has injected partials/cart-drawer.html.
   ============================================================ */
(function () {
  var root = document.getElementById('ptcRoot');
  if (!root) return;
  var filled = document.getElementById('ptcFilled'), empty = document.getElementById('ptcEmpty');
  var $ = function (s) { return document.querySelector(s); };

  // ---------- open / close ----------
  var loadedOnce = false;
  function open() {
    root.classList.add('open'); root.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    loadCart(); // always refresh on open
  }
  function close() {
    root.classList.remove('open'); root.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
  document.querySelectorAll('.cartopen').forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); open(); }); });
  root.querySelectorAll('[data-cart-close]').forEach(function (b) { b.addEventListener('click', close); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('open')) close(); });

  function show(state) {
    var isF = state === 'filled';
    filled.classList.toggle('ptc-hidden', !isF); filled.style.display = isF ? 'flex' : 'none';
    empty.classList.toggle('ptc-hidden', isF); empty.style.display = isF ? 'none' : 'flex';
  }

  // ---------- Store API ----------
  var STORE = location.origin + '/wp-json/wc/store/v1';
  var nonce = window.wcStoreApiNonce || '';
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function stripTags(s) { return String(s == null ? '' : s).replace(/<[^>]*>/g, '').trim(); }
  function setText(sel, t) { var e = $(sel); if (e) e.textContent = t; }
  var _dec = document.createElement('textarea');
  function decode(s) { _dec.innerHTML = String(s == null ? '' : s); return _dec.value; }
  function disp(s) { return esc(stripTags(decode(s))); }
  function cfgValue(v) { return decode(v).replace(/\s*×\s*\d.*$/, '').replace(/(\d)\s*x\s*(\d)/g, '$1 × $2').trim(); }

  function money(minor, cur) {
    cur = cur || {};
    var u = (cur.currency_minor_unit == null) ? 2 : cur.currency_minor_unit;
    var p = ((parseInt(minor, 10) || 0) / Math.pow(10, u)).toFixed(u).split('.');
    p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, cur.currency_thousand_separator || ',');
    return (cur.currency_prefix || cur.currency_symbol || '£') + p.join(cur.currency_decimal_separator || '.') + (cur.currency_suffix || '');
  }

  // The Store API returns line/subtotal/discount amounts EXCLUDING tax, plus a
  // separate *_tax field; the grand total is incl. tax. When the store displays
  // cart prices incl. tax (PT_CART_INCL_TAX, matching checkout), fold the tax
  // back in so every line reads consistently. `base`/`tax` are minor-unit ints.
  var INCL_TAX = (window.PT_CART_INCL_TAX !== false);
  function withTax(base, tax) {
    var b = parseInt(base, 10) || 0;
    return INCL_TAX ? b + (parseInt(tax, 10) || 0) : b;
  }

  function cartFetch(path, opts) {
    opts = opts || {}; opts.credentials = 'include';
    opts.headers = Object.assign({ Accept: 'application/json' }, opts.headers || {});
    if (opts.json != null) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(opts.json); delete opts.json; }
    if (nonce) opts.headers['Nonce'] = nonce;
    return fetch(STORE + path, opts).then(function (r) {
      var n = r.headers.get('Nonce'); if (n) nonce = n;
      return r.json().then(function (j) { if (!r.ok) throw new Error(j && j.message ? j.message : ('HTTP ' + r.status)); return j; });
    });
  }

  var CHEV = '<svg class="ptc-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>';
  var TRASH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>';

  function itemHTML(it) {
    var im = (it.images && it.images[0] && (it.images[0].thumbnail || it.images[0].src)) || '';
    var data = it.item_data || [];
    var key = function (d) { return d.key || d.name || ''; };
    var rng = ''; data.forEach(function (d) { if (String(key(d)).toLowerCase() === 'range') rng = d.value; });
    var rows = data.filter(function (d) { return !d.hidden && String(key(d)).toLowerCase() !== 'range'; }).map(function (d) {
      return '<div class="ptc-crow"><span class="ptc-k">' + disp(key(d)) + '</span><span class="ptc-v">' + esc(cfgValue(d.value)) + '</span></div>';
    }).join('');
    var cfg = rows ? ('<details class="ptc-cfg" open><summary>Your configuration ' + CHEV + '</summary><div class="ptc-rows">' + rows + '</div></details>') : '';
    return '<div class="ptc-line"><div class="ptc-item" data-key="' + esc(it.key) + '">' +
      '<div class="ptc-thumb">' + (im ? '<img src="' + esc(im) + '" alt="' + disp(it.name) + '">' : '') + '</div>' +
      '<div class="ptc-it">' + (rng ? '<div class="ptc-rng">' + disp(rng) + '</div>' : '') +
      '<h3>' + disp(it.name) + '</h3>' +
      // line_subtotal = per-item price BEFORE coupon discount (the discount is a
      // separate summary line), matching the checkout order-summary item cards.
      '<div class="ptc-pr">' + money(withTax(it.totals && it.totals.line_subtotal, it.totals && it.totals.line_subtotal_tax), it.totals) + '</div>' +
      '<button class="ptc-rm" data-key="' + esc(it.key) + '">' + TRASH + ' Remove</button>' +
      '</div></div>' + cfg + '</div>';
  }

  // Composite / bundle children are separate line items linked to a parent container;
  // show only the parent (whose item_data summarises the whole configuration).
  function parentKeyOf(it) {
    var ex = it.extensions || {};
    return it.composite_parent || it.bundled_by || it.parent
      || (ex.composite_products && ex.composite_products.composite_parent)
      || (ex.composites && ex.composites.composite_parent)
      || (ex.bundles && ex.bundles.bundled_by) || null;
  }

  function setBadges(n) { document.querySelectorAll('.cartbadge').forEach(function (b) { b.textContent = n; }); }

  function renderCart(cart) {
    var all = (cart && cart.items) || [];
    var items = all.filter(function (it) { return !parentKeyOf(it); });
    var n = cart.items_count || items.reduce(function (a, b) { return a + (b.quantity || 0); }, 0);
    setBadges(n);
    if (!items.length) { show('empty'); return; }
    show('filled');
    var box = document.getElementById('ptcItems'); if (box) box.innerHTML = items.map(itemHTML).join('');
    setText('#ptcCount', n + ' item' + (n === 1 ? '' : 's'));
    var t = cart.totals || {};
    setText('#ptcSubtotal', money(withTax(t.total_items, t.total_items_tax), t));
    setText('#ptcTotal', money(t.total_price, t)); // already incl. tax
    setText('#ptcVat', 'Includes ' + money(t.total_tax, t) + ' VAT');
    var disc = parseInt(t.total_discount, 10) || 0, dln = $('#ptcDiscountLn');
    if (dln) dln.hidden = !(disc > 0);
    if (disc > 0) setText('#ptcDiscount', '−' + money(withTax(t.total_discount, t.total_discount_tax), t));
    renderCoupons(cart.coupons || []);
    couponMsg('');
  }

  function renderCoupons(list) {
    var cl = $('#ptcCouponList'); if (!cl) return;
    cl.innerHTML = (list || []).map(function (c) {
      var d = (c.totals && parseInt(c.totals.total_discount, 10)) ? ('−' + money(withTax(c.totals.total_discount, c.totals.total_discount_tax), c.totals)) : '';
      return '<div class="ptc-coupon"><span class="code">' + disp(c.code) + '</span>' + (d ? '<span class="cd">' + d + '</span>' : '') +
        '<button class="cx" type="button" data-coupon="' + esc(c.code) + '" aria-label="Remove coupon">✕</button></div>';
    }).join('');
  }
  function couponMsg(msg, isErr) { var e = $('#ptcCouponMsg'); if (!e) return; e.textContent = decode(msg || ''); e.hidden = !msg; e.classList.toggle('err', !!isErr); }

  var _toastT;
  function toast(msg) {
    var t = $('#ptcToast'); if (!t) return;
    t.querySelector('.msg').textContent = msg;
    t.classList.remove('show'); void t.offsetWidth; t.classList.add('show');
    clearTimeout(_toastT); _toastT = setTimeout(function () { t.classList.remove('show'); }, 2600);
  }

  function applyCoupon(code) {
    code = String(code || '').trim(); if (!code) return;
    var btn = $('#ptcCouponApply'); if (btn) btn.disabled = true; couponMsg('');
    cartFetch('/cart/apply-coupon', { method: 'POST', json: { code: code } })
      .then(function (cart) { var i = $('#ptcCouponInput'); if (i) i.value = ''; renderCart(cart); toast('Promo code ' + code.toUpperCase() + ' applied'); })
      .catch(function (err) { couponMsg((err && err.message) || 'Could not apply that code.', true); })
      .then(function () { if (btn) btn.disabled = false; });
  }
  function removeCoupon(code) {
    cartFetch('/cart/remove-coupon', { method: 'POST', json: { code: code } })
      .then(function (cart) { renderCart(cart); toast('Promo code ' + String(code).toUpperCase() + ' removed'); })
      .catch(function (err) { couponMsg((err && err.message) || 'Could not remove coupon.', true); });
  }

  // delegated remove (live items carry data-key → DELETE)
  var itemsBox = document.getElementById('ptcItems');
  if (itemsBox) itemsBox.addEventListener('click', function (e) {
    var rm = e.target.closest && e.target.closest('.ptc-rm'); if (!rm) return;
    var key = rm.getAttribute('data-key'); if (!key) return;
    var line = rm.closest && rm.closest('.ptc-line'), nameEl = line && line.querySelector('h3');
    var name = (nameEl && nameEl.textContent.trim()) || 'Item';
    rm.disabled = true;
    cartFetch('/cart/items/' + encodeURIComponent(key), { method: 'DELETE' })
      .then(function (cart) { renderCart(cart); toast(name + ' removed'); })
      .catch(function (err) { console.error(err); rm.disabled = false; couponMsg((err && err.message) || 'Could not remove that item.', true); });
  });

  var capply = document.getElementById('ptcCouponApply'), cinput = document.getElementById('ptcCouponInput');
  if (capply) capply.addEventListener('click', function () { applyCoupon(cinput && cinput.value); });
  if (cinput) cinput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); applyCoupon(cinput.value); } });
  var clist = document.getElementById('ptcCouponList');
  if (clist) clist.addEventListener('click', function (e) { var b = e.target.closest && e.target.closest('.cx'); if (b) removeCoupon(b.getAttribute('data-coupon')); });

  function loadCart() {
    cartFetch('/cart').then(renderCart).catch(function (err) {
      if (!loadedOnce) console.warn('[mini-cart] live cart unavailable —', err && err.message);
      loadedOnce = true; show('empty'); setBadges(0);
    });
  }

  loadCart(); // prime on page load so the badge count is correct before opening
})();
