<?php
/**
 * Self-contained product content previewer.
 *
 * Renders the EXACT product page body (inc/single-product-body.php) from live
 * ACF + product data for the product id in $pt_pid, as a standalone HTML
 * document with the theme's product CSS and JS inlined — no header/footer/cart
 * chrome, independent of the enqueue system. Reached via ?pt_preview=ID
 * (functions.php gates it to users who can edit the product) so managers can
 * preview content without touching the live template.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $pt_pid is provided by the caller (functions.php template_redirect handler).
$pt_pid        = isset( $pt_pid ) ? (int) $pt_pid : 0;
$pt_is_preview = true; // hides the "Preview content" button inside the preview.

// Shared setup: defines $pt_name, $pt_product, $pt_line, $pt_from, the
// $pt_f/$pt_show/$pt_has_rows helpers, $pt_hero_img, etc.
require __DIR__ . '/single-product-setup.php';

$pt_dir = get_stylesheet_directory();
$pt_uri = get_stylesheet_directory_uri();

// Inline the theme's product styles/scripts so the preview is self-contained.
$pt_css = '';
foreach ( array( '/assets/css/base.css', '/assets/css/product.css' ) as $pt_f_css ) {
	if ( file_exists( $pt_dir . $pt_f_css ) ) {
		$pt_css .= file_get_contents( $pt_dir . $pt_f_css ) . "\n"; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
}
$pt_js = file_exists( $pt_dir . '/assets/js/product.js' ) ? file_get_contents( $pt_dir . '/assets/js/product.js' ) : ''; // phpcs:ignore

// Config globals product.js expects (mirrors functions.php's is_product() block).
$pt_pv_disc = function_exists( 'pt_product_discount_pct' ) ? (float) pt_product_discount_pct( $pt_pid ) : 0.0;
$pt_pv_spec = function_exists( 'pt_spec_size_images' ) ? pt_spec_size_images( $pt_pid ) : array();
$pt_pv_best = array();
if ( function_exists( 'get_field' ) ) {
	$pt_pv_raw = get_field( 'best_seller_sizes', $pt_pid );
	if ( is_array( $pt_pv_raw ) ) {
		foreach ( $pt_pv_raw as $pt_pv_b ) {
			if ( is_string( $pt_pv_b ) ) {
				$pt_pv_best[] = trim( $pt_pv_b );
			} elseif ( is_array( $pt_pv_b ) ) {
				$pt_pv_v = reset( $pt_pv_b );
				if ( is_string( $pt_pv_v ) ) {
					$pt_pv_best[] = trim( $pt_pv_v );
				}
			}
		}
	} elseif ( is_string( $pt_pv_raw ) && '' !== trim( $pt_pv_raw ) ) {
		$pt_pv_best = array_map( 'trim', preg_split( '/[\r\n,]+/', $pt_pv_raw ) );
	}
	$pt_pv_best = array_values( array_filter( $pt_pv_best ) );
}

nocache_headers();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>Preview — <?php echo esc_html( $pt_name ); ?></title>
	<style><?php echo $pt_css; // phpcs:ignore WordPress.Security.EscapeOutput -- theme CSS ?></style>
	<style>
		.pt-preview-ribbon{ position:fixed; top:0; left:0; right:0; z-index:9999; background:#211E24; color:#fff; font:700 .78rem/1 var(--font,system-ui,sans-serif); letter-spacing:.02em; padding:8px 16px; display:flex; align-items:center; gap:10px; }
		.pt-preview-ribbon .dot{ width:8px; height:8px; border-radius:50%; background:#FFFF00; flex:0 0 auto; }
		.pt-preview-ribbon a{ color:#FFFF00; text-decoration:none; margin-left:auto; }
		body{ padding-top:34px; }
	</style>
</head>
<body <?php body_class( 'pt-preview' ); ?>>
	<div class="pt-preview-ribbon"><span class="dot"></span> Content preview — <?php echo esc_html( $pt_name ); ?> (#<?php echo (int) $pt_pid; ?>). Not the live page. <a href="<?php echo esc_url( get_permalink( $pt_pid ) ); ?>" target="_blank" rel="noopener">Open live page &#8599;</a></div>

	<?php require __DIR__ . '/single-product-body.php'; ?>

	<script>
		window.PT_WC_BASE=<?php echo wp_json_encode( untrailingslashit( home_url() ) ); ?>;
		window.PT_PRODUCT_ID=<?php echo wp_json_encode( (string) $pt_pid ); ?>;
		window.PT_BEST_SIZES=<?php echo wp_json_encode( $pt_pv_best ); ?>;
		window.PT_DISCOUNT_PCT=<?php echo wp_json_encode( $pt_pv_disc ); ?>;
		window.PT_SPEC_IMAGES=<?php echo wp_json_encode( (object) $pt_pv_spec ); ?>;
	</script>
	<script><?php echo $pt_js; // phpcs:ignore WordPress.Security.EscapeOutput -- theme JS ?></script>
</body>
</html>
<?php
exit;
