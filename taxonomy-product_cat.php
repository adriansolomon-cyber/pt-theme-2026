<?php
/**
 * Product-category archive — server-rendered (WooCommerce native).
 *
 * Converted from projecttimber-category-page.html. The product grid, filters,
 * count and description are rendered on the server from the current term's
 * DIRECT products (pt_cat_get_data() → mu-plugin timber_catp_build, or the
 * native fallback). assets/js/category.js then runs in "server-rendered mode":
 * it filters/sorts the cards already in the DOM — no fetching, no skeleton.
 *
 * Design markup is unchanged from the prototype; only the grid/filters are now
 * PHP loops emitting the same cards category.js used to inject.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_term     = get_queried_object();
$pt_data     = pt_cat_get_data( $pt_term );
$pt_cat      = isset( $pt_data['category'] ) ? $pt_data['category'] : array();
$pt_products = isset( $pt_data['products'] ) ? $pt_data['products'] : array();
$pt_name     = isset( $pt_cat['name'] ) ? $pt_cat['name'] : ( isset( $pt_term->name ) ? $pt_term->name : '' );
$pt_desc     = isset( $pt_cat['description'] ) ? $pt_cat['description'] : '';
$pt_count    = count( $pt_products );
$pt_intro    = pt_cat_intro_desc( $pt_desc );
$pt_desc_html = ( '' !== trim( (string) $pt_desc ) )
	? ( preg_match( '/<(p|h[1-6]|ul|ol|div|section|br)\b/i', $pt_desc ) ? wp_kses_post( $pt_desc ) : pt_cat_paragraphs_html( $pt_desc ) )
	: '';

// Optional per-category banner (ACF field group "Category Banner" on product_cat).
// The "Show category banner" toggle gates BOTH the top banner image AND the in-grid
// promo tile below — neither shows unless it's on for this category (default off).
$pt_banner_on  = ( function_exists( 'get_field' ) && $pt_term && get_field( 'cat_banner_enabled', $pt_term ) );
$pt_banner_src = $pt_banner_src_m = $pt_banner_link = $pt_banner_alt = '';
if ( $pt_banner_on ) {
	$pt_banner_src   = (string) get_field( 'cat_banner_image', $pt_term );
	$pt_banner_src_m = (string) get_field( 'cat_banner_image_mobile', $pt_term );
	$pt_banner_link  = (string) get_field( 'cat_banner_link', $pt_term );
	$pt_banner_alt   = (string) get_field( 'cat_banner_alt', $pt_term );
}

get_header();
?>

<main class="wrap" id="main" tabindex="-1">
  <!-- breadcrumb -->
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="sep">/</span><span class="here" aria-current="page"><?php echo esc_html( $pt_name ); ?></span>
  </nav>

  <?php if ( '' !== $pt_banner_src ) : ?>
    <?php
    // Picture element so an optional mobile image can swap in under 640px.
    $pt_banner_img = '<picture class="cat-banner-pic">'
        . ( '' !== $pt_banner_src_m ? '<source media="(max-width:640px)" srcset="' . esc_url( $pt_banner_src_m ) . '">' : '' )
        . '<img src="' . esc_url( $pt_banner_src ) . '" alt="' . esc_attr( '' !== $pt_banner_alt ? $pt_banner_alt : $pt_name ) . '" loading="eager">'
        . '</picture>';
    ?>
    <?php if ( '' !== $pt_banner_link ) : ?>
      <a class="cat-banner" href="<?php echo esc_url( $pt_banner_link ); ?>"><?php echo $pt_banner_img; // phpcs:ignore WordPress.Security.EscapeOutput -- built with esc_url/esc_attr above ?></a>
    <?php else : ?>
      <div class="cat-banner"><?php echo $pt_banner_img; // phpcs:ignore WordPress.Security.EscapeOutput -- built with esc_url/esc_attr above ?></div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- intro — title + lede rendered server-side from the category -->
  <div class="cat-intro">
    <h1><?php echo esc_html( $pt_name ); ?></h1>
    <?php if ( '' !== $pt_intro ) : ?>
      <p><?php echo esc_html( $pt_intro ); ?></p>
    <?php else : ?>
      <p></p>
    <?php endif; ?>
  </div>

  <!-- toolbar -->
  <div class="cat-toolbar">
    <button class="filt-btn" id="openFilters"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M6 12h12M10 18h4"/></svg> Filters</button>
    <span class="count"><b id="count"><?php echo (int) $pt_count; ?></b> products</span>
    <div class="sort">
      <label for="sort">Sort</label>
      <select id="sort">
        <option value="featured">Featured</option>
        <option value="low">Price: low to high</option>
        <option value="high">Price: high to low</option>
      </select>
    </div>
  </div>

  <!-- products — rendered server-side; category.js filters/sorts these in place -->
  <div class="prod-grid" id="grid">
    <?php
    foreach ( $pt_products as $pt_i => $pt_p ) {
        echo pt_cat_card_html( $pt_p ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within helper
        // Promo banner as the 2nd grid item (matches category.js placePromo()).
        // Only when the category's banner switcher is on. category.js placePromo()
        // just repositions this card, so omitting it here removes it everywhere.
        if ( $pt_banner_on && 0 === $pt_i ) {
            ?>
            <a class="promo-card" href="<?php echo esc_url( home_url( '/grandmaster/' ) ); ?>" aria-label="Current promotion">
              <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/Square.png" alt="Current promotion">
            </a>
            <?php
        }
    }
    if ( $pt_banner_on && 0 === $pt_count ) {
        ?>
        <a class="promo-card" href="<?php echo esc_url( home_url( '/grandmaster/' ) ); ?>" aria-label="Current promotion">
          <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/Square.png" alt="Current promotion">
        </a>
        <?php
    }
    ?>
    <p class="noresults" id="noresults"<?php echo $pt_count ? ' hidden' : ''; ?>><?php echo $pt_count ? 'No products match those filters. <a href="#" id="clearFilters" style="color:var(--charcoal);font-weight:700">Clear filters</a>' : 'No products found in this category.'; ?></p>
  </div>
  <div class="grid-foot"><?php echo $pt_count ? 'Showing all <b>' . (int) $pt_count . '</b> product' . ( 1 === $pt_count ? '' : 's' ) : ''; ?></div>
</main>

<!-- ===================== CATEGORY CONTENT (SEO) — heading + copy from the category description ===================== -->
<section class="cat-bottom"<?php echo '' === $pt_desc_html ? ' style="display:none"' : ''; ?>><div class="wrap">
  <h2><?php echo esc_html( $pt_name ); ?></h2>
  <?php echo $pt_desc_html; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses_post / built from esc_html above ?>
</div></section>

<!-- ===================== FILTER DRAWER (hidden until opened) ===================== -->
<div class="drawer-backdrop" id="backdrop"></div>
<aside class="drawer" id="drawer" aria-label="Filters">
  <div class="drawer-head"><h3>Filters</h3><button class="ptc-x" id="closeFilters" type="button" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
  <div class="drawer-body"><?php echo pt_cat_filters_html( $pt_products ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within helper ?></div>
  <div class="drawer-foot">
    <button class="reset" id="resetFilters" type="button">Reset</button>
    <button class="apply" id="applyFilters" type="button">Show results</button>
  </div>
</aside>

<!-- trust strip (relocated below the products so it doesn't push the grid down) -->
<div class="trust-strip"><div class="wrap">
  <span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8.3 12.4l2.6 2.6 4.8-5.2"/></svg> Made in Britain</span>
  <span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="7" width="11" height="9" rx="1"/><path d="M13.5 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.5"/><circle cx="17.5" cy="18" r="1.5"/></svg> Free delivery (selected postcodes)*</span>
  <span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg> Up to 25-year anti-rot guarantee*</span>
</div></div>

<?php
get_footer();
