<?php
/**
 * Reusable "still need help?" CTA band.
 *
 * Copy + the secondary button are overridable via $args so pages can tailor it;
 * the defaults are the general "talk to us" version (used by FAQ). The first
 * button is always the phone. Place inside the page's <main class="pt-secondary">.
 *
 *   get_template_part( 'template-parts/help-cta', null, array(
 *     'eyebrow'    => 'Need to arrange a return?',
 *     'title'      => "We're here to help.",
 *     'text'       => 'Our customer care team is available…',
 *     'cta2_label' => 'Email customer care',
 *     'cta2_href'  => 'mailto:care@projecttimber.co.uk',
 *   ) );
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_eyebrow    = ! empty( $args['eyebrow'] ) ? $args['eyebrow'] : 'Still need a hand?';
$pt_title      = ! empty( $args['title'] ) ? $args['title'] : 'Talk to a real person.';
$pt_text       = ! empty( $args['text'] ) ? $args['text'] : 'Our team is here Monday to Friday, 8:30am–7:00pm. Give us a call, drop us an email, or head over to our contact page.';
$pt_cta2_label = ! empty( $args['cta2_label'] ) ? $args['cta2_label'] : 'Contact us';
$pt_cta2_href  = ! empty( $args['cta2_href'] ) ? $args['cta2_href'] : home_url( '/contact' );
?>
<section class="helpcta"><div class="wrap">
	<div class="eyebrow"><?php echo esc_html( $pt_eyebrow ); ?></div>
	<h2><?php echo esc_html( $pt_title ); ?></h2>
	<p><?php echo esc_html( $pt_text ); ?></p>
	<div class="btns">
		<a class="btn btn-y" href="tel:01777553392"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>01777 553392</a>
		<a class="btn btn-o" href="<?php echo esc_url( $pt_cta2_href ); ?>"><?php echo esc_html( $pt_cta2_label ); ?></a>
	</div>
</div></section>
