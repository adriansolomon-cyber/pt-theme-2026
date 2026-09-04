<?php
/**
 * Site footer — output by get_footer().
 *
 * Converted from partials/footer.html. The global support widget and cart
 * drawer (previously data-include partials, present on every page) are pulled
 * in here as template parts so every template gets them via get_footer().
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="site-foot"><div class="wrap">
  <div class="top">
    <div>
      <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="https://www.projecttimber.com/wp-content/uploads/2026/02/ProjectTimber-Logo-2-1.svg" alt="Project Timber"></a>
      <p class="reg">Project Timber Limited is a company registered in England and Wales with registration number 05126131. Our registered office is at Parry Works, Grassthorpe Road, Sutton-on-Trent, Newark NG23 6QX.</p>
      <div class="social">
        <a href="https://www.facebook.com/projecttimber" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M14 9h3V6h-3c-1.66 0-3 1.34-3 3v2H8v3h3v6h3v-6h2.5l.5-3H14V9z"/></svg></a>
        <a href="https://www.instagram.com/projecttimber/" aria-label="Instagram"><svg viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.07 1.2.06 1.8.25 2.2.42.6.22 1 .48 1.4.9.42.4.68.8.9 1.4.17.4.36 1 .42 2.2.07 1.3.07 1.7.07 4.9s0 3.6-.07 4.9c-.06 1.2-.25 1.8-.42 2.2-.22.6-.48 1-.9 1.4-.4.42-.8.68-1.4.9-.4.17-1 .36-2.2.42-1.3.07-1.7.07-4.9.07s-3.6 0-4.9-.07c-1.2-.06-1.8-.25-2.2-.42-.6-.22-1-.48-1.4-.9-.42-.4-.68-.8-.9-1.4-.17-.4-.36-1-.42-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.07-4.9c.06-1.2.25-1.8.42-2.2.22-.6.48-1 .9-1.4.4-.42.8-.68 1.4-.9.4-.17 1-.36 2.2-.42C8.4 2.2 8.8 2.2 12 2.2zm0 1.8c-3.1 0-3.5 0-4.7.07-1.1.05-1.7.24-2.1.4-.5.2-.9.44-1.3.84-.4.4-.64.8-.84 1.3-.16.4-.35 1-.4 2.1-.06 1.2-.06 1.6-.06 4.7s0 3.5.06 4.7c.05 1.1.24 1.7.4 2.1.2.5.44.9.84 1.3.4.4.8.64 1.3.84.4.16 1 .35 2.1.4 1.2.07 1.6.07 4.7.07s3.5 0 4.7-.07c1.1-.05 1.7-.24 2.1-.4.5-.2.9-.44 1.3-.84.4-.4.64-.8.84-1.3.16-.4.35-1 .4-2.1.07-1.2.07-1.6.07-4.7s0-3.5-.07-4.7c-.05-1.1-.24-1.7-.4-2.1-.2-.5-.44-.9-.84-1.3-.4-.4-.8-.64-1.3-.84-.4-.16-1-.35-2.1-.4C15.5 4 15.1 4 12 4z"/><path d="M12 7.1a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8zm0 8.1a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4z"/><circle cx="17.1" cy="6.9" r="1.15"/></svg></a>
        <a href="https://twitter.com/project_timber" aria-label="X"><svg viewBox="0 0 24 24"><path d="M17.3 3H20l-6.1 7L21 21h-5.6l-4.4-5.3L5.8 21H3l6.5-7.5L3 3h5.7l4 4.9L17.3 3zm-1 16h1.5L8 4.7H6.4L16.3 19z"/></svg></a>
        <a href="https://www.pinterest.co.uk/projecttimberltd/" aria-label="Pinterest"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-3.6 19.3c-.1-.8-.2-2 0-2.9l1.1-4.8s-.3-.6-.3-1.4c0-1.3.8-2.3 1.7-2.3.8 0 1.2.6 1.2 1.3 0 .8-.5 2-.8 3.1-.2.9.5 1.6 1.4 1.6 1.6 0 2.8-1.7 2.8-4.1 0-2.2-1.5-3.7-3.8-3.7a4 4 0 0 0-4.1 4c0 .8.3 1.6.7 2.1l-.3 1.1c0 .2-.2.2-.3.1-1-.5-1.7-2-1.7-3.3 0-2.7 2-5.1 5.6-5.1 3 0 5.2 2.1 5.2 4.9 0 3-1.8 5.3-4.4 5.3-.9 0-1.7-.5-2-1l-.5 2c-.2.8-.7 1.7-1 2.3A10 10 0 1 0 12 2z"/></svg></a>
        <a href="https://www.youtube.com/@projecttimber" aria-label="YouTube"><svg viewBox="0 0 24 24"><path d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1c.3-1.2.4-2.5.4-3.8s-.1-2.6-.4-3.8zM10 15V9l5.2 3L10 15z"/></svg></a>
      </div>
    </div>
    <div><h4>Products</h4><ul><li><a href="<?php echo esc_url( home_url( '/garden-sheds/' ) ); ?>">Garden Sheds</a></li><li><a href="<?php echo esc_url( home_url( '/summerhouses/' ) ); ?>">Summerhouses</a></li><li><a href="<?php echo esc_url( home_url( '/garden-offices/' ) ); ?>">Garden Offices</a></li><li><a href="<?php echo esc_url( home_url( '/garden-workshops/' ) ); ?>">Garden Workshops</a></li><li><a href="<?php echo esc_url( home_url( '/insulated-garden-buildings/' ) ); ?>">Insulated Garden Buildings</a></li><li><a href="<?php echo esc_url( home_url( '/log-cabins/' ) ); ?>">Log Cabins</a></li><li><a href="<?php echo esc_url( home_url( '/greenhouses/' ) ); ?>">Greenhouses</a></li></ul></div>
    <div><h4>Help</h4><ul><li><a href="<?php echo esc_url( home_url( '/faq' ) ); ?>">FAQ</a></li><li><a href="<?php echo esc_url( home_url( '/delivery' ) ); ?>">Delivery</a></li><li><a href="<?php echo esc_url( home_url( '/returns' ) ); ?>">Returns</a></li><li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact</a></li><li><a href="<?php echo esc_url( home_url( '/building-base-garden-building/' ) ); ?>">Building a Base</a></li><li><a href="<?php echo esc_url( home_url( '/testimonials/' ) ); ?>">Testimonials</a></li></ul></div>
    <div><h4>Links</h4><ul><li><a href="<?php echo esc_url( home_url( '/terms' ) ); ?>">Terms</a></li><li><a href="<?php echo esc_url( home_url( '/terms/#promotions' ) ); ?>">Promotion and Offers</a></li><li><a href="<?php echo esc_url( home_url( '/privacy' ) ); ?>">Privacy</a></li><li><a href="<?php echo esc_url( home_url( '/resources' ) ); ?>">Blog</a></li><li><a href="<?php echo esc_url( pt_checkout_url() ); ?>">Checkout</a></li></ul></div>
  </div>
  <div class="pay">
    <p>100% secure checkout. Pay your way with Revolut Pay, PayPal, or using any major credit card, Apple Pay, or Google Pay.</p>
    <div class="chips"><span class="chip">Revolut Pay</span><span class="chip">Visa</span><span class="chip">Mastercard</span><span class="chip">Amex</span><span class="chip">Apple Pay</span><span class="chip">Google Pay</span><span class="chip">PayPal</span></div>
  </div>
  <p style="font-size:.74rem;font-weight:300;color:var(--on-dark-soft);margin:0 0 14px;padding-top:24px">* Terms and conditions apply.</p>
  <div class="copy"><span>© 2026 All Rights Reserved, Project Timber</span></div>
</div></footer>

<?php
// Global chrome present on every page (were data-include partials).
get_template_part( 'template-parts/support' );
get_template_part( 'template-parts/cart-drawer' );
get_template_part( 'template-parts/callback-modal' );

wp_footer();
?>
</body>
</html>
