# Themed product page (use-case "themes") — design

**Date:** 2026-10-12
**Status:** Spec for review (no code yet)

## Goal
Let a product page stage the building as different **use-cases** — e.g. *Bike Store*,
*Home Office*, *Garden Studio* — each with its own banner, staged gallery, features,
checklist, "why" block and an auto-popup. A theme is chosen by a shareable
**`?theme=<slug>` URL** or an on-page **switcher**. With no theme the page is the
normal PDP; with a theme it renders that theme's content server-side. The existing
configurator (sizes/price, API-driven) is untouched and shared across themes.

Source mockup: `design-files/product-page-different themes-version.html` (a React
prototype; we port its logic to the theme's PHP + vanilla-JS patterns).

## Decisions locked in
- **Augment, not replace.** The standard product page stays; the themed block is woven
  in. (User.)
- **Server-rendered `?theme=` (full reload).** `?theme=bike-store` renders that theme
  on page load; the switcher is plain links that reload with a new param. No AJAX.
  (User.)
- **Default = the normal PDP.** With no `?theme=` param, the page is exactly today's
  product page and the themed block does not appear. A theme only shows once chosen.
  (User, recommended option.)
- **A theme owns the whole themed experience** — banner, headline, staged product
  gallery, image features, checklist, why, popup. (User: "entire page … gallery for
  product".)
- **Tabs, not a repeater,** for the themes — each theme's sub-lists (shots, features,
  popup bullets) stay single-level repeaters under a labelled tab (no nested
  repeaters). (User.)

## UX / flow
1. Product with the themed layout **enabled** (per-product toggle).
2. No `?theme=` → standard PDP. A **theme switcher** (chips/tabs) is shown near the top
   offering the themes that have content: "See it as a **Bike Store · Home Office ·
   Garden Studio**".
3. Clicking a chip loads `?theme=bike-store` → the **themed block** renders with that
   theme's banner, staged gallery, features, checklist, why; the switcher highlights
   the active theme and offers "Back to standard".
4. The **popup** (if enabled) auto-shows once per visitor (localStorage-gated) on a
   themed view, promoting the use-case, then can be dismissed.
5. The configurator, specs, FAQ, reviews etc. render as normal below.

## ACF — new field group "Themed Product Page"
Location: `post_type == product` (optionally also limited to the target category).
Kept separate from `group_pt_product_content` so it only loads where used. Stored in
`acf-json/` (versioned) → **Custom Fields → Sync** once on the live site.

Top level:
- **Enable themed layout** (true_false) — gates the whole block per product.
- **Auto-show popup** (true_false) — master toggle for the popup auto-open.
- **Switcher intro** (text, optional) — e.g. "See it set up as…".

Then **one tab per use-case theme** (initial set from the design: `Cycle Hub`,
`Mancave`, `Home Gym`, `Secure Storage` — nameable; the default "Workshop" view needs no
tab; adding a new theme type later is a field-group edit). Each tab holds:
- **Theme name** (text) + **Theme slug** (text; used in `?theme=`) — or derive the slug
  from the name.
- **Banner:** image · title · subtitle.
- **Headline:** line 1 · highlight word.
- **Gallery shots** (repeater): label · image — the staged photos for this theme.
- **Image features** (repeater): image · title · body.
- **Checklist** (repeater): feature text.
- **Why:** heading · text.
- **Popup:** title · image · bullet points (repeater) · CTA label · CTA link.

A theme renders in the switcher only when its tab has at least a banner/gallery — so a
product can offer a subset (e.g. just Bike Store + Home Office).

## Switcher placement (per the provided design — confirmed by screenshot)
The theme switcher is a **row of tab/pill buttons directly above the product gallery**,
inside the configurator's left column, under the "Build your …" heading. The **first
tab is the default** (the building's normal name, e.g. "Workshop", shown active); the
rest are the use-case themes. Clicking a theme tab loads `?theme=<slug>`, which restages
the gallery (its shots), the banner and the features for that theme and marks the tab
active. The default tab returns to the standard gallery (`?theme=` cleared).

**Shown only on the PARENT product page, and only when the themed layout is enabled**
for that product — never on size/child products or on non-enabled products.

**Example theme set (from the provided design):** `Workshop` (default) · `Cycle Hub` ·
`Mancave` · `Home Gym` · `Secure Storage`. The default needs no ACF tab (it's the normal
product view); each use-case theme is one ACF tab.

## Rendering
- New `template-parts/product-themed.php` (the themed banner / gallery / features /
  popup) plus the switcher tab-row injected above the gallery.
- In `single-product.php`: if the Enable toggle is on, resolve the active theme from
  `get_query_var`/`$_GET['theme']` (sanitised against the configured slugs), then
  render the switcher tabs above the gallery and the themed sections. The switcher is
  always shown (when enabled); the themed banner / staged gallery / features / popup
  render only when a valid theme is active.
- Active-theme content comes from that theme's ACF tab. Guard every field; an empty
  sub-section just doesn't render.
- The product **gallery** for a theme = its `shots` (falls back to the standard gallery
  if a theme defines none).

## JavaScript (minimal, vanilla — port of the React prototype)
- **Gallery tabs** within a theme (prev/next + dots) — reuse the configurator gallery
  pattern where possible.
- **Popup:** on a themed view with auto-show on, open once after ~900ms unless
  `localStorage` says seen; store a per-theme "seen" key; close on button/overlay/Esc.
- No framework; no theme-switch JS needed (switcher = links).

## SEO / canonical (decision needed)
Themed URLs add `?theme=` query variants of the same product. Options:
- **(Recommended) Canonical each themed URL to the base product URL** — avoids
  duplicate-content dilution; the themes are a UX/marketing layer, ranking stays on the
  one product. Add to the existing canonical module.
- **Index each theme separately** — only if the themes target distinct search intents
  (e.g. "bike storage shed") and get unique copy; higher risk of thin/duplicate pages.
Default to canonical-to-base unless you want the SEO play.

## Enablement / scope
Per-product **Enable** toggle — the "range" is simply the products you tick it on. No
hardcoded category needed (optional category limit on the ACF location rule if you want
the tab hidden elsewhere).

## Phasing
- **P1:** ACF field group + `product-themed.php` rendering one theme from `?theme=`
  (banner, headline, gallery, image features, checklist, why). Switcher links.
- **P2:** popup (auto-show + localStorage) + gallery tabs polish.
- **P3:** canonical handling + analytics (track theme views), refinements.

## Testing
- Enabled product, no `?theme=` → standard PDP + switcher chips only.
- `?theme=bike-store` → themed banner/gallery/features render; switcher highlights it.
- Invalid/unknown `?theme=` → falls back to standard PDP (no error).
- A theme with only some sub-sections filled → only those render.
- Disabled product → no switcher, no block, unaffected.
- Popup shows once, respects localStorage, dismisses.
- Configurator/specs/FAQ/reviews unaffected throughout.

## Rollback
Per-product toggle off hides everything instantly. The template part and ACF group are
additive; reverting the `single-product.php` include + removing the part restores the
original page. ACF group can be deactivated without data loss.

## Resolved
- **Switcher placement:** tab row above the product gallery (configurator left column),
  parent product only, when themed layout enabled. First tab = default view.
- **Theme set (initial):** Workshop (default) + Cycle Hub, Mancave, Home Gym, Secure
  Storage. Nameable per field group.

## Open decisions
1. **Range** — which products get the Enable toggle (and whether to limit the ACF tab to
   a category).
2. **Canonical policy** — canonical-to-base (recommended) vs index-each-theme.
3. **Theme slugs** — derive from the theme name (e.g. "Cycle Hub" → `cycle-hub`) vs an
   explicit slug field (recommended: derive, with an optional override).
