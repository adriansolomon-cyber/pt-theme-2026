<?php
/**
 * Front page (homepage) — converted from projecttimber-home.html.
 *
 * Design is the source of truth: the markup below is reproduced verbatim from
 * the prototype. Only internal .html links are wired to WP/Woo URLs; the header,
 * footer, support widget and cart drawer come from get_header()/get_footer().
 * home.css / home.js are enqueued in functions.php (is_front_page()).
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_offices = esc_url( home_url( '/garden-offices/' ) );
// The "Shop Evolution" tier card links to the My Den Composite product page in the
// mockup. Set this to that product's permalink once known; defaults to the garden
// offices archive so the link is never broken.
$pt_myden = esc_url( home_url( '/garden-offices/' ) );

get_header();
?>

<!-- ===================== HERO ===================== -->
<header class="hero" id="main" tabindex="-1">
  <video id="heroVideo" autoplay muted loop playsinline preload="metadata" poster="https://www.projecttimber.com/wp-content/uploads/2026/06/8x6_My_Den_Composite_Garden_Office_01.jpg" aria-label="A Project Timber garden building in a landscaped garden">
    <source src="https://www.projecttimber.com/wp-content/uploads/2026/06/06291.mp4" type="video/mp4">
  </video>
  <button class="hero-vtoggle" id="heroVtoggle" type="button" aria-label="Pause background video"><svg class="ip" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg><svg class="ipl" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></button>
  <div class="scrim"></div>
  <div class="inner">
    <div class="eyebrow">British-made garden buildings</div>
    <h1>Room to work, rest and play — at the bottom of the garden.</h1>
    <p class="sub">Sheds, summerhouses, workshops and fully-insulated garden offices — designed and made in Britain. Built to last up to 25 years.</p>
    <div class="cta-row">
      <a class="btn-primary" href="#ranges">Shop the range <span class="a">→</span></a>
      <a class="btn-ghost" href="#explore">Find your building</a>
    </div>
  </div>
</header>

<!-- ===================== TRUST BAR ===================== -->
<div class="trustbar"><div class="wrap">
  <span class="ti"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg> Made in Britain</span>
  <span class="ti"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg> Up to 25-year guarantee*</span>
  <span class="ti"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg> Free delivery (selected postcodes)*</span>
</div></div>

<!-- ===================== SHOP BY RANGE ===================== -->
<section class="ranges" id="ranges"><div class="wrap">
  <div class="sec-head"><div class="eyebrow">Shop by building type</div><h2>Find your <span class="fade">space.</span></h2></div>
  <div class="range-grid">
    <a class="range-card" href="<?php echo $pt_offices; ?>">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/office_1x.webp" alt="Project Timber garden office" loading="lazy">
      <div class="glass"><h3>Garden Offices</h3><p class="d">Work from home, year-round.</p><div class="rfoot"><span class="rprice">From £3,972</span><span class="more">See more <span class="a">→</span></span></div></div>
    </a>
    <a class="range-card" href="https://www.projecttimber.com/summerhouses/">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/summerhouse_1x.webp" alt="Project Timber summerhouse" loading="lazy">
      <div class="glass"><h3>Summerhouses</h3><p class="d">Slow down, enjoy the garden.</p><div class="rfoot"><span class="rprice">From £903</span><span class="more">See more <span class="a">→</span></span></div></div>
    </a>
    <a class="range-card" href="https://www.projecttimber.com/garden-workshops/">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/grandmaster_workshop_1x.webp" alt="Project Timber garden workshop" loading="lazy">
      <div class="glass"><h3>Garden Workshops</h3><p class="d">Space for every project.</p><div class="rfoot"><span class="rprice">From £1,198</span><span class="more">See more <span class="a">→</span></span></div></div>
    </a>
    <a class="range-card" href="https://www.projecttimber.com/garden-sheds/">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/shed_1x.webp" alt="Project Timber garden shed" loading="lazy">
      <div class="glass"><h3>Garden Sheds</h3><p class="d">Tough, secure storage.</p><div class="rfoot"><span class="rprice">From £668</span><span class="more">See more <span class="a">→</span></span></div></div>
    </a>
    <a class="range-card" href="https://www.projecttimber.com/insulated-garden-buildings/">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/insulated_garden_building_1x.webp" alt="Project Timber insulated garden building" loading="lazy">
      <div class="glass"><h3>Insulated Garden Buildings</h3><p class="d">Comfort in every season.</p><div class="rfoot"><span class="rprice">From £2,446</span><span class="more">See more <span class="a">→</span></span></div></div>
    </a>
    <a class="range-card" href="https://www.projecttimber.com/greenhouses/">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/greenhouse_1x.webp" alt="Project Timber greenhouse" loading="lazy">
      <div class="glass"><h3>Greenhouses</h3><p class="d">Grow more, all year round.</p><div class="rfoot"><span class="rprice">From £897</span><span class="more">See more <span class="a">→</span></span></div></div>
    </a>
  </div>
</div></section>

<!-- ===================== WHY PROJECT TIMBER ===================== -->
<section><div class="wrap">
  <div class="sec-head"><div class="eyebrow">Why Project Timber</div><h2>Built better, <span class="fade">where it matters.</span></h2></div>
  <div class="whyb-grid">
    <div class="whyb-cell whyb-photo whyb-a">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/modular_panels_2.webp" alt="Modular timber panels being slotted together" loading="lazy">
      <div class="in"><div class="lab">Fast &amp; DIY-friendly</div><h3>Goes together like flat-pack</h3><p>Modular panels, fewer parts, clear step-by-step instructions.</p></div>
    </div>
    <div class="whyb-cell whyb-dark whyb-b">
      <span class="wb-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9.5V13l2.5 1.8"/><path d="M9 2h6"/></svg></span>
      <div class="wb-txt"><div class="lab">Build Time</div><div class="big">Fewer parts.<br>Less time.</div><p>Designed to go up over a weekend — or add assembly on the insulated range.</p></div>
    </div>
    <div class="whyb-cell whyb-photo whyb-c">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/insulated_wall.webp" alt="Cutaway of an insulated wall panel" loading="lazy">
      <div class="in"><div class="lab">All-season</div><h3>Insulation built in</h3><p>Warm walls, floor &amp; roof, year-round.</p></div>
    </div>
    <div class="whyb-cell whyb-light whyb-feat">
      <span class="wb-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6z"/><path d="M12 8l2.3 3.4H9.7zM12 10.8l1.7 2.6h-3.4zM12 13.8v1.6"/></svg></span>
      <div class="wb-txt"><div class="lab">Durability</div><h3>Pressure-treated</h3><p>Protected against rot, as standard.</p></div>
    </div>
    <div class="whyb-cell whyb-light whyb-feat">
      <span class="wb-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V9l8-5 8 5v11"/><path d="M4 20h16M8 20v-6.5M12 20v-8.5M16 20v-6.5"/></svg></span>
      <div class="wb-txt"><div class="lab">Strength</div><h3>Doubled-up framing</h3><p>Extra strength at every joint.</p></div>
    </div>
  </div>
</div></section>

<!-- ===================== EXPLORE (interactive, season-aware) ===================== -->
<section class="explore" id="explore"><div class="wrap">
  <div class="sec-head"><div class="eyebrow">Find your fit</div><h2>What are you <span class="fade">looking for?</span></h2></div>
  <div class="exp-tabs" role="tablist" aria-label="Building type">
    <button class="exp-tab" role="tab" id="tab-office" aria-controls="panel-office" data-panel="office" type="button">Garden office</button>
    <button class="exp-tab" role="tab" id="tab-summerhouse" aria-controls="panel-summerhouse" data-panel="summerhouse" type="button">Summerhouse</button>
    <button class="exp-tab" role="tab" id="tab-workshop" aria-controls="panel-workshop" data-panel="workshop" type="button">Workshop</button>
    <button class="exp-tab" role="tab" id="tab-storage" aria-controls="panel-storage" data-panel="storage" type="button">Storage</button>
  </div>
</div>

  <!-- OFFICE -->
  <div class="exp-panel" id="panel-office" role="tabpanel" aria-labelledby="tab-office">
    <section class="immerse" style="padding:0">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/garden-office-fullwidth3.webp" alt="The insulated My Den Composite garden office interior" loading="lazy">
      <div class="panel">
        <div class="eyebrow" style="color:#fff;opacity:.7">Fully insulated · all year round</div>
        <h2>A garden office you'll actually use in January.</h2>
        <p>The My Den and Evolution ranges are insulated in the walls, floor and roof, with composite cladding and double glazing as standard — pre-assembled panels that go up in days.</p>
        <a class="btn-primary" href="<?php echo $pt_offices; ?>">Explore garden offices <span class="a">→</span></a>
      </div>
    </section>
    <section class="uses"><div class="wrap">
      <div class="sec-head"><div class="eyebrow">A garden office, your way</div><h2>What will <span class="fade">yours be?</span></h2></div>
      <div class="use-grid">
        <a class="use-tile" href="<?php echo $pt_offices; ?>"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/my-den-composite-office.webp" alt="A My Den garden office used as a home office" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="11" rx="1"/><path d="M8 20h8M12 16v4"/></svg> Home office</h3><p>Quiet, insulated and distraction-free.</p></a>
        <a class="use-tile" href="<?php echo $pt_offices; ?>"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/my-den-composite-gym.webp" alt="A My Den garden office used as a gym" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9v6M20 9v6M7 7v10M17 7v10M7 12h10"/></svg> Gym</h3><p>Train, steps from the house.</p></a>
        <a class="use-tile" href="<?php echo $pt_offices; ?>"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/my-den-composite-studio.webp" alt="A My Den garden office used as a studio" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.5-.9 1-1.7-.6-1 .1-2.3 1.3-2.3H17a4 4 0 0 0 4-4c0-5-4-8-9-8z"/><circle cx="8" cy="10" r="1" fill="#3B333D" stroke="none"/><circle cx="12" cy="7.5" r="1" fill="#3B333D" stroke="none"/><circle cx="16" cy="10" r="1" fill="#3B333D" stroke="none"/></svg> Studio</h3><p>Create or practise in peace.</p></a>
        <a class="use-tile" href="<?php echo $pt_offices; ?>"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/my-den-composite-salon.webp" alt="A My Den garden office used as a hobby room" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.5-7-9a4 4 0 0 1 7-2.6A4 4 0 0 1 19 12c0 4.5-7 9-7 9z"/></svg> Hobby room</h3><p>A dedicated space for what you love.</p></a>
        <a class="use-tile" href="<?php echo $pt_offices; ?>"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/my-den-composite-music-room-ai-2.webp" alt="A My Den garden office used as a music room" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l10-2v13"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="16" r="2"/></svg> Music room</h3><p>Space to play without the neighbours.</p></a>
        <a class="use-tile" href="<?php echo $pt_offices; ?>"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/my-den-composite-retreat.webp" alt="A My Den garden office used as a garden retreat" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4"/></svg> Garden retreat</h3><p>Somewhere to simply switch off.</p></a>
      </div>
    </div></section>
  </div>

  <!-- SUMMERHOUSE -->
  <div class="exp-panel" id="panel-summerhouse" role="tabpanel" aria-labelledby="tab-summerhouse" hidden>
    <section class="immerse" style="padding:0">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/GM_Diplomat_LongWindow_Interior.webp" alt="A Grandmaster garden summerhouse" loading="lazy">
      <div class="panel">
        <div class="eyebrow" style="color:#fff;opacity:.7">Grandmaster · summerhouses</div>
        <h2>Somewhere to slow down.</h2>
        <p>A classic Grandmaster summerhouse to enjoy the garden in every season — premium pressure-treated timber, quality fixings and a 25-year anti-rot guarantee.*</p>
        <a class="btn-primary" href="https://www.projecttimber.com/summerhouses/">Explore summerhouses <span class="a">→</span></a>
      </div>
    </section>
    <section class="uses"><div class="wrap">
      <div class="sec-head"><div class="eyebrow">Your garden escape</div><h2>How will <span class="fade">you unwind?</span></h2></div>
      <div class="use-grid">
        <a class="use-tile" href="https://www.projecttimber.com/summerhouses/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/leisure_spot.webp" alt="A summerhouse set up as a garden lounge" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="6" rx="2"/><path d="M5 11V9a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2M6 17v2M18 17v2"/></svg> Garden lounge</h3><p>Sofas, a view and somewhere to relax.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/summerhouses/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/reading_nook.webp" alt="A summerhouse set up as a reading nook" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 0 1 2-2h12v15H6a2 2 0 0 0-2 2z"/><path d="M4 18a2 2 0 0 1 2-2h12"/></svg> Reading nook</h3><p>A quiet corner and a good book.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/summerhouses/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/bar.webp" alt="A summerhouse set up for entertaining with a bar" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h14l-6 8v6M9 18h8"/></svg> Entertaining &amp; bar</h3><p>Drinks, friends and long summer evenings.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/summerhouses/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/family-room.webp" alt="A summerhouse used as a family room" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l9-7 9 7v9a1 1 0 0 1-1 1h-4v-6H8v6H4a1 1 0 0 1-1-1z"/></svg> Family room</h3><p>Room for everyone to spread out.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/summerhouses/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/kids_room.webp" alt="A summerhouse used as a kids' den" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.5 5 5.5.5-4 3.8 1.2 5.4L12 20l-5.2 2.7L8 17.3l-4-3.8 5.5-.5z"/></svg> Kids' den</h3><p>A playroom that keeps the toys outside.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/summerhouses/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/dining.webp" alt="A summerhouse set up for al-fresco dining" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3v8M5 3v4a2 2 0 0 0 4 0V3M17 3c-1.4 0-2 1.8-2 4s.6 4 2 4v6"/></svg> Al-fresco dining</h3><p>Shelter for meals whatever the weather.</p></a>
      </div>
    </div></section>
  </div>

  <!-- WORKSHOP -->
  <div class="exp-panel" id="panel-workshop" role="tabpanel" aria-labelledby="tab-workshop" hidden>
    <section class="immerse" style="padding:0">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/workshop_2_1.webp" alt="Inside a Grandmaster garden workshop" loading="lazy">
      <div class="panel">
        <div class="eyebrow" style="color:#fff;opacity:.7">Grandmaster · heavy-duty</div>
        <h2>Room to build, fix and make.</h2>
        <p>The Grandmaster Pent Workshop is our heavy-duty range — thicker framing, premium fixings and a 25-year anti-rot guarantee.* Space and daylight for tools, projects and everything in between.</p>
        <a class="btn-primary" href="https://www.projecttimber.com/garden-workshops/">Explore workshops <span class="a">→</span></a>
      </div>
    </section>
    <section class="uses"><div class="wrap">
      <div class="sec-head"><div class="eyebrow">A workshop, your way</div><h2>What will <span class="fade">yours build?</span></h2></div>
      <div class="use-grid">
        <a class="use-tile" href="https://www.projecttimber.com/garden-workshops/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/woodworking_1_1x.webp" alt="A garden workshop set up for woodworking" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l4 4-9 9-4 1 1-4z"/><path d="M14 6l3-3 4 4-3 3"/></svg> Woodworking</h3><p>A proper bench and room for the tools.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-workshops/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/diy_and_repairs_1_1x.webp" alt="A garden workshop used for DIY and repairs" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a3.5 3.5 0 0 0-4.6 4.6l-6 6 1.7 1.7 6-6a3.5 3.5 0 0 0 4.6-4.6l-2.1 2.1-1.7-1.7z"/></svg> DIY &amp; repairs</h3><p>Somewhere to fix, tinker and get it done.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-workshops/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/tool_and_bike_store_1_1x.webp" alt="A garden workshop used as a tool and bike store" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="7" width="16" height="13" rx="1.5"/><path d="M8 7V5h8v2M9 12h6"/></svg> Tool &amp; bike store</h3><p>Secure, dry and easy to get to.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-workshops/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/hobby_making_1_1x.webp" alt="A garden workshop used for hobby making" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.5-.9 1-1.7-.6-1 .1-2.3 1.3-2.3H17a4 4 0 0 0 4-4c0-5-4-8-9-8z"/><circle cx="8" cy="10" r="1" fill="#3B333D" stroke="none"/><circle cx="12" cy="7.5" r="1" fill="#3B333D" stroke="none"/><circle cx="16" cy="10" r="1" fill="#3B333D" stroke="none"/></svg> Hobby making</h3><p>Model-making, crafts, restoration and more.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-workshops/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/GM_Pent_Workshop_Interior.webp" alt="A garden workshop used as a home business base" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg> Home business</h3><p>Trade base, studio or workshop from home.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-workshops/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/garden_machinery_1_1x.webp" alt="A garden workshop used to store garden machinery" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.2"/><path d="M12 4v2M12 18v2M4 12h2M18 12h2M6.3 6.3l1.4 1.4M16.3 16.3l1.4 1.4M17.7 6.3l-1.4 1.4M7.7 16.3l-1.4 1.4"/></svg> Garden machinery</h3><p>Mowers, trimmers and kit, kept sheltered.</p></a>
      </div>
    </div></section>
  </div>

  <!-- STORAGE -->
  <div class="exp-panel" id="panel-storage" role="tabpanel" aria-labelledby="tab-storage" hidden>
    <section class="immerse" style="padding:0">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/garden-shed-2.webp" alt="A Hobbyist garden shed" loading="lazy">
      <div class="panel">
        <div class="eyebrow" style="color:#fff;opacity:.7">Hobbyist · dependable</div>
        <h2>Room for everything else.</h2>
        <p>Tough garden sheds to keep tools, toys, furniture and everything else dry and out of the way — pressure-treated timber with a 25-year anti-rot guarantee.*</p>
        <a class="btn-primary" href="https://www.projecttimber.com/garden-sheds/">Explore sheds <span class="a">→</span></a>
      </div>
    </section>
    <section class="uses"><div class="wrap">
      <div class="sec-head"><div class="eyebrow">Sorted &amp; stored</div><h2>What will <span class="fade">yours hold?</span></h2></div>
      <div class="use-grid">
        <a class="use-tile" href="https://www.projecttimber.com/garden-sheds/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/gardening-tools.webp" alt="A garden shed storing gardening tools" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a3.5 3.5 0 0 0-4.6 4.6l-6 6 1.7 1.7 6-6a3.5 3.5 0 0 0 4.6-4.6l-2.1 2.1-1.7-1.7z"/></svg> Garden tools</h3><p>Spades, forks and everything in between.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-sheds/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/outdoor-toys.webp" alt="A garden shed storing outdoor toys" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18"/></svg> Outdoor toys</h3><p>Scooters, sandpit toys and garden games, tidied away out of the rain.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-sheds/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/seasonal-storage.webp" alt="A garden shed used for seasonal storage" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="1"/><path d="M3 11h18M9 7V5h6v2"/></svg> Seasonal storage</h3><p>Furniture and decorations, out of season.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-sheds/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/binandlogstore.webp" alt="A bin and log store" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9l8-4 8 4v10H4z"/><path d="M8 19v-6h8v6"/></svg> Bin &amp; log store</h3><p>Tidy the bins, keep the logs dry.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-sheds/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/furniture-store.webp" alt="A garden shed storing furniture" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="6" rx="2"/><path d="M5 11V9a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2M6 17v2M18 17v2"/></svg> Furniture store</h3><p>Room for the things without a home.</p></a>
        <a class="use-tile" href="https://www.projecttimber.com/garden-sheds/"><img class="uimg" src="https://www.projecttimber.com/wp-content/uploads/2026/07/overflow-storage.webp" alt="A garden shed used for overflow storage" loading="lazy"><h3><svg viewBox="0 0 24 24" fill="none" stroke="#3B333D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="7" width="16" height="13" rx="1.5"/><path d="M8 7V5h8v2M9 12h6"/></svg> Overflow storage</h3><p>Free up the garage and the loft.</p></a>
      </div>
    </div></section>
  </div>
</section>

<!-- ===================== RANGES (good/better/best) ===================== -->
<section class="tiers"><div class="wrap">
  <div class="sec-head"><div class="eyebrow">A range for every garden</div><h2>Pick your <span class="fade">range.</span></h2></div>
  <div class="rb-grid">
    <a class="rb-card rb-feat" href="https://www.projecttimber.com/grandmaster/">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/Grandmaster.webp" alt="Grandmaster range" loading="lazy">
      <div class="rb-in"><span class="tag y">Premium · 10% off GM10</span><h3>Grandmaster</h3><p>Our heavy-duty pressure-treated range — workshops, summerhouses and cabins built to last.</p><div class="rfoot"><span class="rprice">From £1,198</span><span class="shop">Shop Grandmaster <span class="a">→</span></span></div></div>
    </a>
    <a class="rb-card" href="https://www.projecttimber.com/garden-sheds/">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/hobbyistrange.webp" alt="Hobbyist range" loading="lazy">
      <div class="rb-in"><span class="tag">Entry</span><h3>Hobbyist</h3><p>Quality sheds, summerhouses &amp; greenhouses at accessible prices.</p><div class="rfoot"><span class="rprice">From £668</span><span class="shop">Shop Hobbyist <span class="a">→</span></span></div></div>
    </a>
    <a class="rb-card" href="<?php echo $pt_myden; ?>">
      <img src="https://www.projecttimber.com/wp-content/uploads/2026/07/Insulated-garden-buildings.webp" alt="Evolution range" loading="lazy">
      <div class="rb-in"><span class="tag">Insulated</span><h3>Evolution · My Den</h3><p>Fully-insulated, all-season garden offices &amp; rooms.</p><div class="rfoot"><span class="rprice">From £3,972</span><span class="shop">Shop Evolution <span class="a">→</span></span></div></div>
    </a>
  </div>
</div></section>

<!-- ===================== MADE IN BRITAIN ===================== -->
<section class="split rev" style="background:var(--paper)"><div class="wrap"><div class="grid">
  <div class="media"><img src="https://www.projecttimber.com/wp-content/uploads/2026/07/factory_worker_insulation.webp" alt="A worker fitting insulation in Project Timber's Nottinghamshire workshop" loading="lazy"></div>
  <div class="copy">
    <div class="eyebrow">Designed &amp; made in the UK</div>
    <h2>Hand-crafted on the edge of <span class="fade">Sherwood Forest.</span></h2>
    <p>Every building is made at our Nottinghamshire workshop from hand-selected, slow-grown timber and quality-checked before it leaves. Five decades of know-how in every joint.</p>
    <div class="statrow">
      <div><div class="n">50+</div><div class="l">Years' experience</div></div>
      <div><div class="n">UK</div><div class="l">Designed &amp; made</div></div>
      <div><div class="n">25yr</div><div class="l">Anti-rot guarantee*</div></div>
    </div>
  </div>
</div></div></section>

<!-- ===================== SHOWSITE VISIT ===================== -->
<section class="showsite"><div class="wrap">
  <div class="ss-grid">
    <div class="ss-media">
      <div class="ss-ph ss-ph-a"><img src="https://www.projecttimber.com/wp-content/uploads/2026/07/showsite-buildings-2.webp" alt="Project Timber showsite display buildings" loading="lazy"></div>
      <div class="ss-ph ss-ph-b"><img src="https://www.projecttimber.com/wp-content/uploads/2026/07/Showsite_Interior-scaled.webp" alt="Inside a Project Timber display building" loading="lazy"></div>
      <div class="ss-ph ss-ph-c"><img src="https://www.projecttimber.com/wp-content/uploads/2026/07/showsite_evo_cedar.webp" alt="An Evolution cedar garden building at the showsite" loading="lazy"></div>
    </div>
    <div class="ss-copy">
      <div class="eyebrow">Visit us</div>
      <h2>See it for real. <span class="fade">Book a showsite visit.</span></h2>
      <p>Walk around our display buildings, see the quality up close and talk your options through with the team — no pressure, no obligation. Pick a slot that suits you.</p>
      <ul class="ss-points">
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Step inside our display buildings, inside and out</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> See the timber, insulation and finish up close</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Talk sizes, options and delivery through with the team</li>
      </ul>
      <button class="btn-primary ss-open" type="button">Book a showsite visit <span class="a">→</span></button>
      <p class="ss-note">Nottinghamshire showsite · open Mon–Sat</p>
    </div>
  </div>
</div></section>

<!-- booking modal -->
<div class="book" id="book" aria-hidden="true">
  <div class="book-back" data-close></div>
  <div class="book-panel" role="dialog" aria-modal="true" aria-labelledby="book-ttl">
    <button class="book-x" type="button" aria-label="Close" data-close>&times;</button>
    <div class="book-head">
      <div class="eyebrow">Book a visit</div>
      <h3 id="book-ttl">Choose a date &amp; time</h3>
      <p>Pick a slot for your showsite visit and we'll confirm the details by email.</p>
    </div>
    <div class="book-body">
      <div class="cal">
        <div class="cal-head">
          <button class="cal-nav" type="button" data-prev aria-label="Previous month">&lsaquo;</button>
          <div class="cal-title" id="calTitle"></div>
          <button class="cal-nav" type="button" data-next aria-label="Next month">&rsaquo;</button>
        </div>
        <div class="cal-dow"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
        <div class="cal-grid" id="calGrid"></div>
      </div>
      <div class="slots" id="slots" hidden>
        <div class="slots-lbl">Available times · <b id="slotDate"></b></div>
        <div class="slot-list" id="slotList"></div>
      </div>
      <form class="book-form" id="bookForm" hidden>
        <div class="book-sum" id="bookSum"></div>
        <label>Name<input type="text" name="name" required autocomplete="name"></label>
        <label>Email<input type="email" name="email" required autocomplete="email"></label>
        <label>Phone<input type="tel" name="phone" autocomplete="tel"></label>
        <button class="btn-primary" type="submit">Confirm booking <span class="a">→</span></button>
      </form>
      <div class="book-done" id="bookDone" hidden>
        <div class="tick">&checkmark;</div>
        <h3>Visit requested</h3>
        <p id="doneMsg">Thanks — we'll email you to confirm your showsite visit.</p>
        <button class="btn-primary" type="button" data-close>Done</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== REVIEWS ===================== -->
<section class="trust"><div class="wrap">
  <span class="g">★ Loved across Britain</span>
  <h2>Loved by gardens <span class="swipe">across Britain.</span></h2>
  <div class="reviews">
    <div class="review"><div class="stars">★★★★★</div><p>"Genuinely warm in winter — I work in mine every day now."</p><div class="by">— Verified buyer</div></div>
    <div class="review"><div class="stars">★★★★★</div><p>"Went together far faster than I expected, and the finish looks premium."</p><div class="by">— Verified buyer</div></div>
    <div class="review"><div class="stars">★★★★★</div><p>"From order to install was seamless. Kept informed the whole way."</p><div class="by">— Verified buyer</div></div>
  </div>
</div></section>

<!-- ===================== CALLBACK ===================== -->
<section class="callback"><div class="wrap">
  <h2>Talk it through with a real person.</h2>
  <p>Our friendly sales team can help you choose the right building, size and options. Lines open Mon–Fri until 7pm.</p>
  <div class="row">
    <a class="btn-primary" href="#">Request a callback <span class="a">→</span></a>
    <a class="phone" href="tel:01777553392">01777 553392</a>
  </div>
</div></section>

<!-- ===================== FAQ ===================== -->
<section class="faq"><div class="wrap">
  <h2>Questions, <span class="fade">answered.</span></h2>
  <details class="faq-item"><summary>Do I need planning permission?</summary><div class="ans">Usually not — our buildings are designed to stay under 2.5m and fall under "permitted development." Always check with your local authority for conservation areas, national parks or listed buildings.</div></details>
  <details class="faq-item"><summary>Do I need a base?</summary><div class="ans">Yes — a solid, level base. Our purpose-made Eze Base makes it simple, with minimal groundwork.</div></details>
  <details class="faq-item"><summary>Can I get it built for me?</summary><div class="ans">On our insulated Evolution range, yes — add the assembly service at checkout and we'll build it for you. Our other ranges are self-build, with clear step-by-step instructions included.</div></details>
  <details class="faq-item"><summary>How does delivery work?</summary><div class="ans">You choose a preferred delivery date at checkout and we do our best to meet it. At busier times we may need to agree an alternative date with you — we'll always keep you informed.</div></details>
  <!-- FINANCE HIDDEN (re-enable when a finance provider is in place):
  <details class="faq-item"><summary>Is finance available?</summary><div class="ans">Yes — spread the cost at checkout. Representative terms are shown before you buy.</div></details>
  -->
</div></section>

<!-- ===================== FINAL CTA ===================== -->
<section class="final"><div class="wrap">
  <h2>Your garden has<br>more to give.</h2>
  <a class="btn-primary" href="#ranges">Shop the range <span class="a">→</span></a>
  <p class="note">Free delivery (selected postcodes)* · up to 25-year guarantee*</p>
</div></section>

<?php
get_footer();
