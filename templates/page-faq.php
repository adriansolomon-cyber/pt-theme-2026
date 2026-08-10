<?php
/**
 * Template Name: PT — FAQ
 *
 * Frequently asked questions. Converted from
 * design-files/secondary-pages/projecttimber-faq.html. Chrome is the shared
 * get_header()/get_footer(); hero + help-CTA come from the shared template
 * parts; the accordion is native <details>/<summary> (no JS). Assets
 * (secondary.css + faq.css) are auto-enqueued for templates/page-*.php.
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
			'crumb'      => 'FAQ',
			'eyebrow'    => 'Help centre',
			'title_html' => 'Questions, <span class="fade">answered.</span>',
			'lead'       => "Everything you need to know about ordering, delivery, payment and looking after your garden building. Can't find your answer? Our team is only a call away.",
		)
	);
	?>

	<!-- category jump nav -->
	<section class="jump" style="padding-bottom:0"><div class="wrap"><div class="row">
		<a href="#general">Products &amp; buildings</a>
		<a href="#delivery">Delivery</a>
		<a href="#payment">Payment</a>
		<a href="#service">Customer service</a>
		<a href="#ordering">Placing an order</a>
		<a href="#security">Security</a>
	</div></div></section>

	<!-- products & buildings -->
	<section class="faqcat" id="general"><div class="wrap">
		<div class="cathead">
			<span class="n"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 4l9 6.5"/><path d="M5 9.5V20h14V9.5"/><path d="M9 20v-6h6v6"/></svg></span>
			<h2>Products &amp; buildings</h2>
		</div>
		<details class="faq-item"><summary>Is planning permission required for a building in my garden?</summary><div class="ans">Most of our buildings are designed so that planning permission is not required. However, we cannot accept responsibility regarding planning permission and do not accept liability if the correct approval is not obtained. Most of our sheds, summerhouses and garden offices also fall outside Building Regulations approval — but depending on how you use the building and any modifications (such as electrical connections), you may need approval. Almost all electrical work must be carried out by a suitably qualified electrician, who should be consulted before installing electrics. We do not offer legal advice, so please seek independent advice or speak to your local planning authority before placing your order.</div></details>
		<details class="faq-item"><summary>What foundation do I need for my building?</summary><div class="ans">As with all garden buildings, you'll need a solid, level base to assemble onto. We recommend concrete, paving slabs or timber bearers, though other methods such as eco bases are available. To protect your building against weather damage and maintain your guarantee, we strongly recommend leaving a 1m clearance between your building and any solid surface, such as a fence or brick wall. See our <a href="<?php echo esc_url( home_url( '/building-base-garden-building/' ) ); ?>">Building a Base guide</a> for full instructions.</div></details>
		<details class="faq-item"><summary>Do you offer guarantees or warranties?</summary><div class="ans">Yes — we offer anti-rot guarantees across our range, with the length depending on the timber and treatment used. Our composite and cedar products carry a <strong>15-year guarantee</strong>, and our pressure-treated products are covered by our <strong>25-year guarantee</strong>.* These cover fungal decay and insect attack to the timber structure, provided the building has been treated, assembled and maintained in line with our guidelines. To find out more, call our customer support team on <a href="tel:01777553392">01777 553392</a>.</div></details>
		<details class="faq-item"><summary>Is a base needed?</summary><div class="ans">Yes — it is essential that every garden building is assembled on a base that is both solid and level. This ensures assembly goes smoothly and prevents issues later, as all our buildings are designed to be built on a completely level base. Assembling on an unsuitable foundation may invalidate your guarantee. We also offer our specially designed Eze Base, which provides a solid, level foundation for our garden rooms and offices with minimal groundwork — let us know if you'd like to add this to your order.</div></details>
		<details class="faq-item"><summary>Do you offer bespoke buildings?</summary><div class="ans">If you need bespoke alterations to get your building exactly how you want it, let us know and we'll do our best to accommodate you. Call us on <a href="tel:01777553392">01777 553392</a> or email <a href="mailto:sales@projecttimber.co.uk">sales@projecttimber.co.uk</a> with the details. Common alterations include partition walls, extra doors, replacing windows with timber panels and changing the style of the doors.</div></details>
		<details class="faq-item"><summary>Do you supply floors? Can you supply a building without a floor?</summary><div class="ans">All of our buildings are supplied with floors made from either sustainable OSB or durable T&amp;G timber. Let us know when placing your order and our team can help you choose the best flooring for your needs. We're currently unable to deliver a building without a floor, as our buildings are assembled using the floor as the starting point.</div></details>
		<details class="faq-item"><summary>How do you process pressure-treated timber?</summary><div class="ans">All of our timber is fully pressure treated to protect against the rot and mould that can affect untreated wood. First the timber is dried, then it's placed in a pressure-treatment tank where a vacuum draws a wood preservative deep into the grain, creating a fully treated product that stands up to the weather far better than untreated or dip-treated wood. You can usually identify pressure-treated timber by its green tinge. If you saw or cut pressure-treated wood, coat the exposed ends immediately with a high-quality preservative.</div></details>
		<details class="faq-item"><summary>Can I view a building before I buy?</summary><div class="ans">Our showroom is currently under renovation, but we're still offering viewings. If there's a specific building you're interested in, let us know and we'll be happy to advise whether that design and size is available to view. We also have photos of all our assembled buildings and can arrange a video tour if that's more convenient. Our site is at Parry Works, Grassthorpe Road, Sutton-on-Trent, Newark, NG23 6QX — call <a href="tel:01777553392">01777 553392</a> to arrange a viewing.</div></details>
	</div></section>

	<!-- delivery -->
	<section class="faqcat" id="delivery"><div class="wrap">
		<div class="cathead">
			<span class="n"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v8H3z"/><path d="M14 10h4l3 3v2h-7z"/><circle cx="7" cy="18" r="1.7"/><circle cx="17.5" cy="18" r="1.7"/></svg></span>
			<h2>Delivery</h2>
		</div>
		<details class="faq-item"><summary>Are there any delivery surcharges?</summary><div class="ans">The prices on our website include delivery to most UK mainland postcode districts. A few areas carry an additional charge — if this applies to your postcode we'll let you know. If you order online and a surcharge applies, your order may be placed on hold while we contact you. You can then choose to continue by paying the surcharge, or cancel if you'd prefer. Check your area on our <a href="<?php echo esc_url( home_url( '/delivery/' ) ); ?>">Delivery page</a>.</div></details>
		<details class="faq-item"><summary>How long does delivery take?</summary><div class="ans">Our products are always available to order — if we don't have the parts in stock, we make them specifically for your building. Most buildings are ready in around 10 working days. Some upgrades, such as UPVC doors and windows, are made to order and can add to the lead time. You'll choose your preferred delivery date at checkout.</div></details>
		<details class="faq-item"><summary>Are there any discounts available?</summary><div class="ans">Yes — enjoy <strong>10% off Grandmaster products</strong> with code <strong>GM10</strong>. For our full range of products and current offers, give us a call on <a href="tel:01777553392">01777 553392</a>.</div></details>
	</div></section>

	<!-- payment -->
	<section class="faqcat" id="payment"><div class="wrap">
		<div class="cathead">
			<span class="n"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg></span>
			<h2>Payment</h2>
		</div>
		<details class="faq-item"><summary>How can I pay for my order?</summary><div class="ans">You can pay securely with Revolut Pay, PayPal, or any major credit or debit card (Visa, Mastercard, American Express), as well as Apple Pay and Google Pay. If you're ordering for a business or organisation, contact us about setting up a pro forma invoice and we'll arrange it for you.</div></details>
		<details class="faq-item"><summary>Will VAT be added at the checkout?</summary><div class="ans">No — VAT is automatically included in the price shown, to keep things clear and avoid any hidden costs.</div></details>
		<details class="faq-item"><summary>Will there be any extra fees on my order?</summary><div class="ans">The price of your building is calculated accurately from the options you choose on the website. The only possible addition is a delivery surcharge for certain postcodes — check yours on our <a href="<?php echo esc_url( home_url( '/delivery/' ) ); ?>">Delivery page</a>.</div></details>
		<details class="faq-item"><summary>When will my order be processed?</summary><div class="ans">Orders are usually processed as soon as they're placed and paid for, and we'll email you an order confirmation. If it doesn't arrive, please check your spam folder and then call us on <a href="tel:01777553392">01777 553392</a> so we can check the email address on our system.</div></details>
	</div></section>

	<!-- customer service -->
	<section class="faqcat" id="service"><div class="wrap">
		<div class="cathead">
			<span class="n"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 0 1 16 0v5a2 2 0 0 1-2 2h-2v-6h4"/><path d="M4 13v4a2 2 0 0 0 2 2h2v-6H4"/></svg></span>
			<h2>Customer service</h2>
		</div>
		<details class="faq-item"><summary>How can I contact you?</summary><div class="ans">You can reach us by phone, email or live chat. Call <a href="tel:01777553392">01777 553392</a> and choose the department you'd like to speak to, or email the relevant team directly:<br><br>Customer Care &amp; Support — <a href="mailto:care@projecttimber.co.uk">care@projecttimber.co.uk</a><br>Sales &amp; Enquiries — <a href="mailto:sales@projecttimber.co.uk">sales@projecttimber.co.uk</a><br>Logistics &amp; Deliveries — <a href="mailto:deliveries@projecttimber.co.uk">deliveries@projecttimber.co.uk</a></div></details>
		<details class="faq-item"><summary>What are your opening hours?</summary><div class="ans">Our friendly customer service team is available Monday to Friday, 8:30am&ndash;7:00pm (UK time). We're closed at weekends. If you can't reach us, leave a message with your contact details and we'll be in touch as soon as we're able.</div></details>
	</div></section>

	<!-- placing an order -->
	<section class="faqcat" id="ordering"><div class="wrap">
		<div class="cathead">
			<span class="n"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg></span>
			<h2>Placing an order</h2>
		</div>
		<details class="faq-item"><summary>How do I place an order on the website?</summary><div class="ans">Start by finding the product you're interested in, then select your size. Choose all your options and proceed to checkout. Enter your shipping details, complete the 'How did you find us?' section, and pay using our secure server and encrypted connection.</div></details>
		<details class="faq-item"><summary>Can I place an order over the phone?</summary><div class="ans">No problem — call us on <a href="tel:01777553392">01777 553392</a> and we'll make sure you get exactly what you need from your new garden building. Our advisors are available weekdays, 8:30am&ndash;7:00pm. Prefer us to call you? Email <a href="mailto:sales@projecttimber.co.uk">sales@projecttimber.co.uk</a> with your phone number and the size and type of building you're looking for, and we'll call you back.</div></details>
		<details class="faq-item"><summary>How do I know my order has been received?</summary><div class="ans">After placing your order, a confirmation screen will appear on the website and you'll receive a confirmation email. If you need anything after that, feel free to call us on <a href="tel:01777553392">01777 553392</a>.</div></details>
		<details class="faq-item"><summary>Do I need to register for an account before ordering?</summary><div class="ans">There's no need to register before ordering — we'll automatically process all the relevant information at the time you place your order.</div></details>
		<details class="faq-item"><summary>Can I change my order?</summary><div class="ans">It's not a problem to change your order after placing it. Whether you need to change the size of your building, upgrade the floor thickness or add our Building Assembly Service, simply email <a href="mailto:sales@projecttimber.co.uk">sales@projecttimber.co.uk</a> or call us on <a href="tel:01777553392">01777 553392</a> and we'll sort it for you.</div></details>
		<details class="faq-item"><summary>How do I track my order?</summary><div class="ans">On the morning of your delivery you'll receive an email with a tracking link, so you can see where your driver is on a map along with a live estimated arrival time that updates throughout the day. If you have any questions about your delivery, call us on <a href="tel:01777553392">01777 553392</a> and press 2 to speak to Logistics, or email <a href="mailto:deliveries@projecttimber.co.uk">deliveries@projecttimber.co.uk</a>.</div></details>
	</div></section>

	<!-- security -->
	<section class="faqcat" id="security"><div class="wrap">
		<div class="cathead">
			<span class="n"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="M9.5 12l1.8 1.8 3.2-3.6"/></svg></span>
			<h2>Security</h2>
		</div>
		<details class="faq-item"><summary>Is it safe to shop with Project Timber?</summary><div class="ans">Yes — our website is hosted on a secure, SSL-encrypted server. You may notice a padlock icon in your browser when viewing our site, which confirms a secure connection and that your data is safely handled.</div></details>
		<details class="faq-item"><summary>Will my personal details be shared with other organisations?</summary><div class="ans">All information you provide is kept confidential. We will never share, sell or disclose your personal details to any third-party website or company, and we operate strictly within current GDPR guidelines.</div></details>
		<details class="faq-item"><summary>Will I receive promotional emails?</summary><div class="ans">We only use your email address to send you relevant information about your order, or to contact you if necessary.</div></details>
	</div></section>

	<p class="finep">* 25-year anti-rot guarantee on pressure-treated timber buildings; 15-year on cedar and composite. Terms and conditions apply — see our <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" style="color:var(--charcoal);font-weight:700">terms and conditions</a>.</p>

	<?php get_template_part( 'template-parts/help-cta' ); ?>

</main>
<?php
get_footer();
