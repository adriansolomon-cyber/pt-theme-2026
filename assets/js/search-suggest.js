/* Project Timber — header live-search typeahead.
   Enhances the header search box (#hsearch input) with a dropdown of matching
   products from /wp-json/pt/v1/search. Debounced, abortable, cached per term,
   keyboard-navigable. Progressive enhancement: the form still submits to
   search.php normally if JS is off or the user hits enter without a selection. */
(function () {
  var ENDPOINT = window.PT_SEARCH_ENDPOINT;
  if (!ENDPOINT) return;
  var hs = document.getElementById('hsearch'); if (!hs) return;
  var input = hs.querySelector('input[type="search"]'); if (!input) return;
  var wrap = hs.querySelector('form') || hs;

  var panel = document.createElement('div');
  panel.className = 'hsuggest';
  panel.setAttribute('role', 'listbox');
  panel.hidden = true;
  wrap.appendChild(panel);

  var cache = {}, ctrl = null, items = [], active = -1, lastQ = '', timer = null;

  function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : s); return d.innerHTML; }
  function hide() { panel.hidden = true; panel.innerHTML = ''; items = []; active = -1; }

  function render(data) {
    var html = '';
    if (data.items && data.items.length) {
      data.items.forEach(function (it, i) {
        html += '<a class="hs-item" role="option" href="' + esc(it.url) + '" data-i="' + i + '">' +
          '<span class="hs-thumb">' + (it.img ? '<img src="' + esc(it.img) + '" alt="" loading="lazy">' : '') + '</span>' +
          '<span class="hs-meta"><span class="hs-name">' + esc(it.name) + '</span>' +
          '<span class="hs-price">' + (it.price_html || '') + '</span></span>' +
        '</a>';
      });
      html += '<a class="hs-all" href="' + esc(data.url) + '">See all results for &ldquo;' + esc(data.q) + '&rdquo; &rarr;</a>';
    } else {
      html = '<div class="hs-empty">No matches for &ldquo;' + esc(data.q) + '&rdquo;</div>';
    }
    panel.innerHTML = html;
    items = [].slice.call(panel.querySelectorAll('.hs-item, .hs-all'));
    active = -1;
    panel.hidden = false;
  }

  function fetchSuggest(q) {
    if (cache[q]) { render(cache[q]); return; }
    if (ctrl) ctrl.abort();
    ctrl = ('AbortController' in window) ? new AbortController() : null;
    panel.hidden = false;
    panel.innerHTML = '<div class="hs-loading">Searching…</div>';
    fetch(ENDPOINT + '?q=' + encodeURIComponent(q), ctrl ? { signal: ctrl.signal } : {})
      .then(function (r) { return r.json(); })
      .then(function (data) { cache[q] = data; if (input.value.trim() === q) render(data); })
      .catch(function (e) { if (e && e.name === 'AbortError') return; hide(); });
  }

  input.addEventListener('input', function () {
    var q = input.value.trim();
    if (q.length < 2) { hide(); lastQ = ''; return; }
    if (q === lastQ) return;
    lastQ = q;
    clearTimeout(timer);
    timer = setTimeout(function () { fetchSuggest(q); }, 250);
  });

  function mark() {
    items.forEach(function (el, i) { el.classList.toggle('hs-on', i === active); });
    if (active > -1) items[active].scrollIntoView({ block: 'nearest' });
  }

  input.addEventListener('keydown', function (e) {
    if (panel.hidden || !items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); mark(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, -1); mark(); }
    else if (e.key === 'Enter') { if (active > -1) { e.preventDefault(); window.location.href = items[active].getAttribute('href'); } }
    else if (e.key === 'Escape') { hide(); }
  });

  document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) hide(); });
})();
