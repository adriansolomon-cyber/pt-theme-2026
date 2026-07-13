<?php
/**
 * Mini-cart drawer — global chrome (from partials/cart-drawer.html).
 *
 * Items/totals are injected live by assets/js/mini-cart.js (WooCommerce Store
 * API). Markup is a skeleton only; the checkout CTA is wired to the Woo
 * checkout URL.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ptc-root" id="ptcRoot" aria-hidden="true">
  <div class="ptc-scrim" data-cart-close></div>
  <aside class="ptc-drawer" role="dialog" aria-label="Basket" aria-modal="true">
    <div class="ptc-state" id="ptcFilled" style="display:flex;flex-direction:column;min-height:0;flex:1">
      <div class="ptc-head">
        <h2>Your basket <span class="ptc-cnt" id="ptcCount"></span></h2>
        <button class="ptc-x" data-cart-close type="button" aria-label="Close basket"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
      </div>
      <div class="ptc-body">
        <!-- Live basket items are injected here by assets/js/mini-cart.js (WooCommerce Store API).
             The skeleton below is a placeholder only — NO hardcoded data — so nothing real-looking
             flashes before the live cart resolves. -->
        <div id="ptcItems">
          <div class="ptc-line ptc-skel" aria-hidden="true">
            <div class="ptc-item">
              <div class="ptc-thumb ptc-sk"></div>
              <div class="ptc-it">
                <span class="ptc-sk ptc-sk-rng"></span>
                <span class="ptc-sk ptc-sk-title"></span>
                <span class="ptc-sk ptc-sk-price"></span>
              </div>
            </div>
            <div class="ptc-sk ptc-sk-cfg"></div>
          </div>
        </div><!-- /#ptcItems -->
        <div class="ptc-promo">
          <input type="text" id="ptcCouponInput" placeholder="Promo code (e.g. GM10)" aria-label="Promo code">
          <button type="button" id="ptcCouponApply">Apply</button>
        </div>
        <div id="ptcCouponMsg" class="ptc-promo-msg" hidden></div>
        <div id="ptcCouponList" class="ptc-coupons"></div>
        <div class="ptc-assure">
          <div class="ptc-a"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg> Secure checkout · card, Apple Pay or PayPal</div>
          <div class="ptc-a"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v8H3z"/><path d="M14 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/></svg> Free delivery on selected postcodes</div>
        </div>
      </div>
      <div class="ptc-foot">
        <div class="ptc-ln"><span>Subtotal</span><span id="ptcSubtotal"><span class="ptc-sk ptc-sk-amt"></span></span></div>
        <div class="ptc-ln" id="ptcDiscountLn" hidden><span>Discount</span><span id="ptcDiscount"></span></div>
        <div class="ptc-ln"><span>Delivery</span><span>Calculated at checkout</span></div>
        <div class="ptc-tot"><span class="l">Total</span><span class="v" id="ptcTotal"><span class="ptc-sk ptc-sk-amt"></span></span></div>
        <div class="ptc-vat" id="ptcVat"></div>
        <a class="ptc-cta" href="<?php echo esc_url( pt_checkout_url() ); ?>">Proceed to checkout <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        <button class="ptc-cont" data-cart-close type="button">Continue shopping</button>
      </div>
    </div>
    <div class="ptc-state ptc-hidden" id="ptcEmpty" style="flex-direction:column;min-height:0;flex:1">
      <div class="ptc-head">
        <h2>Your basket</h2>
        <button class="ptc-x" data-cart-close type="button" aria-label="Close basket"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
      </div>
      <div class="ptc-empty">
        <div class="ptc-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h2l2.2 11.4a1.5 1.5 0 0 0 1.5 1.2h8.1a1.5 1.5 0 0 0 1.5-1.2L22 8H7"/><circle cx="10" cy="20.5" r="1.2"/><circle cx="18" cy="20.5" r="1.2"/></svg></div>
        <h3>Your basket is empty</h3>
        <p>Once you've configured a garden building, it'll appear here ready for checkout.</p>
        <div class="ptc-ranges">
          <span class="ptc-lab">Popular ranges</span>
          <a href="<?php echo esc_url( home_url( '/garden-offices/' ) ); ?>">Garden Offices <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
          <a href="<?php echo esc_url( home_url( '/summerhouses/' ) ); ?>">Summerhouses <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
          <a href="<?php echo esc_url( home_url( '/garden-sheds/' ) ); ?>">Garden Sheds <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
        </div>
      </div>
    </div>
    <!-- transient confirmation toast (removals / coupons) -->
    <div id="ptcToast" class="ptc-toast" role="status" aria-live="polite">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
      <span class="msg"></span>
    </div>
  </aside>
</div>
