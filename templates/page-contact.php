<?php
/**
 * Template Name: PT — Contact
 *
 * Contact page. Converted from
 * design-files/secondary-pages/projecttimber-contact.html. Shared chrome via
 * get_header/get_footer; hero via the shared part. The enquiry form posts to
 * admin-post.php (handler: includes/contact-form.php) and is progressively
 * enhanced by assets/js/contact.js. Assets (secondary.css + contact.css +
 * contact.js) are auto-enqueued for templates/page-*.php.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// No-JS submit result (the AJAX path never reloads).
$pt_sent = isset( $_GET['pt_contact'] ) && 'sent' === $_GET['pt_contact']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag
$pt_err  = isset( $_GET['pt_contact'] ) && 'err' === $_GET['pt_contact']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$pt_msg  = ( $pt_err && isset( $_GET['pt_msg'] ) ) ? sanitize_text_field( wp_unslash( $_GET['pt_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

get_header();
?>
<main class="pt-secondary" id="main" tabindex="-1">

	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'crumb'      => 'Contact',
			'eyebrow'    => 'Get in touch',
			'title_html' => "We're here to <span class=\"fade\">help.</span>",
			'lead_html'  => "Our friendly, knowledgeable team is on hand to answer any pre- or post-sales questions. We're available Monday to Friday, 8:30am–7:00pm. Outside those hours, we'll get back to you as quickly as we can — or browse our <a href=\"" . esc_url( home_url( '/faq/' ) ) . '">FAQs</a>.',
		)
	);
	?>

	<section style="padding-top:34px"><div class="wrap">
		<div class="cgrid">

			<!-- methods + hours -->
			<div>
				<div class="methods">
					<div class="method">
						<div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg></div>
						<div><h3>Sales &amp; enquiries</h3><div class="val"><a href="tel:01777801214">01777 801214</a></div><p class="mail"><a href="mailto:sales@projecttimber.co.uk">sales@projecttimber.co.uk</a></p><p class="sub">New orders, quotes and bespoke buildings · Mon–Fri, 8:30am–7:00pm</p></div>
					</div>
					<div class="method">
						<div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 0 1 16 0v5a2 2 0 0 1-2 2h-2v-6h4"/><path d="M4 13v4a2 2 0 0 0 2 2h2v-6H4"/></svg></div>
						<div><h3>Customer care &amp; support</h3><div class="val"><a href="tel:01777801215">01777 801215</a></div><p class="mail"><a href="mailto:care@projecttimber.co.uk">care@projecttimber.co.uk</a></p><p class="sub">After-sales help, returns and cancellations · Mon–Fri, 8:30am–7:00pm</p></div>
					</div>
					<div class="method">
						<div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.6"/></svg></div>
						<div><h3>Address</h3><div class="val" style="font-size:1rem">Parry Works, Grassthorpe Road</div><p class="sub">Sutton-on-Trent, Newark, Nottinghamshire, NG23 6QX</p></div>
					</div>
				</div>

				<div class="hours">
					<h3>Business hours</h3>
					<ul>
						<li><span class="d">Monday–Friday</span><span class="open">8:30am – 7:00pm</span></li>
						<li><span class="d">Saturday</span><span class="t">Closed</span></li>
						<li><span class="d">Sunday</span><span class="t">Closed</span></li>
					</ul>
				</div>
			</div>

			<!-- enquiry form -->
			<div class="fcard">
				<h2>Send us a message</h2>
				<p class="fsub">Leave a few details and our team will get back to you — usually the same working day if you're in touch before 6pm.</p>

				<form id="cForm" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate<?php echo $pt_sent ? ' style="display:none"' : ''; ?>>
					<input type="hidden" name="action" value="pt_contact">
					<?php wp_nonce_field( 'pt_contact', 'pt_contact_nonce' ); ?>
					<!-- honeypot: leave empty -->
					<div class="pt-hp" aria-hidden="true"><label>Website<input type="text" name="pt_website" tabindex="-1" autocomplete="off"></label></div>

					<?php if ( $pt_err ) : ?>
						<div class="fdone show" style="background:var(--no-bg);color:#8a1c12" role="alert">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
							<div><b>Your message wasn't sent.</b><p><?php echo esc_html( '' !== $pt_msg ? $pt_msg : 'Please check your details and try again.' ); ?></p></div>
						</div>
					<?php endif; ?>

					<div class="field"><label for="cName">Name <span class="req">*</span></label><input id="cName" name="cName" type="text" autocomplete="name" required></div>
					<div class="field"><label for="cEmail">Email <span class="req">*</span></label><input id="cEmail" name="cEmail" type="email" autocomplete="email" required></div>
					<div class="field"><label for="cPhone">Contact number</label><input id="cPhone" name="cPhone" type="tel" autocomplete="tel"></div>
					<div class="field"><label for="cProd">What product are you interested in?</label><input id="cProd" name="cProd" type="text" placeholder="e.g. My Den garden office, 10x8 shed"></div>
					<div class="field"><label for="cMsg">Message</label><textarea id="cMsg" name="cMsg" placeholder="How can we help?"></textarea></div>
					<div class="field" style="margin-bottom:18px">
						<label>Best time to call you back</label>
						<div class="times">
							<label><input type="radio" name="cTime" value="asap">As soon as possible</label>
							<label><input type="radio" name="cTime" value="am">Any weekday, 8:30am – 1pm</label>
							<label><input type="radio" name="cTime" value="pm">Any weekday afternoon, 1pm – 5pm</label>
							<label><input type="radio" name="cTime" value="eve">Any weekday evening, 5pm – 7pm</label>
						</div>
					</div>
					<button class="fbtn" type="submit">Send message</button>
					<p class="fnote">We'll never sell your details to third parties. See our <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">privacy policy</a>.</p>
				</form>

				<div class="fdone<?php echo $pt_sent ? ' show' : ''; ?>" id="cDone" role="status" aria-live="polite">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
					<div><b>Thanks — we've got your message.</b><p>Our team will be in touch soon. Prefer to talk now? Call our sales team on 01777 801214.</p></div>
				</div>
			</div>

		</div>
	</div></section>

	<!-- delivery strip -->
	<section class="dstrip"><div class="wrap">
		<div class="zgrid">
			<div class="zmap"><img loading="lazy" src="https://www.projecttimber.com/wp-content/uploads/2025/05/deliver_map_2025.webp" alt="Project Timber UK delivery zone map"></div>
			<div>
				<div class="eyebrow">Delivery time &amp; cost</div>
				<h2 style="margin-top:8px">Where we deliver</h2>
				<p class="lead">Free kerbside delivery to most UK mainland postcodes, with a surcharge for some areas and a few we're unable to reach. You'll choose your delivery date at checkout.</p>
				<div class="zrow">
					<div class="zr"><span class="dot a"></span><span class="zt">Zone A — Free delivery</span><span class="zc">FREE</span></div>
					<div class="zr"><span class="dot b"></span><span class="zt">Zone B — Surcharge</span><span class="zc">£99</span></div>
					<div class="zr"><span class="dot c"></span><span class="zt">Zone C — Surcharge</span><span class="zc">£199</span></div>
					<div class="zr"><span class="dot d"></span><span class="zt">Zone D — Not available</span><span class="zc">—</span></div>
				</div>
				<a class="btn-link" href="<?php echo esc_url( home_url( '/delivery/' ) ); ?>">Check your postcode <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
			</div>
		</div>
	</div></section>

</main>
<?php
get_footer();
