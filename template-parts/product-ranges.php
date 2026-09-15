<?php
/**
 * Product page: "Explore Premium Ranges" strip.
 *
 * Faithful port of the old theTimber range slider
 * (template-parts/range-slider-template.php): connected pill labels joined by a
 * horizontal line, image above each, one item highlighted yellow (the 6th, as in
 * the original). Content comes from the GLOBAL ACF Options repeater
 * `products_range` (name / type / image / link) — edited once under
 * Theme Settings, shown on any product where the per-product "show_ranges"
 * toggle is on.
 *
 * The old theme used Swiper for drag; here it's a plain CSS scroll rail plus
 * prev/next arrows (small inline scroll script) — the 2026 theme doesn't load
 * Swiper. Original class names are kept so the ported CSS matches.
 *
 * Heading hidden for now (kept editable under Theme Settings → Premium Ranges).
 *
 * @package pt-new-wp-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'have_rows' ) || ! have_rows( 'products_range', 'option' ) ) {
	return;
}

$pt_ranges_heading = function_exists( 'get_field' ) ? (string) get_field( 'products_range_heading', 'option' ) : '';
if ( '' === $pt_ranges_heading ) {
	$pt_ranges_heading = 'Need more? Explore premium ranges';
}
?>
<section class="pt-ranges" aria-label="Explore premium ranges"><div class="wrap">
	<div class="range-slider-widget">
		<?php // <h3 class="content-wrapper"><?php echo esc_html( $pt_ranges_heading ); ? ></h3> — heading hidden for now ?>
		<button type="button" class="pt-ranges-nav prev" aria-label="Previous ranges">&lsaquo;</button>
		<button type="button" class="pt-ranges-nav next" aria-label="More ranges">&rsaquo;</button>
		<div class="range-slider-container">
			<div class="swiper-wrapper">
				<?php
				$pt_r_count = 0;
				while ( have_rows( 'products_range', 'option' ) ) :
					the_row();
					$pt_r_name  = (string) get_sub_field( 'name' );
					$pt_r_type  = (string) get_sub_field( 'type' );
					$pt_r_image = get_sub_field( 'image' ); // return format: url
					$pt_r_link  = get_sub_field( 'link' );  // link type → array (url/title/target)
					$pt_r_url   = is_array( $pt_r_link ) ? ( $pt_r_link['url'] ?? '' ) : (string) $pt_r_link;
					$pt_r_tgt   = is_array( $pt_r_link ) ? ( $pt_r_link['target'] ?? '' ) : '';
					$pt_r_img   = is_array( $pt_r_image ) ? ( $pt_r_image['url'] ?? '' ) : (string) $pt_r_image;
					if ( '' === $pt_r_url && '' === $pt_r_name ) {
						continue; // skip empty rows
					}
					$pt_r_active = ( 5 === $pt_r_count ) ? ' active' : ''; // 6th item highlighted, as in the original
					?>
					<a href="<?php echo esc_url( $pt_r_url ? $pt_r_url : '#' ); ?>"
						class="range-slider-item swiper-slide<?php echo esc_attr( $pt_r_active ); ?>"
						<?php echo $pt_r_tgt ? 'target="' . esc_attr( $pt_r_tgt ) . '" rel="noopener"' : ''; ?>>
						<figure>
							<?php if ( $pt_r_img ) : ?>
								<img src="<?php echo esc_url( $pt_r_img ); ?>" alt="<?php echo esc_attr( $pt_r_name ); ?>" loading="lazy">
							<?php endif; ?>
						</figure>
						<div class="item-label">
							<?php if ( $pt_r_name ) : ?><span class="title"><?php echo esc_html( $pt_r_name ); ?></span><?php endif; ?>
							<?php if ( $pt_r_type ) : ?><span><?php echo esc_html( $pt_r_type ); ?></span><?php endif; ?>
						</div>
					</a>
					<?php
					$pt_r_count++;
				endwhile;
				?>
			</div>
		</div>
	</div>
</div></section>
<script>
( function () {
	var widget = document.currentScript.previousElementSibling;
	if ( ! widget || ! widget.classList || ! widget.classList.contains( 'pt-ranges' ) ) {
		widget = document.querySelector( '.pt-ranges' );
	}
	if ( ! widget ) { return; }
	var rail = widget.querySelector( '.range-slider-container' );
	var prev = widget.querySelector( '.pt-ranges-nav.prev' );
	var next = widget.querySelector( '.pt-ranges-nav.next' );
	if ( ! rail || ! prev || ! next ) { return; }
	function step() { var c = rail.querySelector( '.swiper-slide' ); return c ? ( c.offsetWidth + 20 ) * 2 : 300; }
	function sync() {
		var max = rail.scrollWidth - rail.clientWidth - 2;
		prev.disabled = rail.scrollLeft <= 2;
		next.disabled = rail.scrollLeft >= max;
		var hide = rail.scrollWidth <= rail.clientWidth + 4;
		prev.hidden = hide; next.hidden = hide;
	}
	prev.addEventListener( 'click', function () { rail.scrollBy( { left: -step(), behavior: 'smooth' } ); } );
	next.addEventListener( 'click', function () { rail.scrollBy( { left: step(), behavior: 'smooth' } ); } );
	rail.addEventListener( 'scroll', sync, { passive: true } );
	window.addEventListener( 'resize', sync );
	sync();
} )();
</script>
