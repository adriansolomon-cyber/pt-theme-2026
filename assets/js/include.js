/* ============================================================
   Project Timber — client-side partial include + page-script loader.

   HTML-only stand-in for WordPress template partials. Each page marks where a
   shared chunk goes with:  <div data-include="partials/header.html"></div>
   This fetches those partials, injects them in place, and ONLY THEN loads the
   page's own JS — so scripts that touch the header / cart drawer run after
   those elements exist in the DOM.

   Forward-compatible: when this becomes a real WP theme, a
   <div data-include="partials/header.html"> maps to <?php get_header(); ?> and
   the data-page script to a wp_enqueue_script() call.

   Preview over HTTP (e.g. `npx serve .` or `python3 -m http.server`) for the
   freshest partials. When opened over file:// (where fetch() is blocked) it
   falls back to the embedded copies in assets/js/partials-data.js — so the
   header/footer still appear. Regenerate that file after editing a partial.
   ============================================================ */
(function () {
  var here = document.currentScript;
  var pageJs = (here && here.getAttribute('data-page')) || '';

  // Resolve one partial's HTML. PREFER the embedded copy (shipped in partials-data.js):
  // it needs no network round-trip, so the header/footer are injected before first paint —
  // no post-load layout shift. Only fetch when a partial isn't embedded (and it also makes
  // file:// work, where fetch is blocked). Regenerate partials-data.js after editing a partial.
  function getPartial(url) {
    var embedded = (window.PT_PARTIALS && window.PT_PARTIALS[url]);
    if (embedded != null) return Promise.resolve(embedded);
    return fetch(url)
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); });
  }

  function injectAll() {
    var nodes = [].slice.call(document.querySelectorAll('[data-include]'));
    return Promise.all(nodes.map(function (el) {
      var url = el.getAttribute('data-include');
      return getPartial(url)
        .then(function (html) { el.insertAdjacentHTML('afterend', html); el.parentNode.removeChild(el); })
        .catch(function (e) { console.warn('[include] failed:', url, e && e.message); });
    }));
  }

  function loadScript(src) {
    return new Promise(function (resolve) {
      var s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = function () { console.warn('[include] script failed:', src); resolve(); };
      document.body.appendChild(s);
    });
  }

  function loadScripts(list) {
    return list.reduce(function (pr, src) { return pr.then(function () { return loadScript(src); }); }, Promise.resolve());
  }
  function boot() {
    injectAll().then(function () {
      var list = pageJs.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
      return loadScripts(list);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
