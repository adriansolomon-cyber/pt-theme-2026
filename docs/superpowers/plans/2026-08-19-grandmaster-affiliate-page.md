# Grandmaster Affiliate Landing Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rework `design-files/affiliate-marketing-pages/grandmaster_homepage.html` from a fictional design mockup into a factually accurate, indexable advertorial page that funnels organic traffic to real Grandmaster products on projecttimber.com.

**Architecture:** Single self-contained static HTML file (inline `<style>` + inline `<script>`, no external dependencies besides Google Fonts). All changes are in-place edits to this one file — content/link fixes first, one structural reorder last, then a full-page verification pass. No build step, no test framework; verification is via `grep` assertions (content presence/absence) and a manual browser check (there is no automated JS test harness for this file).

**Tech Stack:** Plain HTML/CSS/vanilla JS. No frameworks, no package manager, no bundler.

## Global Constraints

(Copied from `docs/superpowers/specs/2026-08-19-grandmaster-affiliate-page-design.md` — every task below implicitly follows these.)

- Only file touched: `design-files/affiliate-marketing-pages/grandmaster_homepage.html`. No WordPress theme files, no changes to projecttimber.com.
- Keep the page presenting as its own "Grandmaster Garden Buildings" advertorial brand — do not rebrand as "Project Timber presents…".
- Every transactional/action CTA (nav CTA button, product "Explore →", "View All Buildings", band CTA, "Configure yours →", final CTA, footer product/shop links) becomes an outbound link to a real `projecttimber.com` URL with this exact UTM suffix: `?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10`. Pure in-page section navigation (nav-links, mobile drawer links) stays as `#anchor` scrolling.
- No `tel:` links, no phone numbers, no call-to-action copy implying a call center.
- Do NOT add `<meta name="robots" content="noindex">` — this page must stay indexable.
- Technical spec numbers already in the mockup (54mm studwork, 3-point locking, double-glazed as standard, etc.) are kept exactly as originally written — do not rewrite them to "verified-only" claims (explicit user decision, despite a real API check finding they don't match one sampled product's actual data). Uniqueness/duplication-avoidance effort goes into product titles/descriptions instead, not the technical claims.
- Real company/legal info: **Project Timber Limited**, registered in England and Wales, company no. **05126131**, registered office **Parry Works, Grassthorpe Road, Sutton-on-Trent, Newark NG23 6QX**.
- Real promo code: **GM10** (10% off Grandmaster range).
- All CSS/visual design, fonts, palette, layout, responsive breakpoints, and non-transactional interactivity (parallax, scroll-reveal, product filter pills, FAQ accordion, mobile nav drawer) stay unchanged.

---

### Task 1: Head metadata

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (head section, lines ~3–23)

**Interfaces:** None (no other task depends on head content).

- [ ] **Step 1: Update title and add a meta description**

Use the Edit tool with this exact `old_string`:

```html
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>
      Grandmaster Garden Buildings – Built Different. Built to Last.
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
```

Replace with this `new_string`:

```html
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Grandmaster Garden Buildings — The Complete Buyer's Guide (2026)</title>
    <meta
      name="description"
      content="Compare every Grandmaster garden building — real prices with code GM10, real customer reviews, and which summerhouse, workshop or garden room fits your garden — before you buy on Project Timber."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
```

This gives the page its own distinct SEO title/description (buying-guide intent, not mirroring any real product/category page title) per the SEO decision in the spec.

- [ ] **Step 2: Trim the stale placeholder comment in the CSS header**

Use the Edit tool with this exact `old_string`:

```html
      /* ============================================================
         GRANDMASTER GARDEN BUILDINGS — HOMEPAGE v4
         Fonts: Cormorant Garamond (headlines) + DM Sans (body/UI)
         Palette: Ink #14100D · Amber #C17F24 · Cream #FAF8F3
         IMAGE SLOTS — replace background-image values with:
         url('https://lh3.googleusercontent.com/d/YOUR_FILE_ID')
         or Shopify: url('{{ section.settings.image | img_url: "1600x" }}')
         ============================================================ */
```

Replace with this `new_string`:

```html
      /* ============================================================
         GRANDMASTER GARDEN BUILDINGS — BUYER'S GUIDE
         Fonts: Cormorant Garamond (headlines) + DM Sans (body/UI)
         Palette: Ink #14100D · Amber #C17F24 · Cream #FAF8F3
         Standalone advertorial — all outbound CTAs route to
         projecttimber.com with UTM tracking.
         ============================================================ */
```

This is a template-authoring comment (Shopify/Google-Drive image-slot instructions) left over from the mockup stage; content is now final, so the comment no longer applies.

- [ ] **Step 3: Verify**

Run: `grep -n "Built Different. Built to Last\|YOUR_FILE_ID" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output (both old strings are gone).

Run: `grep -n "Complete Buyer's Guide\|utm_campaign=gm10" "design-files/affiliate-marketing-pages/grandmaster_homepage.html" | head -3`
Expected: at least the title line matches.

- [ ] **Step 4: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: distinct SEO title/description, trim stale comment"
```

---

### Task 2: Nav and mobile drawer — remove phone, fix CTA links

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (nav ~line 2109, drawer ~line 2132)

**Interfaces:** Introduces the anchor id `#configure` as a nav target — Task 6 creates the section with `id="configure"` that this anchor points to. Task 13 does not move the nav itself.

- [ ] **Step 1: Rewrite the nav bar**

Use the Edit tool with this exact `old_string`:

```html
    <nav class="nav">
      <div class="nav-inner">
        <a href="#" class="nav-logo">
          <span class="nav-logo-name">Grandmaster</span>
          <span class="nav-logo-tag">Garden Buildings</span>
        </a>
        <div class="nav-links">
          <a href="#products">Buildings</a>
          <a href="#bundles">Bundles</a>
          <a href="#why">Quality</a>
          <a href="#reviews">Reviews</a>
        </div>
        <div class="nav-right">
          <a href="tel:01777802300" class="nav-tel">01777 802 300</a>
          <a href="#" class="btn-nav">Get a Quote →</a>
        </div>
        <!-- Hamburger (mobile only) -->
        <button class="nav-burger" id="navBurger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
```

Replace with this `new_string`:

```html
    <nav class="nav">
      <div class="nav-inner">
        <a href="#" class="nav-logo">
          <span class="nav-logo-name">Grandmaster</span>
          <span class="nav-logo-tag">Garden Buildings</span>
        </a>
        <div class="nav-links">
          <a href="#products">Buildings</a>
          <a href="#configure">Customise</a>
          <a href="#why">Quality</a>
          <a href="#reviews">Reviews</a>
        </div>
        <div class="nav-right">
          <a
            href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
            class="btn-nav"
            >Shop the Range →</a
          >
        </div>
        <!-- Hamburger (mobile only) -->
        <button class="nav-burger" id="navBurger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
```

Note: `.nav-tel` CSS rule becomes unused after this edit — leave it (dead CSS is harmless, and the plan keeps all CSS unchanged per the global constraints).

- [ ] **Step 2: Rewrite the mobile drawer**

Use the Edit tool with this exact `old_string`:

```html
    <div class="nav-drawer" id="navDrawer">
      <div class="nav-drawer-top">
        <a href="#" class="nav-logo">
          <span class="nav-logo-name" style="color: #fff">Grandmaster</span>
          <span class="nav-logo-tag">Garden Buildings</span>
        </a>
        <button class="nav-drawer-close" id="navClose" aria-label="Close menu">
          ✕
        </button>
      </div>
      <nav class="nav-drawer-links">
        <a href="#products">Buildings</a>
        <a href="#bundles">Bundles</a>
        <a href="#why">Quality</a>
        <a href="#reviews">Reviews</a>
      </nav>
      <div class="nav-drawer-foot">
        <a href="tel:01777802300" class="nav-drawer-tel">📞 01777 802 300</a>
        <a
          href="#"
          class="btn-nav"
          style="display: block; text-align: center; padding: 14px"
          >Get a Quote →</a
        >
      </div>
    </div>
    <div class="nav-overlay" id="navOverlay"></div>
```

Replace with this `new_string`:

```html
    <div class="nav-drawer" id="navDrawer">
      <div class="nav-drawer-top">
        <a href="#" class="nav-logo">
          <span class="nav-logo-name" style="color: #fff">Grandmaster</span>
          <span class="nav-logo-tag">Garden Buildings</span>
        </a>
        <button class="nav-drawer-close" id="navClose" aria-label="Close menu">
          ✕
        </button>
      </div>
      <nav class="nav-drawer-links">
        <a href="#products">Buildings</a>
        <a href="#configure">Customise</a>
        <a href="#why">Quality</a>
        <a href="#reviews">Reviews</a>
      </nav>
      <div class="nav-drawer-foot">
        <a
          href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
          class="btn-nav"
          style="display: block; text-align: center; padding: 14px"
          >Shop the Range →</a
        >
      </div>
    </div>
    <div class="nav-overlay" id="navOverlay"></div>
```

Note: `.nav-drawer-tel` CSS rule becomes unused — leave it, same reasoning as Step 1.

- [ ] **Step 3: Verify**

Run: `grep -n "01777 802 300\|tel:01777802300\|Get a Quote" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output.

Run: `grep -c "Shop the Range" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: `2`

- [ ] **Step 4: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: remove fake phone numbers, fix nav/drawer CTA links"
```

---

### Task 3: Hero — remove fabricated Trustpilot score, fix hero CTA

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (hero section, ~line 2159)

**Interfaces:** None.

- [ ] **Step 1: Replace the floating Trustpilot card**

Use the Edit tool with this exact `old_string`:

```html
      <!-- Trustpilot float card -->
      <div class="hero-card">
        <div class="card-label">Trustpilot Rating</div>
        <div class="card-score">4.9</div>
        <div class="card-stars">★★★★★</div>
        <div class="card-sub">Thousands of UK reviews</div>
      </div>
```

Replace with this `new_string`:

```html
      <!-- Trustpilot float card -->
      <div class="hero-card">
        <div class="card-label">Trustpilot Rating</div>
        <div class="card-stars" style="font-size: 26px; margin: 4px 0">★★★★★</div>
        <div class="card-sub" style="font-weight: 700; color: var(--ink); font-size: 13px">
          Rated Excellent
        </div>
        <div class="card-sub">Thousands of UK reviews</div>
      </div>
```

This removes the fabricated "4.9" numeric score (unverifiable) while keeping the same visual card, reusing the existing `.card-label`/`.card-stars`/`.card-sub` classes. `.card-score` CSS rule becomes unused — leave it.

- [ ] **Step 2: Make the primary hero button outbound**

Use the Edit tool with this exact `old_string`:

```html
        <div class="hero-btns">
          <a href="#products" class="btn btn-white btn-lg"
            >Explore the Range →</a
          >
          <a href="#why" class="btn btn-ghost">Why Grandmaster</a>
        </div>
```

Replace with this `new_string`:

```html
        <div class="hero-btns">
          <a
            href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
            class="btn btn-white btn-lg"
            >Shop the Range →</a
          >
          <a href="#why" class="btn btn-ghost">Why Grandmaster</a>
        </div>
```

The secondary "Why Grandmaster" button stays as an in-page anchor (informational, not a purchase action).

Do NOT change the hero eyebrow, `<h1>`, or `.hero-body`/`.hero-trust` text — those contain the technical claims (double-glazed as standard, 54mm studwork, etc.) that stay unchanged per the global constraints.

- [ ] **Step 3: Verify**

Run: `grep -n "card-score\|>4.9<" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output for `>4.9<` (the `.card-score` CSS rule itself may still appear in `<style>` — that's expected and fine, only the HTML usage is removed).

Run: `grep -n 'Explore the Range' "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output.

- [ ] **Step 4: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: remove fabricated 4.9 score, outbound hero CTA"
```

---

### Task 4: Products — real names, prices, links, images

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (CSS `pc-img-*` rules ~lines 694–861; products grid HTML ~lines 2396–2526)

**Interfaces:** None (self-contained section).

- [ ] **Step 1: Update the 6 product background images (CSS)**

Use the Edit tool for each of these 6 pairs (each `old_string` is unique in the file):

```
old: url("https://www.projecttimber.com/wp-content/uploads/2020/01/grandmaster-diplomat-summerhouse-garden-room-with-central-doors.jpg"),
new: url("https://www.projecttimber.com/wp-content/uploads/2026/07/GM-Diplomat-Summerhouse-category-image.webp"),
```

```
old: url("https://www.projecttimber.com/wp-content/uploads/2021/08/grandmaster-pent-dual-room-summerhouse-with-side-storage-01-800x450.jpg"),
new: url("https://www.projecttimber.com/wp-content/uploads/2017/11/Heavy-Duty-Workshop-D1000-windowed-apex-door-2.jpg"),
```

```
old: url("https://www.projecttimber.com/wp-content/uploads/2017/11/lounge-contemporary_garden-room-summerhouse-800x450.jpg"),
new: url("https://www.projecttimber.com/wp-content/uploads/2026/07/gm_lounge_contemporary_g1000_garden_room_lifestyle_1_1x-1080x1080.webp"),
```

```
old: url("https://www.projecttimber.com/wp-content/uploads/2018/10/grandmaster-clubhouse-summerhose-garden-room-with-bifold-doors-and-long-windows-800x450.jpg"),
new: url("https://www.projecttimber.com/wp-content/uploads/2026/07/gm_clubhouse_summerhouse_lifestyle_1_1x.webp"),
```

```
old: url("https://www.projecttimber.com/wp-content/uploads/2020/07/grandmaster-fusion-apex-dual-room-summerhouse-with-side-storage-01-800x450.jpg"),
new: url("https://www.projecttimber.com/wp-content/uploads/2026/07/gm_fusion_classic_g1000_summerhouse_with_side_shed_green_lifestyle_1_1x.webp"),
```

```
old: url("https://www.projecttimber.com/wp-content/uploads/2017/12/grandmaster-alpine-summerhouse-403x227.jpg"),
new: url("https://www.projecttimber.com/wp-content/uploads/2026/07/GM-Alpine-Cabin-Summerhouse-Lifestyle.png-3-1080x1080.webp"),
```

(Each of these appears inside its respective `.pc-img-diplomat` / `.pc-img-workshop` / `.pc-img-lounge` / `.pc-img-clubhouse` / `.pc-img-fusion` / `.pc-img-alpine` rule. The `::after` label text on each class — e.g. `content: "Grandmaster Diplomat";` — already matches the real product nicknames used below and does not need to change.)

- [ ] **Step 2: Replace the products grid**

Use the Edit tool with this exact `old_string` (the full grid, lines ~2396–2526):

```html
        <div class="prod-grid" id="pg">
          <!-- PRODUCT 1: DIPLOMAT -->
          <div class="pc reveal" data-c="sh">
            <div class="pc-img pc-img-diplomat">
              <div class="pc-lbl am">Best Seller</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Classic Summerhouse</div>
              <div class="pc-name">Grandmaster Diplomat</div>
              <div class="pc-desc">
                Our most popular model. Offset or centred doors, full
                double-glazing, and that unmistakable Grandmaster presence.
                Available in 6 sizes.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">From</div>
                  <div class="pc-price">£1,778</div>
                </div>
                <button class="btn-shop">Explore →</button>
              </div>
            </div>
          </div>
          <!-- PRODUCT 2: WORKSHOP -->
          <div class="pc reveal" data-c="ws">
            <div class="pc-img pc-img-workshop"></div>
            <div class="pc-body">
              <div class="pc-range">D1000 Heavy Duty Workshop</div>
              <div class="pc-name">Grandmaster Workshop</div>
              <div class="pc-desc">
                Apex, Pent, Windowless, or Side Door. Trusted by tradespeople
                and serious makers — the commercial-grade garden workshop.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">From</div>
                  <div class="pc-price">£1,399</div>
                </div>
                <button class="btn-shop">Explore →</button>
              </div>
            </div>
          </div>
          <!-- PRODUCT 3: LOUNGE -->
          <div class="pc reveal" data-c="gr">
            <div class="pc-img pc-img-lounge">
              <div class="pc-lbl">Garden Office</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Contemporary Garden Room</div>
              <div class="pc-name">Grandmaster Lounge</div>
              <div class="pc-desc">
                Contemporary pent roof, panoramic glazing, year-round comfort.
                Insulate it and it's a proper extension from your garden.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">From</div>
                  <div class="pc-price">£2,099</div>
                </div>
                <button class="btn-shop">Explore →</button>
              </div>
            </div>
          </div>
          <!-- PRODUCT 4: CLUBHOUSE -->
          <div class="pc reveal" data-c="sh">
            <div class="pc-img pc-img-clubhouse"></div>
            <div class="pc-body">
              <div class="pc-range">G1000 Clubhouse — 8ft Depth</div>
              <div class="pc-name">Grandmaster Clubhouse</div>
              <div class="pc-desc">
                Full 8ft internal depth. Standard or Storeroom configuration.
                Built for cricket clubs, summer entertaining, and serious
                storage.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">From</div>
                  <div class="pc-price">£1,714</div>
                </div>
                <button class="btn-shop">Explore →</button>
              </div>
            </div>
          </div>
          <!-- PRODUCT 5: FUSION -->
          <div class="pc reveal" data-c="sh">
            <div class="pc-img pc-img-fusion">
              <div class="pc-lbl">Two Rooms, One Build</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Fusion with Side Shed</div>
              <div class="pc-name">Grandmaster Fusion</div>
              <div class="pc-desc">
                Summerhouse and storage combined. Two separate spaces, one
                footprint, one delivery. Exactly what most gardens actually
                need.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">From</div>
                  <div class="pc-price">£1,644</div>
                </div>
                <button class="btn-shop">Explore →</button>
              </div>
            </div>
          </div>
          <!-- PRODUCT 6: ALPINE -->
          <div class="pc reveal" data-c="gr">
            <div class="pc-img pc-img-alpine">
              <div class="pc-lbl am">Premium</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Summerhouse Cabin</div>
              <div class="pc-name">Grandmaster Alpine</div>
              <div class="pc-desc">
                The statement building. Widest footprint in the range with a
                distinctive cabin roof. The Grandmaster at its most impressive.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">From</div>
                  <div class="pc-price">£5,072</div>
                </div>
                <button class="btn-shop">Explore →</button>
              </div>
            </div>
          </div>
        </div>
        <div class="prod-more">
          <button class="btn-outline-ink">View All Buildings →</button>
        </div>
```

Replace with this `new_string`:

```html
        <div class="prod-grid" id="pg">
          <!-- PRODUCT 1: DIPLOMAT -->
          <div class="pc reveal" data-c="sh">
            <div class="pc-img pc-img-diplomat">
              <div class="pc-lbl am">Best Seller</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Summerhouse</div>
              <div class="pc-name">Grandmaster Diplomat</div>
              <div class="pc-desc">
                Our most popular model. Offset or centred doors, full
                double-glazing, and that unmistakable Grandmaster presence.
                Available in 35 sizes.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">
                    From <span style="text-decoration: line-through; opacity: 0.55">£1,631</span>
                  </div>
                  <div class="pc-price">
                    £1,468
                    <span style="font-size: 11px; font-weight: 700; color: var(--amber)">w/ GM10</span>
                  </div>
                </div>
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Explore →</a
                >
              </div>
            </div>
          </div>
          <!-- PRODUCT 2: WORKSHOP -->
          <div class="pc reveal" data-c="ws">
            <div class="pc-img pc-img-workshop"></div>
            <div class="pc-body">
              <div class="pc-range">D1000 Heavy Duty Workshop</div>
              <div class="pc-name">Grandmaster Workshop</div>
              <div class="pc-desc">
                Apex, Pent, Windowless, or Side Door. Trusted by tradespeople
                and serious makers — the commercial-grade garden workshop.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">
                    From <span style="text-decoration: line-through; opacity: 0.55">£1,549</span>
                  </div>
                  <div class="pc-price">
                    £1,394
                    <span style="font-size: 11px; font-weight: 700; color: var(--amber)">w/ GM10</span>
                  </div>
                </div>
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-traditional-windowed-apex-grandmaster-workshop/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Explore →</a
                >
              </div>
            </div>
          </div>
          <!-- PRODUCT 3: LOUNGE -->
          <div class="pc reveal" data-c="gr">
            <div class="pc-img pc-img-lounge">
              <div class="pc-lbl">Garden Room</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Contemporary Garden Room</div>
              <div class="pc-name">Grandmaster Lounge</div>
              <div class="pc-desc">
                Contemporary pent roof, panoramic glazing, year-round comfort.
                Insulate it and it's a proper extension from your garden.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">
                    From <span style="text-decoration: line-through; opacity: 0.55">£2,225</span>
                  </div>
                  <div class="pc-price">
                    £2,003
                    <span style="font-size: 11px; font-weight: 700; color: var(--amber)">w/ GM10</span>
                  </div>
                </div>
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-lounge-contemporary-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Explore →</a
                >
              </div>
            </div>
          </div>
          <!-- PRODUCT 4: CLUBHOUSE -->
          <div class="pc reveal" data-c="sh">
            <div class="pc-img pc-img-clubhouse"></div>
            <div class="pc-body">
              <div class="pc-range">G1000 Clubhouse Summerhouse</div>
              <div class="pc-name">Grandmaster Clubhouse</div>
              <div class="pc-desc">
                French doors and a bright, sociable layout. Built for summer
                entertaining, garden bars, and serious storage.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">
                    From <span style="text-decoration: line-through; opacity: 0.55">£1,549</span>
                  </div>
                  <div class="pc-price">
                    £1,394
                    <span style="font-size: 11px; font-weight: 700; color: var(--amber)">w/ GM10</span>
                  </div>
                </div>
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-club-house-garden-room-french-doors/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Explore →</a
                >
              </div>
            </div>
          </div>
          <!-- PRODUCT 5: FUSION -->
          <div class="pc reveal" data-c="sh">
            <div class="pc-img pc-img-fusion">
              <div class="pc-lbl">Two Rooms, One Build</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Fusion with Side Shed</div>
              <div class="pc-name">Grandmaster Fusion</div>
              <div class="pc-desc">
                Summerhouse and storage combined. Two separate spaces, one
                footprint, one delivery. Exactly what most gardens actually
                need.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">
                    From <span style="text-decoration: line-through; opacity: 0.55">£2,127</span>
                  </div>
                  <div class="pc-price">
                    £1,914
                    <span style="font-size: 11px; font-weight: 700; color: var(--amber)">w/ GM10</span>
                  </div>
                </div>
                <a
                  href="https://www.projecttimber.com/grandmaster/grandmaster-fusion-classic-g1000-summerhouse-with-side-shed-16mm-pressure-treated-tongue-and-groove/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Explore →</a
                >
              </div>
            </div>
          </div>
          <!-- PRODUCT 6: ALPINE -->
          <div class="pc reveal" data-c="gr">
            <div class="pc-img pc-img-alpine">
              <div class="pc-lbl am">Premium</div>
            </div>
            <div class="pc-body">
              <div class="pc-range">G1000 Summerhouse Cabin</div>
              <div class="pc-name">Grandmaster Alpine</div>
              <div class="pc-desc">
                The statement building. Widest footprint in the range with a
                distinctive cabin roof. The Grandmaster at its most impressive.
              </div>
              <div class="pc-foot">
                <div>
                  <div class="pc-from">
                    From <span style="text-decoration: line-through; opacity: 0.55">£4,956</span>
                  </div>
                  <div class="pc-price">
                    £4,460
                    <span style="font-size: 11px; font-weight: 700; color: var(--amber)">w/ GM10</span>
                  </div>
                </div>
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-alpine-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Explore →</a
                >
              </div>
            </div>
          </div>
        </div>
        <div class="prod-more">
          <a
            href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
            class="btn-outline-ink"
            style="text-decoration: none; display: inline-block"
            >View All 25 Buildings →</a
          >
        </div>
```

Note: `data-c` filter categories are unchanged from the original (`sh` = summerhouse, `ws` = workshop, `gr` = garden room) — the `fp()` filter JS function in the `<script>` block needs no changes.

- [ ] **Step 3: Verify**

Run: `grep -n "£1,778\|£1,399\|£2,099\|£1,714\|£1,644\|£5,072" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output (all fictional prices removed).

Run: `grep -c "w/ GM10" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: `6`

Run: `grep -c "utm_campaign=gm10" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: at least `9` (6 product links + nav CTA + drawer CTA + hero CTA so far)

- [ ] **Step 4: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: real products, GM10 prices, links, and images"
```

---

### Task 5: Parallax band — outbound CTA

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (~line 2529)

**Interfaces:** None.

- [ ] **Step 1: Update the band CTA link**

Use the Edit tool with this exact `old_string`:

```html
        <a href="#why" class="btn-white-sm">See the full specification →</a>
```

Replace with this `new_string`:

```html
        <a
          href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
          class="btn-white-sm"
          >See the full range on Project Timber →</a
        >
```

Do not change the band eyebrow/heading/body copy — no fabricated facts there, and it carries the same "designed to a standard" claim kept unchanged per the global constraints.

- [ ] **Step 2: Verify**

Run: `grep -n "See the full specification" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output.

- [ ] **Step 3: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: outbound parallax band CTA"
```

---

### Task 6: Replace Bundles section with "Make It Yours" (real configurator options)

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (~lines 2545–2781)

**Interfaces:** Produces the `id="configure"` anchor target that Task 2's nav links (`#configure`) point to.

- [ ] **Step 1: Replace the entire Bundles section**

Use the Edit tool with this exact `old_string` (the full `<section class="bundles-bg section" id="bundles">...</section>` block):

```html
    <!-- BUNDLES -->
    <section class="bundles-bg section" id="bundles">
      <div class="wrap">
        <div class="reveal" style="text-align: center; margin-bottom: 44px">
          <div class="eyebrow eyebrow-center">Built-For-You Bundles</div>
          <h2 class="h2">
            Choose how you'll use it.<br /><em>We'll pack what you need.</em>
          </h2>
          <p class="subtitle" style="max-width: 520px; margin: 12px auto 0">
            Based on thousands of customer orders, we've built the most popular
            combinations — one delivery, one price, no choices missed.
          </p>
        </div>
        <div
          style="display: flex; justify-content: center; margin-bottom: 48px"
        >
          <div class="btabs">
            <button class="btab on" onclick="switchB(this,'sh')">
              For Summerhouses
            </button>
            <button class="btab" onclick="switchB(this,'ws')">
              For Workshops
            </button>
          </div>
        </div>
        <div class="bgrid" id="bg">
          <!-- Summerhouse bundles -->
          <div class="bcard" data-bt="sh">
            <div class="bcard-head">
              <div class="bcard-ey">Summerhouse Bundle</div>
              <div class="bcard-name">Weather Protection Pack</div>
              <div class="bcard-desc">
                Core protection against the British climate — everything that
                adds decades to your building's life.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>EPDM rubber roof upgrade (40-yr
                  life)
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Guttering — front fascia kit
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Treatment paint + sealer (5L)
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Door & window draught seals
                </li>
              </ul>
              <div class="bcard-foot">
                <div class="bcard-save">Best value protection</div>
                <button class="btn-shop">Add →</button>
              </div>
            </div>
          </div>
          <div class="bcard star" data-bt="sh">
            <div class="bcard-head">
              <div class="bcard-ey">Summerhouse Bundle</div>
              <div class="bcard-name">Garden Room Ready Pack</div>
              <div class="bcard-desc">
                Turn your Grandmaster into a year-round garden office or studio
                — insulated, weatherproofed, and finished from day one.
              </div>
            </div>
            <div class="bcard-popular">Most Popular</div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Full insulation kit — walls,
                  floor & roof
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>EPDM rubber roof upgrade
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Professional assembly service
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Guttering + downpipe set
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Treatment paint (10L) +
                  applicator
                </li>
              </ul>
              <div class="bcard-foot">
                <div class="bcard-save">Save up to 12%</div>
                <button class="btn-amber">Add Bundle →</button>
              </div>
            </div>
          </div>
          <div class="bcard" data-bt="sh">
            <div class="bcard-head">
              <div class="bcard-ey">Summerhouse Bundle</div>
              <div class="bcard-name">Complete Retreat Pack</div>
              <div class="bcard-desc">
                The full experience. Built, weatherproofed, insulated, and
                decked. Arrive home to a finished building.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Full insulation kit + vapour
                  barrier
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>EPDM rubber roof + drip edge
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Professional assembly service
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Full guttering + downpipe set
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Paint treatment (10L)
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Veranda decking kit
                </li>
              </ul>
              <div class="bcard-foot">
                <div class="bcard-save">Save up to 16%</div>
                <button class="btn-shop">Add →</button>
              </div>
            </div>
          </div>
          <!-- Workshop bundles -->
          <div class="bcard" data-bt="ws" style="display: none">
            <div class="bcard-head">
              <div class="bcard-ey">Workshop Bundle</div>
              <div class="bcard-name">Security & Storage Pack</div>
              <div class="bcard-desc">
                Lock it down, keep it dry. Everything to make your Grandmaster a
                serious secure storage building.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>28mm heavy-duty floor boards
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Security lock bar upgrade
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Concrete anchor bolt kit
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Guttering + downpipe set
                </li>
              </ul>
              <div class="bcard-foot">
                <div class="bcard-save">Best value</div>
                <button class="btn-shop">Add →</button>
              </div>
            </div>
          </div>
          <div class="bcard star" data-bt="ws" style="display: none">
            <div class="bcard-head">
              <div class="bcard-ey">Workshop Bundle</div>
              <div class="bcard-name">Year-Round Workshop Pack</div>
              <div class="bcard-desc">
                Insulated, reinforced, and weatherproofed. Work in your
                Grandmaster all year without freezing or sweating.
              </div>
            </div>
            <div class="bcard-popular">Most Popular</div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Full insulation kit — walls,
                  floor, roof
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>28mm heavy-duty floor boards
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>EPDM rubber roof upgrade
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Professional assembly service
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Security lock bar upgrade
                </li>
              </ul>
              <div class="bcard-foot">
                <div class="bcard-save">Save up to 14%</div>
                <button class="btn-amber">Add Bundle →</button>
              </div>
            </div>
          </div>
          <div class="bcard" data-bt="ws" style="display: none">
            <div class="bcard-head">
              <div class="bcard-ey">Workshop Bundle</div>
              <div class="bcard-name">Trade Workshop Pack</div>
              <div class="bcard-desc">
                For professionals. Heavy use, serious tools, long hours — built
                to match the people who use it hardest.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>28mm heavy-duty floor boards
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>EPDM rubber roof upgrade
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Security lock bar upgrade
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Professional assembly service
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Concrete anchor bolt kit
                </li>
              </ul>
              <div class="bcard-foot">
                <div class="bcard-save">Trade recommended</div>
                <button class="btn-shop">Add →</button>
              </div>
            </div>
          </div>
        </div>
        <p class="bundles-note">
          All bundles arrive with your building on the same delivery.
          <strong>Available on every size and configuration.</strong> Can't see
          what you need? Call 01777 802 300.
        </p>
      </div>
    </section>
```

Replace with this `new_string`:

```html
    <!-- MAKE IT YOURS -->
    <section class="bundles-bg section" id="configure">
      <div class="wrap">
        <div class="reveal" style="text-align: center; margin-bottom: 44px">
          <div class="eyebrow eyebrow-center">Make It Yours</div>
          <h2 class="h2">One range.<br /><em>Configured your way.</em></h2>
          <p class="subtitle" style="max-width: 520px; margin: 12px auto 0">
            Every Grandmaster is built to order on Project Timber's own
            configurator — choose your wall thickness, roof, guttering,
            colour and base before it's ever cut.
          </p>
        </div>
        <div class="bgrid" id="bg">
          <div class="bcard">
            <div class="bcard-head">
              <div class="bcard-ey">Structure</div>
              <div class="bcard-name">Wall Thickness</div>
              <div class="bcard-desc">
                Standard 11mm shiplap or step up to 16mm for extra rigidity
                and a heavier, more solid feel.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>11mm Shiplap (included)
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>16mm Shiplap upgrade
                </li>
              </ul>
              <div class="bcard-foot" style="justify-content: flex-end">
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Configure yours →</a
                >
              </div>
            </div>
          </div>
          <div class="bcard">
            <div class="bcard-head">
              <div class="bcard-ey">Weatherproofing</div>
              <div class="bcard-name">Roof Cover</div>
              <div class="bcard-desc">
                Choose the felt or covering that suits your climate and how
                long you want it to last between re-coats.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Mineral felt roof covering
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Upgrade options at checkout
                </li>
              </ul>
              <div class="bcard-foot" style="justify-content: flex-end">
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Configure yours →</a
                >
              </div>
            </div>
          </div>
          <div class="bcard">
            <div class="bcard-head">
              <div class="bcard-ey">Drainage</div>
              <div class="bcard-name">Guttering</div>
              <div class="bcard-desc">
                Keep water off your base and away from the frame with
                guttering fitted at the same time as your build.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Front fascia guttering
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Fitted alongside your build
                </li>
              </ul>
              <div class="bcard-foot" style="justify-content: flex-end">
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Configure yours →</a
                >
              </div>
            </div>
          </div>
          <div class="bcard">
            <div class="bcard-head">
              <div class="bcard-ey">Finish</div>
              <div class="bcard-name">Paint Colour</div>
              <div class="bcard-desc">
                Pick from the Grandmaster colour range to match your garden
                and house — applied before delivery, not after.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Full Grandmaster colour range
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Factory-applied finish
                </li>
              </ul>
              <div class="bcard-foot" style="justify-content: flex-end">
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Configure yours →</a
                >
              </div>
            </div>
          </div>
          <div class="bcard">
            <div class="bcard-head">
              <div class="bcard-ey">Finish</div>
              <div class="bcard-name">Colour Trim</div>
              <div class="bcard-desc">
                Contrast or match your trim to the main paint colour for a
                sharper, more finished look.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Matching or contrast trim
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Chosen alongside paint colour
                </li>
              </ul>
              <div class="bcard-foot" style="justify-content: flex-end">
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Configure yours →</a
                >
              </div>
            </div>
          </div>
          <div class="bcard">
            <div class="bcard-head">
              <div class="bcard-ey">Groundworks</div>
              <div class="bcard-name">Base</div>
              <div class="bcard-desc">
                Optional base add-on if you don't already have a flat, solid
                surface ready for your Grandmaster.
              </div>
            </div>
            <div class="bcard-body">
              <ul class="bcard-items">
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Optional at checkout
                </li>
                <li class="bcard-item">
                  <span class="bcheck">✓</span>Team can advise on your garden
                </li>
              </ul>
              <div class="bcard-foot" style="justify-content: flex-end">
                <a
                  href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                  class="btn-shop"
                  >Configure yours →</a
                >
              </div>
            </div>
          </div>
        </div>
        <p class="bundles-note">
          Options shown here are configured on the product page during
          checkout on Project Timber — availability and pricing depend on the
          specific building and size you choose.
        </p>
      </div>
    </section>
```

Notes for the implementer (do not act on these beyond what's stated — they explain why some CSS/JS is left alone):
- `.btabs`/`.btab` CSS rules become unused (no more tab-switching UI) — leave them, CSS stays unchanged per the global constraints.
- The tablet-width rule `.bgrid .bcard:nth-child(3), .bgrid .bcard:nth-child(6) { display: none !important; }` still applies and will hide the "Guttering" and "Base" cards specifically at ≤1024px (keeps the 2-column grid even), reappearing at ≤768px via the existing mobile override. This is a harmless cosmetic side effect of reusing the existing grid CSS unchanged — no action needed.
- Task 12 removes the now-orphaned `switchB()` JS function (nothing in this new HTML calls it).

- [ ] **Step 2: Verify**

Run: `grep -n "Weather Protection Pack\|Add Bundle\|EPDM\|switchB(this" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output for the HTML usages (the `switchB` function definition in `<script>` still exists until Task 12 — that's expected here).

Run: `grep -c "Configure yours" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: `6`

Run: `grep -n 'id="configure"' "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: one match.

- [ ] **Step 3: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: replace fictional Bundles with real Make It Yours options"
```

---

### Task 7: Use Cases — fix outbound links (content only, position unchanged until Task 13)

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (~lines 2782–2860)

**Interfaces:** None.

- [ ] **Step 1: Fix the 4 tile links**

Use the Edit tool for each of these 4 (each `old_string` is unique — different link text per tile):

```
old: <a href="#" class="uc-link">Shop office buildings →</a>
new: <a
                href="https://www.projecttimber.com/garden-offices/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop office buildings →</a
              >
```

```
old: <a href="#" class="uc-link">Shop gym buildings →</a>
new: <a
                href="https://www.projecttimber.com/insulated-garden-buildings/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop gym buildings →</a
              >
```

```
old: <a href="#" class="uc-link">Shop studio buildings →</a>
new: <a
                href="https://www.projecttimber.com/insulated-garden-buildings/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop studio buildings →</a
              >
```

```
old: <a href="#" class="uc-link">Shop workshop buildings →</a>
new: <a
                href="https://www.projecttimber.com/garden-workshops/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop workshop buildings →</a
              >
```

Home Gym and Creative Studio both point to `/insulated-garden-buildings/` because no dedicated gym/studio category exists on the real site — this real category explicitly covers offices/studios/gyms (confirmed live).

Do not move this section yet — Task 13 handles reordering after all content tasks are done.

- [ ] **Step 2: Verify**

Run: `grep -c 'href="#" class="uc-link"' "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: `0`

Run: `grep -c "garden-offices\|insulated-garden-buildings\|garden-workshops" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: `4`

- [ ] **Step 3: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: real outbound links for Use Cases tiles"
```

---

### Task 8: Reviews — remove fabricated score, use real testimonials

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (~lines 2861–2918)

**Interfaces:** None.

- [ ] **Step 1: Replace the Trustpilot stat block**

Use the Edit tool with this exact `old_string`:

```html
          <div style="display: flex; align-items: center; gap: 20px">
            <div class="tp-num">4.9</div>
            <div>
              <div class="tp-stars">★★★★★</div>
              <div class="tp-lbl">Excellent</div>
              <div class="tp-sub">Thousands of verified UK reviews</div>
              <button class="btn-shop" style="margin-top: 14px">
                View on Trustpilot →
              </button>
            </div>
          </div>
```

Replace with this `new_string`:

```html
          <div style="display: flex; align-items: center; gap: 20px">
            <div>
              <div class="tp-stars" style="font-size: 32px">★★★★★</div>
              <div class="tp-lbl">Rated Excellent</div>
              <div class="tp-sub">Thousands of verified UK reviews</div>
              <a
                href="https://www.projecttimber.com/testimonials/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="btn-shop"
                style="margin-top: 14px"
                >View Real Reviews →</a
              >
            </div>
          </div>
```

`.tp-num` CSS rule becomes unused — leave it.

- [ ] **Step 2: Replace the 3 review cards with real testimonials**

Use the Edit tool with this exact `old_string`:

```html
        <div class="rv-grid">
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">Grandmaster Diplomat Classic</div>
            <div class="rv-text">
              "It's better than half my house. Up in one day, zero stress. The
              double glazing makes a huge difference — it's an actual building,
              not a shed."
            </div>
            <div class="rv-author">Mark T.</div>
            <div class="rv-meta">Verified Buyer · Feb 2025</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">Grandmaster D1000 Workshop</div>
            <div class="rv-text">
              "I've had sheds before. This isn't a shed. 18 months as my home
              office and it's warm, dry, and solid. It hasn't missed a beat."
            </div>
            <div class="rv-author">Graham S.</div>
            <div class="rv-meta">Verified Buyer · Nov 2024</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">Grandmaster Clubhouse Pavilion</div>
            <div class="rv-text">
              "The quality blew us away. The glass is thick, the door locks
              properly. Our cricket club is over the moon with it."
            </div>
            <div class="rv-author">James P.</div>
            <div class="rv-meta">Verified Buyer · Sep 2024</div>
          </div>
        </div>
```

Replace with this `new_string`:

```html
        <div class="rv-grid">
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">The modular design makes a lot of sense</div>
            <div class="rv-text">
              "Flexible design, standardisation of parts and easy-to-manage
              panels. It fits compactly onto one pallet and doesn't take up
              much space until you're ready to assemble. Screws to fix the
              panels help pull everything together neatly. Excellent customer
              care team, very helpful. Would definitely recommend."
            </div>
            <div class="rv-author">William</div>
            <div class="rv-meta">Verified Buyer · Sep 09, 2024</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">
              Excellent customer service and a great product
            </div>
            <div class="rv-text">
              "The shed arrived within 2 weeks, on the exact day promised and
              at the time specified. Packed neatly on one pallet, it was easy
              to transport the parts to where they were needed."
            </div>
            <div class="rv-author">Tony H.</div>
            <div class="rv-meta">Verified Buyer · Oct 19, 2023</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">Great quality and amazing shed</div>
            <div class="rv-text">
              "…the customer service was second to none. The shed arrived on a
              single pallet on the date agreed and all the panels were in
              great shape. I built it with no issues at all and it looks
              amazing!"
            </div>
            <div class="rv-author">Dean M.</div>
            <div class="rv-meta">Verified Buyer · Mar 03, 2023</div>
          </div>
        </div>
```

These are real testimonials (names, dates, quotes) sourced verbatim/contiguously-trimmed from `templates/page-testimonials.php`. The `rv-prod` label now shows each review's own headline rather than a fabricated product name.

Do not move this section yet — Task 13 handles reordering.

- [ ] **Step 3: Verify**

Run: `grep -n "Mark T.\|Graham S.\|James P.\|tp-num\">4.9" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output.

Run: `grep -n "William\|Tony H.\|Dean M." "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: 3 matches (one per name, in the review cards).

- [ ] **Step 4: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: real testimonials, remove fabricated Trustpilot score"
```

---

### Task 9: FAQ — add id, add GM10 discount question

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (~lines 2919–3008)

**Interfaces:** Produces the `id="faq"` anchor target that Task 11's footer "FAQs" link points to.

- [ ] **Step 1: Add the section id**

Use the Edit tool with this exact `old_string`:

```html
    <!-- FAQ -->
    <section class="faq-bg section">
```

Replace with this `new_string`:

```html
    <!-- FAQ -->
    <section class="faq-bg section" id="faq">
```

- [ ] **Step 2: Add the GM10 discount FAQ item**

Use the Edit tool with this exact `old_string` (the last existing FAQ item plus the closing of `.faq-grid`):

```html
          <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-q">
              <span class="faq-qt">Do I need planning permission?</span
              ><span class="faq-tog">+</span>
            </div>
            <div class="faq-ans">
              <div class="faq-ans-in">
                Most Grandmaster buildings fall within Permitted Development
                rights — single-storey, under 2.5m eaves, under 50% garden
                coverage. Our team can advise on typical scenarios.
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- FINAL CTA -->
```

Replace with this `new_string`:

```html
          <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-q">
              <span class="faq-qt">Do I need planning permission?</span
              ><span class="faq-tog">+</span>
            </div>
            <div class="faq-ans">
              <div class="faq-ans-in">
                Most Grandmaster buildings fall within Permitted Development
                rights — single-storey, under 2.5m eaves, under 50% garden
                coverage. Our team can advise on typical scenarios.
              </div>
            </div>
          </div>
          <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-q">
              <span class="faq-qt">Are there any discounts available?</span
              ><span class="faq-tog">+</span>
            </div>
            <div class="faq-ans">
              <div class="faq-ans-in">
                Yes — enjoy 10% off Grandmaster products with code
                <strong>GM10</strong> at checkout on Project Timber.
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- FINAL CTA -->
```

- [ ] **Step 3: Verify**

Run: `grep -n 'id="faq"' "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: one match.

Run: `grep -n "Are there any discounts available" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: one match.

- [ ] **Step 4: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: add FAQ id and GM10 discount question"
```

---

### Task 10: Final CTA — outbound link, remove phone button

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (~lines 3009–3030)

**Interfaces:** None.

- [ ] **Step 1: Replace the final CTA copy and buttons**

Use the Edit tool with this exact `old_string`:

```html
        <p class="final-sub">
          Browse the full Grandmaster range, choose your size and layout, and
          speak to our UK team — no bots, no pressure, just the right building
          for your garden.
        </p>
        <div class="final-btns">
          <a href="#products" class="btn btn-white btn-lg"
            >Explore All Buildings →</a
          >
          <a href="tel:01777802300" class="btn btn-ghost">Call 01777 802 300</a>
        </div>
```

Replace with this `new_string`:

```html
        <p class="final-sub">
          Browse the full Grandmaster range, choose your size and layout, and
          apply code <strong style="color: #fff">GM10</strong> for 10% off on
          Project Timber.
        </p>
        <div class="final-btns">
          <a
            href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
            class="btn btn-white btn-lg"
            >Shop the Grandmaster Range →</a
          >
        </div>
```

- [ ] **Step 2: Verify**

Run: `grep -n "tel:01777802300\|Call 01777\|speak to our UK team" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output.

- [ ] **Step 3: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: outbound final CTA, remove phone button"
```

---

### Task 11: Footer — real company info and links

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (~lines 3031–3080)

**Interfaces:** Consumes the `#configure` (Task 6) and `#faq` (Task 9) anchor ids.

- [ ] **Step 1: Replace the Buildings, Information, and Contact columns**

Use the Edit tool with this exact `old_string`:

```html
          <div class="ft-col">
            <div class="ft-col-h">Buildings</div>
            <a href="#">Diplomat Classic</a><a href="#">Clubhouse</a>
            <a href="#">Fusion + Side Shed</a
            ><a href="#">Lounge Garden Room</a> <a href="#">Alpine Cabin</a
            ><a href="#">D1000 Workshop</a>
          </div>
          <div class="ft-col">
            <div class="ft-col-h">Information</div>
            <a href="#">Specifications</a><a href="#">Bundles & Upgrades</a>
            <a href="#">Installation Guide</a><a href="#">Base Advice</a>
            <a href="#">Planning Guide</a><a href="#">FAQs</a>
          </div>
          <div class="ft-col">
            <div class="ft-col-h">Contact</div>
            <a href="tel:01777802300">01777 802 300</a>
            <a href="#">Request a Callback</a><a href="#">WhatsApp Us</a>
            <a href="#">Email the Team</a><a href="#">Trustpilot Reviews</a>
          </div>
```

Replace with this `new_string`:

```html
          <div class="ft-col">
            <div class="ft-col-h">Buildings</div>
            <a
              href="https://www.projecttimber.com/grandmaster/pressure-treated-diplomat-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Diplomat</a
            ><a
              href="https://www.projecttimber.com/grandmaster/pressure-treated-club-house-garden-room-french-doors/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Clubhouse</a
            >
            <a
              href="https://www.projecttimber.com/grandmaster/grandmaster-fusion-classic-g1000-summerhouse-with-side-shed-16mm-pressure-treated-tongue-and-groove/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Fusion + Side Shed</a
            ><a
              href="https://www.projecttimber.com/grandmaster/pressure-treated-lounge-contemporary-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Lounge Garden Room</a
            >
            <a
              href="https://www.projecttimber.com/grandmaster/pressure-treated-alpine-grandmaster-summerhouse/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Alpine Cabin</a
            ><a
              href="https://www.projecttimber.com/grandmaster/pressure-treated-traditional-windowed-apex-grandmaster-workshop/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >D1000 Workshop</a
            >
          </div>
          <div class="ft-col">
            <div class="ft-col-h">Information</div>
            <a href="#why">Specifications</a>
            <a href="#configure">Make It Yours</a>
            <a href="#faq">FAQs</a>
          </div>
          <div class="ft-col">
            <div class="ft-col-h">Project Timber</div>
            <a
              href="https://www.projecttimber.com/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Visit Project Timber</a
            >
            <a
              href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Shop Grandmaster</a
            >
            <a
              href="https://www.projecttimber.com/testimonials/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
              >Customer Reviews</a
            >
          </div>
```

- [ ] **Step 2: Fix the legal/copyright line and add the guarantee disclaimer**

Use the Edit tool with this exact `old_string`:

```html
        <div class="ft-bottom">
          <p>
            © 2025 Grandmaster Garden Buildings · Kybotech Ltd. All rights
            reserved.
          </p>
          <p>Privacy · Terms · Cookies</p>
        </div>
```

Replace with this `new_string`:

```html
        <p
          style="
            font-size: 11px;
            color: rgba(255, 255, 255, 0.22);
            max-width: 700px;
            margin: 0 auto 20px;
            text-align: center;
          "
        >
          *Guarantee terms vary by product and are confirmed at checkout on
          projecttimber.com.
        </p>
        <div class="ft-bottom">
          <p>
            © 2026 Grandmaster Garden Buildings, an independent buying guide.
            Products sold by Project Timber Limited, company no. 05126131.
            Registered office: Parry Works, Grassthorpe Road,
            Sutton-on-Trent, Newark NG23 6QX.
          </p>
          <p>Privacy · Terms · Cookies</p>
        </div>
```

- [ ] **Step 3: Verify**

Run: `grep -n "Kybotech\|tel:01777802300\|Request a Callback\|WhatsApp Us" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output.

Run: `grep -n "05126131\|Sutton-on-Trent" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: at least one match each.

- [ ] **Step 4: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: real company info and footer links"
```

---

### Task 12: Script cleanup — remove orphaned bundle-tab function

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html` (`<script>` block, ~line 3154)

**Interfaces:** None. Depends on Task 6 already having removed all `onclick="switchB(...)"` call sites.

- [ ] **Step 1: Remove the `switchB` function**

Use the Edit tool with this exact `old_string`:

```html
      // ── BUNDLE TABS
      function switchB(btn, type) {
        document
          .querySelectorAll(".btab")
          .forEach((b) => b.classList.remove("on"));
        btn.classList.add("on");
        document.querySelectorAll(".bcard").forEach((c) => {
          c.style.display = c.dataset.bt === type ? "block" : "none";
        });
      }

```

Replace with this `new_string`:

```html
```

(i.e. delete the block entirely — leave nothing in its place.)

- [ ] **Step 2: Verify**

Run: `grep -n "switchB\|BUNDLE TABS" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: no output.

Run: `node --check "design-files/affiliate-marketing-pages/grandmaster_homepage.html" 2>&1 | head -5 || true`
(This will report a syntax error pointing at the `<!doctype html>` line since it's not a `.js` file — that's expected and not meaningful. Instead, extract and check just the script contents:)

Run:
```bash
python3 -c "
import re
html = open('design-files/affiliate-marketing-pages/grandmaster_homepage.html').read()
m = re.search(r'<script>(.*)</script>', html, re.S)
open('/tmp/gm-script-check.js', 'w').write(m.group(1))
"
node --check /tmp/gm-script-check.js && echo "SCRIPT OK"
```
Expected: `SCRIPT OK` (confirms the remaining JS is still syntactically valid after deleting the function).

- [ ] **Step 3: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: remove orphaned switchB bundle-tab function"
```

---

### Task 13: Reorder sections — Use Cases early, Reviews after Products

**Files:**
- Modify: `design-files/affiliate-marketing-pages/grandmaster_homepage.html`

**Interfaces:** Depends on Tasks 7 and 8 having already updated the Use Cases and Reviews content in place — this task only moves the (already-correct) blocks.

**Safety note before starting:** the `old_string`/`new_string` blocks below were hand-authored to match the output of Tasks 7 and 8, but whitespace/indentation can drift by a space or two across a plan this size. Before each Edit call in this task, if the exact text below is not found verbatim in the file, use the Read tool to view the current content at that location, and adjust only the whitespace/indentation of the `old_string` (never the wording) to match what's actually in the file, then retry. Do not guess at content — the wording itself should already be correct from Tasks 7/8.

Current order at the start of this task: NAV, HERO, PROMISE BAR, WHY/SPECS, PRODUCTS, PARALLAX BAND, MAKE IT YOURS, USE CASES, REVIEWS, FAQ, FINAL CTA, FOOTER.

Target order: NAV, HERO, PROMISE BAR, **USE CASES**, WHY/SPECS, PRODUCTS, **REVIEWS**, PARALLAX BAND, MAKE IT YOURS, FAQ, FINAL CTA, FOOTER.

- [ ] **Step 1: Insert Use Cases after the Promise bar**

Use the Edit tool with this exact `old_string` (the tail of the Promise bar and the start of the Why/Specs comment):

```html
        <div class="psep"></div>
        <div class="pitem">
          <span class="pam">✓</span>&nbsp;<strong>Free</strong>&nbsp;UK Delivery
        </div>
      </div>
    </div>
    <!-- WHY / SPECS -->
```

Replace with this `new_string` (same anchor text, with the full Use Cases section inserted between):

```html
        <div class="psep"></div>
        <div class="pitem">
          <span class="pam">✓</span>&nbsp;<strong>Free</strong>&nbsp;UK Delivery
        </div>
      </div>
    </div>
    <!-- USE CASES -->
    <section class="uses-dark section">
      <div class="wrap">
        <div class="reveal" style="text-align: center; margin-bottom: 44px">
          <div
            class="eyebrow eyebrow-center"
            style="color: rgba(255, 255, 255, 0.38)"
          >
            Built for your life
          </div>
          <h2 class="h2 h2-white">What will yours become?</h2>
          <p
            class="subtitle"
            style="
              color: rgba(255, 255, 255, 0.48);
              max-width: 440px;
              margin: 12px auto 0;
            "
          >
            A Grandmaster is a blank canvas with serious bones. Whatever your
            garden needs — all year round.
          </p>
        </div>
        <div class="uses-grid">
          <div class="uc">
            <div class="uc-bg uc-bg-office"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">💼</span>
              <div class="uc-title">Garden Office</div>
              <div class="uc-desc">
                Quiet, insulated, 30 seconds from the house. A real workspace
                with room to think.
              </div>
              <a
                href="https://www.projecttimber.com/garden-offices/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop office buildings →</a
              >
            </div>
          </div>
          <div class="uc">
            <div class="uc-bg uc-bg-gym"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">🏋️</span>
              <div class="uc-title">Home Gym</div>
              <div class="uc-desc">
                The 54mm studwork handles squat racks, pull-up rigs, and
                whatever you throw at it.
              </div>
              <a
                href="https://www.projecttimber.com/insulated-garden-buildings/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop gym buildings →</a
              >
            </div>
          </div>
          <div class="uc">
            <div class="uc-bg uc-bg-studio"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">🎨</span>
              <div class="uc-title">Creative Studio</div>
              <div class="uc-desc">
                Natural light, privacy, acoustic calm. Better than any spare
                room.
              </div>
              <a
                href="https://www.projecttimber.com/insulated-garden-buildings/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop studio buildings →</a
              >
            </div>
          </div>
          <div class="uc">
            <div class="uc-bg uc-bg-workshop"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">🔧</span>
              <div class="uc-title">Trade Workshop</div>
              <div class="uc-desc">
                A workshop that finally matches your tools. Commercial-grade at
                garden scale.
              </div>
              <a
                href="https://www.projecttimber.com/garden-workshops/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop workshop buildings →</a
              >
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- WHY / SPECS -->
```

- [ ] **Step 2: Delete Use Cases from its old position**

At this point the Use Cases block exists twice: once newly inserted (Step 1, preceded by `<!-- USE CASES -->` right after the Promise bar), and once at its original location (preceded by the Make-it-yours section's closing and followed by `<!-- REVIEWS -->`). Use the Edit tool to delete the **original** one — use enough surrounding context to target only that occurrence.

Use this exact `old_string` (Make-it-yours' closing tags + the old-position Use Cases block + the start of Reviews):

```html
        <p class="bundles-note">
          Options shown here are configured on the product page during
          checkout on Project Timber — availability and pricing depend on the
          specific building and size you choose.
        </p>
      </div>
    </section>
    <!-- USE CASES -->
    <section class="uses-dark section">
      <div class="wrap">
        <div class="reveal" style="text-align: center; margin-bottom: 44px">
          <div
            class="eyebrow eyebrow-center"
            style="color: rgba(255, 255, 255, 0.38)"
          >
            Built for your life
          </div>
          <h2 class="h2 h2-white">What will yours become?</h2>
          <p
            class="subtitle"
            style="
              color: rgba(255, 255, 255, 0.48);
              max-width: 440px;
              margin: 12px auto 0;
            "
          >
            A Grandmaster is a blank canvas with serious bones. Whatever your
            garden needs — all year round.
          </p>
        </div>
        <div class="uses-grid">
          <div class="uc">
            <div class="uc-bg uc-bg-office"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">💼</span>
              <div class="uc-title">Garden Office</div>
              <div class="uc-desc">
                Quiet, insulated, 30 seconds from the house. A real workspace
                with room to think.
              </div>
              <a
                href="https://www.projecttimber.com/garden-offices/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop office buildings →</a
              >
            </div>
          </div>
          <div class="uc">
            <div class="uc-bg uc-bg-gym"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">🏋️</span>
              <div class="uc-title">Home Gym</div>
              <div class="uc-desc">
                The 54mm studwork handles squat racks, pull-up rigs, and
                whatever you throw at it.
              </div>
              <a
                href="https://www.projecttimber.com/insulated-garden-buildings/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop gym buildings →</a
              >
            </div>
          </div>
          <div class="uc">
            <div class="uc-bg uc-bg-studio"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">🎨</span>
              <div class="uc-title">Creative Studio</div>
              <div class="uc-desc">
                Natural light, privacy, acoustic calm. Better than any spare
                room.
              </div>
              <a
                href="https://www.projecttimber.com/insulated-garden-buildings/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop studio buildings →</a
              >
            </div>
          </div>
          <div class="uc">
            <div class="uc-bg uc-bg-workshop"></div>
            <div class="uc-grad"></div>
            <div class="uc-content">
              <span class="uc-icon">🔧</span>
              <div class="uc-title">Trade Workshop</div>
              <div class="uc-desc">
                A workshop that finally matches your tools. Commercial-grade at
                garden scale.
              </div>
              <a
                href="https://www.projecttimber.com/garden-workshops/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="uc-link"
                >Shop workshop buildings →</a
              >
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- REVIEWS -->
```

Replace with this `new_string`:

```html
        <p class="bundles-note">
          Options shown here are configured on the product page during
          checkout on Project Timber — availability and pricing depend on the
          specific building and size you choose.
        </p>
      </div>
    </section>
    <!-- REVIEWS -->
```

- [ ] **Step 3: Verify Use Cases now appears exactly once, in the right place**

Run: `grep -c "Built for your life" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"`
Expected: `1`

Run: `grep -n "Free.*UK Delivery\|USE CASES\|WHY / SPECS" "design-files/affiliate-marketing-pages/grandmaster_homepage.html" | head -5`
Expected: order shows `UK Delivery` line, then `<!-- USE CASES -->`, then `<!-- WHY / SPECS -->` (in that line-number order).

- [ ] **Step 4: Insert Reviews after Products**

Use the Edit tool with this exact `old_string` (the closing of the Products section's "View All" button and the start of the Parallax band):

```html
        <div class="prod-more">
          <a
            href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
            class="btn-outline-ink"
            style="text-decoration: none; display: inline-block"
            >View All 25 Buildings →</a
          >
        </div>
      </div>
    </section>
    <!-- PARALLAX BAND -->
```

Replace with this `new_string` (same anchor text, with the full updated Reviews section inserted between):

```html
        <div class="prod-more">
          <a
            href="https://www.projecttimber.com/grandmaster/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
            class="btn-outline-ink"
            style="text-decoration: none; display: inline-block"
            >View All 25 Buildings →</a
          >
        </div>
      </div>
    </section>
    <!-- REVIEWS -->
    <section class="rv-bg section" id="reviews">
      <div class="wrap">
        <div class="rv-header reveal">
          <div>
            <div class="eyebrow">Customer Reviews</div>
            <h2 class="h2">Real gardens.<br /><em>Real results.</em></h2>
          </div>
          <div style="display: flex; align-items: center; gap: 20px">
            <div>
              <div class="tp-stars" style="font-size: 32px">★★★★★</div>
              <div class="tp-lbl">Rated Excellent</div>
              <div class="tp-sub">Thousands of verified UK reviews</div>
              <a
                href="https://www.projecttimber.com/testimonials/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="btn-shop"
                style="margin-top: 14px"
                >View Real Reviews →</a
              >
            </div>
          </div>
        </div>
        <div class="rv-grid">
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">The modular design makes a lot of sense</div>
            <div class="rv-text">
              "Flexible design, standardisation of parts and easy-to-manage
              panels. It fits compactly onto one pallet and doesn't take up
              much space until you're ready to assemble. Screws to fix the
              panels help pull everything together neatly. Excellent customer
              care team, very helpful. Would definitely recommend."
            </div>
            <div class="rv-author">William</div>
            <div class="rv-meta">Verified Buyer · Sep 09, 2024</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">
              Excellent customer service and a great product
            </div>
            <div class="rv-text">
              "The shed arrived within 2 weeks, on the exact day promised and
              at the time specified. Packed neatly on one pallet, it was easy
              to transport the parts to where they were needed."
            </div>
            <div class="rv-author">Tony H.</div>
            <div class="rv-meta">Verified Buyer · Oct 19, 2023</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">Great quality and amazing shed</div>
            <div class="rv-text">
              "…the customer service was second to none. The shed arrived on a
              single pallet on the date agreed and all the panels were in
              great shape. I built it with no issues at all and it looks
              amazing!"
            </div>
            <div class="rv-author">Dean M.</div>
            <div class="rv-meta">Verified Buyer · Mar 03, 2023</div>
          </div>
        </div>
      </div>
    </section>
    <!-- PARALLAX BAND -->
```

- [ ] **Step 5: Delete Reviews from its old position**

The Reviews block now exists twice. Delete the **original** one, which sits between the (already emptied-of-Use-Cases) Use Cases removal point and the FAQ section. Use this exact `old_string` (the tail of the now-relocated-away Use Cases area is gone already — the original Reviews block is now directly followed by `<!-- FAQ -->`):

```html
    <!-- REVIEWS -->
    <section class="rv-bg section" id="reviews">
      <div class="wrap">
        <div class="rv-header reveal">
          <div>
            <div class="eyebrow">Customer Reviews</div>
            <h2 class="h2">Real gardens.<br /><em>Real results.</em></h2>
          </div>
          <div style="display: flex; align-items: center; gap: 20px">
            <div>
              <div class="tp-stars" style="font-size: 32px">★★★★★</div>
              <div class="tp-lbl">Rated Excellent</div>
              <div class="tp-sub">Thousands of verified UK reviews</div>
              <a
                href="https://www.projecttimber.com/testimonials/?utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10"
                class="btn-shop"
                style="margin-top: 14px"
                >View Real Reviews →</a
              >
            </div>
          </div>
        </div>
        <div class="rv-grid">
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">The modular design makes a lot of sense</div>
            <div class="rv-text">
              "Flexible design, standardisation of parts and easy-to-manage
              panels. It fits compactly onto one pallet and doesn't take up
              much space until you're ready to assemble. Screws to fix the
              panels help pull everything together neatly. Excellent customer
              care team, very helpful. Would definitely recommend."
            </div>
            <div class="rv-author">William</div>
            <div class="rv-meta">Verified Buyer · Sep 09, 2024</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">
              Excellent customer service and a great product
            </div>
            <div class="rv-text">
              "The shed arrived within 2 weeks, on the exact day promised and
              at the time specified. Packed neatly on one pallet, it was easy
              to transport the parts to where they were needed."
            </div>
            <div class="rv-author">Tony H.</div>
            <div class="rv-meta">Verified Buyer · Oct 19, 2023</div>
          </div>
          <div class="rv-card reveal">
            <div class="rv-q">"</div>
            <div class="rv-stars">★★★★★</div>
            <div class="rv-prod">Great quality and amazing shed</div>
            <div class="rv-text">
              "…the customer service was second to none. The shed arrived on a
              single pallet on the date agreed and all the panels were in
              great shape. I built it with no issues at all and it looks
              amazing!"
            </div>
            <div class="rv-author">Dean M.</div>
            <div class="rv-meta">Verified Buyer · Mar 03, 2023</div>
          </div>
        </div>
      </div>
    </section>
    <!-- FAQ -->
```

Replace with this `new_string`:

```html
    <!-- FAQ -->
```

- [ ] **Step 6: Verify final section order**

Run:
```bash
grep -n "<!-- HERO -->\|<!-- PROMISE BAR -->\|<!-- USE CASES -->\|<!-- WHY / SPECS -->\|<!-- PRODUCTS -->\|<!-- REVIEWS -->\|<!-- PARALLAX BAND -->\|<!-- MAKE IT YOURS -->\|<!-- FAQ -->\|<!-- FINAL CTA -->\|<footer>" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
```
Expected: the line numbers increase in exactly this order: HERO, PROMISE BAR, USE CASES, WHY/SPECS, PRODUCTS, REVIEWS, PARALLAX BAND, MAKE IT YOURS, FAQ, FINAL CTA, footer.

Run: `grep -c "Built for your life" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"` → expect `1`
Run: `grep -c "Real gardens" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"` → expect `1`
Run: `grep -c 'id="reviews"' "design-files/affiliate-marketing-pages/grandmaster_homepage.html"` → expect `1`

- [ ] **Step 7: Commit**

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: reorder sections for redirect-only conversion funnel"
```

---

### Task 14: Full-page verification

**Files:**
- Read-only checks against: `design-files/affiliate-marketing-pages/grandmaster_homepage.html`

**Interfaces:** None — final QA gate for the whole plan.

- [ ] **Step 1: Sweep for every fabricated fact that should be gone**

Run:
```bash
grep -n "Kybotech\|01777 802 300\|tel:01777802300\|4\.9\|Weather Protection Pack\|Garden Room Ready Pack\|Complete Retreat Pack\|Security & Storage Pack\|Year-Round Workshop Pack\|Trade Workshop Pack\|Mark T\.\|Graham S\.\|James P\.\|switchB\|BUNDLE TABS\|href=\"#\" class=\"uc-link\"" "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
```
Expected: no output at all.

- [ ] **Step 2: Confirm no remaining bare `#` outbound-style CTAs**

Run:
```bash
grep -n 'href="#"' "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
```
Expected: only the 2 logo links (`<a href="#" class="nav-logo">` in nav and drawer) — these are intentionally inert brand-mark links, not CTAs, and were never in scope to change.

- [ ] **Step 3: Confirm every outbound link carries the UTM tag**

Run:
```bash
grep -o 'href="https://www.projecttimber.com[^"]*"' "design-files/affiliate-marketing-pages/grandmaster_homepage.html" | sort -u
```
Expected: every line contains `utm_source=grandmaster-guide&utm_medium=advertorial&utm_campaign=gm10`. Manually eyeball the output — flag anything missing the UTM suffix.

- [ ] **Step 4: Confirm the script is still valid JS**

Run:
```bash
python3 -c "
import re
html = open('design-files/affiliate-marketing-pages/grandmaster_homepage.html').read()
m = re.search(r'<script>(.*)</script>', html, re.S)
open('/tmp/gm-script-check.js', 'w').write(m.group(1))
"
node --check /tmp/gm-script-check.js && echo "SCRIPT OK"
```
Expected: `SCRIPT OK`

- [ ] **Step 5: Manual browser QA**

Run a local server and open the file:
```bash
cd "design-files/affiliate-marketing-pages" && python3 -m http.server 8642
```
Then open `http://localhost:8642/grandmaster_homepage.html` in a browser and check:
- Section order top-to-bottom matches: Hero → Promise bar → Use Cases → Why/Specs → Products → Reviews → Parallax band → Make It Yours → FAQ → Final CTA → Footer.
- Product filter pills (All/Summerhouses/Workshops & Sheds/Garden Rooms) still show/hide the right 6 cards.
- FAQ accordion still opens/closes on click, including the new 7th GM10 item.
- Mobile drawer (resize below 1024px, click hamburger) opens/closes and its links scroll to the right sections.
- Parallax backgrounds still shift on scroll (desktop width only — disabled below 768px by design).
- Spot-check 2–3 outbound links (e.g. a product "Explore →", the nav "Shop the Range →", footer "Visit Project Timber") open the correct real projecttimber.com URL with the UTM query string intact.

Stop the server with `Ctrl+C` when done.

- [ ] **Step 6: Final commit (only if Step 5 surfaced fixes)**

If manual QA found nothing to fix, no commit is needed for this task. If it did, fix inline, re-run Steps 1–4, then:

```bash
git add "design-files/affiliate-marketing-pages/grandmaster_homepage.html"
git commit -m "Grandmaster page: fix issues found in final QA pass"
```
