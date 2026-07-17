<?php
/**
 * Single product (PDP) — converted from projecttimber-product-page.html.
 *
 * DESIGN-ONLY PASS (per decision "all products now, content later"): the dynamic
 * configurator (gallery, option rows, price, add-to-cart, size buttons, spec
 * values) is driven live by assets/js/product.js against the config/specs API
 * for the CURRENT product (functions.php injects window.PT_PRODUCT_ID +
 * window.PT_WC_BASE). The static marketing sections below are still
 * My-Den-Composite-specific copy and will show on every product until they're
 * made content-driven in a later phase.
 *
 * header/footer/support/cart-drawer come from get_header()/get_footer().
 * product.css/product.js are enqueued in functions.php (is_singular('product')).
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Dynamic bits (design-only pass) --------------------------------------
$pt_pid     = get_queried_object_id();
$pt_name    = get_the_title( $pt_pid );                       // product title (e.g. "Consort Summerhouse")
$pt_product = function_exists( 'wc_get_product' ) ? wc_get_product( $pt_pid ) : null;
$pt_line    = pt_product_line_singular( $pt_pid );            // singular category (e.g. "Summerhouse")
if ( '' === $pt_line ) {
	$pt_line = $pt_name;
}
$pt_from = $pt_product ? pt_product_from_price_html( $pt_product ) : '';
if ( '' === $pt_from ) {
	$pt_from = 'From £—';
}
// --- Editable content (ACF field group "Product Page Content") -------------
// pt_f() returns an ACF field value, or the supplied static fallback when the
// field is empty / ACF is inactive — so a product with no content filled still
// renders exactly the original design. pt_has_rows() guards repeater loops.
$pt_f = function ( $name, $fallback = '' ) use ( $pt_pid ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}
	$v = get_field( $name, $pt_pid );
	if ( null === $v || '' === $v || false === $v || array() === $v ) {
		return $fallback;
	}
	return $v;
};
$pt_has_rows = function ( $name ) use ( $pt_pid ) {
	return function_exists( 'have_rows' ) && have_rows( $name, $pt_pid );
};

$pt_hero_img = $pt_f(
	'hero_image',
	has_post_thumbnail( $pt_pid )
		? get_the_post_thumbnail_url( $pt_pid, 'large' )
		: 'https://www.projecttimber.com/wp-content/uploads/2026/06/My_Den_Composite_Garden_Office-scaled.webp'
);

// Product structured data (JSON-LD). This custom template fires none of WooCommerce's
// single-product hooks, so WC never generates it — trigger it here. WooCommerce outputs
// the assembled markup on wp_footer (footer.php calls wp_footer), and the migrated
// fix_composite_product_price_schema filter (legacy-functions.php) applies during generation.
if ( $pt_product && function_exists( 'WC' ) && WC()->structured_data ) {
	$GLOBALS['product'] = $pt_product;                 // WC generator reads the global $product
	WC()->structured_data->generate_product_data();
}

get_header();
?>

<div class="subnav">
  <span class="brand"><?php echo esc_html( $pt_name ); ?></span>
  <nav class="tabs"><a href="#highlights">Overview</a><a href="#specs">Tech Specs</a><a href="#faq">FAQ</a></nav>
  <button class="buy">Customise &amp; buy</button>
</div>

<!-- ===================== HERO ===================== -->
<header class="hero" id="main" tabindex="-1"><div class="wrap">
  <div class="eyebrow"><?php echo esc_html( $pt_line ); ?> · <?php echo esc_html( $pt_f( 'hero_eyebrow', 'Composite · Fully insulated' ) ); ?></div>
  <?php $pt_hero_heading = get_field( 'hero_heading', $pt_pid ); ?>
  <?php if ( $pt_hero_heading ) : ?>
    <h1 class="display"><?php echo wp_kses_post( $pt_hero_heading ); ?></h1>
  <?php else : ?>
    <h1 class="display">Work happens at the<br><span class="fade">bottom of the </span><span class="swipe">garden.</span></h1>
  <?php endif; ?>
  <p class="lead"><?php echo esc_html( $pt_f( 'hero_lead', 'A year-round, fully insulated garden office — composite cladding, pre-insulated panels, delivered and built in days.' ) ); ?></p>
  <div class="hero-figure">
    <img src="<?php echo esc_url( $pt_hero_img ); ?>" alt="<?php echo esc_attr( $pt_name ); ?>">
  </div>
  <div class="pricepill">
    <span class="pl"><b><?php echo esc_html( $pt_from ); ?></b></span>
    <button class="go">Customise &amp; buy</button>
  </div>
</div></header>

<!-- ===================== INLINE CONFIGURATOR (non-popup) ===================== -->
<section class="configurator" id="configure"><div class="wrap">
  <div class="sec-head"><h2>Build <span class="fade">your <?php echo esc_html( $pt_line ); ?>.</span></h2></div>
  <div class="cfg-grid">

    <div class="cfg-preview">
      <div class="cfg-gallery" id="cfgGallery">
        <img id="cfgImg" src="https://www.projecttimber.com/wp-content/uploads/2024/10/8x6_Evolution_My_Den_Composite_Cladding_Garden_Office_09-1.jpg" alt="<?php echo esc_attr( $pt_name ); ?> preview">
        <div class="cfg-disc" id="cfgDisc">
          <button class="cfg-disc-btn" type="button" aria-label="Image disclaimer" aria-expanded="false">i</button>
          <div class="cfg-disc-panel" role="tooltip"><b>Disclaimer:</b> Painted buildings are shown for illustration only. All buildings are delivered in their standard or pressure-treated colour, which may vary from those shown. Please keep adequate distance between your building and the boundary to allow access for the annual treatment required to comply with the anti-rot guarantee*.</div>
        </div>
        <button class="cfg-navbtn prev" type="button" aria-label="Previous image">&lsaquo;</button>
        <button class="cfg-navbtn next" type="button" aria-label="Next image">&rsaquo;</button>
        <div class="cfg-dots" id="cfgDots"></div>
      </div>
      <div class="pcap"><span class="nm" id="cfgProdName"><?php echo esc_html( $pt_name ); ?></span><span class="sz" id="cfgSize">8 × 6</span></div>
    </div>

    <div>
      <!-- Option steps are rendered live from the WooCommerce composite product (assets/js/product.js).
           The card / row markup the engine emits reuses the exact same design classes as the rest
           of the page — only the data is dynamic. -->
      <p class="cfg-status" id="cfgStatus" role="status"></p>
      <div class="cfg-rows" id="cfgRows"></div>

      <!-- live summary -->
      <div class="cfg-summary">
        <div class="deliv" id="cfgDeliv">Free delivery to most mainland UK postcodes* · choose your delivery date at checkout</div>
        <!-- FINANCE HIDDEN (re-enable when a finance provider is in place):
        <div class="ptoggle"><button class="on" data-pay="cash">Cash</button><button data-pay="finance">Finance</button></div>
        -->
        <div class="price" id="cfgPrice">£4,461.00</div>
        <div class="pvat">inc. VAT</div>
        <button class="addbtn cfgadd" id="cfgAdd" disabled>Add to basket</button>
        <p class="fineprint">Prices as per the live configurator. Paint/trim swatch colours are indicative approximations.</p>
      </div>
    </div>

  </div>
</div></section>

<!-- ===================== HIGHLIGHTS ===================== -->
<section id="highlights"><div class="wrap">
  <div class="sec-head">
    <?php $pt_hl_heading = get_field( 'highlights_heading', $pt_pid ); ?>
    <?php if ( $pt_hl_heading ) : ?>
      <h2><?php echo esc_html( $pt_hl_heading ); ?></h2>
    <?php else : ?>
      <h2>Get the <span class="fade">highlights.</span></h2>
    <?php endif; ?>
    <a class="filmlink" href="<?php echo esc_url( $pt_f( 'showcase_video_url', 'https://www.youtube.com/watch?v=1AidYysfFB4&t=7s' ) ); ?>" target="_blank" rel="noopener"><span class="pp">▶</span> Watch the showcase</a>
  </div>
  <?php if ( $pt_has_rows( 'highlights' ) ) : ?>
  <div class="rail">
    <?php while ( have_rows( 'highlights', $pt_pid ) ) : the_row(); ?>
      <div class="card photo">
        <img src="<?php echo esc_url( get_sub_field( 'image' ) ); ?>" alt="<?php echo esc_attr( get_sub_field( 'title' ) ); ?>">
        <div class="scrim"></div>
        <div class="ctxt"><h3><?php echo esc_html( get_sub_field( 'title' ) ); ?></h3><p class="sub"><?php echo esc_html( get_sub_field( 'subtitle' ) ); ?></p></div>
      </div>
    <?php endwhile; ?>
  </div>
  <?php else : ?>
  <div class="rail">
    <div class="card photo c-big">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/composite_cladding.webp" alt="Composite cladding">
      <div class="scrim"></div>
      <div class="ctxt"><h3 style="font-size:1.7rem">Composite cladding</h3><p class="sub">The look of wood, the toughness of composite</p></div>
    </div>
    <div class="card photo c-tall">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/80mm_thick_wall.webp" alt="80mm thick insulated wall">
      <div class="scrim"></div>
      <div class="ctxt"><div class="big">80<span class="u">mm</span></div><p class="sub">Total wall thickness · doubled-up framing · <span class="accentword">fully insulated</span></p></div>
    </div>
    <div class="card photo">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/upvc_double_glazed.webp" alt="Double glazing and UPVC door">
      <div class="scrim"></div>
      <div class="ctxt"><h3>Double glazing &amp; UPVC door</h3><p class="sub">Multi-point locking, as standard</p></div>
    </div>
    <div class="card photo">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/15-years-anti-rot-composite.webp" alt="15-year anti-rot guarantee">
      <div class="scrim"></div>
      <div class="ctxt"><h3>15-year guarantee</h3><p class="sub">Anti-rot — composite*</p></div>
    </div>
    <div class="card photo c-wide">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/metal_roof.webp" alt="Insulated metal roof">
      <div class="scrim"></div>
      <div class="ctxt"><h3>Insulated metal roof</h3><p class="sub">Ultimate weather protection</p></div>
    </div>
    <div class="card photo c-wide">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/Parts-10x8-My-Den-Composite.png" alt="Easy self assembly">
      <div class="scrim"></div>
      <div class="ctxt"><h3>Easy self-assembly</h3><p class="sub">Pre-insulated, pre-assembled panels</p></div>
    </div>
  </div>
  <?php endif; ?>
</div></section>

<!-- ===================== WHAT'S INCLUDED ===================== -->
<section class="included"><div class="wrap">
  <div class="eyebrow"><?php echo esc_html( $pt_f( 'included_eyebrow', 'In the box' ) ); ?></div>
  <?php $pt_inc_heading = get_field( 'included_heading', $pt_pid ); ?>
  <?php if ( $pt_inc_heading ) : ?>
    <h2><?php echo esc_html( $pt_inc_heading ); ?></h2>
  <?php else : ?>
    <h2>Everything you need, <span class="fade">in one delivery.</span></h2>
  <?php endif; ?>
  <?php $pt_inc_lead = get_field( 'included_lead', $pt_pid ); ?>
  <p class="lead"><?php echo $pt_inc_lead ? esc_html( $pt_inc_lead ) : 'No hidden extras and no surprise add-ons. Your ' . esc_html( $pt_line ) . ' arrives complete — every panel, fixing and fitting ready to go.'; ?></p>
  <div class="inc-grid">
    <div class="inc-media"><img src="<?php echo esc_url( $pt_f( 'included_image', 'https://www.projecttimber.com/wp-content/uploads/2024/10/8x8_Evolution_My_Den_Composite_Cladding_Garden_Office_01.jpg' ) ); ?>" alt="<?php echo esc_attr( $pt_name ); ?>"></div>
    <ul class="inc-list">
      <?php $pt_ck = '<svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'; ?>
      <?php if ( $pt_has_rows( 'included_items' ) ) : ?>
        <?php while ( have_rows( 'included_items', $pt_pid ) ) : the_row(); ?>
          <li><?php echo $pt_ck; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static SVG. ?> <?php echo esc_html( get_sub_field( 'item' ) ); ?></li>
        <?php endwhile; ?>
      <?php else : ?>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Pre-assembled, pre-insulated wall panels</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Pre-fitted multi-foil insulation &amp; primed internal cladding</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Weather-resistant LP Strongcore composite cladding</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Heavy-duty, fully insulated metal roof &amp; fixings</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Double-glazed front timber windows &amp; opening side windows</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> UPVC single door with multi-point locking</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Tongue-and-groove floor</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Eze base and fixings</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Guttering</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> All fixings and fittings</li>
      <li><svg class="ck" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#3B333D"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Easy-to-follow, illustrated instructions</li>
      <?php endif; ?>
    </ul>
  </div>
  <a class="btn-primary" href="#configure">Configure your <?php echo esc_html( $pt_line ); ?> <span class="a">→</span></a>
</div></section>

<!-- ===================== WHY CHOOSE ===================== -->
<section class="why-choose"><div class="wrap">
  <?php $pt_why_eyebrow = get_field( 'why_eyebrow', $pt_pid ); ?>
  <div class="eyebrow"><?php echo $pt_why_eyebrow ? esc_html( $pt_why_eyebrow ) : 'Why the ' . esc_html( $pt_line ); ?></div>
  <?php $pt_why_heading = get_field( 'why_heading', $pt_pid ); ?>
  <?php if ( $pt_why_heading ) : ?>
    <h2><?php echo esc_html( $pt_why_heading ); ?></h2>
  <?php else : ?>
    <h2>Eight reasons it's <span class="fade">built differently.</span></h2>
  <?php endif; ?>
  <div class="wc-grid">
    <?php if ( $pt_has_rows( 'why_reasons' ) ) : ?>
      <?php while ( have_rows( 'why_reasons', $pt_pid ) ) : the_row(); ?>
        <div class="wc-card">
          <div class="ic"><img src="<?php echo esc_url( get_sub_field( 'icon' ) ); ?>" alt=""></div>
          <h3><?php echo esc_html( get_sub_field( 'title' ) ); ?></h3>
          <p><?php echo esc_html( get_sub_field( 'text' ) ); ?></p>
        </div>
      <?php endwhile; ?>
    <?php else : ?>
    <div class="wc-card">
      <div class="ic"><img src="https://www.projecttimber.com/wp-content/uploads/2020/02/Composite-Cladding-1.png" alt=""></div>
      <h3>Superior composite timber</h3>
      <p>LP Strongcore composite — the look of wood, none of the upkeep.</p>
    </div>
    <div class="wc-card">
      <div class="ic"><img src="https://www.projecttimber.com/wp-content/uploads/2020/04/Fully-Insulated.png" alt=""></div>
      <h3>Fully insulated</h3>
      <p>Walls, floor and roof — comfortable in every season.</p>
    </div>
    <div class="wc-card">
      <div class="ic"><img src="https://www.projecttimber.com/wp-content/uploads/2020/04/UPVC-Single-Door.png" alt=""></div>
      <h3>UPVC door</h3>
      <p>Domestic-standard door with multi-point locking.</p>
    </div>
    <div class="wc-card">
      <div class="ic"><img src="https://www.projecttimber.com/wp-content/uploads/2020/04/Wall-Thickness.png" alt=""></div>
      <h3>80mm total wall thickness</h3>
      <p>Doubled-up framing for strength where it works hardest.</p>
    </div>
    <div class="wc-card">
      <div class="ic"><img src="https://www.projecttimber.com/wp-content/uploads/2020/04/Insulated-Metal-Roof.png" alt=""></div>
      <h3>Insulated metal roof</h3>
      <p>Heavy-duty steel with a 40mm insulated core.</p>
    </div>
    <div class="wc-card">
      <div class="ic"><img src="https://www.projecttimber.com/wp-content/uploads/2017/10/Double-Glazing-Option.png" alt=""></div>
      <h3>Double glazing</h3>
      <p>Warmer, quieter and more secure, all year round.</p>
    </div>
    <div class="wc-card">
      <div class="ic"><img src="https://www.projecttimber.com/wp-content/uploads/2020/11/15-Year-Anti-rot-Guarantee.png" alt=""></div>
      <h3>15-year anti-rot guarantee*</h3>
      <p>Backed for the long term on composite.</p>
    </div>
    <div class="wc-card">
      <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.2 5.2l-6.2 6.2 2 2 6.2-6.2a4 4 0 0 0 5.2-5.2l-2.4 2.4-2-2z"/></svg></div>
      <h3>Easy self-assembly</h3>
      <p>Pre-insulated, pre-assembled panels.</p>
    </div>
    <?php endif; ?>
  </div>
</div></section>

<!-- ===================== INSULATION (3 + 4) ===================== -->
<section class="split" style="background:var(--mist)"><div class="wrap"><div class="grid">
  <div class="media"><img src="<?php echo esc_url( $pt_f( 'insulation_image', 'https://projecttimber.com/wp-content/uploads/2018/09/main_img4th.jpg' ) ); ?>" alt="<?php echo esc_attr( $pt_name ); ?>"></div>
  <div class="copy">
    <div class="eyebrow">Insulation</div>
    <?php $pt_ins_heading = get_field( 'insulation_heading', $pt_pid ); ?>
    <?php if ( $pt_ins_heading ) : ?>
      <h2><?php echo esc_html( $pt_ins_heading ); ?></h2>
    <?php else : ?>
      <h2>Warm in winter.<br><span class="fade">Cool in summer.</span></h2>
    <?php endif; ?>
    <?php $pt_ins_body = get_field( 'insulation_body', $pt_pid ); ?>
    <?php if ( $pt_ins_body ) : ?>
      <?php echo wp_kses_post( $pt_ins_body ); ?>
    <?php else : ?>
      <p>The <?php echo esc_html( $pt_line ); ?> is insulated in the walls, floor and roof with high-performance multi-foil. Its layered design traps heat in the cold months and reflects it in summer — achieving U-values comparable to 120mm of glass wool, in a far slimmer profile that leaves more room inside.</p>
      <p>The heavy-duty metal roof adds a 40mm insulated core with a steel outer shell and white-gloss interior — built to shrug off British weather, season after season.</p>
    <?php endif; ?>
    <div class="statrow">
      <?php if ( $pt_has_rows( 'insulation_stats' ) ) : ?>
        <?php while ( have_rows( 'insulation_stats', $pt_pid ) ) : the_row(); ?>
          <div><div class="n"><?php echo esc_html( get_sub_field( 'number' ) ); ?><span style="font-size:1rem"><?php echo esc_html( get_sub_field( 'unit' ) ); ?></span></div><div class="l"><?php echo esc_html( get_sub_field( 'label' ) ); ?></div></div>
        <?php endwhile; ?>
      <?php else : ?>
        <div><div class="n">120<span style="font-size:1rem">mm</span></div><div class="l">Glass-wool U-value equivalent</div></div>
        <div><div class="n">40<span style="font-size:1rem">mm</span></div><div class="l">Insulated metal roof core</div></div>
      <?php endif; ?>
    </div>
  </div>
</div></div></section>

<!-- ===================== COMPOSITE CLADDING (5) ===================== -->
<section class="immerse">
  <video class="clad-video" muted playsinline preload="auto">
    <source src="<?php echo esc_url( $pt_f( 'cladding_video_url', 'https://www.projecttimber.com/wp-content/uploads/2026/06/Composite_Cladding_Final.mp4' ) ); ?>" type="video/mp4">
  </video>
  <div class="panel">
    <div class="eyebrow" style="color:#fff;opacity:.7">Composite cladding</div>
    <h3><?php echo esc_html( $pt_f( 'cladding_heading', 'The look of timber, without the upkeep.' ) ); ?></h3>
    <?php $pt_clad_body = get_field( 'cladding_body', $pt_pid ); ?>
    <p><?php echo $pt_clad_body ? esc_html( $pt_clad_body ) : 'LP Strongcore composite is wood fibres bonded with high-quality resins — the natural character of wood with the strength and low maintenance of composite. It resists weather, moisture, fungal growth and decay, and gives the ' . esc_html( $pt_line ) . ' its modern anthracite finish.'; ?></p>
  </div>
</section>

<!-- ===================== BUILT TO LAST (6) ===================== -->
<section class="split"><div class="wrap"><div class="grid">
  <div class="media"><img src="<?php echo esc_url( $pt_f( 'built_image', 'https://www.projecttimber.com/wp-content/uploads/2026/07/factory_worker_insulation.webp' ) ); ?>" alt="<?php echo esc_attr( $pt_name ); ?>"></div>
  <div class="copy">
    <div class="eyebrow">Built to last · Made in Britain</div>
    <?php $pt_built_heading = get_field( 'built_heading', $pt_pid ); ?>
    <?php if ( $pt_built_heading ) : ?>
      <h2><?php echo esc_html( $pt_built_heading ); ?></h2>
    <?php else : ?>
      <h2>Engineered stronger <span class="fade">where it counts.</span></h2>
    <?php endif; ?>
    <?php $pt_built_body = get_field( 'built_body', $pt_pid ); ?>
    <?php if ( $pt_built_body ) : ?>
      <?php echo wp_kses_post( $pt_built_body ); ?>
    <?php else : ?>
      <p>The modular wall panels feature doubled-up timber framing at every join, adding strength exactly where the building works hardest — with an 80mm total wall thickness built to take daily use and weather in its stride.</p>
      <p>Each <?php echo esc_html( $pt_line ); ?> is hand-crafted at our Nottinghamshire workshop on the edge of Sherwood Forest, from hand-selected Scandinavian slow-grown timber, and quality-checked before it reaches your garden.</p>
    <?php endif; ?>
    <div class="statrow">
      <?php if ( $pt_has_rows( 'built_stats' ) ) : ?>
        <?php while ( have_rows( 'built_stats', $pt_pid ) ) : the_row(); ?>
          <div><div class="n"><?php echo esc_html( get_sub_field( 'number' ) ); ?><span style="font-size:1rem"><?php echo esc_html( get_sub_field( 'unit' ) ); ?></span></div><div class="l"><?php echo esc_html( get_sub_field( 'label' ) ); ?></div></div>
        <?php endwhile; ?>
      <?php else : ?>
        <div><div class="n">80<span style="font-size:1rem">mm</span></div><div class="l">Total wall thickness</div></div>
        <div><div class="n">50+</div><div class="l">Years' experience</div></div>
        <div><div class="n">UK</div><div class="l">Designed &amp; made</div></div>
      <?php endif; ?>
    </div>
  </div>
</div></div></section>

<!-- ===================== WE DO THE HARD WORK (7) ===================== -->
<section class="split rev" style="background:var(--paper)"><div class="wrap"><div class="grid">
  <div class="media"><img src="<?php echo esc_url( $pt_f( 'assembly_image', 'https://www.projecttimber.com/wp-content/uploads/2024/10/My-Den-Composite-Animated-building.gif' ) ); ?>" alt="<?php echo esc_attr( $pt_name ); ?>"></div>
  <div class="copy">
    <div class="eyebrow">Assembly</div>
    <?php $pt_asm_heading = get_field( 'assembly_heading', $pt_pid ); ?>
    <?php if ( $pt_asm_heading ) : ?>
      <h2><?php echo esc_html( $pt_asm_heading ); ?></h2>
    <?php else : ?>
      <h2>Most of the build is <span class="fade">already done.</span></h2>
    <?php endif; ?>
    <?php $pt_asm_body = get_field( 'assembly_body', $pt_pid ); ?>
    <?php if ( $pt_asm_body ) : ?>
      <?php echo wp_kses_post( $pt_asm_body ); ?>
    <?php else : ?>
      <p>Your <?php echo esc_html( $pt_line ); ?> arrives as pre-assembled, pre-insulated panels — designed to fit through a standard UK doorway and slot together with far fewer parts than a traditional log cabin. A quicker, simpler build, whether you do it yourself or bring in help.</p>
      <p>Every building includes clear, step-by-step illustrated instructions. We recommend two people with basic DIY know-how — or add our assembly service at checkout and we'll handle it for you.</p>
    <?php endif; ?>
    <a class="btn-primary" href="#configure" style="margin-top:6px">Add assembly at checkout <span class="a">→</span></a>
  </div>
</div></div></section>

<!-- ===================== MAKE IT YOURS (8) ===================== -->
<section class="why-choose" style="background:var(--mist)"><div class="wrap">
  <div class="eyebrow"><?php echo esc_html( $pt_f( 'mk_eyebrow', 'Make it yours' ) ); ?></div>
  <?php $pt_mk_heading = get_field( 'mk_heading', $pt_pid ); ?>
  <?php if ( $pt_mk_heading ) : ?>
    <h2><?php echo esc_html( $pt_mk_heading ); ?></h2>
  <?php else : ?>
    <h2>Configure it around <span class="fade">how you'll use it.</span></h2>
  <?php endif; ?>
  <div class="mk-grid">
    <?php if ( $pt_has_rows( 'mk_options' ) ) : ?>
      <?php
      while ( have_rows( 'mk_options', $pt_pid ) ) :
        the_row();
        $pt_mk_link = get_sub_field( 'link' );
        ?>
        <?php if ( $pt_mk_link ) : ?>
          <a class="mk-card" href="<?php echo esc_url( $pt_mk_link ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( get_sub_field( 'image' ) ); ?>" alt="<?php echo esc_attr( get_sub_field( 'title' ) ); ?>"><span class="play">▶</span><div class="txt"><h3><?php echo esc_html( get_sub_field( 'title' ) ); ?></h3><p><?php echo esc_html( get_sub_field( 'text' ) ); ?></p></div></a>
        <?php else : ?>
          <div class="mk-card"><img src="<?php echo esc_url( get_sub_field( 'image' ) ); ?>" alt="<?php echo esc_attr( get_sub_field( 'title' ) ); ?>"><div class="txt"><h3><?php echo esc_html( get_sub_field( 'title' ) ); ?></h3><p><?php echo esc_html( get_sub_field( 'text' ) ); ?></p></div></div>
        <?php endif; ?>
      <?php endwhile; ?>
    <?php else : ?>
    <div class="mk-card"><img src="https://www.projecttimber.com/wp-content/uploads/2026/06/Tongue-and-groove-Timber-Floor.png" alt="Tongue and groove timber floor"><div class="txt"><h3>Floor</h3><p>Upgrade to 19mm tongue-and-groove for extra support underfoot.</p></div></div>
    <div class="mk-card"><img src="https://www.projecttimber.com/wp-content/uploads/2026/06/Laminate-flooring.webp" alt="Laminate flooring"><div class="txt"><h3>Laminate flooring</h3><p>Trend Oak Grey or Summer Oak Brown for a finished, home-like feel.</p></div></div>
    <div class="mk-card"><img class="img1" src="https://www.projecttimber.com/wp-content/uploads/2026/06/My-Den-Composite-upvc-white-window.png" alt="UPVC white window"><img class="img2" src="https://www.projecttimber.com/wp-content/uploads/2026/06/My-Den-Composite-upvc-graphite-window.png" alt="UPVC graphite window"><div class="txt"><h3>Windows</h3><p>Double-glazed UPVC — choose white or graphite.</p></div></div>
    <div class="mk-card"><img src="https://www.projecttimber.com/wp-content/uploads/2026/06/UPVC_Door_options-1.webp" alt="UPVC door options"><div class="txt"><h3>Door</h3><p>UPVC single door — choose your colour and positioning.</p></div></div>
    <a class="mk-card" href="https://www.youtube.com/watch?v=37ugD8sF6qs" target="_blank" rel="noopener"><img src="https://www.projecttimber.com/wp-content/uploads/2026/07/DSC04911-scaled.jpg" alt="Paint and trim colour options"><span class="play">▶</span><div class="txt"><h3>Paint &amp; trim</h3><p>A range of colours — watch the colour options.</p></div></a>
    <div class="mk-card"><img src="https://www.projecttimber.com/wp-content/uploads/2026/06/assembly_myden_composite.webp" alt="Building assembly service"><div class="txt"><h3>Assembly service</h3><p>Prefer not to build it? Let our team do it for you.</p></div></div>
    <?php endif; ?>
  </div>
  <a class="btn-primary" href="#configure" style="margin-top:30px">Build &amp; price yours <span class="a">→</span></a>
</div></section>

<!-- ===================== WORK FROM HOME (9) ===================== -->
<section class="immerse">
  <img src="<?php echo esc_url( $pt_f( 'wfh_image', 'https://www.projecttimber.com/wp-content/uploads/2026/06/10x8_My_Den_Composite_Garden_Office_04.jpg' ) ); ?>" alt="<?php echo esc_attr( $pt_name ); ?>">
  <div class="panel">
    <div class="eyebrow" style="color:#fff;opacity:.7">Work from home</div>
    <h3><?php echo esc_html( $pt_f( 'wfh_heading', 'Your commute is now the garden path.' ) ); ?></h3>
    <?php $pt_wfh_body = get_field( 'wfh_body', $pt_pid ); ?>
    <p><?php echo $pt_wfh_body ? esc_html( $pt_wfh_body ) : 'Turn the bottom of your garden into a private, professional workspace — quiet, insulated and distraction-free. Just as at home as a gym, studio, hobby room or therapy space.'; ?></p>
  </div>
</section>

<!-- ===================== SPEC TABLE ===================== -->
<section class="specs" id="specs"><div class="wrap">
  <div class="sec-head" style="margin-bottom:18px"><h2>Specifications</h2></div>
  <div class="spec-controls">
    <div class="selset">
      <span class="lbl"><b>Select</b><span>Size</span></span>
      <div class="seg" id="sizeseg"><!-- size buttons rendered live from the product's sizes --></div>
    </div>
    <div class="selset">
      <span class="lbl"><b>Select</b><span>Measurements</span></span>
      <div class="seg" id="unitseg">
        <button class="on" data-unit="metric">Metric</button>
        <button data-unit="imperial">Imperial</button>
      </div>
    </div>
  </div>
  <div class="dim-soon">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 8.5l5-5 12 12-5 5z"/><path d="M8 8l1.8 1.8M11 5l1.8 1.8M14 8l1.8 1.8M5 11l1.8 1.8"/></svg>
    <div><b>Interactive measurements coming soon</b><p>A live, unit-aware dimension diagram is on the way. Full specifications are below.</p></div>
  </div>
  <!-- Values carrying data-spec are filled live from GET /products/{sizeId}/specs; data-dim marks a
       dimension cell (stored in cm, converted for the Imperial toggle). Cells without data-spec keep
       their authored value. The text below each cell is the fallback shown until live data loads. -->
  <div class="spec-cards">
    <div class="spec-card"><h3>Overall Dimensions</h3><div class="ul"></div><table>
      <tr><td>Overall Width</td><td data-spec="_specs_overall_width" data-dim="1">253 cm</td></tr>
      <tr><td>Overall Depth</td><td data-spec="_specs_overall_depth" data-dim="1">229.2 cm</td></tr>
      <tr><td>Total Wall Thickness <span class="q">(incl. insulation)</span></td><td data-spec="_specs_total_wall_thickness" data-dim="1">80 mm</td></tr>
      <tr><td>Width <span class="q">(internal)</span></td><td data-spec="_specs_width_internal" data-dim="1">229 cm</td></tr>
      <tr><td>Depth <span class="q">(internal)</span></td><td data-spec="_specs_depth_internal" data-dim="1">174.3 cm</td></tr>
    </table></div>
    <div class="spec-card"><h3>Eaves &amp; Ridge</h3><div class="ul"></div><table>
      <tr><td>Eaves Height <span class="q">(inc. floor)</span></td><td data-spec="_specs_eaves_height_inc_floor" data-dim="1">230.7 cm</td></tr>
      <tr><td>Eaves Height <span class="q">(excl. floor)</span></td><td data-spec="_specs_eaves_height_excl_floor" data-dim="1">218.2 cm</td></tr>
      <tr><td>Ridge Height <span class="q">(inc. floor)</span></td><td data-spec="_specs_ridge_height_inc_floor" data-dim="1">240.2 cm</td></tr>
      <tr><td>Ridge Height <span class="q">(excl. floor)</span></td><td data-spec="_specs_ridge_height_excl_floor" data-dim="1">227.7 cm</td></tr>
      <tr><td>Eaves Height <span class="q">(internal)</span></td><td data-spec="_specs_eaves_height_internal" data-dim="1">204.4 cm</td></tr>
      <tr><td>Ridge Height <span class="q">(internal)</span></td><td data-spec="_specs_ridge_height_internal" data-dim="1">212.4 cm</td></tr>
    </table></div>
    <div class="spec-card"><h3>Doors</h3><div class="ul"></div><table>
      <tr><td>Door Height</td><td data-spec="_specs_door_height" data-dim="1">180 cm</td></tr>
      <tr><td>Door Width</td><td data-spec="_specs_door_width" data-dim="1">73 cm</td></tr>
      <tr><td>Door Opening <span class="q">(H × W)</span></td><td data-spec="_specs_door_opening_size_w_x_h" data-dim="1">H180 × W73 cm</td></tr>
    </table></div>
    <div class="spec-card"><h3>Windows</h3><div class="ul"></div><table>
      <tr><td>Glazing Thickness</td><td data-spec="_specs_glazing_thickness">4–20–4 mm</td></tr>
      <tr><td>Frame Thickness <span class="q">(H × W)</span></td><td data-spec="_specs_frame_thickness_h_x_w" data-dim="1">2.7 × 4.4 cm</td></tr>
    </table></div>
    <div class="spec-card"><h3>Floor &amp; Base</h3><div class="ul"></div><table>
      <tr><td>Overall Floor Size <span class="q">(W × D)</span></td><td data-spec="_specs_overall_floor_size_w_x_d" data-dim="1">249 × 194.3 cm</td></tr>
      <tr><td>Base Size</td><td data-spec="_specs_base_size_w_x_d" data-dim="1">244 × 189.3 cm</td></tr>
    </table></div>
    <div class="spec-card"><h3>Materials</h3><div class="ul"></div><table>
      <tr><td>Cladding</td><td>9mm LP Strongcore composite</td></tr>
      <tr><td>Floor material</td><td data-spec="_specs_floor_material">Tongue &amp; groove</td></tr>
      <tr><td>Roof material</td><td data-spec="_specs_roof_material">Galvanised steel &amp; foam insulation</td></tr>
      <tr><td>Roof covering</td><td data-spec="_specs_roof_covering_material">Galvanised steel</td></tr>
      <tr><td>Glazing</td><td data-spec="_specs_glazing_material">Double glazing</td></tr>
    </table></div>
    <div class="spec-card"><h3>Features</h3><div class="ul"></div><table>
      <tr><td>Building type</td><td data-spec="_specs_shed_type">Garden room</td></tr>
      <tr><td>Windows</td><td data-spec="_specs_windows">4</td></tr>
      <tr><td>Roof style</td><td data-spec="_specs_roof_style">Pent</td></tr>
      <tr><td>Cladding style</td><td data-spec="_specs_cladding_style">Composite board</td></tr>
      <tr><td>Locking system</td><td data-spec="_specs_locking_system">UPVC multi-point</td></tr>
      <tr><td>U-value, metal roof</td><td data-spec="_u_values_of_metal_roof">0.49 W/(m²·K)</td></tr>
      <tr><td>U-value, walls &amp; floor</td><td data-spec="_u_values_of_walls_and_floor">0.31 W/(m²·K)</td></tr>
      <tr><td>Pre-assembled panels</td><td data-spec="_specs_pre_assembled_side_panels">Yes</td></tr>
      <tr><td>Interchangeable windows</td><td data-spec="_specs_interchangeable_windows">No</td></tr>
      <tr><td>Fixtures &amp; fittings</td><td data-spec="_specs_supplied_with_fixtures_and_fittings">Yes</td></tr>
    </table></div>
  </div>
  <p style="font-weight:300;font-size:.74rem;color:var(--txt-soft);margin-top:20px">Dimensions shown for the 8 × 6; imperial values are converted from metric. 15-year anti-rot guarantee on composite — annual treatment required; terms apply.</p>
</div></section>

<!-- ===================== FAQ (13) ===================== -->
<section class="faq" id="faq"><div class="wrap">
  <h2>Questions, <span class="fade">answered.</span></h2>
  <div class="faq-video" id="faqVideo">
    <button class="fv-play" type="button" aria-label="Play the <?php echo esc_attr( $pt_line ); ?> FAQ video">
      <img src="<?php echo esc_url( $pt_f( 'faq_video_image', 'https://www.projecttimber.com/wp-content/uploads/2020/04/faq-2.jpg' ) ); ?>" alt="<?php echo esc_attr( $pt_name ); ?> FAQ video">
      <span class="fv-btn"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></span>
    </button>
  </div>

  <?php if ( $pt_has_rows( 'faqs' ) ) : ?>
    <?php while ( have_rows( 'faqs', $pt_pid ) ) : the_row(); ?>
      <details class="faq-item"><summary><?php echo esc_html( get_sub_field( 'question' ) ); ?></summary><div class="ans"><?php echo wp_kses_post( get_sub_field( 'answer' ) ); ?></div></details>
    <?php endwhile; ?>
  <?php else : ?>
  <details class="faq-item"><summary>Do I need planning permission?</summary><div class="ans">Usually not. Our buildings are designed to stay under 2.5m in height and fall under "permitted development," so planning permission typically isn't required. Always check with your local authority if you're in a conservation area, national park or listed building, and note the building shouldn't be used as self-contained accommodation. We don't offer legal advice — please confirm with your local planning authority before ordering.</div></details>
  <details class="faq-item"><summary>Do I need a base?</summary><div class="ans">Yes — all garden buildings must be assembled on a solid, level foundation. Our purpose-made Eze Base provides a solid, level foundation with minimal groundwork; ask us to add it to your order. Assembling on an unsuitable foundation may invalidate your warranty.</div></details>
  <details class="faq-item"><summary>Is it difficult to assemble?</summary><div class="ans">No. The panels arrive pre-assembled and pre-insulated and fit through a standard doorway. We recommend two people with basic DIY tools, and every building comes with clear, illustrated instructions. Prefer not to build it? Add our assembly service at checkout.</div></details>
  <details class="faq-item"><summary>Will it stay warm enough to use in winter?</summary><div class="ans">Yes. The walls, floor and roof are fully insulated with multi-foil (U-value comparable to 120mm glass wool), and double glazing comes as standard — so it stays comfortable year-round.</div></details>
  <details class="faq-item"><summary>How is it delivered?</summary><div class="ans">Kerbside, by lorry or van. You choose a preferred delivery date at checkout and we do our best to meet it — at busier times we may need to agree an alternative date with you, and we'll keep you informed throughout. Please ensure there's clear access in front of your property on the delivery date.</div></details>
  <?php endif; ?>
</div></section>

<!-- ===================== TRUST ===================== -->
<section class="trust" id="trust"><div class="wrap">
  <span class="g">★ <?php echo esc_html( $pt_f( 'trust_guarantee', '15-year anti-rot guarantee' ) ); ?></span>
  <?php $pt_trust_heading = get_field( 'trust_heading', $pt_pid ); ?>
  <?php if ( $pt_trust_heading ) : ?>
    <h2><?php echo esc_html( $pt_trust_heading ); ?></h2>
  <?php else : ?>
    <h2>Bought with <span class="swipe">confidence.</span></h2>
  <?php endif; ?>
  <p class="lead" style="max-width:48ch;margin:8px auto 0"><?php echo esc_html( $pt_f( 'trust_lead', 'Free delivery to selected postcodes, and Made in Britain.' ) ); ?></p>
  <div class="reviews">
    <?php if ( $pt_has_rows( 'reviews' ) ) : ?>
      <?php
      while ( have_rows( 'reviews', $pt_pid ) ) :
        the_row();
        $pt_rating = (int) get_sub_field( 'rating' );
        if ( $pt_rating < 1 || $pt_rating > 5 ) {
          $pt_rating = 5;
        }
        ?>
        <?php $pt_rev_img = get_sub_field( 'image' ); ?>
        <div class="review"><?php if ( $pt_rev_img ) : ?><div class="rev-media"><img src="<?php echo esc_url( $pt_rev_img ); ?>" alt="Customer photo"></div><?php endif; ?><div class="stars"><?php echo str_repeat( '★', $pt_rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — literal glyph. ?></div><p><?php echo esc_html( get_sub_field( 'quote' ) ); ?></p><div class="by">— <?php echo esc_html( get_sub_field( 'author' ) ? get_sub_field( 'author' ) : 'Verified buyer' ); ?></div></div>
      <?php endwhile; ?>
    <?php else : ?>
    <div class="review"><div class="rev-media"><img src="https://www.projecttimber.com/wp-content/uploads/2020/04/My-Den-Customer-Image-1.jpg" alt="Customer photo"></div><div class="stars">★★★★★</div><p>"Genuinely warm in winter — I work in here every day now. Build quality is excellent."</p><div class="by">— Verified buyer</div></div>
    <div class="review"><div class="rev-media"><img src="https://www.projecttimber.com/wp-content/uploads/2020/04/My-Den-Customer-Image-2.jpg" alt="Customer photo"></div><div class="stars">★★★★★</div><p>"Panels went together far faster than I expected. The composite finish looks premium."</p><div class="by">— Verified buyer</div></div>
    <div class="review"><div class="rev-media"><img src="https://www.projecttimber.com/wp-content/uploads/2020/04/My_den_01-1.jpg" alt="Customer photo"></div><div class="stars">★★★★★</div><p>"From order to install was seamless. The team kept me informed the whole way."</p><div class="by">— Verified buyer</div></div>
    <?php endif; ?>
  </div>
</div></section>

<!-- ===================== FINAL CTA ===================== -->
<section class="final"><div class="wrap">
  <?php $pt_final_heading = get_field( 'final_heading', $pt_pid ); ?>
  <?php if ( $pt_final_heading ) : ?>
    <h2><?php echo wp_kses_post( $pt_final_heading ); ?></h2>
  <?php else : ?>
    <h2>Your office is<br>ready when you are.</h2>
  <?php endif; ?>
  <button class="go">Customise &amp; buy →</button>
</div></section>

<div class="buybar">
  <div class="p"><?php echo esc_html( $pt_from ); ?> <small>FREE DELIVERY*</small></div>
  <button class="go">Customise &amp; buy</button>
</div>

<?php
get_footer();
