/* ============================================================
   Project Timber — admin enhancement for the product-category metabox
   (product edit screen). WordPress' native hierarchical category checklist
   has no search and keeps checked items wherever they sit in the (very long)
   tree. This adds:
     • a live search box that filters the list as you type (matches float up
       with their parent context), and
     • "selected first" ordering — checked categories (and branches containing
       them) are moved to the top of each level on load.
   Progressive enhancement only; the real checkboxes/inputs are untouched, so
   saving works exactly as before.
   ============================================================ */
(function () {
  function init() {
    var box = document.getElementById('product_catchecklist');
    if (!box || box.getAttribute('data-pt-enhanced')) return;
    box.setAttribute('data-pt-enhanced', '1');
    var panel = document.getElementById('product_cat-all') || box.parentNode;

    // ---- styles (injected once) ----
    if (!document.getElementById('pt-catsearch-css')) {
      var st = document.createElement('style');
      st.id = 'pt-catsearch-css';
      st.textContent =
        '.pt-catsearch{position:sticky;top:0;background:#fff;padding:6px 0 8px;z-index:2;margin:0}' +
        '.pt-catsearch input{width:100%;box-sizing:border-box;padding:5px 8px}' +
        '.pt-catsearch .pt-cs-none{display:none;color:#787c82;font-style:italic;padding:6px 2px}' +
        '#product_cat-all.tabs-panel,#product_cat-all{max-height:320px}';
      document.head.appendChild(st);
    }

    // ---- search box ----
    var wrap = document.createElement('div');
    wrap.className = 'pt-catsearch';
    var input = document.createElement('input');
    input.type = 'search';
    input.placeholder = 'Search categories…';
    input.setAttribute('aria-label', 'Search product categories');
    var none = document.createElement('div');
    none.className = 'pt-cs-none';
    none.textContent = 'No categories match.';
    wrap.appendChild(input);
    wrap.appendChild(none);
    panel.insertBefore(wrap, box);

    var lis = [].slice.call(box.querySelectorAll('li'));
    function ownLabel(li) { var l = li.querySelector('label'); return l ? l.textContent.toLowerCase() : ''; }

    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      if (!q) { lis.forEach(function (li) { li.style.display = ''; }); none.style.display = 'none'; return; }
      lis.forEach(function (li) { li.style.display = 'none'; });
      var hits = 0;
      lis.forEach(function (li) {
        if (ownLabel(li).indexOf(q) === -1) return;
        hits++;
        li.style.display = '';                      // the match…
        var p = li.parentNode;                       // …plus its ancestors for context
        while (p && p !== box) { if (p.tagName === 'LI') p.style.display = ''; p = p.parentNode; }
      });
      none.style.display = hits ? 'none' : 'block';
    });

    // ---- selected first (stable, preserves tree) ----
    function hasChecked(li) { return !!li.querySelector('input[type="checkbox"]:checked'); }
    function sortSelectedFirst(ul) {
      var items = [].slice.call(ul.children).filter(function (n) { return n.tagName === 'LI'; });
      items.forEach(function (li) { var c = li.querySelector(':scope > ul.children'); if (c) sortSelectedFirst(c); });
      var yes = [], no = [];
      items.forEach(function (li) { (hasChecked(li) ? yes : no).push(li); });
      yes.concat(no).forEach(function (li) { ul.appendChild(li); });
    }
    sortSelectedFirst(box);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
