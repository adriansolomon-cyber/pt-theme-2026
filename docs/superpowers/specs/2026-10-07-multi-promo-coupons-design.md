# Multiple parallel promo coupons (HOBBY20 + GM20 + …) — design

**Date:** 2026-10-07
**Goal:** Run two (or more) targeted discount codes at the same time — e.g. HOBBY20 for
Hobbyist products and GM20 for Grandmaster — each auto-applied to its own range, shown
with the right code + sale price on every surface, and applied correctly at checkout
(both, in the rare mixed cart). HOBBY20's current behaviour must not change.

---

## Today (single-code model)

- ACF option fields: `coupon_code` (default), `special_coupon_code` (special, currently
  `HOBBY20`), `special_coupon_percentage` (20), `special_offer_category_includes` (`4359`),
  `show_coupon` (master toggle), `campaign_end_date`.
- `auto_voucher_enabled()` = `show_coupon` AND not past `campaign_end_date`.
- **Apply (checkout):** `woocommerce_before_calculate_totals` applies **one** coupon per
  cart — the special when a cart item is in the special category (`av_cart_qualifies_for_special()`
  → `pt_product_in_special_category()` → cat 4359), else the default. The special
  **replaces** the default (mutually exclusive).
- **Display:** `pt_product_discount_pct()/code()` (per product) and
  `pt_term_discount_pct()/code()` (per category) return the **special** %/code when the
  product/term is in the special category, else the **default**.

**Why it can't do two today:** there is one "special" slot, and the special replaces the
default — there is no room for a second independent targeted code.

---

## New model

### Config (no new ACF fields)
The two existing "special" fields become **aligned comma-separated lists**, one entry per
promo, matched by position:

| Field | Value | Meaning |
|---|---|---|
| `special_coupon_code` | `HOBBY20, GM20` | the managed codes |
| `special_offer_category_includes` | `4359, <Grandmaster cat id>` | the category each code maps to (for category-page display) |
| `special_coupon_percentage` | (optional) | display % fallback only |

`coupon_code` (default slot) stays as-is — a broad code applied to any cart when set.
**Must be empty today for HOBBY20 to be unaffected** (verify before build).

### Source of truth
- **What actually discounts** = each coupon's own WooCommerce restriction (product IDs /
  categories). The theme never second-guesses it.
- **Which code maps to a category page** = the aligned `special_offer_category_includes`
  list (needed because a product-restricted coupon like HOBBY20 has no category to resolve
  from).
- **Display %** = read from the coupon (`WC_Coupon::get_amount()` for percent coupons), so
  the shown sale price always equals what the coupon charges. ACF % is a fallback.

### New helpers (in `includes/wc-custom-checkout-functions.php`)
- `pt_campaign_promos()` → array of `{ code, cat_ids[], pct }`, parsed from the aligned
  ACF lists (and `apply_filters` extensible). Empty when `auto_voucher_enabled()` is off.
- `pt_auto_promo_codes()` → the managed codes (default + every promo code), lowercased.
- `pt_coupon_valid_for_cart($code)` → `true` only when WooCommerce validates the coupon for
  the current cart (i.e. the cart holds an item the coupon covers). Uses `WC_Discounts`.
- `pt_product_promo($product_id)` → the first managed promo whose coupon
  `is_valid_for_product()` the product → `{ code, pct }` (or none). Drives per-product display.
- `pt_term_promo($term_id)` → the first promo whose `cat_ids` include the term (or an
  ancestor) → `{ code, pct }`. Drives per-category display.

### Apply logic (rewrite the `woocommerce_before_calculate_totals` handler)
- If the customer typed their **own** coupon (outside the managed list) → leave everything
  alone (unchanged behaviour).
- For **each** managed code: apply it when `pt_coupon_valid_for_cart()` is true, remove it
  when not. Multiple apply together; WooCommerce scopes each to its own items.
- Remove the old `woocommerce_coupon_is_valid` fallback filter (its special→default forced
  swap is incompatible with independent codes; WooCommerce's own rejection message covers
  a mis-typed code).

### Display logic (rewrite the four helpers to use the promos)
- `pt_product_discount_pct()/code()` → `pt_product_promo()`.
- `pt_term_discount_pct()/code()` → `pt_term_promo()`.

Everything downstream then adapts automatically because it already calls these four:
- Product page configurator + sale box — `functions.php:287,319`, `single-product.php:205-206`
- Category cards — `inc/category-render.php:184-185`
- Product render / from-price — `inc/product-render.php:211,408`
- Search suggestions — `inc/search-suggest.php:113`
- Product schema (discounted Offer price) — `legacy-functions.php:4780`
- Category page % + code — `functions.php:359-360`

### Surfaces that need a light, explicit touch
- **FAQ** (`templates/page-faq.php:75-81`) — currently reads default→special code + ACF %.
  Update to show the promo list (or the first promo) via the new helpers.
- **Generic Hobbyist banners** (`header.php:294` announcement, `taxonomy-product_cat.php:182`
  chip) — these are hardcoded *Hobbyist* copy and should keep showing **HOBBY20**.
  `av_get_display_voucher_code()` stays returning the first managed code so they don't break.
  A Grandmaster banner with GM20 is separate *content* the business adds when ready — not
  auto-generated.
- **Diagnostics** (`templates/page-test.php` `?special_check`) — extend to list all promos
  and show which one matches a product (nice-to-have).

---

## WooCommerce setup (business side, before go-live)
1. Create **GM20**: 20%, restricted to the Grandmaster category, same usage rules as HOBBY20.
2. **Neither HOBBY20 nor GM20 may be "Individual use only"** — otherwise WooCommerce blocks
   the second one in a mixed cart.
3. Set the ACF fields to the aligned lists above.

---

## HOBBY20 backward-compatibility guarantee
With only `HOBBY20` configured (no comma) and the default slot empty, `pt_campaign_promos()`
returns a single promo and every helper resolves exactly as today. The behaviour change is
additive: GM20's branch only exists once GM20 is added to the list.

## Consistency guarantee (what you see = what you're charged)
Per-product display uses the coupon's **own** `is_valid_for_product()` and `get_amount()`,
so a product shows a code + sale price **only** where that coupon will actually discount it
at checkout. No "advertised sale that doesn't apply."

## Edge cases
- **Mixed cart (Hobbyist + Grandmaster):** both coupons apply, each discounting its own line.
- **Category page % when a coupon is product-restricted (HOBBY20):** the page shows the
  category-level % from the coupon; cards still resolve per product, so a non-covered product
  in that category shows no sale.
- **Expiry / master toggle:** unchanged — `auto_voucher_enabled()` switches everything off.

## Testing (on live, after deploy + OPcache flush)
1. **HOBBY20 unchanged:** Hobbyist cart → HOBBY20 auto-applies at the same amount; Hobbyist
   product/category page shows HOBBY20 + 20% as before.
2. **GM20:** Grandmaster cart → GM20 applies; Grandmaster product/category page shows GM20 +
   20% + discounted price.
3. **Mixed cart:** both apply, each scoped to its own items.
4. **Non-promo product:** no code, no sale, no coupon.
5. **Customer's own coupon:** still wins / isn't stripped.

## Rollback
Single file (`includes/wc-custom-checkout-functions.php`) — `git revert` the commit and
flush OPcache. ACF list fields can hold a single value again with no code change.

## Open decisions for sign-off
1. Confirm the **default `coupon_code` slot is empty** today.
2. **Display %** from the coupon (`get_amount`, recommended — no sync) vs the ACF % field?
3. This round = **checkout + per-product/category display**. Grandmaster *banner/announcement*
   copy is separate content — build now or later?
