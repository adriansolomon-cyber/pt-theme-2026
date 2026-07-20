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

    function optimo_create_or_update_order( WC_Order $order, string $delivery_date, string $apikey = OPTIMO_API_KEY ): array {
        $orderNo = $order->get_order_number();
        $order_id = $order->get_id();

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

        // Build shared fields
        $parent_product_names = implode( ', ', array_unique( array_filter( array_map( function ( $item ) {
            // Skip bundle/composite child items — only keep top-level products
            if ( $item->get_meta( '_bundled_by' ) || $item->get_meta( '_composite_parent' ) ) return null;

            $product = $item->get_product();
            if ( ! $product ) return 'Unknown Product';
            $parent_id = $product->get_parent_id();
            $parent    = $parent_id ? wc_get_product( $parent_id ) : $product;
            $name      = $parent ? $parent->get_name() : '';
            return $name ?: 'Unknown Product';
        }, $order->get_items() ) ) ) );

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
                'szegedi.szilard@projecttimber.co.uk, adrian.solomon@projecttimber.co.uk',
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