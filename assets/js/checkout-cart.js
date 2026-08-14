/* Project Timber — checkout order-summary in-place item removal.
 *
 * The order summary's "Remove" (woocommerce/checkout/review-order.php) is a link to
 * wc_get_cart_remove_url(), which navigates to /cart/ to remove the item — bouncing
 * the customer off checkout to the cart page. Intercept it and remove via the
 * WooCommerce Store API instead (same as the basket drawer), then refresh the order
 * review in place so they stay on checkout:
 *   - other items left  → trigger WooCommerce's update_checkout (recalcs totals /
 *     shipping / order review, no navigation)
 *   - last item removed → show an empty state, never redirect to /cart/
 *
 * If anything fails, we fall back to the native remove link so removal still works.
 */
(function () {
  var summary = document.querySelector('.co-summary') || document.getElementById('order_review');
  if (!summary) return;

  var STORE = location.origin + '/wp-json/wc/store/v1';
  var nonce = window.wcStoreApiNonce || '';

  document.addEventListener('click', function (e) {
    var rm = e.target.closest && e.target.closest('.sum-item .rm');
    if (!rm) return;
    e.preventDefault();
    var key = rm.getAttribute('data-key');
    var href = rm.getAttribute('href');
    if (!key) { if (href) window.location.href = href; return; }   // no key → native remove
    if (rm.getAttribute('data-busy')) return;
    rm.setAttribute('data-busy', '1');

    var item = rm.closest('.sum-item');
    if (item) item.classList.add('removing');

    fetch(STORE + '/cart/items/' + encodeURIComponent(key), {
      method: 'DELETE',
      credentials: 'include',
      headers: nonce ? { 'Nonce': nonce, 'Accept': 'application/json' } : { 'Accept': 'application/json' }
    }).then(function (r) {
      var nn = r.headers.get('Nonce'); if (nn) { nonce = nn; window.wcStoreApiNonce = nn; }
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.text();   // Store API DELETE returns 204 No Content
    }).then(function () {
      if (item && item.parentNode) item.parentNode.removeChild(item);
      var remaining = summary.querySelectorAll('.sum-item').length;
      if (remaining > 0) {
        // Recalculate the order review + totals + shipping in place; stays on checkout.
        if (window.jQuery) window.jQuery(document.body).trigger('update_checkout');
      } else {
        showEmpty();   // last item — don't let WooCommerce bounce to /cart/
      }
    }).catch(function () {
      // Any failure → fall back to WooCommerce's native remove link.
      if (item) item.classList.remove('removing');
      rm.removeAttribute('data-busy');
      if (href) window.location.href = href;
    });
  });

  // Replace the summary with an empty state and stop the (empty) order being placed.
  function showEmpty() {
    var card = summary.querySelector('.summary-card') || summary;
    if (card) {
      card.innerHTML =
        '<div class="co-empty">' +
          '<h2>Your basket is empty</h2>' +
          '<p>Add a garden building to continue to checkout.</p>' +
          '<a class="co-empty-cta" href="' + (window.PT_SHOP_URL || '/') + '">Browse buildings</a>' +
        '</div>';
    }
    var place = document.getElementById('place_order');
    if (place) { place.setAttribute('disabled', 'disabled'); place.classList.add('is-disabled'); }
  }
})();
