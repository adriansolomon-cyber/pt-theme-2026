# Week Deal layout — the reusable promo template

This is the canonical layout for a "week deal" promo (a time-boxed extra discount or
freebie on top of the live site-wide discount). The Paint & Colour Trim 10% promo is the
reference build; it was iterated with the team and this is the layout that survived review.
**Every future week deal reuses these surfaces, classes, tokens, and engine — don't
reinvent them.** Changing copy, dates, and the discount rule should be the whole job.

Date of this snapshot: 2026-10-16 (after the gallery label and feature column were pulled —
see "What we deliberately dropped").

---

## 1. The surfaces (what the layout shows)

Each surface is gated on the campaign being active (`*_campaign_active()` / the `PAINTTRIM`
JS flag). When the window is closed, nothing renders for the public; admins
(`manage_woocommerce`) always see it for preview.

| # | Surface | Where | Class / hook |
|---|---------|-------|--------------|
| 1 | **Sale-box pill + live countdown** | Inside the yellow `.pt-prodsale-box` on the PDP (both the site-discount box and the cladding box), plus a standalone box when neither exists | `.pt-wkbadge` (pill) + `.pt-wkcountdown` (timer); rendered by `*_box_inner_html()` |
| 2 | **Step-header pill** | On the relevant configurator step header | `.cfg-head .freeup .freeup-pt` |
| 3 | **Step banner** | A short explainer line at the top of the relevant step | `.pt-paint-banner` (via `product.js` `rowHTML(..., pre)` arg) |
| 4 | **Per-option "Save £X" tag** | Top-right of the discounted price on each eligible option card | `.opt-card .pr .now .pt-save` |
| 5 | **Hero price-pill chip** | Next to the main price pill in the sticky header | `.pt-paint-chip` |
| 6 | **Cart-drawer fee line + upsell** | Mini-cart drawer | `#ptcPaintFeeLn` (the saving line, read from `cart.fees`) + `#ptcPaintUpsell` (deep-link to the step) |
| 7 | **Category sale-banner pill + countdown** | All 4 category sale banners | `.pt-wkbadge` + `.pt-wkcountdown` (category.css) |
| 8 | **Homepage hero coupon** | Inside the dark `.getb-panel` under "ALL BUILDING RANGES", on every GET20 (`.getb`) sale slide | `.getb-cpn` (dashed ticket w/ side notches; `front-page.php` + self-contained CSS in home.css) |
| 9 | **Homepage building-type card pill** | Top-left of each `.range-card` in "Find your space" | `$pt_pt_pill` (`front-page.php`); self-contained `.range-card .pt-wkbadge` CSS in home.css |
| 10 | **Top announcement strip** | Appended to the GET20 `.pt-annmsg` message (not a separate rotating slide) | `header.php`, gated inline |

All pills carry the **"moving shade" shimmer** (`@keyframes pt-shine`, applied via `::after`,
off under `prefers-reduced-motion`). The homepage surfaces (8–10) load **only** base.css +
home.css, which don't carry the shared `.pt-wkbadge`/shimmer rules — so each homepage surface
ships **self-contained** styling (its own pill look + `pt-shine` keyframe) in home.css.

### Tag wording is settings-driven (don't hard-code "Weekend")
The short tag reads **"Weekend deal"** when the promo window spans a Sat/Sun, else **"Week deal"** —
from `pt_paint_trim_tag_label()` (walks `PT_PAINT_TRIM_START/END`; filter `pt_paint_trim_tag_label`).
Used by the hero coupon tag and the announcement strip. A new week deal reuses this helper rather
than printing a fixed string.

### Hero checkout parity
The checkout fee (`pt_paint_trim_fee()`) rounds **per colour option exactly like the configurator's
"Save £X"** — `round60(after site discount) − round60(after the extra %)` on the VAT-INCLUSIVE price,
then converted back to ex-VAT for the taxable fee so the displayed line is a whole £ (−£6, not −£6.30)
and the config total == checkout total.

### What we deliberately dropped
Two surfaces were built then removed as too much — **do not add them back** for a standard
week deal:
- Gallery overlay label (`.pt-paint-gal` "+10% OFF PAINT" on the first gallery slide).
- A 5th `.mf-col` feature column ("EXTRA 10% OFF") in the `.pt-mainfeat` strip (and its
  `.pt-mainfeat--pt` 3+2 mobile wrap).

---

## 2. Visual vocabulary (tokens + classes)

Reuse these — a week deal must look consistent with the last one.

- **Green deal colour:** `#00FE6C` (the badge background / icon accent). Sale red: `#C0392B`.
- **Pill (`.pt-wkbadge`):** green bg, charcoal text, gift/clock icon, pill radius, soft shadow,
  shimmer. Defined in both `assets/css/category.css` (~L279) and `assets/css/product.css` (~L761).
- **Countdown (`.pt-wkcountdown`):** clock icon + `<b>` tabular-nums timer. Filled by the
  `wp_footer` ticker (guarded by `window.__ptCountdownTicker`), which reads `[data-ends]` and
  hides any end date >400 days out (so the 2099 placeholder stays invisible to the public).
- **On the PDP sale box**, the pill is restyled to **charcoal + white + green icon**
  (`.pt-prodsale-box .pt-ptbadge`) so it reads against the yellow box. Category pills stay green.
- **Shimmer:** `@keyframes pt-shine` + the shared `::after` rule listing every pill class
  (product.css ~L880). Add any new promo pill class to that selector list to get the shimmer.
- **Hero coupon (`.getb-cpn`):** a dashed-border ticket with punched side notches, inside the dark
  `.getb-panel`. `+<pct>%` in `--accent`, a dashed divider, then the green `.getb-cpn-tag` + the
  `.getb-cpn-main` line. Notches are filled `--charcoal` to match the panel — **if the panel colour
  changes, change the notch fill with it.** On every GET20 slide; centred, `width:max-content`.

---

## 3. The engine (copy this module structure)

Each week deal is **one self-contained module** in `includes/pt-campaign-<slug>.php`, required
from `functions.php`. Prices are **NEVER edited** — the discount is applied as a VAT-correct
**negative cart fee**, and the configurator just *displays* the reduced number. Reference
modules: `pt-campaign-paint-trim.php`, `pt-campaign-free-shelf.php`, `pt-campaign-wall-upgrade.php`.

Standard function set (rename `paint_trim` → your slug):

- `PT_<X>_START` / `PT_<X>_END` constants (Europe/London). **Ship with a `2099-…` placeholder**
  so it's admin-preview-only until real dates are set.
- `pt_<x>_pct()` — the discount percent (filterable).
- `pt_<x>_window_open()` — now within START…END.
- `pt_<x>_campaign_active()` — `window_open() || current_user_can('manage_woocommerce')`.
  **To hard-stop for everyone:** `return false;` at the top.
- `pt_<x>_fee( $cart )` on `woocommerce_cart_calculate_fees` — adds the negative fee =
  pct of each eligible item's **ex-VAT** price. Match eligible items by **component title**
  via the cart child's `composite_item` id → `$parent->get_components()` (not by option name).
- `pt_<x>_label()`, `pt_<x>_badge_html()`, `pt_<x>_countdown_html()`, `pt_<x>_box_inner_html()`,
  `pt_<x>_end_timestamp()` — the HTML helpers the surfaces call.

**functions.php:** require the module; localize `window.PT_<X>` + `window.PT_<X>_PCT` on the
product page (next to the other promo flags) and `window.PT_<X>` globally for the mini-cart.

**product.js:** flags (`<X>`/`<X>PCT`), the promo price fn (`disc(n) - n*PCT/100` for additive
stacking on the site discount), the eligible component ids, the card price branch, the
"Save £X" tag, and the step pill/banner.

---

## 4. Spin up a new week deal — checklist

1. `cp includes/pt-campaign-paint-trim.php includes/pt-campaign-<slug>.php`; rename functions,
   set `pt_<x>_pct()`, set the eligibility match (which component titles / products qualify).
2. Leave `PT_<X>_START/END` as the `2099` placeholder → deploys admin-preview-only.
3. Require it in `functions.php`; add the `window.PT_<X>` localizations (PDP + global).
4. Wire the surfaces: `product.js` flags/price/Save tag/step pill, `single-product.php` sale-box
   + hero chip, `mini-cart.js` fee line + upsell, `taxonomy-product_cat.php` category pills.
   Most of this is copy-paste from the paint-trim wiring — reuse `.pt-wkbadge`/`.pt-wkcountdown`/
   `.pt-paint-banner`/`.pt-save`/`.pt-paint-chip` as-is (rename only the chip copy).
5. Set the real START/END dates (this also switches the live countdown on) when going public.
6. Verify in admin: the "<deal> −£Z" checkout line appears; only eligible/priced options
   discount; configurator total ≈ checkout total (small round60 drift OK); no overflow at
   360/768/1440.
7. Deploy: `git push origin main`, then **flush OPcache + clear Cloudflare**.

See memory `campaign-paint-trim-10pct` for the reference build's full commit history and gotchas.
