<?php
/**
 * TEMPORARY one-off admin tool — set "Roof Material" (_specs_roof_material) on
 * specific product SIZE IDs. Remove after use.
 *
 * Usage (must be logged in as an admin / shop manager):
 *
 *   By explicit size IDs (recommended — you control exactly which get changed):
 *     Dry-run (changes nothing, lists current values):
 *       /?pt_roof_dryrun=1&ids=12345,12346,12347
 *     Apply "Rubber Roof":
 *       /?pt_roof_apply=1&ids=12345,12346,12347&confirm=yes
 *
 *   Or resolve every size option from a composite parent id:
 *       /?pt_roof_dryrun=1&parent=88778
 *       /?pt_roof_apply=1&parent=88778&confirm=yes
 *
 * Value is fixed to "Rubber Roof"; only _specs_roof_material is touched.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'template_redirect',
	static function () {
		$do_apply  = isset( $_GET['pt_roof_apply'] );
		$do_dryrun = isset( $_GET['pt_roof_dryrun'] );
		if ( ! $do_apply && ! $do_dryrun ) {
			return;
		}

		// Admin-only.
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			status_header( 403 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo 'Forbidden — log in as an admin / shop manager.';
			exit;
		}

		$is_apply = ( $do_apply && isset( $_GET['confirm'] ) && 'yes' === $_GET['confirm'] );
		$value    = 'Rubber Roof';
		$key      = '_specs_roof_material';

		header( 'Content-Type: text/plain; charset=UTF-8' );

		// --- Collect target IDs: explicit `ids` list, else resolve from `parent`. ---
		$ids    = array();
		$source = '';
		if ( isset( $_GET['ids'] ) && '' !== trim( (string) wp_unslash( $_GET['ids'] ) ) ) {
			$raw    = explode( ',', (string) wp_unslash( $_GET['ids'] ) );
			$ids    = array_map( 'absint', $raw );
			$source = 'explicit ids';
		} elseif ( isset( $_GET['parent'] ) && absint( $_GET['parent'] ) ) {
			$pid     = absint( $_GET['parent'] );
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
			if ( ! $product || ! $product->is_type( 'composite' ) ) {
				echo "parent {$pid} is not a composite (or not found).";
				exit;
			}
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
			$ids    = array_map( 'absint', (array) $ids );
			$source = "composite parent {$pid}";
		} else {
			echo "Provide either &ids=1,2,3 or &parent=<compositeId>.";
			exit;
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );

		echo ( $is_apply ? 'APPLY' : 'DRY-RUN' ) . " — source: {$source}\n";
		echo 'target size IDs: ' . count( $ids ) . "\n";
		echo str_repeat( '-', 64 ) . "\n";

		if ( empty( $ids ) ) {
			echo "No IDs to process.\n";
			exit;
		}

		$done = 0;
		foreach ( $ids as $oid ) {
			$o    = function_exists( 'wc_get_product' ) ? wc_get_product( $oid ) : null;
			$name = $o ? $o->get_name() : '(not a product / missing)';
			$cur  = get_post_meta( $oid, $key, true );
			if ( $is_apply ) {
				update_post_meta( $oid, $key, $value );
				++$done;
				echo sprintf( "%-8d %-26s  '%s' -> '%s'\n", $oid, $name, $cur, $value );
			} else {
				echo sprintf( "%-8d %-26s  current='%s'\n", $oid, $name, $cur );
			}
		}

		echo str_repeat( '-', 64 ) . "\n";
		if ( $do_apply && ! $is_apply ) {
			echo "NOTE: add &confirm=yes to actually apply. Nothing was changed.\n";
		} else {
			echo 'DONE' . ( $is_apply ? " — updated {$done} size product(s)." : ' — dry-run, nothing changed.' ) . "\n";
		}
		exit;
	},
	0
);
