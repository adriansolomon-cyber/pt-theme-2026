<?php
/**
 * Request-a-callback modal — global chrome (output in the footer on every page).
 *
 * Opened from any [data-callback] trigger (the support widget's "Request a
 * Callback" option). The form posts to admin-post.php (handler:
 * includes/contact-form.php → pt_callback_handle) and is progressively enhanced
 * by assets/js/callback-modal.js (fetch submit + inline confirmation). Works
 * without JS too. Converted from the callback popup in
 * design-files/secondary-pages/projecttimber-contact.html.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pcb-ov" id="pcbOverlay" role="dialog" aria-modal="true" aria-labelledby="pcbTitle" aria-hidden="true">
	<div class="pcb">
		<button class="pcb-x" type="button" id="pcbClose" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
		<div class="pcb-eb">Request a callback</div>
		<h2 id="pcbTitle">We'll call you <span class="pcb-fade">back.</span></h2>
		<p class="pcb-intro">Our friendly sales team would be happy to talk through any questions about our products or services. Leave a few details and we'll call you back &mdash; the same day if you request before 6pm, otherwise the next working day or whenever suits you.</p>

		<form id="pcbForm" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
			<input type="hidden" name="action" value="pt_callback">
			<input type="hidden" name="pt_source" value="<?php echo esc_url( ( is_ssl() ? 'https://' : 'http://' ) . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ); ?>">
			<?php wp_nonce_field( 'pt_callback', 'pt_callback_nonce' ); ?>
			<div class="pt-hp" aria-hidden="true"><label>Website<input type="text" name="pt_website" tabindex="-1" autocomplete="off"></label></div>

			<div class="pcb-grid">
				<div>
					<div class="pcb-f"><label for="pcbName">Name <span class="rq">*</span></label><input id="pcbName" name="pcbName" type="text" autocomplete="name" required></div>
					<div class="pcb-f"><label for="pcbPhone">Contact number <span class="rq">*</span></label><input id="pcbPhone" name="pcbPhone" type="tel" autocomplete="tel" required></div>
					<div class="pcb-f"><label for="pcbProd">What product are you interested in?</label><input id="pcbProd" name="pcbProd" type="text" placeholder="e.g. My Den garden office, 10x8 shed"></div>
				</div>
				<div>
					<div class="pcb-f"><label>Best time to call you back</label>
						<div class="pcb-times">
							<label><input type="radio" name="pcbTime" value="asap">As soon as possible</label>
							<label><input type="radio" name="pcbTime" value="am">Any weekday, 8:30am &ndash; 1pm</label>
							<label><input type="radio" name="pcbTime" value="pm">Any weekday afternoon, 1pm &ndash; 5pm</label>
							<label><input type="radio" name="pcbTime" value="eve">Any weekday evening, 5pm &ndash; 7pm</label>
						</div>
					</div>
				</div>
			</div>
			<button class="pcb-btn" type="submit">Send request</button>
			<p class="pcb-note">We'll never sell your details to third parties. See our <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">privacy policy</a>.</p>
		</form>

		<div class="pcb-done" id="pcbDone" role="status" aria-live="polite"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><div><b>Thanks &mdash; we'll be in touch.</b><p>Our sales team will call you back at your chosen time. Prefer to talk now? Call 01777 553392.</p></div></div>
	</div>
</div>
