<?php
/**
 * Template Name: PT — Returns
 *
 * Returns & cancellations. Converted from
 * design-files/secondary-pages/projecttimber-returns.html. Shared chrome via
 * get_header/get_footer; hero + help-CTA via the shared parts; accordion is
 * native <details>/<summary> (no JS). Assets (secondary.css + returns.css) are
 * auto-enqueued for templates/page-*.php.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="pt-secondary" id="main" tabindex="-1">

	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'crumb'      => 'Returns',
			'eyebrow'    => 'Returns & cancellations',
			'title_html' => 'Changed your mind? <span class="fade">No problem.</span>',
			'lead'       => "Cancelling an order or arranging a return is straightforward — here's how it works, and what to keep in mind before you get in touch.",
		)
	);
	?>

	<!-- steps -->
	<section class="steps-sec"><div class="wrap">
		<div class="steps">
			<div class="step"><div class="n">1</div><h3>Get in touch</h3><p>To cancel, email <a href="mailto:care@projecttimber.co.uk">care@projecttimber.co.uk</a>. To return an item, call our customer service team on <a href="tel:01777553392">01777 553392</a> to make the arrangements.</p></div>
			<div class="step"><div class="n">2</div><h3>We arrange it</h3><p>Our team will confirm your cancellation or talk you through the return, including collection where needed, so you know exactly what happens next.</p></div>
			<div class="step"><div class="n">3</div><h3>Receive your refund</h3><p>Once processed, your refund is returned to your original payment method. Refunds usually take 3&ndash;5 working days to appear.</p></div>
		</div>
	</div></section>

	<!-- key points -->
	<section class="points"><div class="wrap">
		<div style="text-align:center">
			<div class="eyebrow">Good to know</div>
			<h2 style="font-size:clamp(1.8rem,4.4vw,2.4rem);margin-top:8px">A few things before you return</h2>
		</div>
		<div class="grid">
			<div class="pt ok"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div><div><h3>Change of mind welcome</h3><p>You can return your building if you change your mind. We may deduct reasonable compensation from the refund for any net costs incurred.</p></div></div>
			<div class="pt no"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg></div><div><h3>Not once assembly has started</h3><p>As we supply a flat-pack product, we're unfortunately unable to accept returns for items that have begun to be assembled.</p></div></div>
			<div class="pt ok"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 1 0 8-8"/><path d="M4 5v4h4"/></svg></div><div><h3>Cancel any time before assembly</h3><p>Confirm your cancellation by emailing <a href="mailto:care@projecttimber.co.uk" style="color:var(--charcoal);font-weight:700">care@projecttimber.co.uk</a> and we'll take care of the rest.</p></div></div>
			<div class="pt ok"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg></div><div><h3>Refunds within 3&ndash;5 working days</h3><p>If it's been longer than that, email <a href="mailto:care@projecttimber.co.uk" style="color:var(--charcoal);font-weight:700">care@projecttimber.co.uk</a> or call us and we'll look into it straight away.</p></div></div>
		</div>
	</div></section>

	<!-- faq -->
	<section class="faq"><div class="wrap">
		<h2>Returns questions, <span class="fade">answered.</span></h2>
		<details class="faq-item"><summary>Can I still cancel my order?</summary><div class="ans">Yes — you can confirm your order cancellation by sending an email to <a href="mailto:care@projecttimber.co.uk">care@projecttimber.co.uk</a>.</div></details>
		<details class="faq-item"><summary>How do I return items?</summary><div class="ans">Call our customer service team on <a href="tel:01777553392">01777 553392</a> to make the necessary arrangements.</div></details>
		<details class="faq-item"><summary>What if I cancelled my order but haven't received my refund?</summary><div class="ans">If it's been more than 3&ndash;5 working days, there may have been a delay in processing your refund. Please email <a href="mailto:care@projecttimber.co.uk">care@projecttimber.co.uk</a> or call our friendly customer service team on <a href="tel:01777553392">01777 553392</a> for more information.</div></details>
		<details class="faq-item"><summary>Can I return an assembled item?</summary><div class="ans">As we supply a flat-pack product, we're unfortunately not able to accept returns for items that have begun to be assembled.</div></details>
		<details class="faq-item"><summary>Can I return my building if I change my mind?</summary><div class="ans">Yes — however, we may need to deduct reasonable compensation from the refund for any net costs incurred.</div></details>
	</div></section>

	<?php
	get_template_part(
		'template-parts/help-cta',
		null,
		array(
			'eyebrow'    => 'Need to arrange a return?',
			'title'      => "We're here to help.",
			'text'       => "Our customer care team is available Monday to Friday, 8:30am–7:00pm. Get in touch and we'll take it from there.",
			'cta2_label' => 'Email customer care',
			'cta2_href'  => 'mailto:care@projecttimber.co.uk',
		)
	);
	?>

</main>
<?php
get_footer();
