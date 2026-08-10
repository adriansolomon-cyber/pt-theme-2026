<?php
/**
 * Template Name: PT — Building a Base
 *
 * Base-preparation guide. Converted from
 * design-files/secondary-pages/projecttimber-building-a-base.html. Shared chrome
 * via get_header/get_footer; hero + help-CTA via the shared parts; the three
 * method tabs are wired by assets/js/building-base-garden-building.js. Assets
 * (secondary.css + building-base-garden-building.css/.js) are auto-enqueued for
 * templates/page-*.php.
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
			'crumb'      => 'Building a Base',
			'eyebrow'    => 'Preparation guide',
			'title_html' => 'Building a base for <span class="fade">your garden building.</span>',
			'lead'       => 'A solid, level base is the single most important step for a smooth build and a long-lasting building. Here\'s how to choose a spot and prepare the ground — concrete, paving slabs or timber bearers.',
		)
	);
	?>

	<!-- why it matters -->
	<section class="prose" style="padding-bottom:24px"><div class="wrap">
		<h2>Why the base matters</h2>
		<p>It is essential that all garden buildings are assembled on a solid, level base. This ensures the construction goes smoothly and deters future issues such as doors and windows dropping out of alignment and becoming difficult to operate — which can also become a source of water leakage.</p>
		<div class="callout">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
			<p>Assembling a garden building on an incorrect base is likely to <strong>invalidate any warranty</strong> provided with your building.</p>
		</div>
	</div></section>

	<!-- where to place -->
	<section class="prose" style="padding-top:24px"><div class="wrap">
		<h2>Where to place your base</h2>
		<p>When deciding where to locate your garden building, there are a few things to pay attention to:</p>
		<ul>
			<li><strong>Boundary walls, fences and other buildings</strong> — allow for any roof overhang your building may have, as this can extend beyond the base. You'll also need to reach all points of the building to apply wood treatments and preservatives.</li>
			<li><strong>Trees and large bushes</strong> — installing close to trees or bushes can cause problems from overhanging branches. Cut these back before assembly, and check overhanging foliage regularly afterwards, as rubbing on the roofing material can cause damage, leading to water leakage and voiding any guarantees.</li>
			<li><strong>Access and use</strong> — try to visualise the building in position. You may not want to transport large or heavy items into hard-to-reach areas of the garden.</li>
			<li><strong>Light and outlook</strong> — consider a spot with good natural light or a nice view, especially for buildings such as summerhouses.</li>
			<li><strong>Services</strong> — if you intend to fit electricity or water, choose a location that suits this, taking into account where the mains supplies are.</li>
		</ul>
	</div></section>

	<!-- methods -->
	<section class="methods"><div class="wrap">
		<div class="head">
			<div class="eyebrow">How to construct your base</div>
			<h2 style="font-size:clamp(1.8rem,4.4vw,2.4rem);margin-top:8px">Three proven methods</h2>
		</div>

		<div class="tip" style="max-width:820px;margin:0 auto 24px">
			<img src="https://www.projecttimber.com/wp-content/uploads/2018/06/Easy-Self-Assembly.png" alt="Easy self-assembly">
			<p>We recommend using a reputable local builder or handyman, but if you're confident it's relatively straightforward to build your own. <strong>Build your base slightly larger than the building — add approximately 30–40mm to each side.</strong></p>
		</div>

		<div class="mtabs" role="tablist" aria-label="Base construction methods">
			<button class="mtab" role="tab" id="tab-concrete" aria-selected="true" aria-controls="panel-concrete">Concrete</button>
			<button class="mtab" role="tab" id="tab-paving" aria-selected="false" aria-controls="panel-paving" tabindex="-1">Paving slabs</button>
			<button class="mtab" role="tab" id="tab-bearers" aria-selected="false" aria-controls="panel-bearers" tabindex="-1">Timber bearers</button>
		</div>

		<!-- concrete -->
		<div class="mpanel show" id="panel-concrete" role="tabpanel" aria-labelledby="tab-concrete">
			<span class="rec">Recommended for larger buildings</span>
			<h3>Concrete base</h3>
			<p class="mintro">A concrete base gives the most solid, durable foundation and is strongly recommended for larger garden buildings.</p>
			<ol class="blist">
				<li>Remove any vegetation from the area where you've chosen to construct your base.</li>
				<li>Use pegs and string to mark out the area. Measure the lengths between opposite corners to check the area is square — these will be equal if the base is square.</li>
				<li>Excavate the marked area to around <strong>6" (150mm)</strong> deep.</li>
				<li>Lay approximately <strong>3" (75mm)</strong> of firmly compacted hardcore, scalping or brick rubble as a foundation, levelling with compacted sand if appropriate. A rake helps with levelling. Remove the pegs and string.</li>
				<li>Measure, cut and fit timber rails or steel shuttering to the shape of the base. Use a tape measure, spirit level and tri-square to make sure the shuttered base is 100% level and square.</li>
				<li>Lay approximately <strong>3" (75mm)</strong> of concrete. Use bags of dry-mixed concrete with small amounts of water added at a time, or mix 'all-in' ballast, cement and water in a ratio of <strong>1 part cement to 5 parts 'all-in' ballast</strong>. (Around 1.25 bags of 40kg 'all-in' ballast produces roughly 1 cubic foot of concrete.) Don't let the mix get too wet, as this weakens the concrete.</li>
				<li>Spread the concrete evenly in the shuttering, pushing it well into the corners and edges. Lay it a layer at a time and compact it until the frame is full. Leave it flush with the top of the framework and smooth with a wooden or plastic float.</li>
				<li>Cover the concrete with sheets to let it dry naturally. Don't let it dry too quickly — in warm, dry weather you may need to spray it with water.</li>
				<li>Once firm and dry, the base is ready for you to begin assembly.</li>
			</ol>
		</div>

		<!-- paving -->
		<div class="mpanel" id="panel-paving" role="tabpanel" aria-labelledby="tab-paving" hidden>
			<h3>Paving slab base</h3>
			<p class="mintro">A neat, budget-friendly option that's well within reach for a confident DIYer.</p>
			<ol class="blist">
				<li>Remove any vegetation from the chosen area.</li>
				<li>Use pegs and string to mark out the area. Measure between opposite corners to check it's square — the lengths will be equal if it is.</li>
				<li>Excavate the marked area to around <strong>2.5" (63.5mm)</strong> deep. Remove the pegs and string.</li>
				<li>Lay approximately <strong>1.5" (40mm)</strong> of a dry mix of <strong>1 part cement to 8 parts building sand</strong>. Level the mix — a rake and spirit level help.</li>
				<li>Starting from a corner, lay the paving slabs and tap down with a rubber mallet. The slab surface should sit slightly higher than the surrounding ground to encourage rainwater drainage. Use a spirit level to keep all slabs square, level and firmly butted together.</li>
				<li>Brush off any excess sand and cement mix — the base is now ready for assembly.</li>
			</ol>
		</div>

		<!-- bearers -->
		<div class="mpanel" id="panel-bearers" role="tabpanel" aria-labelledby="tab-bearers" hidden>
			<h3>Timber bearers base</h3>
			<p class="mintro">A quicker method using bearers laid over a levelled sub-base.</p>
			<ol class="blist">
				<li>Remove any vegetation from the chosen area.</li>
				<li>Use pegs and string to mark out the area. Measure between opposite corners to check it's square. Excavate to around <strong>2" (50mm)</strong> deep, then remove the pegs and string.</li>
				<li>Lay approximately <strong>1.5" (40mm)</strong> of gravel or soil. Level the mix — a rake and spirit level help.</li>
				<li>Lay either concrete floor bearers or pressure-treated (tanalised) timber bearers across the gravel/soil, equally spaced at intervals of approximately <strong>400–600mm</strong>, running perpendicular to the floor joists already built into your building. If you're installing the base before your building arrives, ask our team which direction the bearers should run.</li>
				<li>Make sure all bearers are level with one another using a spirit level. Tap down with a rubber mallet if needed.</li>
				<li><strong>How many bearers?</strong> This depends on the size of your building. All of our buildings come with bearers attached to the floor panels — check the instructions for your floor plan, or call us and we'll advise where the bearers run.</li>
			</ol>
		</div>
	</div></section>

	<?php
	get_template_part(
		'template-parts/help-cta',
		null,
		array(
			'eyebrow'    => 'Need a hand with your base?',
			'title'      => "We'll point you in the right direction.",
			'text'       => 'For further advice on constructing or installing your new garden building or base, give us a call and one of our friendly team will be happy to help.',
			'cta2_label' => 'Read the FAQs',
			'cta2_href'  => home_url( '/faq/' ),
		)
	);
	?>

</main>
<?php
get_footer();
