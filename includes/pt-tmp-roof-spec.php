<?php
/**
 * TEMPORARY one-off admin tool — set "Roof Material" (_specs_roof_material) on
 * every SIZE OPTION of a composite (not the parent). Remove after use.
 *
 * Usage (must be logged in as an admin / shop manager):
 *   Dry-run (changes nothing, lists current values):
 *     /?pt_roof_dryrun=88778
 *   Apply "Rubber Roof" to every size option:
 *     /?pt_roof_apply=88778&confirm=yes
 *
 * Value is fixed to "Rubber Roof". Parent product is never touched.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'template_redirect',
	static function () {
		$apply = isset( $_GET['pt_roof_apply'] ) ? absint( $_GET['pt_roof_apply'] ) : 0;
		$dry   = isset( $_GET['pt_roof_dryrun'] ) ? absint( $_GET['pt_roof_dryrun'] ) : 0;
		$pid   = $apply ? $apply : $dry;
		if ( ! $pid ) {
			return;
		}

		// Admin-only.
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			status_header( 403 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo "Forbidden — log in as an admin / shop manager.";
			exit;
		}

		$is_apply = ( $apply && isset( $_GET['confirm'] ) && 'yes' === $_GET['confirm'] );
		$value    = 'Rubber Roof';
		$key      = '_specs_roof_material';

		header( 'Content-Type: text/plain; charset=UTF-8' );

		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
		if ( ! $product || ! $product->is_type( 'composite' ) ) {
			echo "Product $pid is not a composite (or not found).";
			exit;
		}

		// Resolve the Size component's option sub-product IDs.
		$ids = array();
		if ( function_exists( 'timber_catp_size_options' ) ) {
			$ids = (array) timber_catp_size_options( $product );
		}
		if ( empty( $ids ) && is_callable( array( $product, 'get_components' ) ) ) {
			foreach ( (array) $product->get_components() as $comp ) {
				$title = ( is_object( $comp ) && is_callable( array( $comp, 'get_title' ) ) ) ? strtolower( trim( (string) $comp->get_title() ) ) : '';
				if ( 'size' === $title ) {
					$ids = is_callable( array( $comp, 'get_options' ) ) ? (array) $comp->get_options() : array();
					break;
				}
			}
		}
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );

		echo ( $is_apply ? 'APPLY' : 'DRY-RUN' ) . " — composite {$pid}: {$product->get_name()}\n";
		echo 'size options found: ' . count( $ids ) . "\n";
		echo str_repeat( '-', 60 ) . "\n";

		if ( empty( $ids ) ) {
			echo "No size options resolved — nothing to do.\n";
			exit;
		}

		foreach ( $ids as $oid ) {
			$o    = wc_get_product( $oid );
			$name = $o ? $o->get_name() : '(missing product)';
			$cur  = get_post_meta( $oid, $key, true );
			if ( $is_apply ) {
				update_post_meta( $oid, $key, $value );
				echo sprintf( "%-8d %-24s  '%s' -> '%s'\n", $oid, $name, $cur, $value );
			} else {
				echo sprintf( "%-8d %-24s  current='%s'\n", $oid, $name, $cur );
			}
		}

		echo str_repeat( '-', 60 ) . "\n";
		echo "parent {$pid} {$key} (left unchanged) = '" . get_post_meta( $pid, $key, true ) . "'\n";
		if ( $apply && ! $is_apply ) {
			echo "\nNOTE: add &confirm=yes to actually apply. Nothing was changed.\n";
		} else {
			echo "\nDONE" . ( $is_apply ? ' — values updated.' : ' — dry-run, nothing changed.' ) . "\n";
		}
		exit;
	},
	0
);
