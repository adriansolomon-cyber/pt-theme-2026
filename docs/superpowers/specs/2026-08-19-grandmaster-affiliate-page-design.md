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
- **Real product images:** pull from the live composite config API responses
  (e.g. `GM-Diplomat-Summerhouse-category-image.webp`,
  `gm_diplomat_classic_g1000_summerhouse_lifestyle_1_1x.webp`) and from
  `front-page.php`'s existing Grandmaster image references
  (`Grandmaster.webp`, `GM_Diplomat_LongWindow_Interior.webp`,
  `grandmaster_workshop_1x.webp`, `workshop_2_1.webp`). Exact per-card image URLs for
  the 5 non-Diplomat products to be confirmed during implementation (fetch each
  product's config/page for its real image, same method used for Diplomat).
- **Real testimonials:** pulled verbatim (shortened where needed) from
  `templates/page-testimonials.php`, e.g. Tony H. (Oct 19, 2023), William
  (Sep 09, 2024) — real names/dates, no fabricated authors.
- **Guarantee wording:** align with live site's "Up to 25-year anti-rot guarantee"
  phrasing (the live category page also mentions a base 15-year guarantee with no
  annual re-treatment — use "up to 25-year" as the headline claim, consistent with
  `front-page.php`'s trust bar).

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
