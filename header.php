<?php
/**
 * Site header — output by get_header().
 *
 * Converted from partials/header.html (client-side data-include). Design markup
 * is preserved verbatim; only the hrefs are wired to WP/Woo URLs and the
 * <head>/<body> open now run wp_head() / wp_body_open().
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_cats = array(
	'garden-sheds'                => 'Garden Sheds',
	'summerhouses'                => 'Summerhouses',
	'garden-offices'              => 'Garden Offices',
	'garden-workshops'            => 'Garden Workshops',
	'insulated-garden-buildings'  => 'Insulated Garden Buildings',
	'log-cabins'                  => 'Log Cabins',
	'greenhouses'                 => 'Greenhouses',
);
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main">Skip to content</a>

<div class="promo">FREE DELIVERY — <b>selected postcodes*</b> &nbsp;·&nbsp; 10% OFF GRANDMASTER — CODE <b>GM10</b></div>

<!-- Phone numbers — single source of truth: Phone_Numbers.md (website default = 01777 553392). -->
<header class="mainhead">
  <button class="menu" aria-label="Open menu" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
  <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Project Timber home"><img src="https://www.projecttimber.com/wp-content/themes/theTimber/assets/images/tplogo.svg" alt="Project Timber"></a>
  <button class="search" type="button" aria-label="Search Project Timber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg> Search Project Timber</button>
  <div class="icons">
    <button class="ic searchic" type="button" aria-label="Search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg></button>
    <button class="ic supporttrigger" type="button" aria-label="Customer support"><img src="https://www.projecttimber.com/wp-content/uploads/2026/06/proicons_chat.png" alt=""></button>
    <a class="ic" href="<?php echo esc_url( pt_account_url() ); ?>" aria-label="My account"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></a>
    <button class="ic cartopen" type="button" aria-label="Basket"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M3 4h2l2.3 12.3a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.2L22 8H6"/></svg><span class="badge cartbadge">0</span></button>
  </div>
</header>

<!-- Nav → top-level WooCommerce product-category archives (slugs match the live projecttimber.com URLs). -->
<nav class="primnav" id="primnav"><ul>
<?php foreach ( $pt_cats as $pt_slug => $pt_label ) : ?>
  <li><a href="<?php echo esc_url( home_url( '/' . $pt_slug . '/' ) ); ?>"><?php echo esc_html( $pt_label ); ?></a></li>
<?php endforeach; ?>
</ul></nav>

<div class="hsearch" id="hsearch" hidden>
  <form class="wrap" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Search Project Timber" aria-label="Search Project Timber">
    <input type="hidden" name="post_type" value="product">
  </form>
</div>
