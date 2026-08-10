<?php
/**
 * Template Name: PT — Resources
 *
 * Resources / blog index. Converted from
 * design-files/secondary-pages/projecttimber-resources.html. Shared chrome via
 * get_header/get_footer; hero via the shared part.
 *
 * Data: a live blog index — the newest published post becomes the featured
 * card and the rest fill the grid, using each post's featured image, title,
 * excerpt and permalink. If there are no posts yet, it falls back to a curated
 * set of cards so the page is never empty. Assets (secondary.css +
 * resources.css) are auto-enqueued for templates/page-*.php.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read-more arrow (reused by every card).
$pt_arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

$pt_posts = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 13, // 1 featured + up to 12 in the grid.
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

get_header();
?>
<main class="pt-secondary" id="main" tabindex="-1">

	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'crumb'      => 'Resources',
			'eyebrow'    => 'Resources & ideas',
			'title_html' => 'Tips, guides &amp; <span class="fade">garden inspiration.</span>',
			'lead'       => 'Keep up to date with the latest garden tips and news — from weatherproofing and maintenance to ideas for making the most of your building.',
		)
	);
	?>

	<?php if ( $pt_posts->have_posts() ) : ?>

		<?php $pt_posts->the_post(); // newest = featured. ?>
		<section class="feat-sec"><div class="wrap">
			<a class="feat" href="<?php the_permalink(); ?>">
				<div class="img"><?php echo has_post_thumbnail() ? get_the_post_thumbnail( null, 'large', array( 'loading' => 'lazy' ) ) : ''; ?></div>
				<div class="body">
					<span class="tag">Latest guide</span>
					<h2><?php the_title(); ?></h2>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 34 ) ); ?></p>
					<span class="readmore">Read more <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG ?></span>
				</div>
			</a>
		</div></section>

		<?php if ( $pt_posts->post_count > 1 ) : ?>
		<section class="grid-sec" style="padding-top:20px"><div class="wrap">
			<div class="head"><h2>More from the blog</h2></div>
			<div class="pgrid">
				<?php
				while ( $pt_posts->have_posts() ) :
					$pt_posts->the_post();
					?>
					<a class="pcard" href="<?php the_permalink(); ?>">
						<div class="img"><?php echo has_post_thumbnail() ? get_the_post_thumbnail( null, 'medium_large', array( 'loading' => 'lazy' ) ) : ''; ?></div>
						<div class="body"><h3><?php the_title(); ?></h3><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p><span class="readmore">Read more <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG ?></span></div>
					</a>
					<?php
				endwhile;
				?>
			</div>
		</div></section>
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>

	<?php else : ?>

		<?php
		// Fallback: no posts yet — show a curated set so the page isn't empty.
		$pt_fallback = array(
			array( 'slug' => 'top-5-winter-garden-room-ideas/', 'img' => 'https://www.projecttimber.com/wp-content/uploads/2021/11/Cover-1-1.jpg', 'title' => 'Top 5 Winter Garden Room Ideas', 'excerpt' => 'During winter we spend more time indoors, which can leave the home feeling cramped. A fully insulated garden room is a great way to enjoy your garden all year round.' ),
			array( 'slug' => 'man-cave-ideas/', 'img' => 'https://www.projecttimber.com/wp-content/uploads/2021/10/Featured-Image-1.jpg', 'title' => 'Man Cave Ideas', 'excerpt' => 'A man cave in your garden is a great way to create a dedicated space for fun, relaxation, entertainment and hobbies that everyone can enjoy.' ),
			array( 'slug' => 'damp-proof-shed/', 'img' => 'https://www.projecttimber.com/wp-content/uploads/2021/10/0.-hOW-TO-DAMP-rOOF-YOUR-SHED.jpg', 'title' => 'How To Damp Proof Your Shed', 'excerpt' => 'With winter approaching, damp-proofing your shed, summerhouse or workshop helps stop moisture damaging the fabric of your building and protects your belongings inside.' ),
			array( 'slug' => 'work-home-ideas/', 'img' => 'https://www.projecttimber.com/wp-content/uploads/2021/10/0.Work-From-Home-Ideas.jpg', 'title' => 'Work From Home Ideas', 'excerpt' => 'Working from home has become far more popular, with an impact on living space and motivation. Here are ideas for creating a productive space of your own.' ),
			array( 'slug' => 'garden-bar-ideas/', 'img' => 'https://www.projecttimber.com/wp-content/uploads/2021/09/Featured-images-1.jpg', 'title' => 'Garden Bar Ideas', 'excerpt' => 'A bar in your garden is becoming more and more popular — great for entertaining friends and family, adding value to your home, and enjoying yourself.' ),
			array( 'slug' => 'how-to-look-after-your-garden/', 'img' => 'https://www.projecttimber.com/wp-content/uploads/2021/09/cover-01.jpg', 'title' => 'How to Look After Your Garden', 'excerpt' => 'A healthy, beautiful garden transforms the feel of your home. Here are some of our top tips for keeping it well maintained and looking its best.' ),
		);
		$pt_feat = array( 'slug' => 'keeping-the-elements-out-the-ultimate-guide-to-weatherproofing-your-project-timber-building/', 'img' => 'https://www.projecttimber.com/wp-content/uploads/2026/03/how-to-waterproof-you-shed-building.png', 'title' => 'Keeping the elements out: the ultimate guide to weatherproofing your building', 'excerpt' => "Your new Project Timber building has arrived — whether it's a garden office, a studio or a hobbyist retreat. Timber is a natural, living material, so here's how to keep your new space dry, sturdy and looking its best." );
		?>
		<section class="feat-sec"><div class="wrap">
			<a class="feat" href="<?php echo esc_url( home_url( '/' . $pt_feat['slug'] ) ); ?>">
				<div class="img"><img loading="lazy" src="<?php echo esc_url( $pt_feat['img'] ); ?>" alt="<?php echo esc_attr( $pt_feat['title'] ); ?>"></div>
				<div class="body">
					<span class="tag">Latest guide</span>
					<h2><?php echo esc_html( $pt_feat['title'] ); ?></h2>
					<p><?php echo esc_html( $pt_feat['excerpt'] ); ?></p>
					<span class="readmore">Read more <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG ?></span>
				</div>
			</a>
		</div></section>

		<section class="grid-sec" style="padding-top:20px"><div class="wrap">
			<div class="head"><h2>More from the blog</h2></div>
			<div class="pgrid">
				<?php foreach ( $pt_fallback as $pt_c ) : ?>
					<a class="pcard" href="<?php echo esc_url( home_url( '/' . $pt_c['slug'] ) ); ?>">
						<div class="img"><img loading="lazy" src="<?php echo esc_url( $pt_c['img'] ); ?>" alt="<?php echo esc_attr( $pt_c['title'] ); ?>"></div>
						<div class="body"><h3><?php echo esc_html( $pt_c['title'] ); ?></h3><p><?php echo esc_html( $pt_c['excerpt'] ); ?></p><span class="readmore">Read more <?php echo $pt_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG ?></span></div>
					</a>
				<?php endforeach; ?>
			</div>
		</div></section>

	<?php endif; ?>

</main>
<?php
get_footer();
