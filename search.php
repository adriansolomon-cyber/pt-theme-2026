<?php
/**
 * Product search results — server-rendered, reusing the category card grid.
 *
 * The header search form submits ?s=<term>&post_type=product, so search on this
 * site is product-only. Results are rendered with the SAME cards as the category
 * archive (pt_cat_product_entry() → pt_cat_card_html()), including composite
 * "from" pricing and campaign discount badges.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_term  = trim( get_search_query() );
$pt_paged = max( 1, (int) get_query_var( 'paged' ) );

$pt_products = array();
$pt_pages    = 1;

if ( '' !== $pt_term && function_exists( 'wc_get_product' ) ) {
	$pt_q = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			's'                   => $pt_term,
			'posts_per_page'      => 24,
			'paged'               => $pt_paged,
			'ignore_sticky_posts' => true,
			// Respect catalog visibility: drop products hidden from search.
			'tax_query'           => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-search' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);
	$pt_pages = max( 1, (int) $pt_q->max_num_pages );
	foreach ( (array) $pt_q->posts as $pt_post ) {
		$entry = pt_cat_product_entry( wc_get_product( $pt_post->ID ) );
		if ( $entry ) {
			$pt_products[] = $entry;
		}
	}
	wp_reset_postdata();
}

$pt_count = count( $pt_products );

get_header();
?>

<main class="wrap" id="main" tabindex="-1">
  <!-- breadcrumb -->
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="sep">/</span><span class="here" aria-current="page">Search</span>
  </nav>

  <!-- intro -->
  <div class="cat-intro">
    <?php if ( '' !== $pt_term ) : ?>
      <h1>Search results for &ldquo;<?php echo esc_html( $pt_term ); ?>&rdquo;</h1>
    <?php else : ?>
      <h1>Search</h1>
      <p>Enter a search term to find products.</p>
    <?php endif; ?>
  </div>

  <?php if ( '' !== $pt_term ) : ?>
  <!-- toolbar -->
  <div class="cat-toolbar">
    <span class="count"><b><?php echo (int) $pt_count; ?></b> product<?php echo 1 === $pt_count ? '' : 's'; ?></span>
  </div>

  <!-- products — same cards as the category grid -->
  <div class="prod-grid" id="grid">
    <?php
    foreach ( $pt_products as $pt_p ) {
        echo pt_cat_card_html( $pt_p ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within helper
    }
    if ( 0 === $pt_count ) {
        ?>
        <p class="noresults">No products match &ldquo;<?php echo esc_html( $pt_term ); ?>&rdquo;. Try a different search, or <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--charcoal);font-weight:700">browse the range</a>.</p>
        <?php
    }
    ?>
  </div>

  <?php if ( $pt_pages > 1 ) : ?>
  <nav class="search-pagination" aria-label="Search results pages">
    <?php
    echo wp_kses_post(
        paginate_links(
            array(
                'base'      => esc_url_raw( add_query_arg( 'paged', '%#%' ) ),
                'format'    => '',
                'current'   => $pt_paged,
                'total'     => $pt_pages,
                'prev_text' => '‹ Prev',
                'next_text' => 'Next ›',
            )
        )
    );
    ?>
  </nav>
  <?php endif; ?>

  <div class="grid-foot">
    <?php echo $pt_count ? 'Showing <b>' . (int) $pt_count . '</b> product' . ( 1 === $pt_count ? '' : 's' ) . ( $pt_pages > 1 ? ' (page ' . (int) $pt_paged . ' of ' . (int) $pt_pages . ')' : '' ) : ''; ?>
  </div>
  <?php endif; ?>
</main>

<?php
get_footer();
