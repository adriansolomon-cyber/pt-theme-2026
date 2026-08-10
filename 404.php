<?php
/**
 * 404 — page not found, rendered inside the full theme chrome.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main class="wrap pt-page pt-404" id="main" tabindex="-1">
	<div class="pt-page-content" style="text-align:center;padding:6vh 0;max-width:560px;margin:0 auto">
		<h1 class="pt-page-title">Page not found</h1>
		<p>Sorry, we couldn't find that page — it may have moved or no longer exists.</p>

		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="pt-404-search" style="display:flex;gap:8px;margin:22px auto 6px;max-width:420px">
			<input type="search" name="s" placeholder="Search Project Timber" aria-label="Search Project Timber" style="flex:1;min-width:0;padding:13px 16px;border:1.5px solid var(--line,#e6e2e6);border-radius:999px;font-family:var(--font);font-size:1rem">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" class="btn-primary">Search</button>
		</form>

		<p style="margin-top:26px"><a class="btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home <span class="a">&rarr;</span></a></p>
	</div>
</main>

<?php
get_footer();
