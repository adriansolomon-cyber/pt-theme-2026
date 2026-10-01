# Campaign: Free "3ft Shelf Stack (4 Shelves)" — weekend

**Status:** DESIGN APPROVED 2026-10-01, NOT built yet. Build before Sat 3 Oct 2026.

## Offer

The **3ft Shelf Stack (4 Shelves)** shelf option becomes **£0** on **all products
except Insulated**, for the weekend. Auto-applied — **no coupon code** (same as the
cladding free-upgrade). **Badge on the option only — no homepage/category banners.**

## Mechanism — mirrors `includes/pt-campaign-wall-upgrade.php` (prices NEVER touched)

Prices stay real everywhere (no `get_price` filtering). The option shows £0 in the
configurator; the real price is netted out at checkout with a VAT-correct negative fee.

### Key difference from cladding
Cladding is a **default** upgrade (16mm pre-selected). The shelf is an **opt-in add-on**
(default "None"). So we do **NOT** auto-select it — we only make it **free if chosen**.
No default-swap; just £0 display + checkout netting.

### 1. Scope set (shared by JS + PHP so they can't disagree)
Resolve the **3ft Shelf Stack (4 Shelves)** shelf option product ID(s) across all
**non-Insulated** composites; cache in a transient (bump with the price-cache gen).
Match the option by name (`/3\s*ft/i` + `/shelf/i` + `/4\s*shel/i` — finalise at build).

### 2. Configurator (`assets/js/product.js`, guarded by a new `SHELFUP` flag; no-op otherwise)
- The 3ft-shelf option renders `~~£X~~ FREE`, counts **£0** in the running total.
- "Free this weekend" pill on the **Shelf** step header.
- Reuse existing `.freeup` / `.pr .free` CSS (product.css) — no new styles needed.
- NOT default-selected (opt-in add-on).

### 3. Checkout (`includes/pt-campaign-free-shelf.php`, new, self-contained + toggleable)
- On `woocommerce_cart_calculate_fees`: for each non-Insulated composite container
  whose children include the 3ft-shelf option, add a **VAT-correct negative fee**
  = shelf option ex-VAT price × qty → "3ft Shelf Stack — Free upgrade −£X" in totals.
- Same tax-aware `add_fee(..., $amount_ex, true, '')` pattern as the wall module.

### 4. Gate — `pt_shelf_campaign_active()`
Live when **(date window)** OR **`current_user_can('manage_woocommerce')`** (admin
preview/test anytime, like the cladding admin gate). Localized to
`window.PT_SHELF_UPGRADE` (functions.php) for the configurator.

- **Date window:** Sat 2026-10-03 00:00 → Sun 2026-10-04 23:59 **Europe/London**.
- Make the start/end a single pair of constants (or a filter) at the top of the module
  so the dates are a one-line edit. Auto on/off — no one flips a switch.

## Files
- **New:** `includes/pt-campaign-free-shelf.php` (scope resolution + localize flag/target-IDs
  to product.js + the checkout negative fee + `pt_shelf_campaign_active()`), required in
  functions.php after the wall-upgrade require.
- **Edit:** `assets/js/product.js` — `SHELFUP` guard, £0 display + £0-in-total for the
  target option, Shelf-step pill. (Copy the `WALLUP` branches.)
- **Edit:** `functions.php` — localize `window.PT_SHELF_UPGRADE` next to `PT_WALL_UPGRADE`.
- **No banner/template changes** (badge-on-option only).

## Build-time verifications (do these first)
1. **Insulated category id** — exclude the Insulated category (noted as cat **1639 +
   children** from the Klaviyo work) — confirm before scoping.
2. **Shelf option identity** — is "3ft Shelf Stack (4 Shelves)" ONE shared add-on product
   or one per building? Confirm the exact name/ID and that nothing non-target matches.
3. **VAT basis** — shelf price is VAT-inclusive; the negative fee must be ex-VAT (tax-aware),
   same as the wall module.
4. **Acceptance test:** basket total == configurator total for a non-Insulated building with
   the shelf chosen; and the option is NOT discounted on an Insulated building. Verify in
   admin preview (checkout internals aren't locally testable).

## Deploy
Theme files → WP Pusher (push) + **OPcache flush** + **Cloudflare purge** (product.js is
edge-cached). Dates are in the pushed code, so deploy before Saturday; admins can preview
immediately after deploy.

Related: [[campaign-free-floor-upgrade]] (the wall-upgrade pattern this copies).
