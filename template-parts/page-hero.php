<?php
/**
 * Reusable page hero + breadcrumb for the secondary / content pages.
 *
 * Content only — the header, footer and support modal stay the shared chrome
 * from get_header()/get_footer(). Render it as the first child of the page's
 * <main class="pt-secondary"> and pass its copy via $args:
 *
 *   get_template_part( 'template-parts/page-hero', null, array(
 *     'crumb'      => 'FAQ',                                       // breadcrumb current-page label (plain text)
 *     'eyebrow'    => 'Help centre',                               // small kicker (plain text)
 *     'title_html' => 'Questions, <span class="fade">answered.</span>', // <h1> (limited HTML — allows the .fade span)
 *     'lead'       => 'Everything you need to know…',              // intro paragraph (plain text)
 *     'lead_html'  => 'Browse our <a href="…">FAQs</a>.',          // intro paragraph (limited HTML; wins over 'lead')
 *   ) );
 *
 * All args are optional; crumb/title fall back to the current page title.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_crumb     = ! empty( $args['crumb'] ) ? $args['crumb'] : get_the_title();
$pt_eyebrow   = ! empty( $args['eyebrow'] ) ? $args['eyebrow'] : '';
$pt_title     = ! empty( $args['title_html'] ) ? $args['title_html'] : esc_html( get_the_title() );
$pt_lead_html = ! empty( $args['lead_html'] ) ? $args['lead_html'] : ( ! empty( $args['lead'] ) ? esc_html( $args['lead'] ) : '' );
?>
<header class="hero"><div class="wrap">
	<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="sep">/</span><?php echo esc_html( $pt_crumb ); ?></div>
	<?php if ( '' !== $pt_eyebrow ) : ?>
		<div class="eyebrow" style="margin-top:18px"><?php echo esc_html( $pt_eyebrow ); ?></div>
	<?php endif; ?>
	<h1><?php echo wp_kses_post( $pt_title ); ?></h1>
	<?php if ( '' !== $pt_lead_html ) : ?>
		<p class="lead"><?php echo wp_kses_post( $pt_lead_html ); ?></p>
	<?php endif; ?>
</div></header>
