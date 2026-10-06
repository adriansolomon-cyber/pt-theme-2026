<?php
/**
* ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
* OPTIMOROUTE HELPERS
* ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
*/

define( 'OPTIMO_API_KEY', defined( 'PT_OPTIMO_API_KEY' ) ? PT_OPTIMO_API_KEY : '' );
define( 'OPTIMO_BASE_URL', 'https://api.optimoroute.com/v1' );

/**
* Query OptimoRoute completion details for a single orderNo.
* Returns one of: delivered | not_found | not_delivered | unknown
*/

function optimo_get_completion_state( $orderNo, $apikey ) {
    $url = add_query_arg(
        [ 'key' => $apikey, 'orderNo' => $orderNo ],
        OPTIMO_BASE_URL . '/get_completion_details'
    );
    $resp = wp_remote_get( $url, [ 'timeout' => 20 ] );
    if ( is_wp_error( $resp ) ) return 'unknown';

    $decoded = json_decode( wp_remote_retrieve_body( $resp ), true );
    $root    = is_array( $decoded ) && isset( $decoded[ 0 ] ) ? $decoded[ 0 ] : $decoded;

    if ( !is_array( $root ) || empty( $root[ 'orders' ][ 0 ] ) ) return 'unknown';

    $orderObj = $root[ 'orders' ][ 0 ];
    if ( !empty( $orderObj[ 'code' ] ) && $orderObj[ 'code' ] === 'ERR_ORD_NOT_FOUND' ) return 'not_found';
    if ( !empty( $orderObj[ 'data' ][ 'status' ] ) && $orderObj[ 'data' ][ 'status' ] === 'success' ) return 'delivered';

    return 'not_delivered';
}

/**
* Simple cURL JSON POST helper.
*/

function optimo_curl_post_json( $url, array $payload ) {
    $ch = curl_init();
    curl_setopt_array( $ch, [
        CURLOPT_URL            => $url,
        CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json' ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 80,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode( $payload ),
    ] );
    $result = curl_exec( $ch );
    curl_close( $ch );
    return json_decode( $result );
}

/**
* ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
* PUBLIC API — callable from any plugin or integration
* ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
*/

/**
* Create or update an order in OptimoRoute.
*
* @param WC_Order $order        The WooCommerce order object.
* @param string   $delivery_date  Y-m-d formatted delivery date.
* @param string   $apikey        OptimoRoute API key ( optional, falls back to constant ).
*
* @return array {
    *   bool   $success
    *   string $action   'created' | 'updated' | 'blocked' | 'error'
    *   string $message  Human-readable result
    * }
    */

    /**
     * Whether an order already has a Palletways consignment — i.e. it ships by pallet
     * freight and must NOT be routed through OptimoRoute (own-vehicle delivery).
     * con_no / response_id are set when the consignment is created; tracking_id can be
     * empty or the literal 'Array' until the depot assigns it, so that's "no value".
     *
     * @param WC_Order $order Order.
     * @return bool
     */
    function optimo_order_has_palletways_consignment( WC_Order $order ): bool {
        foreach ( [ '_palletways_response_id', '_palletways_con_no', '_palletways_tracking_id' ] as $key ) {
            $v = $order->get_meta( $key );
            if ( ! empty( $v ) && 'Array' !== $v ) {
                return true;
            }
        }
        return false;
    }

    /**
     * If the order has a Palletways consignment, make sure it isn't left in Optimo:
     * pull it out ONCE (it may have been pushed before the consignment existed), flag
     * it so the delete/API call isn't repeated on every later update, and tell the
     * caller to stop (the order must not be routed through OptimoRoute).
     *
     * @param WC_Order $order Order.
     * @return bool True when it's a Palletways delivery (the caller should return).
     */
    function optimo_remove_if_palletways( WC_Order $order ): bool {
        if ( ! optimo_order_has_palletways_consignment( $order ) ) {
            return false;
        }
        if ( ! $order->get_meta( '_optimo_removed_palletways' ) ) {
            optimo_delete_order( $order );
            $order->update_meta_data( '_optimo_removed_palletways', current_time( 'mysql' ) );
            $order->save_meta_data();
        }
        return true;
    }

    /**
     * The chosen SIZE of a composite building, read straight from its Size COMPONENT
     * — the structured selection behind the "16 x 8" line — NOT by scanning line-item
     * names (part/kit names also contain sizes). Mirrors how the order email resolves
     * the per-size image (get_composite_product_size_image_id).
     *
     * @param WC_Order_Item_Product $item Composite container line item.
     * @return string e.g. "16 x 8", or '' when not resolvable from the component.
     */
    function optimo_size_from_composite_item( $item ): string {
        if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
            return '';
        }
        $parent_product = $item->get_product();
        if ( ! $parent_product || ! $parent_product->is_type( 'composite' ) ) {
            return '';
        }
        $composite_data = $item->get_meta( '_composite_data', true );
        if ( ! is_array( $composite_data ) || ! $composite_data ) {
            return '';
        }
        // Map component id => lowercased title, to find the "Size" component.
        $title_map  = [];
        $components = is_callable( [ $parent_product, 'get_components' ] ) ? $parent_product->get_components() : [];
        if ( is_array( $components ) ) {
            foreach ( $components as $cid => $comp ) {
                if ( is_object( $comp ) && is_callable( [ $comp, 'get_title' ] ) ) {
                    $title_map[ (string) $cid ] = strtolower( trim( (string) $comp->get_title() ) );
                }
            }
        }
        $size_config = null;
        foreach ( $composite_data as $cid => $cfg ) {
            $title = $title_map[ (string) $cid ] ?? '';
            if ( 'size' === $title || false !== strpos( $title, 'size' ) ) {
                $size_config = $cfg;
                break;
            }
        }
        if ( ! is_array( $size_config ) ) {
            return '';
        }
        $pid = ! empty( $size_config['variation_id'] ) ? (int) $size_config['variation_id']
             : ( ! empty( $size_config['product_id'] ) ? (int) $size_config['product_id'] : 0 );
        if ( ! $pid ) {
            return '';
        }
        $size_product = wc_get_product( $pid );
        if ( ! $size_product ) {
            return '';
        }
        // The size product's NAME ("16 x 8") is the clean source; the SKU
        // ("16ft-x-8ft-…") is the backup. Parse a single N x N from either.
        $size_re = '/(\d+(?:\.\d+)?)\s*(?:ft|m|cm|\')?\s*(?:x|×|by)\s*(\d+(?:\.\d+)?)/iu';
        foreach ( [ $size_product->get_name(), $size_product->get_sku() ] as $cand ) {
            if ( '' !== (string) $cand && preg_match( $size_re, (string) $cand, $m ) ) {
                return $m[1] . ' x ' . $m[2];
            }
        }
        return '';
    }

    function optimo_create_or_update_order( WC_Order $order, string $delivery_date, string $apikey = OPTIMO_API_KEY ): array {
        $orderNo = $order->get_order_number();
        $order_id = $order->get_id();

        // Palletways guard: a pallet-freight order is NOT an Optimo (own-vehicle)
        // delivery. If it already has a Palletways consignment, skip the Optimo push
        // entirely — both triggers (status change + admin save) land here, so this
        // stops an admin save of a Palletways order from re-sending it to OptimoRoute.
        if ( optimo_remove_if_palletways( $order ) ) {
            return [ 'success' => false, 'action' => 'skipped', 'message' => 'Palletways consignment present — removed from / not sent to OptimoRoute.' ];
        }

        // Guard: block if delivered or unknown
        $state = optimo_get_completion_state( $orderNo, $apikey );
        if ( $state === 'delivered' ) {
            $msg = '🚫 Optimo: order already delivered — create/update blocked.';
            $order->add_order_note( $msg, 0, false );
            return [ 'success' => false, 'action' => 'blocked', 'message' => $msg ];
        }
        if ( $state === 'unknown' ) {
            $msg = '⚠️ Optimo: completion status unknown — create/update blocked (fail-safe).';
            $order->add_order_note( $msg, 0, false );
            return [ 'success' => false, 'action' => 'blocked', 'message' => $msg ];
        }

        // Build the product name(s) for Optimo, with each composite's SIZE appended
        // ("Grandmaster Pent - 12 x 10"). PRIMARY source is the composite's Size
        // COMPONENT (optimo_size_from_composite_item) — the structured selection behind
        // the "16 x 8" line, immune to part/kit names. Only if that can't resolve (e.g.
        // a non-composite order, or missing _composite_data) do we FALL BACK to scanning
        // the line-item names: a pure-size line ("16 x 8") or a size line resolving to
        // the container's parent. The name scan rejects multi-size kit descriptors and
        // never overrides a component-locked size. The regex tolerates unit suffixes
        // (16ft x 8ft) and decimals.
        $optimo_lines = [];
        $optimo_cur   = null;
        $size_re      = '/(\d+(?:\.\d+)?)\s*(?:ft|m|cm|\')?\s*(?:x|×|by)\s*(\d+(?:\.\d+)?)/iu';
        foreach ( $order->get_items() as $item ) {
            $product   = $item->get_product();
            $iname     = $product ? (string) $product->get_name() : (string) $item->get_name();
            $parent_id = $product ? (int) $product->get_parent_id() : 0;
            $is_child  = $item->get_meta( '_composite_parent' ) || $item->get_meta( '_bundled_by' );
            $has_size  = preg_match( $size_re, $iname, $m );
            // A line whose NAME is nothing but a size ("16 x 8", "16ft x 8ft") is a
            // size regardless of flags/parent — covers size options stored as their own
            // simple product (parent_id 0).
            $pure_size = $has_size && preg_match( '/^\s*\d+(?:\.\d+)?\s*(?:ft|m|cm|\')?\s*(?:x|×|by)\s*\d+(?:\.\d+)?\s*(?:ft|m|cm|\')?\s*$/iu', trim( $iname ) );
            // A name carrying MULTIPLE distinct "N x N" tokens is a kit/parts descriptor
            // ("Building Fixing Kit - 12x10-10x10-12x8-10x8 …"), NOT the chosen size —
            // it must never set or override the building size (this is what produced the
            // wrong Optimo size: a part line clobbered the real size option).
            $multi_size = false;
            if ( preg_match_all( $size_re, $iname, $all_m, PREG_SET_ORDER ) ) {
                $toks = [];
                foreach ( $all_m as $one ) { $toks[ $one[1] . 'x' . $one[2] ] = true; }
                $multi_size = count( $toks ) > 1;
            }

            // Is this line the SIZE of the current building?
            $is_size_line = $optimo_cur && $has_size && ! $multi_size && (
                $is_child
                || ( $parent_id && ! empty( $optimo_cur['parent_id'] ) && $parent_id === (int) $optimo_cur['parent_id'] )
                || $pure_size
            );
            if ( $is_size_line ) {
                // A pure-size line ("12 x 8") is authoritative and LOCKS the size; an
                // embedded single-size line only fills when nothing has locked it yet,
                // so a later single-size part line can't clobber the real size.
                if ( $pure_size ) {
                    $optimo_cur['size']        = $m[1] . ' x ' . $m[2];
                    $optimo_cur['size_locked'] = true;
                } elseif ( empty( $optimo_cur['size_locked'] ) ) {
                    $optimo_cur['size'] = $m[1] . ' x ' . $m[2];
                }
                continue;
            }
            if ( $is_child ) {
                continue; // a non-size child (wall, floor, roof…)
            }

            // Otherwise this is a building (container) — start a new line.
            if ( $optimo_cur ) $optimo_lines[] = $optimo_cur;
            $parent     = $parent_id ? wc_get_product( $parent_id ) : $product;
            $optimo_cur = [
                'name'        => ( $parent ? $parent->get_name() : '' ) ?: 'Unknown Product',
                'size'        => '',
                'size_locked' => false,
                'parent_id'   => $parent_id ?: ( $product ? (int) $product->get_id() : 0 ),
            ];
            // PRIMARY: read the size from THIS composite's Size component and lock it,
            // so the name-scan below (and PIP) only ever act as fallbacks.
            $comp_size = optimo_size_from_composite_item( $item );
            if ( '' !== $comp_size ) {
                $optimo_cur['size']        = $comp_size;
                $optimo_cur['size_locked'] = true;
            }
        }
        if ( $optimo_cur ) $optimo_lines[] = $optimo_cur;

        // Fallback for a blank size: pull it from the PIP "Extras/Options" field
        // (_pip_extras_or_option), which reads "{name} ||| {size}, {components}" per
        // building and is populated when an admin saves the order. Only fills a size
        // the line items couldn't resolve — never overrides — and only helps on an
        // update push, since the field is empty at checkout (first create push uses
        // the line items). Sizes are matched to the buildings in order.
        $needs_size = false;
        foreach ( $optimo_lines as $l ) {
            if ( '' === $l['size'] ) { $needs_size = true; break; }
        }
        if ( $needs_size ) {
            $pip = (string) $order->get_meta( '_pip_extras_or_option' );
            if ( '' !== $pip && preg_match_all( '/\|\|\|\s*(\d+(?:\.\d+)?\s*(?:ft|m|cm|\')?\s*(?:x|×|by)\s*\d+(?:\.\d+)?)/iu', $pip, $mm ) ) {
                $i = 0;
                foreach ( $optimo_lines as $k => $l ) {
                    if ( '' === $l['size'] && isset( $mm[1][ $i ] ) && preg_match( $size_re, $mm[1][ $i ], $sm ) ) {
                        $optimo_lines[ $k ]['size'] = $sm[1] . ' x ' . $sm[2];
                    }
                    $i++;
                }
            }
        }

        $parent_product_names = implode( ', ', array_unique( array_filter( array_map( function ( $l ) {
            return '' !== $l['size'] ? ( $l['name'] . ' - ' . $l['size'] ) : $l['name'];
        }, $optimo_lines ) ) ) );

        $phone = str_replace( ' ', '', explode( '/', ( string ) $order->get_billing_phone() )[ 0 ] ?? '' );
        $address = str_replace( '<br/>', ',', $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address() );
        $total_load = round( order_get_total_weight( $order_id ), 0 );
        $email = strtolower( $order->get_billing_email() ?: 'sales@projecttimber.com' );
        $type  = $order->get_status() === 'rdm' ? 'T' : 'D';
        $name  = ucwords( $order->get_formatted_billing_full_name() );

        $create_payload = [
            'operation' => 'CREATE',
            'orderNo'   => $orderNo,
            'type'      => $type,
            'date'      => $delivery_date,
            'location'  => [
                'address'               => $address,
                'locationNo'            => '',
                'locationName'          => $name,
                'acceptPartialMatch'    => true,
                'acceptMultipleResults' => true,
            ],
            'duration'               => 30,
            'twFrom'                 => '',
            'twTo'                   => '',
            'load1'                  => $total_load ?: 0,
            'load2'                  => 0,
            'vehicleFeatures'        => [],
            'skills'                 => [],
            'notes'                  => $order->get_meta( 'special_instructions' ),
            'email'                  => $email,
            'phone'                  => $phone,
            'notificationPreference' => 'both',
            'customFields' => [
                    'product_name_custom_field' => $parent_product_names,
                ],

        ];

        $data = optimo_curl_post_json( OPTIMO_BASE_URL . '/create_order?key=' . $apikey, $create_payload );

        // Order already exists — attempt UPDATE
        $exists = !empty( $data->message ) &&
        ( str_contains( $data->message, 'exists' ) || str_contains( $data->message, 'orderNo' ) );

        if ( $exists ) {
            // Re-check before update
            $state2 = optimo_get_completion_state( $orderNo, $apikey );
            if ( $state2 === 'delivered' || $state2 === 'unknown' ) {
                $msg = '🚫 Optimo: delivered/unknown state before update — update blocked.';
                $order->add_order_note( $msg, 0, false );
                return [ 'success' => false, 'action' => 'blocked', 'message' => $msg ];
            }

            $update_payload = [
                'orders' => [ [
                    'operation'              => 'MERGE',
                    'orderNo'                => $orderNo,
                    'type'                   => $type,
                    'date'                   => $delivery_date,
                    'location'               => [
                        'address'      => $address,
                        'locationName' => $name,
                    ],
                    'load1'                  => $total_load ?: 0,
                    'notes'                  => $order->get_meta( 'special_instructions' ),
                    'email'                  => $email,
                    'phone'                  => $phone,
                    'notificationPreference' => 'both',
                    'customFields' => [
                    'product_name_custom_field' => $parent_product_names,
                ],

                ] ],
            ];

            $data = optimo_curl_post_json( OPTIMO_BASE_URL . '/create_or_update_orders?key=' . $apikey, $update_payload );

            if ( !empty( $data->success ) ) {
                $order->add_order_note( '♻️ Order updated in OptimoRoute.', 0, false );
                return [ 'success' => true, 'action' => 'updated', 'message' => 'Order updated in OptimoRoute.' ];
            }

            $err = $data->message ?? ( $data->orders[ 0 ]->message ?? 'Unknown error' );
            optimo_send_error_email( $order_id, $err, 'Update failed' );
            return [ 'success' => false, 'action' => 'error', 'message' => $err ];
        }

        // CREATE result
        if ( !empty( $data->success ) ) {
            $order->add_order_note( '📦 Order sent to OptimoRoute.', 0, false );
            return [ 'success' => true, 'action' => 'created', 'message' => 'Order created in OptimoRoute.' ];
        }

        $err = $data->message ?? 'Unknown error';
        optimo_send_error_email( $order_id, $err, 'Create failed' );
        return [ 'success' => false, 'action' => 'error', 'message' => $err ];
    }

    /**
    * Delete an order from OptimoRoute.
    *
    * @param WC_Order $order   The WooCommerce order object.
    * @param string   $apikey  OptimoRoute API key ( optional, falls back to constant ).
    *
    * @return array {
        *   bool   $success
        *   string $action   'deleted' | 'blocked' | 'not_found' | 'error'
        *   string $message
        * }
        */

        function optimo_delete_order( WC_Order $order, string $apikey = OPTIMO_API_KEY ): array {
            $orderNo = $order->get_order_number();

            $state = optimo_get_completion_state( $orderNo, $apikey );

            if ( $state === 'delivered' ) {
                $msg = '🚫 Optimo: order already delivered — delete blocked.';
                $order->add_order_note( $msg, false, false );
                return [ 'success' => false, 'action' => 'blocked', 'message' => $msg ];
            }
            if ( $state === 'unknown' ) {
                $msg = '⚠️ Optimo: completion status unknown — delete blocked (fail-safe).';
                $order->add_order_note( $msg, false, false );
                return [ 'success' => false, 'action' => 'blocked', 'message' => $msg ];
            }
            if ( $state === 'not_found' ) {
                $msg = 'ℹ️ Optimo: order not found — nothing to delete.';
                $order->add_order_note( $msg, false, false );
                return [ 'success' => true, 'action' => 'not_found', 'message' => $msg ];
            }

            // not_delivered — safe to delete
            $data = optimo_curl_post_json(
                OPTIMO_BASE_URL . '/delete_order?key=' . $apikey,
                [ 'orderNo' => $orderNo, 'forceDelete'=> true ]
            );

            if ( !empty( $data->success ) ) {
                $order->add_order_note( '🗑️ Order deleted from OptimoRoute.', false, false );
                return [ 'success' => true, 'action' => 'deleted', 'message' => 'Order deleted from OptimoRoute.' ];
            }

            $msg = '❌ Failed deleting from OptimoRoute.';
            $order->add_order_note( $msg, false, false );
            return [ 'success' => false, 'action' => 'error', 'message' => $msg ];
        }

        /**
        * Send error notification email.
        */

        function optimo_send_error_email( int $order_id, string $message, string $subject_suffix = 'Error' ): void {
            wp_mail(
                'adrian.solomon@projecttimber.co.uk',
                "Error in OptimoRoute projecttimber — {$subject_suffix}",
                "Error: {$message}<br/> Edit Order: " . admin_url( "post.php?post={$order_id}&action=edit" ),
                [
                    'Content-Type: text/html; charset=UTF-8',
                    'From: Project Timber <sales@projecttimber.com>',
                    'Reply-To: <sales@projecttimber.com>',
                ]
            );
        }

        /**
        * ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
        * 1 ) Status-change hook — delegates to public API functions
        * ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
        */

        function optimo_add_working_days( int $days ): string {
            $date = new DateTime();
            $added = 0;
            while ( $added < $days ) {
                $date->modify( '+1 day' );
                if ( $date->format( 'N' ) < 6 ) $added++;
            }
            return $date->format( 'Y-m-d' );
        }

        function sendAllOrdersToOptimo( $order_id, $old_status, $new_status ) {
            $order = wc_get_order( $order_id );
            if ( !$order ) return;

            // Palletways order → never in Optimo. On ANY status change (incl. the move
            // to "Planned" when the consignment is created, which returns early below),
            // pull it out of Optimo once if it was pushed earlier, then stop.
            if ( optimo_remove_if_palletways( $order ) ) return;

            $formatted_status = strtolower( ( string ) $new_status );

            if ( str_contains( $formatted_status, 'cancel' ) || str_contains( $formatted_status, 'failed' ) ) {
                optimo_delete_order( $order );
                return;
            }

            if ( $new_status !== 'processing' ) return;

            $date = get_post_meta( $order_id, '_from_delivery_date', true );
            $date = $date ? date( 'Y-m-d', strtotime( $date ) ) : optimo_add_working_days( 20 );

            optimo_create_or_update_order( $order, $date );
        }
        add_action( 'woocommerce_order_status_changed', 'sendAllOrdersToOptimo', 10, 3 );

        /**
        * ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
        * 2 ) Admin save hook — delegates to public API functions
        * ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
        */

        function pt_optimoroute_create_order() {
            if ( empty( $_POST[ 'post_ID' ] ) ) return;

            $order_status_post = ( string ) ( $_POST[ 'order_status' ] ?? '' );
            if (
                $order_status_post === 'wc-completed' ||
                str_contains( $order_status_post, 'failed' ) ||
                str_contains( $order_status_post, 'pending' ) ||
                str_contains( $order_status_post, 'cancel' )
            ) return;

            if ( empty( $_POST[ '_final_delivery_date' ] ) || !is_user_logged_in() ) return;

            $order_id = ( int ) $_POST[ 'post_ID' ];
            $order    = new WC_Order( $order_id );

            $date = date_format( date_create( sanitize_text_field( $_POST[ '_final_delivery_date' ] ) ), 'Y-m-d' );

            optimo_create_or_update_order( $order, $date );
        }
        add_action( 'woocommerce_process_shop_order_meta', 'pt_optimoroute_create_order' );