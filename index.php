<?php
/**
 * Project Timber — index.php
 *
 * Minimal, safe fallback template (stage-1 scaffold). Its only job right now is
 * to let the theme ACTIVATE cleanly — WordPress requires an index.php and will
 * refuse to activate a theme without one. The real front end still lives in the
 * projecttimber-*.html prototypes; converting those into WP templates
 * (header.php, footer.php, front-page.php, WooCommerce templates, …) is stage-2.
 *
 * Deliberately uses only core WP functions (no custom calls) so activation
 * cannot fatal.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
	<main style="max-width:720px;margin:12vh auto;padding:0 24px;font-family:system-ui,-apple-system,sans-serif;text-align:center;line-height:1.6">
		<h1 style="margin-bottom:.5em"><?php bloginfo( 'name' ); ?></h1>
		<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		<p style="color:#666;margin-top:1.5em">
			Project Timber 2026 theme scaffold is installed and active.
			Front-end templates are pending the WordPress conversion stage.
		</p>
	</main>
<?php wp_footer(); ?>
</body>
</html>
