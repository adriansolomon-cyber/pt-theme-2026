<?php
/**
 * Default page template.
 *
 * Renders regular WordPress pages — including the WooCommerce shortcode pages
 * (Cart, Checkout, My Account) and CMS pages — inside the theme chrome. Without
 * this, pages fell through to index.php (a bare scaffold) and showed no content.
 *
 * WooCommerce pages render their own markup via the shortcode in the_content();
 * cart/checkout/account styling comes from their per-page CSS + template overrides
 * (e.g. woocommerce/checkout/form-checkout.php).
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// WooCommerce shortcode pages render their own headings — only show the page
// title on ordinary CMS pages.
$pt_is_wc_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>

<main class="wrap pt-page" id="main" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<?php if ( ! $pt_is_wc_page && ! is_front_page() ) : ?>
			<header class="pt-page-head"><h1 class="pt-page-title"><?php the_title(); ?></h1></header>
		<?php endif; ?>
		<div class="pt-page-content">
			<?php the_content(); ?>
		</div>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
