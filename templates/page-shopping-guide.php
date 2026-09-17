<?php
/**
 * Template Name: PT — Shopping Guide
 *
 * The Buyer's Guide "brochure" page, recovered from the old theTimber
 * shopping-guide template (v2 / "demo" — the full version). Sections:
 *   1. PDF-download hero
 *   2. Editable guide body (the WordPress page content)
 *   3. "Find your perfect match" category slider
 *   4. USP strip
 *   5. Video + founder quote
 *   6. Latest-from-the-blog grid (3 newest posts)
 *   7. Full-width CTA banner
 *
 * Ported to the 2026 chrome: shared get_header()/get_footer(), F37 Jagger +
 * theme tokens, a dependency-free scroll-snap carousel in place of Swiper, and
 * all styling moved to assets/css/shopping-guide.css (auto-enqueued for
 * templates/page-*.php).
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

// Founder-quote YouTube video ID (filterable).
$pt_guide_video = apply_filters( 'pt_shopping_guide_video', 'bYCDkSZnQww' );

// Download-arrow glyph, reused by the hero, card buttons and text links.
$pt_arrow = '<svg class="ar" xmlns="http://www.w3.org/2000/svg" width="9" height="15" viewBox="0 0 9 15" fill="none" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.60041 6.6003C6.99085 6.20991 7.62395 6.21004 8.01447 6.6003C8.40498 6.99081 8.40493 7.62383 8.01447 8.01436L1.70686 14.323C1.31638 14.7132 0.683271 14.7132 0.292795 14.323C-0.0976624 13.9325 -0.0975344 13.2994 0.292795 12.9089L6.60041 6.6003ZM0.292795 0.292682C0.683255 -0.0975736 1.31638 -0.0975479 1.70686 0.292682L5.83088 4.41768C6.2214 4.80821 6.2214 5.44122 5.83088 5.83174C5.44034 6.22201 4.80725 6.22219 4.41682 5.83174L0.292795 1.70674C-0.0974848 1.3162 -0.0976459 0.683129 0.292795 0.292682Z" fill="currentColor" /></svg>';

// Yellow tick-in-charcoal-circle glyph for the USP list.
$pt_usp_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect width="24" height="24" rx="12" fill="#3B333D"/><path fill-rule="evenodd" clip-rule="evenodd" d="M17.096 7.39004L9.93602 14.3L8.03602 12.27C7.68602 11.94 7.13602 11.92 6.73602 12.2C6.34602 12.49 6.23602 13 6.47602 13.41L8.72602 17.07C8.94602 17.41 9.32601 17.62 9.75601 17.62C10.166 17.62 10.556 17.41 10.776 17.07C11.136 16.6 18.006 8.41004 18.006 8.41004C18.906 7.49004 17.816 6.68004 17.096 7.38004V7.39004Z" fill="#FFFF00"/></svg>';

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

// USP strip copy.
$pt_usps = array( 'UK delivery', 'Free Pressure Treatment', '25 Year Anti Rot Guarantee' );

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
			if ( '' !== trim( get_the_content() ) ) :
				?>
				<section class="pdf-preview"><?php the_content(); ?></section>
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

	</div><!-- /.wrap -->

	<!-- USP strip (full-bleed) -->
	<section class="usp-wrapper">
		<div class="usp-inner">
			<div class="usp-text">
				<h3>Revamp Your Space with Stylish Garden Buildings</h3>
			</div>
			<div class="usp-list">
				<?php foreach ( $pt_usps as $pt_usp ) : ?>
					<p><span><?php echo $pt_usp_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php echo esc_html( $pt_usp ); ?></p>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Video + founder quote -->
	<section class="video-quote-section">
		<div class="wrap">
			<div class="video-quote-wrapper">
				<div class="video-side">
					<iframe src="https://www.youtube.com/embed/<?php echo esc_attr( $pt_guide_video ); ?>" title="Project Timber — garden building guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen loading="lazy"></iframe>
				</div>
				<div class="quote-side">
					<p>&ldquo;At Project Timber, we&rsquo;re passionate about helping you make the most of your outdoor space. That&rsquo;s why we offer a wide range of garden buildings, as well as expert advice and support to help you choose the right one for your needs.&rdquo;</p>
				</div>
			</div>
		</div>
	</section>

	<!-- Latest from the blog (full-bleed) -->
	<section class="blog-section">
		<div class="blog-inner">
			<h2>Check out our blog for garden ideas!</h2>
			<p class="blog-subtitle">Seeking ideas for your garden room project? Explore our newest guides and inspirations.</p>

			<div class="blog-cards">
				<?php
				$pt_blog = new WP_Query(
					array(
						'post_type'           => 'post',
						'posts_per_page'      => 3,
						'post_status'         => 'publish',
						'orderby'             => 'date',
						'order'               => 'DESC',
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
				if ( $pt_blog->have_posts() ) :
					while ( $pt_blog->have_posts() ) :
						$pt_blog->the_post();
						?>
						<a href="<?php the_permalink(); ?>" class="blog-card">
							<div class="blog-card-thumb">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'medium_large' ); ?>
								<?php else : ?>
									<div class="blog-card-thumb--fallback"><span class="thumb-label"><?php the_title(); ?></span></div>
								<?php endif; ?>
							</div>
							<p class="blog-card-title"><?php the_title(); ?></p>
						</a>
						<?php
					endwhile;
					wp_reset_postdata();
				endif;
				?>
			</div>

			<?php
			$pt_blog_url = get_permalink( get_option( 'page_for_posts' ) );
			if ( ! $pt_blog_url ) {
				$pt_blog_url = home_url( '/blog/' );
			}
			?>
			<a href="<?php echo esc_url( $pt_blog_url ); ?>" class="cta-light">See Our Blog <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</div>
	</section>

	<!-- Full-width CTA banner -->
	<section class="cta-banner-section">
		<div class="cta-banner-inner">
			<picture>
				<source media="(max-width: 680px)" srcset="https://www.projecttimber.com/wp-content/uploads/2026/06/Frame-26903-3.svg">
				<img src="https://www.projecttimber.com/wp-content/uploads/2026/05/Frame-26903-1.svg" alt="" loading="lazy">
			</picture>
			<div class="cta-banner-content">
				<h3>Ready to finalize your perfect design?</h3>
				<p>Our team is ready to answer questions about bases, insulation, or delivery.</p>
				<a href="<?php echo esc_url( home_url( '/insulated-garden-buildings/' ) ); ?>" class="cta-light">Explore All Ranges&hellip; <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>
		</div>
	</section>

</main>
<?php
get_footer();
