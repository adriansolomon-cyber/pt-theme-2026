<?php
/**
 * Email Order Items
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'get_composite_product_size_image_id' ) ) {
/**
 * Get image ID from composite SIZE component
 * - prefers variation image
 * - falls back to simple size product image
 * - falls back to parent later
 */
function get_composite_product_size_image_id( WC_Order_Item_Product $item ): int {

    $parent_product = $item->get_product();
    if ( ! $parent_product || ! $parent_product->is_type( 'composite' ) ) {
        return 0;
    }

    $composite_data = $item->get_meta( '_composite_data', true );
    if ( ! is_array( $composite_data ) || empty( $composite_data ) ) {
        return 0;
    }

    // Build map: component_id => title (lowercase)
    $components = method_exists( $parent_product, 'get_components' ) ? $parent_product->get_components() : [];
    $title_map  = [];

    if ( is_array( $components ) ) {
        foreach ( $components as $cid => $component_obj ) {
            if ( is_object( $component_obj ) && method_exists( $component_obj, 'get_title' ) ) {
                $title_map[ (string) $cid ] = strtolower( trim( (string) $component_obj->get_title() ) );
            }
        }
    }

    // Find the Size component config ONLY
    $size_config = null;

    foreach ( $composite_data as $component_id => $component_config ) {
        $title = $title_map[ (string) $component_id ] ?? '';

        // match "size" (you can extend with other labels if needed)
        if ( $title === 'size' || strpos( $title, 'size' ) !== false ) {
            $size_config = $component_config;
            break;
        }
    }

    // If we couldn't identify size component, DO NOT grab other component images
    if ( ! is_array( $size_config ) ) {
        return 0;
    }

    // Prefer variation image
    if ( ! empty( $size_config['variation_id'] ) ) {
        $variation = wc_get_product( (int) $size_config['variation_id'] );
        if ( $variation && $variation->get_image_id() ) {
            return (int) $variation->get_image_id();
        }
    }

    // Fallback to size product image
    if ( ! empty( $size_config['product_id'] ) ) {
        $size_product = wc_get_product( (int) $size_config['product_id'] );
        if ( $size_product && $size_product->get_image_id() ) {
            return (int) $size_product->get_image_id();
        }
    }

    // Size found but no image -> use parent
    return 0;
}
}


$text_align = is_rtl() ? 'right' : 'left';
?>

<tr>
    <td colspan="3" style="text-align:center;font-size:1.5rem;font-weight:600;">
        ORDER SUMMARY
    </td>
</tr>

<?php foreach ( $items as $item_id => $item ) :

    if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
        continue;
    }

    $product = $item->get_product();
    if ( ! $product ) {
        continue;
    }

    $component_title = trim( (string) get_component_title_by_order_item( $item->get_id(), $item ) );

    /**
     * ------------------------------------------------
     * MAIN PRODUCT ROW (composite parent)
     * ------------------------------------------------
     */
    if ( $component_title === '' && ! has_term( [ 'parts', 'bundles' ], 'product_cat', $product->get_id() ) ) :

       $size_image_id = get_composite_product_size_image_id( $item );
        //    $image_id = $size_image_id ?: ( $product ? $product->get_image_id() : 0 );
        $image_id = $product ? $product->get_image_id() : 0;


        ?>

<tr>
    <td colspan="3" style="text-align:center;">
        <p style="font-size:1.3rem;font-weight:600;line-height:30px;">
            <?php echo apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ); ?>
            <span style="font-size:1rem;font-weight:normal;">× <?php echo (int) $item->get_quantity(); ?></span>
        </p>

        <?php
        echo apply_filters(
            'woocommerce_order_item_thumbnail',
            '<span style="margin-bottom:5px;"><img src="' .
            ( $image_id
                ? esc_url( current( wp_get_attachment_image_src( $image_id, 'medium' ) ) )
                : esc_url( wc_placeholder_img_src() )
            ) .
            '" style="display: unset !important;" alt="' . esc_attr__( 'Product image', 'email-control' ) . '" width="250" /></span>',
            $item
        );
        ?>

        <p style="font-size:12px;font-style:italic;">
            Please note that the image is for illustration purposes only and may not reflect the exact size of the item.
        </p>
    </td>
</tr>

<tr>
    <th style="padding:0;border-bottom:1px solid;">ITEM</th>
    <th style="text-align:center;border-bottom:1px solid;width:80px;">QUANTITY</th>
    <th style="text-align:right;border-bottom:1px solid;">PRICE</th>
</tr>

<tr>
    <td><?php echo apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ); ?></td>
    <td style="text-align:center;"><?php echo (int) $item->get_quantity(); ?></td>
    <td style="text-align:right;"><?php echo $order->get_formatted_line_subtotal( $item ) ?: wc_price( 0 ); ?></td>
</tr>

<?php
    /**
     * ------------------------------------------------
     * COMPONENT ROWS
     * ------------------------------------------------
     */
    elseif ( $component_title !== '' ) : ?>

<tr>
    <td style="width:80%;">
        <dl style="margin:2px 0;">
            <dt style="display:inline;font-weight:bold;"><?php echo esc_html( ucwords( $component_title ) ); ?>:</dt>
            <dd style="display:inline;margin:0;"><?php echo esc_html( $product->get_title() ); ?></dd>
        </dl>
    </td>
    <td style="text-align:center;"><?php echo (int) $item->get_quantity(); ?></td>
    <td style="text-align:right;"><?php echo $order->get_formatted_line_subtotal( $item ) ?: wc_price( 0 ); ?></td>
</tr>

<?php endif;

    // Purchase note (optional)
    if ( isset( $show_purchase_note ) && $show_purchase_note && ( $note = $product->get_purchase_note() ) ) : ?>

<!-- <tr>
    <td colspan="3"><?php //echo wpautop( wp_kses_post( $note ) ); ?></td>
</tr> -->

<?php endif;

endforeach; ?>