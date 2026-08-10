<?php
/**
 * Fallback template.
 *
 * Renders anything without a more specific template — the posts/blog index,
 * date/category/tag/author archives, and single blog posts — inside the full
 * theme chrome (get_header()/get_footer() + site-wide base.css). This replaces
 * the old bare "scaffold installed" placeholder so no front-end URL can ever
 * fall through to an unstyled page.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main class="wrap pt-page" id="main" tabindex="-1">
	<?php if ( have_posts() ) : ?>

		<?php if ( is_singular() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'pt-post pt-single' ); ?>>
					<header class="pt-page-head"><h1 class="pt-page-title"><?php the_title(); ?></h1></header>
					<div class="pt-post-content"><?php the_content(); ?></div>
				</article>
				<?php
			endwhile;
			?>
		<?php else : ?>

			<header class="pt-page-head">
				<?php if ( is_home() ) : ?>
					<h1 class="pt-page-title"><?php echo esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ?: 'Latest' ); ?></h1>
				<?php elseif ( is_archive() ) : ?>
					<h1 class="pt-page-title"><?php the_archive_title(); ?></h1>
					<?php the_archive_description( '<div class="pt-archive-desc">', '</div>' ); ?>
				<?php else : ?>
					<h1 class="pt-page-title"><?php bloginfo( 'name' ); ?></h1>
				<?php endif; ?>
			</header>

			<div class="pt-post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'pt-post' ); ?>>
						<h2 class="pt-post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div class="pt-post-excerpt"><?php the_excerpt(); ?></div>
						<p><a class="btn-primary" href="<?php the_permalink(); ?>">Read more <span class="a">&rarr;</span></a></p>
					</article>
					<?php
				endwhile;
				?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'prev_text' => '&lsaquo; Prev',
					'next_text' => 'Next &rsaquo;',
				)
			);
			?>

		<?php endif; ?>

	<?php else : ?>

		<div class="pt-page-content" style="text-align:center;padding:6vh 0">
			<h1 class="pt-page-title">Nothing found</h1>
			<p>There's nothing here yet. Try a search, or browse our range of garden buildings.</p>
			<p><a class="btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home <span class="a">&rarr;</span></a></p>
		</div>

	<?php endif; ?>
</main>

<?php
get_footer();
