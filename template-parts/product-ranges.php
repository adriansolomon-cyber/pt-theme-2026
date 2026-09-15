<?php
/**
 * Product page: "Explore Premium Ranges" cross-sell strip.
 *
 * Ported from the old theTimber theme (template-parts/range-slider-template.php).
 * Content comes from the GLOBAL ACF Options repeater `products_range` (edit once,
 * shows on every product where the per-product "show_ranges" toggle is on).
 * Sub-fields: name (text), type (text), image (image → url), link (link → array).
 *
 * A plain CSS scroll-snap rail — no Swiper (the 2026 theme uses CSS rails).
 * Renders nothing when the global list is empty, so an enabled product with no
 * ranges configured simply shows nothing.
 *
 * @package pt-new-wp-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'have_rows' ) || ! have_rows( 'products_range', 'option' ) ) {
	return;
}

// Heading hidden for now — kept editable under Theme Settings → Premium Ranges.
// To show it again, restore the <h3 class="pt-ranges-title"> line below.
$pt_ranges_heading = function_exists( 'get_field' ) ? (string) get_field( 'products_range_heading', 'option' ) : '';
if ( '' === $pt_ranges_heading ) {
	$pt_ranges_heading = 'Need more? Explore premium ranges';
}
?>
<section class="pt-ranges" aria-label="Explore premium ranges"><div class="wrap">
	<?php // <h3 class="pt-ranges-title"><?php echo esc_html( $pt_ranges_heading ); ? ></h3> — hidden for now ?>
	<div class="pt-ranges-rail">
		<?php
		while ( have_rows( 'products_range', 'option' ) ) :
			the_row();
			$pt_r_name  = (string) get_sub_field( 'name' );
			$pt_r_type  = (string) get_sub_field( 'type' );
			$pt_r_image = get_sub_field( 'image' ); // return format: url
			$pt_r_link  = get_sub_field( 'link' );  // link type → array (url/title/target)
			$pt_r_url   = is_array( $pt_r_link ) ? ( $pt_r_link['url'] ?? '' ) : (string) $pt_r_link;
			$pt_r_tgt   = is_array( $pt_r_link ) ? ( $pt_r_link['target'] ?? '' ) : '';
			if ( '' === $pt_r_url && '' === $pt_r_name ) {
				continue; // skip empty rows
			}
			?>
			<a class="pt-range-card"
				href="<?php echo esc_url( $pt_r_url ? $pt_r_url : '#' ); ?>"
				<?php echo $pt_r_tgt ? 'target="' . esc_attr( $pt_r_tgt ) . '" rel="noopener"' : ''; ?>>
				<span class="pt-range-media">
					<?php if ( $pt_r_image ) : ?>
						<img src="<?php echo esc_url( is_array( $pt_r_image ) ? ( $pt_r_image['url'] ?? '' ) : $pt_r_image ); ?>" alt="<?php echo esc_attr( $pt_r_name ); ?>" loading="lazy">
					<?php endif; ?>
				</span>
				<span class="pt-range-label">
					<?php if ( $pt_r_name ) : ?><span class="pt-range-name"><?php echo esc_html( $pt_r_name ); ?></span><?php endif; ?>
					<?php if ( $pt_r_type ) : ?><span class="pt-range-type"><?php echo esc_html( $pt_r_type ); ?></span><?php endif; ?>
				</span>
			</a>
		<?php endwhile; ?>
	</div>
</div></section>
