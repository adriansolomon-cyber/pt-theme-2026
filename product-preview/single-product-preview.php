<?php
/**
 * Template Name: Product Preview
 *
 * Self-contained product content previewer. Attach this template to a Page, then
 * visit that page with ?pid=<product_id> in the URL (also accepts ?product_id
 * or ?product).
 *
 * Renders the EXACT product page body (product-preview/single-product-body.php) from live
 * ACF + product data, as a standalone HTML document with the theme's product CSS
 * and JS inlined — no header/footer/cart chrome, independent of the enqueue
 * system. Restricted to users who can edit the product.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Minimal standalone notice page (no product id / not permitted), then stop. */
if ( ! function_exists( 'pt_preview_notice' ) ) {
	function pt_preview_notice( $title, $msg, $code = 200 ) {
		if ( $code >= 400 ) {
			status_header( $code );
		}
		nocache_headers();
		echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $title ) . '</title>';
		echo '<div style="max-width:540px;margin:16vh auto;padding:0 24px;font:400 16px/1.6 system-ui,-apple-system,sans-serif;text-align:center;color:#211E24">';
		echo '<h1 style="font-size:1.4rem;margin:0 0 .5em">' . esc_html( $title ) . '</h1>';
		echo '<p style="color:#555">' . wp_kses_post( $msg ) . '</p></div>';
		exit;
	}
}

// Product id from the page URL (?pid / ?product_id / ?product).
if ( empty( $pt_pid ) ) {
	$pt_pid = 0;
	foreach ( array( 'pid', 'product_id', 'product' ) as $pt_pv_key ) {
		if ( ! empty( $_GET[ $pt_pv_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$pt_pid = absint( wp_unslash( $_GET[ $pt_pv_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			break;
		}
	}
}
$pt_pid = (int) $pt_pid;

// Validate. The preview page is publicly reachable (access is controlled by
// showing the "Preview content" button only to managers); we still restrict it
// to PUBLISHED products so drafts / unpublished content aren't exposed publicly.
// Users who can edit the product may preview it in any status.
if ( ! $pt_pid || 'product' !== get_post_type( $pt_pid ) ) {
	pt_preview_notice( 'Add a product to preview', 'Append <code>?pid=PRODUCT_ID</code> to this page&#8217;s URL — for example <code>?pid=1234</code>.' );
}
if ( 'publish' !== get_post_status( $pt_pid ) && ! current_user_can( 'edit_post', $pt_pid ) ) {
	pt_preview_notice( 'Not available', 'This product isn&#8217;t published yet.', 404 );
}

$pt_is_preview = true; // hides the "Preview content" button inside the preview.

// Portable fallbacks for the theme helpers the previewer uses (pt_product_line_singular,
// pt_product_from_price_*, pt_spec_size_images). Guarded with function_exists(), so this
// is a no-op when the new theme is active and a safe fallback when this folder is dropped
// into a different active theme (e.g. the live "theTimber") that doesn't define them.
require __DIR__ . '/preview-deps.php';

// Shared setup: defines $pt_name, $pt_product, $pt_line, $pt_from, the
// $pt_f/$pt_show/$pt_has_rows helpers, $pt_hero_img, etc.
require __DIR__ . '/single-product-setup.php';

// Inline the product styles/scripts so the preview is self-contained. Prefer the
// copies bundled INSIDE product-preview/assets/ so the preview always shows the NEW
// design — even when this folder is installed in a different active theme (e.g. the
// live "theTimber", whose own assets are the OLD design). Fall back to the active
// theme's assets if a bundled copy is missing.
// NOTE: the bundled copies are snapshots of assets/{css,js}. If you change the theme's
// base.css / product.css / product.js, re-copy them into product-preview/assets/.
$pt_theme = get_stylesheet_directory();
$pt_asset = function ( $rel ) use ( $pt_theme ) {
	$rel     = ltrim( $rel, '/' );
	$bundled = __DIR__ . '/assets/' . $rel;
	if ( file_exists( $bundled ) ) {
		return file_get_contents( $bundled ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	$themed = $pt_theme . '/assets/' . $rel;
	return file_exists( $themed ) ? file_get_contents( $themed ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
};
$pt_css = $pt_asset( 'css/base.css' ) . "\n" . $pt_asset( 'css/product.css' ) . "\n";
$pt_js  = $pt_asset( 'js/product.js' );

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
