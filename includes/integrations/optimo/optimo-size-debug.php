<?php
/**
 * Read-only admin diagnostic for the Optimo product-name SIZE extraction.
 *
 * Visit, signed in as an admin (manage_woocommerce):
 *     https://www.projecttimber.com/?pt_optimo_size_debug=HPY97070
 *
 * Prints, for that order: every line item (name / flags / parent), the size the
 * line-item walk resolves for each building, the raw _pip_extras_or_option field,
 * whether the PIP fallback was triggered, and the final string that would be sent
 * to Optimo's product_name_custom_field. Changes NOTHING.
 *
 * It mirrors the extraction in optimo-integrations-functions.php (the
 * $optimo_lines walk + PIP fallback) — keep in sync if that logic changes.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	if ( ! isset( $_GET['pt_optimo_size_debug'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$ref = sanitize_text_field( wp_unslash( $_GET['pt_optimo_size_debug'] ) );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	$order = function_exists( 'pt_rdm_find_order_by_number' ) ? pt_rdm_find_order_by_number( $ref ) : false;
	if ( ! $order ) {
		echo 'Order not found for: ' . $ref . "\n";
		exit;
	}

	echo 'Order: ' . $order->get_order_number() . '  (ID ' . $order->get_id() . ')  status=' . $order->get_status() . "\n";
	echo str_repeat( '=', 72 ) . "\n\nLINE-ITEM WALK\n";

	$size_re      = '/(\d+(?:\.\d+)?)\s*(?:ft|m|cm|\')?\s*(?:x|×|by)\s*(\d+(?:\.\d+)?)/iu';
	$optimo_lines = array();
	$optimo_cur   = null;

	foreach ( $order->get_items() as $item ) {
		$product   = $item->get_product();
		$iname     = $product ? (string) $product->get_name() : (string) $item->get_name();
		$parent_id = $product ? (int) $product->get_parent_id() : 0;
		$is_child  = $item->get_meta( '_composite_parent' ) || $item->get_meta( '_bundled_by' );
		$has_size  = preg_match( $size_re, $iname, $m );
		$pure_size = $has_size && preg_match( '/^\s*\d+(?:\.\d+)?\s*(?:ft|m|cm|\')?\s*(?:x|×|by)\s*\d+(?:\.\d+)?\s*(?:ft|m|cm|\')?\s*$/iu', trim( $iname ) );
		$multi_size = false;
		if ( preg_match_all( $size_re, $iname, $all_m, PREG_SET_ORDER ) ) {
			$toks = array();
			foreach ( $all_m as $one ) {
				$toks[ $one[1] . 'x' . $one[2] ] = true;
			}
			$multi_size = count( $toks ) > 1;
		}

		printf(
			"- %-46s parent=%-7d child=%s size=%-7s pure=%s multi=%s\n",
			'"' . $iname . '"',
			$parent_id,
			$is_child ? 'Y' : 'n',
			$has_size ? ( $m[1] . 'x' . $m[2] ) : '-',
			$pure_size ? 'Y' : 'n',
			$multi_size ? 'Y' : 'n'
		);

		$is_size_line = $optimo_cur && $has_size && ! $multi_size && (
			$is_child
			|| ( $parent_id && ! empty( $optimo_cur['parent_id'] ) && $parent_id === (int) $optimo_cur['parent_id'] )
			|| $pure_size
		);
		if ( $is_size_line ) {
			if ( $pure_size ) {
				$optimo_cur['size']        = $m[1] . ' x ' . $m[2];
				$optimo_cur['size_locked'] = true;
				echo '    -> SIZE ' . $optimo_cur['size'] . ' LOCKED on "' . $optimo_cur['name'] . "\" (pure size)\n";
			} elseif ( empty( $optimo_cur['size_locked'] ) ) {
				$optimo_cur['size'] = $m[1] . ' x ' . $m[2];
				echo '    -> SIZE ' . $optimo_cur['size'] . ' set on "' . $optimo_cur['name'] . "\" (embedded, unlocked)\n";
			} else {
				echo "    -> size candidate ignored (building size already locked)\n";
			}
			continue;
		}
		if ( $is_child ) {
			echo '    -> non-size child, skipped' . ( $multi_size ? ' (multi-size kit name)' : '' ) . "\n";
			continue;
		}

		if ( $optimo_cur ) {
			$optimo_lines[] = $optimo_cur;
		}
		$parent     = $parent_id ? wc_get_product( $parent_id ) : $product;
		$optimo_cur = array(
			'name'        => ( $parent ? $parent->get_name() : '' ) ?: 'Unknown Product',
			'size'        => '',
			'size_locked' => false,
			'parent_id'   => $parent_id ?: ( $product ? (int) $product->get_id() : 0 ),
		);
		echo '    -> NEW building "' . $optimo_cur['name'] . '" (parent_id ' . $optimo_cur['parent_id'] . ")\n";
		// PRIMARY: size straight from the composite's Size component.
		$comp_size = function_exists( 'optimo_size_from_composite_item' ) ? optimo_size_from_composite_item( $item ) : '';
		if ( '' !== $comp_size ) {
			$optimo_cur['size']        = $comp_size;
			$optimo_cur['size_locked'] = true;
			echo '    -> SIZE ' . $comp_size . " from Size COMPONENT (primary), LOCKED\n";
		} else {
			echo "    -> Size component did not resolve — will rely on name-scan / PIP fallback\n";
		}
	}
	if ( $optimo_cur ) {
		$optimo_lines[] = $optimo_cur;
	}

	echo "\nRESULT OF LINE-ITEM WALK\n";
	foreach ( $optimo_lines as $l ) {
		echo '  building="' . $l['name'] . '" size="' . ( '' !== $l['size'] ? $l['size'] : '(blank)' ) . "\"\n";
	}

	$needs_size = false;
	foreach ( $optimo_lines as $l ) {
		if ( '' === $l['size'] ) {
			$needs_size = true;
			break;
		}
	}
	echo "\nneeds_size (would trigger PIP fallback)? " . ( $needs_size ? 'YES' : 'no' ) . "\n";

	$pip = (string) $order->get_meta( '_pip_extras_or_option' );
	echo "\n_pip_extras_or_option (raw):\n" . ( '' === $pip ? '(empty)' : $pip ) . "\n";

	if ( $needs_size && '' !== $pip && preg_match_all( '/\|\|\|\s*(\d+(?:\.\d+)?\s*(?:ft|m|cm|\')?\s*(?:x|×|by)\s*\d+(?:\.\d+)?)/iu', $pip, $mm ) ) {
		echo "\nPIP fallback sizes matched: " . implode( ' | ', $mm[1] ) . "\n";
		$i = 0;
		foreach ( $optimo_lines as $k => $l ) {
			if ( '' === $l['size'] && isset( $mm[1][ $i ] ) && preg_match( $size_re, $mm[1][ $i ], $sm ) ) {
				$optimo_lines[ $k ]['size'] = $sm[1] . ' x ' . $sm[2];
				echo '  filled building #' . $k . ' with ' . $sm[1] . ' x ' . $sm[2] . " (from PIP)\n";
			}
			$i++;
		}
	}

	$final = implode( ', ', array_unique( array_filter( array_map( function ( $l ) {
		return '' !== $l['size'] ? ( $l['name'] . ' - ' . $l['size'] ) : $l['name'];
	}, $optimo_lines ) ) ) );

	echo "\n" . str_repeat( '=', 72 ) . "\nFINAL product_name_custom_field that WOULD be sent to Optimo:\n  " . $final . "\n";
	exit;
} );
