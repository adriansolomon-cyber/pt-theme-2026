<?php
/**
 * Template Name: PT — Testimonials
 *
 * Customer reviews wall. Converted from
 * design-files/secondary-pages/projecttimber-testimonials.html. Shared chrome
 * via get_header/get_footer; hero via the shared part. Reviews are static
 * (edit the arrays below); the 5 stars are rendered server-side, so there is
 * no JavaScript. Assets (secondary.css + testimonials.css) are auto-enqueued
 * for templates/page-*.php.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Latest reviews (recent, top section).
$pt_recent = array(
	array( 'title' => 'Selection, purchase and delivery all excellent', 'quote' => 'The shed selection process was easy to use and made buying a smooth, seamless experience. The delivery and support were clear, prompt and easy to manage, and the delivery driver was attentive and helpful.', 'name' => 'Mr Wood', 'date' => 'Jul 28, 2026' ),
	array( 'title' => 'A good delivery', 'quote' => 'Delivery was punctual, friendly and professional. The product looks good and well made.', 'name' => 'Allan Bowles', 'date' => 'Jul 31, 2026' ),
	array( 'title' => 'Recommend to anyone', 'quote' => 'New shed delivered and unloaded with a great explanation of the next steps. No damage, and placed so it can be moved easily. Recommend to anyone.', 'name' => 'Steven C', 'date' => 'Jul 31, 2026' ),
	array( 'title' => '5-star delivery', 'quote' => "We've just had our shed delivered and wanted to thank Patrick B for his fantastic service. He couldn't have been more helpful and polite. Can't wait to get it built now!", 'name' => 'Stephanie', 'date' => 'Aug 6, 2026' ),
	array( 'title' => '', 'quote' => 'Really friendly and helpful — even gave me a few tips to help me put it up.', 'name' => 'Kevin Powell', 'date' => 'Jul 30, 2026' ),
	array( 'title' => 'Kept me informed', 'quote' => 'Kept me informed on when delivery was coming, and very polite throughout the delivery process.', 'name' => 'Gary Bigot', 'date' => 'Jul 28, 2026' ),
	array( 'title' => 'Bang on time', 'quote' => 'Great delivery and bang on time, with good progress updates.', 'name' => 'John Miller', 'date' => 'Aug 3, 2026' ),
	array( 'title' => 'Great service', 'quote' => 'Very helpful, pleasant delivery driver. Great customer service!', 'name' => 'Julie Bligh', 'date' => 'Jul 31, 2026' ),
);

// Wider selection of reviews (main wall).
$pt_wall = array(
	array( 'title' => 'A superb company to deal with', 'quote' => 'A superb company to deal with — the staff are friendly, helpful and knowledgeable, and the delivery company was also very obliging. The shed is of acceptable quality for the price paid. There was a slight hiccup with the item delivered (not helped by me changing my mind after the initial order!), but this was quickly rectified to my complete satisfaction. I would definitely recommend Project Timber and would go back to them for future products.', 'name' => 'Tricia K.', 'date' => 'Feb 23, 2024' ),
	array( 'title' => '', 'quote' => 'I had delivery of my new 8x6 shed today, right on time. Very pleased. Nigel, who delivered to my house — which is in a difficult location — went out of his way to help me carry the shed to the installation site. Thank you so much.', 'name' => 'Dave S.', 'date' => 'Feb 09, 2024' ),
	array( 'title' => 'Great experience', 'quote' => 'Great experience — I called and ordered a shed, checking that delivery was available. I ordered it, it arrived on time and went up easily! Would highly recommend this company.', 'name' => 'Nathan L.', 'date' => 'Jan 02, 2024' ),
	array( 'title' => 'Just what you should expect from a professional company', 'quote' => 'Great website, very easy to select the building options I required, and payment was simple. There was an issue with the delivery notification time, but that was quickly addressed by their support team, especially Stacy. After that, delivery was flawless. The product is building like a dream — some components were partially assembled, which saved a load of time. Very happy customer.', 'name' => 'Ian B.', 'date' => 'Dec 14, 2023' ),
	array( 'title' => 'Great service', 'quote' => 'Great service. Online ordering was simple and user-friendly. My unit was delivered around my schedule, as arranged in a professional manner by Stacey, and assembly was also simple. Would definitely use again, should I find myself in need of a second unit.', 'name' => 'Rob S.', 'date' => 'Dec 07, 2023' ),
	array( 'title' => 'Absolutely amazing!!', 'quote' => 'I bought a shed from here — very good quality and very easy to put together. Delivery was on time and customer service was spot on. Highly recommend!!!', 'name' => 'Dechlan H.', 'date' => 'Nov 20, 2023' ),
	array( 'title' => 'Excellent customer service and a great product', 'quote' => "Ordered a 6x12ft shed from another well-known online supplier in June — when it still hadn't arrived by October, I cancelled and ordered through Project Timber. The shed arrived within 2 weeks, on the exact day promised and at the time specified. Packed neatly on one pallet, it was easy to transport the parts to where they were needed. The online instructions were clear and easy to follow (mostly), and two of us had it up in a weekend, including preparing the base. Now erected, it's a beautiful and useful addition to the garden — still dry inside despite the terrible weather this autumn. Within a couple of weeks, I received a courtesy follow-up call from Abbie — not to upsell me, but just to make sure I was happy. Fantastic customer service and a great product. My only regret is that I didn't come to Project Timber first.", 'name' => 'Tony H.', 'date' => 'Oct 19, 2023' ),
	array( 'title' => 'Project shed', 'quote' => "Project Timber have been more than helpful, starting with the delivery driver who gave me sound advice on how to erect the shed in the right order. It's now up thanks to the easy-to-follow instructions. I received a call from Stacy in customer care checking on my progress, which was appreciated. Most impressed with this company — highly recommended. Give it a go!", 'name' => 'Martin W.', 'date' => 'Oct 17, 2023' ),
	array( 'title' => 'Great service and good quality shed', 'quote' => "Great service and good quality shed at a great price. I had to change the delivery date as my garden wasn't ready, but it wasn't a problem for Abbie and Project Timber, and they delivered when I needed it. It went together very well and is of great quality. I would recommend them to anyone.", 'name' => 'Terry', 'date' => 'Oct 17, 2023' ),
	array( 'title' => 'Easy to build, good quality product', 'quote' => 'The shed came well packed and on a pallet, meaning I could leave it until I was ready to build without risk of weather damage. It arrived on the date given, with good tracking. The instructions were easy to follow and the shed was easy to build, with good-quality pieces. Abbie from Project Timber was helpful with a follow-up call to check everything went well. Overall very happy with the product and service.', 'name' => 'Michael', 'date' => 'Oct 07, 2023' ),
	array( 'title' => 'A delighted customer', 'quote' => "Project Timber's website is one of the clearest and most user-friendly purchasing websites I've ever used. The information on the different products is superb, and the range of options meant I was able to custom-build my ideal workshop. Project Timber clearly know the importance of delighting the customer. I'm really looking forward to many years of their quality products.", 'name' => 'Simon T.', 'date' => 'Sep 15, 2023' ),
	array( 'title' => 'So happy I found these guys', 'quote' => "After weeks of trying to decide and going from supplier to supplier, I found Project Timber. I was delighted, but as I wanted two sheds with different delivery dates I didn't want to order online. I spoke to Craig in Sales and, despite my rambling to explain what we needed and when, he followed my explanation and covered what could and couldn't be done. Professional but also friendly — couldn't ask for better service and advice. Would definitely recommend and will shop again, but how many sheds can one person need? 😃", 'name' => 'Victoria B.', 'date' => 'Sep 06, 2023' ),
	array( 'title' => '', 'quote' => "Their website is extremely easy to use and the photos illustrate every option beautifully. Prices are laid out very clearly so you know what you're buying. The shed is due to be delivered next week, so I'll update on quality once received. Very satisfied so far! 😁", 'name' => 'Guest', 'date' => 'Jul 12, 2023' ),
	array( 'title' => 'Five-star rating', 'quote' => 'My husband purchased the Cannes summerhouse for me as a birthday present. Sadly he died before we were able to build it, and understandably it was some time before it was built. There were some problems assembling it and a few issues with the Care Team — however, the owner of the company got involved and all my problems were immediately sorted out. He couldn\'t have been more helpful. Five-star rating for this company.', 'name' => 'Alison Joy W.', 'date' => 'Jun 22, 2023' ),
	array( 'title' => 'Great range of products', 'quote' => 'Great range of products and a clear, informative website. The web chat — even at weekends — answered my questions well and even called me back to discuss my order to make sure everything was just as I needed it. The summer sale also meant very good value for money!', 'name' => 'Tom M.', 'date' => 'Jun 20, 2023' ),
	array( 'title' => '', 'quote' => "Website is very easy to use — the photos illustrate each item beautifully. Prices are laid out very clearly so you know what you're buying. Shed due to be delivered in a couple of weeks — will update on quality once received. Overall, satisfied with my dealings with this company! 😁", 'name' => 'Katie R.', 'date' => 'Jun 14, 2023' ),
	array( 'title' => 'Exactly as described', 'quote' => "After reading some of the reviews I was dubious, but it was a great price compared to some, so I bought a 10x6 Hobbyist pent shed — and overall I'm pretty satisfied. It was delivered when it was supposed to be, the driver was friendly and helpful, and the shed itself was easy enough to put up and decent quality. There are a few knot holes to fill and slight differences in the panels, but nothing I wouldn't expect with a budget shed. All in all I'm pleased with my purchase and would recommend. 🙂👌", 'name' => 'Aaron S.', 'date' => 'May 31, 2023' ),
	array( 'title' => 'Great quality and amazing shed', 'quote' => 'I was a little sceptical when ordering, as there are quite a few mixed reviews, but after placing my order for the Pressure Treated Hobbyist Pent Central Door Tall Garden Shed (20x8), the customer service was second to none. The shed arrived on a single pallet on the date agreed and all the panels were in great shape. I built it with no issues at all and it looks amazing! So happy with the quality and the look of it.', 'name' => 'Dean M.', 'date' => 'Mar 03, 2023' ),
	array( 'title' => '', 'quote' => "Project Timber are absolutely amazing. I purchased a 14x6 pent shed from them; delivery was by pallet service, which was brilliant. I had a damaged panel, which they immediately sorted and are sending me a replacement. From purchase to delivery, Project Timber have been excellent. I'll definitely be purchasing from them again when I need a summerhouse. Keep up the good work!", 'name' => 'James G.', 'date' => 'Feb 02, 2023' ),
	array( 'title' => '', 'quote' => "I'm very happy with my shed and the service I received from the team at Project Timber. They were very polite and helpful! I intend to buy more in the future — a good company, and I recommend them to all!", 'name' => 'Abdul', 'date' => 'Mar 20, 2024' ),
	array( 'title' => 'Well packaged and as described', 'quote' => "Ordered a 10x6 pent shed — it came promptly, well packaged and as described. Great value for money using Project Timber, and I'll definitely be ordering sheds for my clients from you in the future. Thank you.", 'name' => 'Gareth', 'date' => 'Apr 18, 2024' ),
	array( 'title' => 'Thank you for your customer support', 'quote' => "Your team has provided such excellent service. These days it's so difficult to find a company that actually wants to help when you need assistance. I highly recommend Project Timber. Thanks again.", 'name' => 'Richard', 'date' => 'May 22, 2024' ),
	array( 'title' => 'Fabulous support for our charity project', 'quote' => "Anne, our adviser, was fabulous in supporting our charity to get the summerhouse delivered on time. It's part of a project where young people are volunteering over two weeks to create a forest school for other young people experiencing hardship. Fantastic summerhouse — we absolutely love it — and amazing customer service. Thank you!", 'name' => 'Claire', 'date' => 'Jul 11, 2024' ),
	array( 'title' => '', 'quote' => '5 stars all round — the shed itself is amazing and was really easy to assemble. My partner put it up by himself with little help from me. The delivery team was amazing: we were given a delivery time and received a phone call 30 minutes before they arrived. I would highly recommend.', 'name' => 'Katy', 'date' => 'Aug 24, 2024' ),
	array( 'title' => '', 'quote' => "Really impressed so far. I chose Project Timber as they're a reputable company and I wanted a big shed. I particularly liked the website and the vast choice of customisations available. The shed arrived promptly and I'm very happy with the overall quality, easy assembly and appearance. It's not quite finished but already looks great.", 'name' => 'Adam', 'date' => 'Aug 26, 2024' ),
	array( 'title' => 'The modular design of the D100 makes a lot of sense', 'quote' => 'Flexible design, standardisation of parts and easy-to-manage panels. It fits compactly onto one pallet and doesn\'t take up much space until you\'re ready to assemble. Screws to fix the panels help pull everything together neatly. Excellent customer care team, very helpful. Would definitely recommend.', 'name' => 'William', 'date' => 'Sep 09, 2024' ),
	array( 'title' => 'The service from this company was excellent', 'quote' => "From the phone call to them, to the delivery which arrived on the day we agreed, the summerhouse is just as described — which we're really pleased with. They supplied a video beforehand to show you how to build it, and there were no parts missing. You would not be disappointed.", 'name' => 'Pauline', 'date' => 'Jul 08, 2024' ),
	array( 'title' => '', 'quote' => 'Firstly, I liked the design (and price) of the shed I bought. The online buying process was easy and without problems, as was the arranged delivery. The kit came well protected on its pallet, and the delivery driver was polite and helpful. All items were correct and present, and construction was relatively straightforward. I was impressed with the quality of the wood and how each piece was labelled, which greatly assisted construction. A couple of questions (down to my own ignorance and easily overcome) were answered promptly and satisfactorily by Zoe at Customer Care. Thank you.', 'name' => 'Gerry', 'date' => 'Sep 30, 2024' ),
);

/**
 * Render one review card, with 5 server-rendered stars.
 *
 * @param array $r Review (title, quote, name, date).
 */
function pt_render_review_card( $r ) {
	static $stars = '';
	if ( '' === $stars ) {
		$stars = str_repeat( '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.8 5.9 21.4l1.4-6.8L2.2 9.9l6.9-.8z"/></svg>', 5 );
	}
	echo '<figure class="tcard">';
	echo '<div class="stars" role="img" aria-label="Rated 5 out of 5">' . $stars . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup
	if ( ! empty( $r['title'] ) ) {
		echo '<h3 class="tt">' . esc_html( $r['title'] ) . '</h3>';
	}
	echo '<blockquote>' . esc_html( $r['quote'] ) . '</blockquote>';
	echo '<figcaption><span class="name">' . esc_html( $r['name'] ) . '</span><span class="date">' . esc_html( $r['date'] ) . '</span></figcaption>';
	echo '</figure>';
}

get_header();
?>
<main class="pt-secondary" id="main" tabindex="-1">

	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'crumb'      => 'Testimonials',
			'eyebrow'    => 'What our customers say',
			'title_html' => 'Real stories from <span class="fade">real gardens.</span>',
			'lead'       => 'From first click to final screw, here\'s what customers think of ordering, delivering and building a Project Timber.',
		)
	);
	?>

	<!-- latest reviews -->
	<section class="wall alt"><div class="wrap">
		<div class="whead">
			<div class="eyebrow">Just in</div>
			<h2>The latest <span class="fade">five-star reviews.</span></h2>
		</div>
		<div class="cols">
			<?php foreach ( $pt_recent as $pt_r ) { pt_render_review_card( $pt_r ); } ?>
		</div>
		<p class="selnote">Recent 5-star reviews as published on Trustpilot.</p>
	</div></section>

	<!-- selection of reviews -->
	<section class="wall"><div class="wrap">
		<div class="whead">
			<div class="eyebrow">In their own words</div>
			<h2>From first click to <span class="fade">final screw.</span></h2>
		</div>
		<div class="cols">
			<?php foreach ( $pt_wall as $pt_r ) { pt_render_review_card( $pt_r ); } ?>
		</div>
		<p class="selnote">A selection of reviews from Project Timber customers.</p>
	</div></section>

	<!-- CTA (bespoke: range-first, phone second) -->
	<section class="helpcta"><div class="wrap">
		<div class="eyebrow">Ready to start yours?</div>
		<h2>Join thousands of happy gardens.</h2>
		<p>Browse the range, customise your building online, and pick a delivery date that suits you. Questions? Our team is here Monday to Friday, 8:30am–7:00pm.</p>
		<div class="btns">
			<a class="btn btn-y" href="<?php echo esc_url( home_url( '/garden-offices/' ) ); ?>">Explore the range</a>
			<a class="btn btn-o" href="tel:01777553392"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>01777 553392</a>
		</div>
	</div></section>

</main>
<?php
get_footer();
