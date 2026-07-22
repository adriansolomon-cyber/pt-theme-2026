/* ============================================================
   Project Timber — WooCommerce notice behaviour (checkout / cart).

   Every notice (error, success, info) shows for 10 seconds with a filling
   countdown bar along its bottom edge, then fades/collapses away — EXCEPT the
   voucher/discount "…applied" message, which carries a hidden .pt-voucher-notice
   marker (added server-side in wc-custom-checkout-functions.php) and stays
   visible permanently.

   The bar itself is drawn purely in CSS (.pt-notice-autodismiss::after in
   assets/css/wc-notices.css); this script only tags each notice and schedules
   its removal, watching for notices WooCommerce injects later via AJAX
   (coupon apply, add-to-basket, shipping recalculation).
   ============================================================ */
(function () {
  var SEL = '.woocommerce-message, .woocommerce-error, .woocommerce-info';
  var TTL = 10000; // ms a notice stays before auto-dismiss

  // The voucher/discount message is tagged persistent with a hidden marker span.
  function isPersistent(el) {
    return !!(el.querySelector && el.querySelector('.pt-voucher-notice'));
  }

  function collapse(el) {
    el.classList.add('pt-notice-dismissing');
    el.style.overflow = 'hidden';
    el.style.maxHeight = el.scrollHeight + 'px';
    requestAnimationFrame(function () {
      el.style.opacity = '0';
      el.style.maxHeight = '0';
      el.style.marginTop = '0'; el.style.marginBottom = '0';
      el.style.paddingTop = '0'; el.style.paddingBottom = '0';
    });
    setTimeout(function () { if (el && el.parentNode) el.parentNode.removeChild(el); }, 550);
  }

  function process(el) {
    if (!el || el.nodeType !== 1 || el.getAttribute('data-pt-notice')) return;
    el.setAttribute('data-pt-notice', '1');

    if (isPersistent(el)) {           // voucher — keep on screen, no bar
      el.classList.add('pt-notice-persist');
      return;
    }

    el.classList.add('pt-notice-autodismiss'); // CSS ::after draws the 10s bar
    setTimeout(function () { collapse(el); }, TTL); // authoritative removal
  }

  function scan(root) {
    if (!root || root.nodeType !== 1 && root.nodeType !== 9) return;
    if (root.nodeType === 1 && root.matches && root.matches(SEL)) process(root);
    if (root.querySelectorAll) [].forEach.call(root.querySelectorAll(SEL), process);
  }

  function run() {
    scan(document);
    if (!window.MutationObserver) return;
    new MutationObserver(function (muts) {
      muts.forEach(function (m) { [].forEach.call(m.addedNodes || [], scan); });
    }).observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
