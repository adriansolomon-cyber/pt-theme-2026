<?php
/**
 * Template Name: PT — Delivery
 *
 * Delivery information + postcode checker. Converted from
 * design-files/secondary-pages/projecttimber-delivery.html. Shared chrome via
 * get_header/get_footer; hero via the shared part; the FAQ accordion is native
 * <details>/<summary>. The postcode checker is a front-end zone simulation
 * (zones mirror the live delivery page) wired by assets/js/delivery.js. Assets
 * (secondary.css + delivery.css/.js) are auto-enqueued for templates/page-*.php.
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
			'crumb'      => 'Delivery',
			'eyebrow'    => 'Delivery',
			'title_html' => 'Getting it to <span class="fade">your garden.</span>',
			'lead'       => 'Free kerbside delivery to most UK mainland postcodes. Check yours below, then pick a delivery date that suits you at checkout.',
		)
	);
	?>

	<!-- postcode checker -->
	<section class="checker"><div class="wrap">
		<div class="pc-card">
			<h2>Check delivery to your postcode</h2>
			<p class="sub">Enter your postcode to see availability and any delivery charge for your area.</p>
			<form class="pc-form" id="pcForm" novalidate>
				<input id="pcInput" type="text" inputmode="text" autocomplete="postal-code" placeholder="e.g. NG23 6QX" aria-label="Your postcode" maxlength="8">
				<button type="submit">Check</button>
			</form>
			<div class="pc-result" id="pcResult" role="status" aria-live="polite">
				<svg id="pcIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"></svg>
				<div><b id="pcTitle"></b><p id="pcMsg"></p></div>
			</div>
			<p class="pc-note">Indicative only — your exact delivery cost and available dates are confirmed at checkout.</p>
		</div>
	</div></section>

	<!-- zones + map -->
	<section class="zones"><div class="wrap">
		<div style="text-align:center;margin-bottom:30px">
			<div class="eyebrow">Where we deliver</div>
			<h2 style="font-size:clamp(2rem,5vw,2.8rem);margin-top:8px">Four delivery zones.</h2>
		</div>
		<div class="zgrid">
			<div class="zmap"><img loading="lazy" src="https://www.projecttimber.com/wp-content/uploads/2025/05/deliver_map_2025.webp" alt="Project Timber UK delivery zone map"></div>
			<div class="zlist">
				<details class="zone" open>
					<summary><span class="zdot a"></span><span class="zt">Zone A — Free delivery</span><span class="zc">FREE</span><svg class="zchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></summary>
					<div class="zcodes"><b>Free pick-a-day delivery</b> on selected buildings and postcodes: AL · B · BA · BB · BD · BH · BL · BN · BR · BS · CA · CB · CF · CH · CM · CO · CR · CT · CV · CW · DA · DE · DH · DL · DN · DT · DY · E · EC · EN · FY · GL · GU · HA · HD · HG · HP · HR · HU · HX · IG · IP · KT · L · LA · LD · LE · LL · LN · LS · LU · M · ME · MK · N · NE · NG · NN · NP · NR · NW · OL · OX · PE · PR · PO1–29 · RG · RH · RM · S · SE · SG · SK · SL · SM · SN · SO · SP · SR · SS · ST · SW · SY · SA1–20 · TA · TF · TN · TS · TW · UB · W · WA · WC · WD · WF · WN · WR · WS · WV · YO</div>
				</details>
				<details class="zone">
					<summary><span class="zdot b"></span><span class="zt">Zone B — £99 surcharge</span><span class="zc">£99</span><svg class="zchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></summary>
					<div class="zcodes">FK · G · KA · ML · EH · DG · TD · SA31–73 · TR · PL · EX · TQ</div>
				</details>
				<details class="zone">
					<summary><span class="zdot c"></span><span class="zt">Zone C — £199 surcharge</span><span class="zc">£199</span><svg class="zchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></summary>
					<div class="zcodes">Typically 15–20 working days: AB · PH · DD · KY · PA</div>
				</details>
				<details class="zone">
					<summary><span class="zdot d"></span><span class="zt">Zone D — Not available</span><span class="zc">—</span><svg class="zchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></summary>
					<div class="zcodes">KW · IV · PO30–41 · Channel Islands · Orkney &amp; Shetland · Ireland · Northern Ireland · all other offshore islands</div>
				</details>
			</div>
		</div>
	</div></section>

	<!-- how it works -->
	<section class="how"><div class="wrap">
		<div class="eyebrow" style="text-align:center">How delivery works</div>
		<h2>Kerbside, on a day <span class="fade">that suits you.</span></h2>
		<p class="lead">Your building arrives flat-packed as pre-made panels, delivered kerbside by lorry or van. Please make sure there's clear access in front of your property on the day.</p>
		<div class="feat">
			<div class="f"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v8H3z"/><path d="M14 10h4l3 3v2h-7z"/><circle cx="7" cy="18" r="1.7"/><circle cx="17.5" cy="18" r="1.7"/></svg></div><h3>Free UK delivery</h3><p>To most mainland postcodes (Zone A).</p></div>
			<div class="f"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="9" width="18" height="11" rx="2"/><path d="M3 9l3-5h12l3 5"/></svg></div><h3>Kerbside delivery</h3><p>Delivered to the kerb by lorry or van.</p></div>
			<div class="f"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg></div><h3>Pick your day</h3><p>Choose a delivery date at checkout.</p></div>
			<div class="f"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><h3>8am – 5pm</h3><p>We schedule deliveries through the day.</p></div>
		</div>
		<div class="steps">
			<div class="step"><span class="n">1</span><div><h3>Order &amp; confirm</h3><p>You'll get an order confirmation by email with everything you've bought.</p></div></div>
			<div class="step"><span class="n">2</span><div><h3>We book your date</h3><p>We'll be in touch to agree a delivery day that works for you — no fixed rush.</p></div></div>
			<div class="step"><span class="n">3</span><div><h3>Kerbside drop-off</h3><p>Your driver aims to call ahead, and delivers to the kerb. Please check the parts on arrival.</p></div></div>
		</div>
	</div></section>

	<!-- faq -->
	<section class="faq"><div class="wrap">
		<h2>Delivery questions, <span class="fade">answered.</span></h2>
		<details class="faq-item"><summary>How much does delivery cost?</summary><div class="ans">For most of the UK mainland it's free (Zone A). Some areas carry a £99 or £199 surcharge, and a few locations we're unable to reach — use the postcode checker above to see your area. Your exact cost is always shown at checkout.</div></details>
		<details class="faq-item"><summary>Where do you deliver?</summary><div class="ans">We deliver to most UK mainland postcodes. We're unable to deliver to Northern Ireland, the Republic of Ireland, the Channel Islands and some Scottish islands (Zone D).</div></details>
		<details class="faq-item"><summary>What time do you deliver?</summary><div class="ans">Deliveries are scheduled between 8am and 5pm. Your driver will usually try to call when they're on their way.</div></details>
		<details class="faq-item"><summary>Can I choose my delivery date?</summary><div class="ans">Yes — you pick a preferred delivery date at checkout, and we'll do our best to meet it. At busier times we may need to agree an alternative day with you, and we'll keep you informed throughout.</div></details>
		<details class="faq-item"><summary>How is it delivered and packaged?</summary><div class="ans">Your building arrives as pre-made, flat-packed panels — on a pallet, or on a van for larger buildings. Delivery is kerbside, so please ensure there's suitable access in front of your property.</div></details>
		<details class="faq-item"><summary>What if parts are damaged or missing?</summary><div class="ans">Please check the delivery on arrival before signing. If anything is damaged or missing, note it on the receipt and call our sales team so we can put it right. Full details are in the delivery section of our <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" style="color:var(--charcoal);font-weight:700">terms and conditions</a>.</div></details>
	</div></section>

</main>
<?php
get_footer();
