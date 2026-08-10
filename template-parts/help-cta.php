<?php
/**
 * Reusable "still need help?" CTA band.
 *
 * Sits at the foot of several secondary pages (FAQ, Returns, Building a Base,
 * Testimonials…). Static copy; the phone number matches the site default and
 * the Contact link resolves to the current domain. Place inside the page's
 * <main class="pt-secondary"> so it picks up the shared section rhythm.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="helpcta"><div class="wrap">
	<div class="eyebrow">Still need a hand?</div>
	<h2>Talk to a real person.</h2>
	<p>Our team is here Monday to Friday, 8:30am&ndash;7:00pm. Give us a call, drop us an email, or head over to our contact page.</p>
	<div class="btns">
		<a class="btn btn-y" href="tel:01777553392"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>01777 553392</a>
		<a class="btn btn-o" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact us</a>
	</div>
</div></section>
