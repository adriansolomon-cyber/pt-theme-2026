# Grandmaster Affiliate Landing Page — Rework Design

Date: 2026-08-19
File: `design-files/affiliate-marketing-pages/grandmaster_homepage.html`

## Purpose

This is a standalone, single-file HTML advertorial/brochure page intended to live on a
separate "strategic domain" (not projecttimber.com). Its only job is to build enough
trust and desire that a visitor clicks through to the real Project Timber site
(`projecttimber.com`) to purchase. It does not transact anything itself.

The current file is a polished design mockup with fabricated brand details (wrong
phone number, wrong company name in the footer, fictional products/bundles, fictional
Trustpilot score) that need to be replaced with facts pulled from the live
projecttimber.com site and this repo's WordPress theme, while keeping the page's own
"Grandmaster Garden Buildings" advertorial identity.

## Decisions

1. **Brand framing:** Keep the page presenting as its own advertorial brand
   ("Grandmaster Garden Buildings" — own nav/logo feel), not an overt "Project Timber
   presents…" spin-off. Only the facts change, not the framing device.
2. **CTA behavior:** Every CTA (nav button, product "Explore →", band CTA, "Configure
   yours →", final CTA, footer links) becomes an outbound link to a real
   projecttimber.com URL, tagged with UTM params for attribution
   (`utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10`). No
   phone/`tel:` links or call-to-action copy — there is no real call center behind
   this advertorial identity.
3. **Bundles → "Make it yours":** The current "Built-For-You Bundles" section
   (fictional pre-packaged "Weather Protection Pack" etc. with "Add to bundle"
   buttons) does not reflect how Project Timber actually sells. Replace it with a
   content section presenting the **real per-product configurator options** (Wall
   Thickness 11mm/16mm, Roof Cover, Guttering, Paint Colour, Colour Trim, Base) as
   value-prop cards, each with a "Configure yours →" CTA linking to a real product
   page. No fake transactional buttons.
4. **Section order (reordered for a redirect-only conversion funnel):**
   Hero → Promise bar → Use Cases → Why/Specs → Products → Reviews → Parallax band →
   Make it yours → FAQ → Final CTA → Footer.
   - Use Cases moved early: it's a "who is this for" self-identification section: works
     better right after the hero than buried after Why/Specs.
   - Reviews moved up to directly after Products: social proof lands right where a
     visitor is deciding whether to click a specific product.
   - FAQ stays immediately before the Final CTA (handle objections, then ask).
   - All sections are kept — none are cut. See conversation rationale: Promise bar
     (skimmable teaser) vs Why/Specs (deep dive) are complementary; Make it yours vs
     Why/Specs is "what you can add" vs "what you always get"; Use Cases vs Products
     is persona-first vs SKU-first.
5. **No fabricated stats:** Drop the invented "4.9" Trustpilot score (unverifiable).
   Use qualitative trust language ("Rated Excellent on Trustpilot") and pull 2–3 real
   testimonials (real names/dates) from `templates/page-testimonials.php`.
6. **SEO duplicate-content protection (page stays indexable):** This bridge page is
   meant to earn its own organic traffic, so it must NOT be `noindex`'d — that would
   defeat the point of the strategic domain. The duplicate-content risk (verbatim
   text competing with projecttimber.com's own rankings) is mitigated without
   sacrificing indexability:
   - **Unique, substantial copy** — the page keeps its own advertorial/buying-guide
     narrative and voice throughout (hero copy, section intros, product blurbs, FAQ
     answers). Only hard facts are shared with the real site (names, prices, specs,
     links) — never paragraph-length descriptions copied verbatim from
     projecttimber.com product pages.
   - **Distinct `<title>`/meta description** targeting different, broader/top-of-
     funnel search intent (e.g. "which Grandmaster building is right for you" /
     buying-guide framing) rather than mirroring any real product or category page's
     exact SEO title — so the two domains aren't competing for identical queries.
   - **Normal dofollow outbound links** to projecttimber.com — this is one company
     linking to its own other property, not a manipulative scheme, so no
     `rel="nofollow"` needed.
   - Skip a cross-domain `rel=canonical` to the real pages — that signal fits
     near-identical content, and this page's design/framing/purpose (guide vs.
     transactional product page) is intentionally distinct.
7. **Technical spec numbers stay as originally written.** The real per-product specs
   API was checked (`wc/v3/products/9216/specs`) and contradicts some of the
   mockup's universal claims for that specific product/size (real: 27×44mm frame,
   "Level Handle" lock, glazing listed as "Optional" — not the mockup's 54mm
   studwork/3-point lock/DG-standard). Per explicit user decision, the technical
   spec numbers in the Why/Specs section and hero/promise-bar trust items are **kept
   as originally written** — not rewritten to the verified-only claims. The
   uniqueness/duplication effort instead goes into product **titles and
   descriptions**: featured products keep short in-house nicknames ("Grandmaster
   Diplomat", "Grandmaster Workshop", etc.) rather than the real page's full SEO
   title, and all blurb copy stays original, not copied from real product
   descriptions.

## Real data to use (verified against the live site and this repo)

- **Real phone (not used on page per decision #2, but noted for reference):**
  01777 553392.
- **Real company / footer legal text:** Project Timber Limited, registered in
  England and Wales, company no. 05126131. Registered office: Parry Works,
  Grassthorpe Road, Sutton-on-Trent, Newark NG23 6QX. (Replaces the mockup's
  fictional "Kybotech Ltd".)
- **Real promo:** 10% off Grandmaster range, code `GM10` (live sitewide banner in
  `header.php` / `partials/header.html`).
- **Real category page:** `https://www.projecttimber.com/grandmaster/`
- **Real featured products** (name / full price / GM10 price / product URL):
  | Product | From | With GM10 | URL |
  |---|---|---|---|
  | Diplomat Classic G1000 Summerhouse | £1,631 | £1,468 | `/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/` |
  | Heavy Duty D1000 Apex Workshop Shed | £1,549 | £1,394 | `/grandmaster/pressure-treated-traditional-windowed-apex-grandmaster-workshop/` |
  | Lounge Contemporary G1000 Garden Room | £2,225 | £2,003 | `/grandmaster/pressure-treated-lounge-contemporary-summerhouse/` |
  | Clubhouse G1000 Summerhouse | £1,549 | £1,394 | `/grandmaster/pressure-treated-club-house-garden-room-french-doors/` |
  | Fusion Classic G1000 Summerhouse with Side Shed | £2,127 | £1,914 | `/grandmaster/grandmaster-fusion-classic-g1000-summerhouse-with-side-shed-16mm-pressure-treated-tongue-and-groove/` |
  | Alpine G1000 Summerhouse Cabin | £4,956 | £4,460 | `/grandmaster/pressure-treated-alpine-grandmaster-summerhouse/` |
- **Real configurator options** (from live `wc/v3/product/9235/config`): Size, Wall
  Thickness (11mm Shiplap / 16mm Shiplap +£330), Floor, Roof Cover, Guttering, Paint
  Colour, Colour Trim, Base (optional).
- **Real product images** (confirmed live gallery/hero image per product):
  | Product | Image URL |
  |---|---|
  | Diplomat Classic Summerhouse | `.../2026/07/GM-Diplomat-Summerhouse-category-image.webp` |
  | Heavy Duty D1000 Apex Workshop | `.../2017/11/Heavy-Duty-Workshop-D1000-windowed-apex-door-2.jpg` |
  | Lounge Contemporary Garden Room | `.../2026/07/gm_lounge_contemporary_g1000_garden_room_lifestyle_1_1x-1080x1080.webp` |
  | Clubhouse Summerhouse | `.../2026/07/gm_clubhouse_summerhouse_lifestyle_1_1x.webp` |
  | Fusion Classic with Side Shed | `.../2026/07/gm_fusion_classic_g1000_summerhouse_with_side_shed_green_lifestyle_1_1x.webp` |
  | Alpine Summerhouse Cabin | `.../2026/07/GM-Alpine-Cabin-Summerhouse-Lifestyle.png-3-1080x1080.webp` |

  (all under `https://www.projecttimber.com/wp-content/uploads/`)
- **Real testimonials** (verbatim or trimmed-but-contiguous excerpts) from
  `templates/page-testimonials.php`:
  - William, Sep 09, 2024 (used in full): "Flexible design, standardisation of parts
    and easy-to-manage panels. It fits compactly onto one pallet and doesn't take up
    much space until you're ready to assemble. Screws to fix the panels help pull
    everything together neatly. Excellent customer care team, very helpful. Would
    definitely recommend."
  - Tony H., Oct 19, 2023 (trimmed): "The shed arrived within 2 weeks, on the exact
    day promised and at the time specified. Packed neatly on one pallet, it was easy
    to transport the parts to where they were needed."
  - Dean M., Mar 03, 2023 (trimmed): "…the customer service was second to none. The
    shed arrived on a single pallet on the date agreed and all the panels were in
    great shape. I built it with no issues at all and it looks amazing! So happy
    with the quality and the look of it."
- **Real use-case landing pages** (confirmed live): Garden Office →
  `/garden-offices/`; Home Gym & Creative Studio → `/insulated-garden-buildings/`
  (no dedicated gym/studio category exists; this real category explicitly covers
  offices/studios/gyms); Trade Workshop → `/garden-workshops/`.
- **Real GM10 FAQ answer** (adapted from `templates/page-faq.php:69`, phone number
  dropped per decision #2): "Yes — enjoy 10% off Grandmaster products with code
  **GM10**."
- **Guarantee wording:** reuse `front-page.php`'s exact real phrase "Up to 25-year
  guarantee*" for headline trust claims. Add one small disclaimer line near the
  footer: "*Guarantee terms vary by product and are confirmed at checkout on
  projecttimber.com." — honest, doesn't invent specifics, mirrors the asterisk usage
  already present in the real site's own trust bar.

## What stays unchanged

- All CSS/visual design, fonts, color palette, layout, responsive breakpoints.
- All existing front-end interactivity that doesn't imply a transaction: parallax
  scroll effect, scroll-reveal animations, product filter pills, FAQ accordion,
  mobile nav drawer. These stay as client-side polish.
- The hero/parallax-band/final-CTA background images already pointing at real
  projecttimber.com uploads (verified as legitimate live asset paths via the
  WooCommerce REST API, even though direct `curl` to media URLs is blocked by
  Cloudflare bot protection for non-browser requests — a known caveat, see the
  `wc-api-endpoints` project memory).

## Out of scope

- No changes to any WordPress theme file (`front-page.php`, `header.php`, etc.) —
  this is a standalone static HTML file for a separate domain.
- No real purchase/cart/checkout functionality on this page — everything routes out
  to projecttimber.com.
- No changes to the real projecttimber.com site itself.
