<?php
    // include_once('../../../../../wp-config.php');
    // include_once('../../../../../wp-includes/wp-db.php');

    include "attempted-order-header.php";
?>

<!-- Body Content -->

<tr style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

    <td class="body_content" align="center" valign="top" style="color: #3B333D; font-family: 'jr', sans-serif !important; font-size: 16px; line-height: 1.5em; font-weight: normal !important; text-align: center; margin: 0; padding: 30px 0 0 !important">

    <div class="top_heading" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; color: #3B333D; font-size: 30px; letter-spacing: -1px; line-height: 30px; margin: 0; padding: 0 0 10px !important">

        <p style="margin: 10px 0; padding: 0; font-family: 'jr', sans-serif !important; font-weight: normal !important; text-align: center; color: #3B333D">Attempted order</p>

    </div>

    <p style="margin: 10px 0; padding: 0; font-family: 'jr', sans-serif !important; font-weight: normal !important; text-align: center; color: #3B333D">You have received an attempted order from <?php _e( $attempted_order->name );?>.</p>

    <p style="margin: 10px 0px; padding: 0; text-align: center; color: rgb(59, 51, 61); font-family: 'jr', sans-serif !important; font-weight: normal !important">The details are as follows:</p>
    <p style="margin:10px 0;padding:0;font-family:jr,sans-serif!important;font-weight:normal!important;text-align:center;color:#3b333d;">&nbsp;</p>
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="color: #3B333D; font-family: 'jr', sans-serif, sans-serif; font-size: 16px; line-height: 1.5em">

    <tr style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

        <td class="order_items_table_holder" style="color: #3B333D; font-family: 'jr', sans-serif !important; font-size: 16px; line-height: 1.5em; font-weight: normal !important; background: #fdfdfd; border-radius: 0; border: 0">

            <table cellspacing="0" cellpadding="0" border="0" width="100%" style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

                <!-- <tr style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

                    <td class="order-table-heading" style="color: #757575; font-family: 'jr', sans-serif !important; font-size: 16px; line-height: 1.5em; font-weight: normal !important; padding: 24px 12px 0 !important">
                        <p style="margin: 2px 0; padding: 0; font-family: 'jr', sans-serif !important; font-weight: normal !important; text-align: center; color: #757575"><span class="highlight" style="color: #3B333D; text-decoration: none; font-style: none; padding-right: 5px">Order Date</span> <?php _e( date( 'jS F Y', strtotime( $attempted_order->date_added ) ) ); ?></p>
                    </td>

                </tr> -->

            </table>

            <table cellspacing="0" cellpadding="0" class="order_items_table" border="0" width="100%" style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em; margin: 0; overflow: hidden; width: 100%; background: white;">

                <tbody>
                    <?php

                        foreach ( $cart_items as $cart_item_key => $cart_item ):

                              echo '<pre>';
                       // print_r($cart_items);
                        echo '</pre>';

                    ?>

                        <?php
                        $_product = wc_get_product( $cart_item->product_id );

                        if ( $_product && $cart_item->quantity > 0 ) : ?>

                            <?php if ( $_product->product_type == 'composite' ) : ?>

                                <tr class="order_item-details parent" style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

                                    <td class="order_items_table_td order_items_table_td_product order_items_table_td_product_details" width="80%" style="color: #9e9e9e; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 20px; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: left; vertical-align: top; padding-top: 20px; padding-bottom: 5px !important">

                                        <table class="order_items_table_product_details_inner" cellpadding="0" cellspacing="0" border="0" width="100%" style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em; min-width: 250px;">

                                            <tr style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

                                                <td class="order_items_table_product_details_inner_td order_items_table_product_details_inner_td_text" width="100%" style="color: #9e9e9e; font-family: 'jr', sans-serif !important; font-size: 13px; line-height: 1.5em; font-weight: normal !important; vertical-align: top; padding-bottom: 5px !important">

                                                    <div class="order_items_table_product_details_inner_title" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; color: #3B333D !important; font-size: 18px !important; line-height: 1.35em !important; padding: 0 0 10px !important; padding-bottom: 3px"><?php _e( $_product->get_title() );?></div>

                                                </td>

                                            </tr>

                                        </table>

                                    </td>

                                    <?php

                                    $composite_parent_line_total = 0;
                                    $composite_parent_line_subtotal = 0;

                                    foreach ( $cart_items as $cart_item_key2 => $cart_item2 ) {

                                        if( $cart_item2->composite_parent == $cart_item_key ) {
                                            $composite_parent_line_total += $cart_item2->line_total;
                                            $composite_parent_line_subtotal += $cart_item2->line_subtotal;
                                        }
                                    }

                                    $composite_parent_line_total *= $cart_item->quantity;

                                    ?>

                                    <td class="order_items_table_td order_items_table_td_product order_items_table_td_product_total" style="text-align: right; color: #9e9e9e; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 1.5em; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; vertical-align: top; padding-top: 25px; padding-bottom: 5px !important">

                                        <span class="woocommerce-Price-amount amount" style="font-family: 'jr', sans-serif !important; font-size: 16px !important; font-weight: normal !important; color: #757575"><span class="woocommerce-Price-currencySymbol">£</span><?php echo round( $composite_parent_line_total, 2 ); ?></span>

                                    </td>

                                </tr>

                            <?php elseif( $_product->product_type == 'bundle' ) : ?>

                                <tr class="order_item-details child" style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

                                    <td class="order_items_table_td order_items_table_td_product order_items_table_td_product_details" width="80%" style="color: #9E9E9E; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 20px; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: left; vertical-align: top; padding-top: 0 !important; padding-bottom: 1px !important; border: none !important">

                                        <table class="order_items_table_product_details_inner" cellpadding="0" cellspacing="0" border="0" width="100%" style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

                                            <tr style="color: #3B333D; font-family: 'jr', sans-serif; font-size: 16px; line-height: 1.5em">

                                                <td class="order_items_table_product_details_inner_td order_items_table_product_details_inner_td_text" width="100%" style="color: #9E9E9E; font-family: 'jr', sans-serif !important; font-size: 13px; line-height: 1.5em; font-weight: normal !important; vertical-align: top; padding-top: 0 !important; padding-bottom: 1px !important; border: none !important">

                                                    <div class="order_items_table_product_details_inner_title" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; color: #3B333D !important; font-size: 18px !important; line-height: 1.35em !important; padding: 0 0 10px !important; margin: 0 !important; padding-bottom: 3px">

                                                        <dl class="component" style="margin: 0 !important">

                                                            <dt class="component-title" style="float: left; font-size: 14px; width: 100px; font-weight: normal !important; margin: 0 !important; line-height: 1.5em !important"><?php
                                                                $composite_item = $cart_item->composite_item;
                                                                 _e( $cart_item->composite_data->$composite_item->title );?>:</dt>
                                                            <dd class="component-value" style="float: left; font-size: 14px; color: rgb(158,158,158) !important; line-height: 1.5em !important; margin: 0 !important"><?php _e( $_product->get_title() );?></dd>

                                                        </dl>

                                                    </div>

                                                </td>

                                            </tr>

                                        </table>

                                    </td>

                                    <!-- <td class="order_items_table_td order_items_table_td_product order_items_table_td_product_quantity">    1   </td> -->

                                    <td class="order_items_table_td order_items_table_td_product order_items_table_td_product_total" style="text-align: right; color: #9E9E9E; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 1.5em; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; vertical-align: top; padding-top: 0 !important; padding-bottom: 1px !important; border: none !important">

                                    </td>

                                </tr>

                            <?php endif; ?>

                        <?php endif; ?>

                    <?php endforeach;?>
                </tbody>
            </table>

        </td>
    </tr>

<?php include "attempted-order-footer.php"; ?>
