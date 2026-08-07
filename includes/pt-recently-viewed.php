<?php
/**
 * "You might also like" — recently-viewed recommendations.
 *
 * The product page's recommendation rail defaults to WooCommerce up-sells (or
 * related products). This layer lets it instead show the parent products a
 * visitor has recently viewed, tracked client-side in localStorage, and falls
 * back to the up-sells/related set for first-time visitors.
 *
 * Why client-side: the site is behind full-page caching, so a server-rendered
 * per-visitor list would be cached and shown to everyone. Instead the page
 * ships the default (fallback) cards server-side, and recently-viewed.js swaps
 * in the visitor's history via the REST endpoint below — which renders the
 * SAME card markup (pt_render_recommend_card) so the two are pixel-identical.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default recommendations for a product: up-sells, else related. Max $limit IDs.
 *
 * @param int $product_id
 * @param int $limit
 * @return int[]
 */
function pt_default_recommend_ids( $product_id, $limit = 4 ) {
	$product = wc_get_product( $product_id );
	$ids     = $product ? $product->get_upsell_ids() : array();
	if ( empty( $ids ) && function_exists( 'wc_get_related_products' ) ) {
		$ids = wc_get_related_products( $product_id, $limit );
	}
	return array_slice( array_values( array_filter( array_map( 'intval', (array) $ids ) ) ), 0, $limit );
}

/**
 * Build the final recommendation list: recently-viewed first (valid, published,
 * not the current product, unique), then padded with the product's default
 * recommendations up to $limit.
 *
 * @param int   $current_id  Product being viewed (excluded from results).
 * @param int[] $viewed_ids  Recently-viewed IDs, most-recent first.
 * @param int   $limit
 * @return int[]
 */
function pt_recommend_ids( $current_id, $viewed_ids = array(), $limit = 4 ) {
	$current_id = (int) $current_id;
	$out        = array();

	foreach ( (array) $viewed_ids as $vid ) {
		$vid = (int) $vid;
		if ( $vid
			&& $vid !== $current_id
			&& ! in_array( $vid, $out, true )
			&& 'product' === get_post_type( $vid )
			&& 'publish' === get_post_status( $vid )
		) {
			$out[] = $vid;
		}
		if ( count( $out ) >= $limit ) {
			break;
		}
	}

	if ( count( $out ) < $limit ) {
		foreach ( pt_default_recommend_ids( $current_id, $limit ) as $did ) {
			if ( $did !== $current_id && ! in_array( $did, $out, true ) ) {
				$out[] = $did;
			}
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
	}

	return array_slice( $out, 0, $limit );
}

/**
 * Render a single recommendation card. Markup is the single source of truth for
 * both the server-side fallback and the REST endpoint. Returns '' if invalid.
 *
 * @param int $product_id
 * @return string
 */
function pt_render_recommend_card( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return '';
	}

	$img   = get_the_post_thumbnail_url( $product_id, 'large' );
	$rng   = function_exists( 'pt_product_line_singular' ) ? pt_product_line_singular( $product_id ) : '';
	$price = function_exists( 'pt_product_from_price_html' ) ? pt_product_from_price_html( $product ) : '';

	ob_start();
	?>
	<a class="rec-card" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
		<div class="rec-img"><?php if ( $img ) : ?><img loading="lazy" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>"><?php endif; ?></div>
		<div class="rec-body">
			<?php if ( $rng ) : ?><div class="rec-rng"><?php echo esc_html( $rng ); ?></div><?php endif; ?>
			<h3><?php echo esc_html( $product->get_name() ); ?></h3>
			<?php if ( $price ) : ?>
				<div class="rec-price"><?php echo wp_kses_post( $price ); ?> <small>inc. VAT</small></div>
			<?php else : ?>
				<div class="rec-price rec-tbc">Price on request</div>
			<?php endif; ?>
		</div>
	</a>
	<?php
	return ob_get_clean();
}

/**
 * Render all cards for a list of IDs.
 *
 * @param int[] $ids
 * @return string
 */
function pt_render_recommend_rail( $ids ) {
	$html = '';
	foreach ( (array) $ids as $id ) {
		$html .= pt_render_recommend_card( $id );
	}
	return $html;
}

/**
 * REST: rendered recommendation cards for a visitor's recently-viewed list.
 * Public + read-only (only public product data). Never cached (per-visitor).
 *
 * GET /wp-json/pt/v1/recommended?current=123&viewed=45,67,89
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'pt/v1',
			'/recommended',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'current' => array( 'sanitize_callback' => 'absint' ),
					'viewed'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
				'callback'            => function ( WP_REST_Request $req ) {
					nocache_headers();
					$current = (int) $req->get_param( 'current' );
					$viewed  = array_filter( array_map( 'intval', explode( ',', (string) $req->get_param( 'viewed' ) ) ) );
					$ids     = pt_recommend_ids( $current, $viewed, 4 );
					return new WP_REST_Response( array( 'html' => pt_render_recommend_rail( $ids ) ), 200 );
				},
			)
		);
	}
);

/**
 * Enqueue the tracking/swap script on single product pages.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$rel = '/assets/js/recently-viewed.js';
		$abs = get_stylesheet_directory() . $rel;
		if ( ! file_exists( $abs ) ) {
			return;
		}
		wp_enqueue_script( 'pt-recently-viewed', get_stylesheet_directory_uri() . $rel, array(), (string) filemtime( $abs ), true );
		wp_localize_script(
			'pt-recently-viewed',
			'PT_RV',
			array(
				'current' => (int) get_queried_object_id(),
				'rest'    => esc_url_raw( rest_url( 'pt/v1/recommended' ) ),
			)
		);
	}
);
