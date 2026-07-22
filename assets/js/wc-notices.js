/* ============================================================
   Project Timber — WooCommerce notice behaviour (checkout / cart).

   WooCommerce shows a blue "info" notice we don't want lingering: the admin-only
   "Customer matched zone …" shipping-debug line. This:
     • hides info notices present ON LOAD immediately, and
     • auto-dismisses any info notices that appear afterwards a few seconds later,
   while ALWAYS keeping SUCCESS (green) and ERROR notices — so "coupon applied"
   confirmations and validation problems both stay visible to the shopper.

   The PHP side (functions.php) already strips most load-time notices server-
   side; this is the client-side backstop for ones WooCommerce re-adds during
   shipping/total calculation.
   ============================================================ */
(function () {
  var INFO = '.woocommerce-info'; // info only — keep .woocommerce-message (green) + .woocommerce-error
  var DISMISS_AFTER = 5000;

  function isError(el) {
    return el.classList && el.classList.contains('woocommerce-error');
  }

  function fadeRemove(el, delay) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s ease, max-height .5s ease, margin .4s ease, padding .4s ease';
      el.style.overflow = 'hidden';
      el.style.maxHeight = el.scrollHeight + 'px';
      // next frame → collapse
      requestAnimationFrame(function () {
        el.style.opacity = '0';
        el.style.maxHeight = '0';
        el.style.marginTop = '0'; el.style.marginBottom = '0';
        el.style.paddingTop = '0'; el.style.paddingBottom = '0';
      });
      setTimeout(function () { if (el && el.parentNode) el.parentNode.removeChild(el); }, 550);
    }, delay);
  }

  function handleNode(el, onLoad) {
    if (!el || el.nodeType !== 1 || isError(el)) return;
    if (onLoad) {
      el.style.display = 'none';                 // instant, no flash-lingering
      if (el.parentNode) el.parentNode.removeChild(el);
    } else {
      fadeRemove(el, DISMISS_AFTER);             // let it show briefly, then dismiss
    }
  }

  function run() {
    // 1) notices already on the page at load → remove immediately
    [].forEach.call(document.querySelectorAll(INFO), function (el) { handleNode(el, true); });

    // 2) notices added later (AJAX cart/coupon updates) → auto-dismiss, keep errors
    if (!window.MutationObserver) return;
    new MutationObserver(function (muts) {
      muts.forEach(function (m) {
        [].forEach.call(m.addedNodes || [], function (n) {
          if (n.nodeType !== 1) return;
          if (n.matches && n.matches(INFO)) handleNode(n, false);
          if (n.querySelectorAll) [].forEach.call(n.querySelectorAll(INFO), function (el) { handleNode(el, false); });
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
