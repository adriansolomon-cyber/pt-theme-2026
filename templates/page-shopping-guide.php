<?php
/**
 * Template Name: PT — Shopping Guide
 *
 * The Buyer's Guide "brochure" page, recovered from the old theTimber
 * `template-parts/shopping-guide-page-template.php`. Same three parts as the
 * original: a PDF-download hero, the editable on-page guide (the WordPress
 * page content), and a "find your perfect match" category slider.
 *
 * Ported to the 2026 chrome: shared get_header()/get_footer(), F37 Jagger +
 * theme tokens, and a dependency-free scroll-snap carousel in place of Swiper.
 * Assets (secondary.css + shopping-guide.css) are auto-enqueued for
 * templates/page-*.php.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The give-away PDF. Filterable so it can be swapped without editing the theme.
$pt_guide_pdf = apply_filters(
	'pt_shopping_guide_pdf',
	'https://www.projecttimber.com/wp-content/uploads/2026/02/PT-Buyers-Guide.pdf'
);

// Download-arrow glyph, reused by the hero and the card buttons.
$pt_arrow = '<svg class="ar" xmlns="http://www.w3.org/2000/svg" width="9" height="15" viewBox="0 0 9 15" fill="none" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.60041 6.6003C6.99085 6.20991 7.62395 6.21004 8.01447 6.6003C8.40498 6.99081 8.40493 7.62383 8.01447 8.01436L1.70686 14.323C1.31638 14.7132 0.683271 14.7132 0.292795 14.323C-0.0976624 13.9325 -0.0975344 13.2994 0.292795 12.9089L6.60041 6.6003ZM0.292795 0.292682C0.683255 -0.0975736 1.31638 -0.0975479 1.70686 0.292682L5.83088 4.41768C6.2214 4.80821 6.2214 5.44122 5.83088 5.83174C5.44034 6.22201 4.80725 6.22219 4.41682 5.83174L0.292795 1.70674C-0.0974848 1.3162 -0.0976459 0.683129 0.292795 0.292682Z" fill="currentColor" /></svg>';

// The three "perfect match" cards — copy carried over verbatim from the old template.
$pt_cards = array(
	array(
		'title'    => 'Storage',
		'image'    => 'https://www.projecttimber.com/wp-content/uploads/2026/02/Workshops.webp',
		'benefits' => array( 'No Cheap Overlap', 'The 25-Year Promise', 'Heavy-Duty Security', 'British Weather Ready' ),
		'cta'      => 'Shop Sheds',
		'href'     => home_url( '/garden-sheds/' ),
	),
	array(
		'title'    => 'Leisure',
		'image'    => 'https://www.projecttimber.com/wp-content/uploads/2026/02/Summerhouses.webp',
		'benefits' => array( 'Toughened Glass', 'Extra-Tall Eaves', 'Decades of Relaxation', 'Shiplap Tongue & Groove' ),
		'cta'      => 'Shop Summerhouses',
		'href'     => home_url( '/summerhouses/' ),
	),
	array(
		'title'    => 'Living',
		'image'    => 'https://www.projecttimber.com/wp-content/uploads/2026/02/Offices.webp',
		'benefits' => array( 'True 365-Day Use', 'Home-Grade Security', 'Zero-Hassle Roofing', 'Vapour Control' ),
		'cta'      => 'Shop Offices',
		'href'     => home_url( '/garden-offices/' ),
	),
);

// Inline check glyph for the benefit rows (replaces the old Check-icon.svg asset).
$pt_check = '<svg class="tick" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="rgba(255,255,255,.16)"/><path d="M7 12.4l3.2 3.2L17 8.8" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

get_header();
?>
<main class="pt-secondary pt-sguide" id="main" tabindex="-1">
	<div class="wrap">

		<!-- Hero: the PDF give-away -->
		<section class="page-header">
			<div class="content-box">
				<h1>Your Ultimate Garden Building Guide is Ready!</h1>
				<p>You&rsquo;ve taken the first step to avoiding the 5 most costly mistakes buyers make.</p>
			</div>
			<div class="cta-wrapper">
				<a href="<?php echo esc_url( $pt_guide_pdf ); ?>" class="cta-button" download target="_blank" rel="noopener">
					Download PDF <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>
		</section>

		<!-- The editable on-page guide (WordPress page content) -->
		<?php
		while ( have_posts() ) :
			the_post();
			$pt_body = trim( get_the_content() );
			if ( '' !== $pt_body ) :
				?>
				<section class="pdf-preview">
					<?php the_content(); ?>
				</section>
				<?php
			endif;
		endwhile;
		?>

		<!-- Perfect-match category slider -->
		<section class="perfect-match-slider">
			<h2>Now You Know the Rules, Find Your Perfect Match</h2>

			<div class="slider-items-wrapper">
				<div class="sliders-items">
					<?php foreach ( $pt_cards as $pt_card ) : ?>
						<article class="item" style="background-image:url('<?php echo esc_url( $pt_card['image'] ); ?>')">
							<div class="item-content">
								<h3><?php echo esc_html( $pt_card['title'] ); ?></h3>
								<div class="benefits">
									<?php foreach ( $pt_card['benefits'] as $pt_benefit ) : ?>
										<div class="benefit"><?php echo $pt_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $pt_benefit ); ?></span></div>
									<?php endforeach; ?>
								</div>
								<a href="<?php echo esc_url( $pt_card['href'] ); ?>" class="btn-glass-effect">
									<?php echo esc_html( $pt_card['cta'] ); ?> <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

	</div>
</main>
<?php
get_footer();
