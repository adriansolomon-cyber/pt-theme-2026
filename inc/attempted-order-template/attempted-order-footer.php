<?php
    $subtotal = $composite_parent_line_total / 1.2;
    $vat      = $composite_parent_line_total - $subtotal;
?>

                        <tfoot>

                            <tr style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em">

                                <td colspan="3" style="color: #3B333D; font-family: 'jr', sans-serif !important; font-size: 16px; line-height: 1.5em; font-weight: normal !important">

                                    <table cellspacing="0" cellpadding="0" class="order_items_table" border="0" width="100%" style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em; margin: 0; overflow: hidden; width: 100%; background: white; padding-top: 25px">

                                        <thead>

                                            <tr style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em"></tr>

                                            <tr class="order_items_table_total_row order_items_table_total_row_subtotal" style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em">

                                                <th scope="row" class="order_items_table_totals_style order_items_table_td" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; color: #757575; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: right; vertical-align: top; font-size: 14px; background: #fcfcfc; line-height: 1em; width: 50%; padding-right: 12px">Subtotal:</th>

                                                <td class="order_items_table_totals_style order_items_table_td" style="color: #757575; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 1em; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: left; vertical-align: top; background: #fcfcfc; width: 50%; padding-left: 12px">

                                                    <span class="woocommerce-Price-amount amount" style="font-size: 16px !important; font-weight: normal !important">
                                                        <span class="woocommerce-Price-currencySymbol">£</span><?php echo round( $subtotal, 2 ); ?>
                                                    </span>

                                                </td>

                                            </tr>

                                            <?php if( $cart->shipping_total > 0 ) : ?>

                                                <tr class="order_items_table_total_row order_items_table_total_row_delivery" style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em">

                                                    <th scope="row" class="order_items_table_totals_style order_items_table_td" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; color: #757575; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: right; vertical-align: top; font-size: 14px; background: #fcfcfc; line-height: 1em; width: 50%; padding-right: 12px">Delivery:</th>

                                                    <td class="order_items_table_totals_style order_items_table_td" style="color: #757575; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 1em; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: left; vertical-align: top; background: #fcfcfc; width: 50%; padding-left: 12px">

                                                        <span class="woocommerce-Price-amount amount" style="font-size: 16px !important; font-weight: normal !important"><span class="woocommerce-Price-currencySymbol">£</span><?php echo round( $cart->shipping_total, 2 ); ?>
                                                    </span>

                                                </td>

                                            </tr>

                                            <?php endif; ?>

                                            <tr class="order_items_table_total_row order_items_table_total_row_order_tax" style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em">

                                                <th scope="row" class="order_items_table_totals_style order_items_table_td" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; color: #757575; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: right; vertical-align: top; font-size: 14px; background: #fcfcfc; line-height: 1em; width: 50%; padding-right: 12px">VAT:</th>

                                                <td class="order_items_table_totals_style order_items_table_td" style="color: #757575; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 1em; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: left; vertical-align: top; background: #fcfcfc; width: 50%; padding-left: 12px">

                                                    <span class="woocommerce-Price-amount amount" style="font-size: 16px !important; font-weight: normal !important">
                                                        <span class="woocommerce-Price-currencySymbol">£</span><?php echo round( $vat, 2 ); ?>
                                                    </span>

                                                </td>

                                            </tr>

                                            <tr class="order_items_table_total_row order_items_table_total_row_total" style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em">

                                                <th scope="row" class="order_items_table_totals_style order_items_table_td" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; color: #757575; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: right; vertical-align: top; font-size: 14px; background: #fcfcfc; line-height: 1em; width: 50%; padding-right: 12px">Total:</th>

                                                <td class="order_items_table_totals_style order_items_table_td" style="color: #757575; font-family: 'jr', sans-serif !important; font-size: 14px; line-height: 1em; font-weight: normal !important; padding: 15px 30px; border-top: 1px solid #f7f7f7; text-align: left; vertical-align: top; background: #fcfcfc; width: 50%; padding-left: 12px">

                                                    <span class="woocommerce-Price-amount amount" style="font-size: 16px !important; font-weight: normal !important">
                                                        <span class="woocommerce-Price-currencySymbol">£</span><?php echo round( $composite_parent_line_total, 2 ); ?>
                                                    </span>

                                                </td>

                                            </tr>

                                        </thead>

                                    </table>

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </td>

            </tr>

        </table>

        <div style="margin: 10px 0px; padding: 0; text-align: center; color: rgb(59,51,61); font-family: jr, sans-serif !important; font-weight: normal !important">

            &nbsp;

            <br class="m_-485736099679653331webkit-block-placeholder">

        </div>

        <h3 style="font-family: 'jr', sans-serif !important; letter-spacing: -.5px; font-weight: normal !important; color: #3B333D; text-align: center; margin: 18px 0 12px; padding: 0; font-size: 20px; line-height: 20px">Personal Details</h3>

        <div class="m_-485736099679653331edit" style="float:left;text-align:center;width:100%;font-family:jr,sans-serif!important;font-weight:normal!important">

           <div class="edit" style="font-family: 'jr', sans-serif !important;font-weight: normal !important;float: left;text-align: center;width: 100%;height: 35px;">
                <div class="line" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; background: #FFFF00; display: inline-block; height: 2px; position: relative; text-align: center; width: 20px"></div>
            </div>

            <span class="m_-485736099679653331text">
                <a href="mailto:<?php _e( $attempted_order->email );?>" target="_blank">
                    <?php _e( $attempted_order->email );?>
                </a>
            </span>

            <span class="m_-485736099679653331text" style="display:block;padding:3px 0px 0px">
                <?php _e( $attempted_order->phone );?>
            </span>

        </div>

        <table id="addresses" cellspacing="0" cellpadding="0" align="center" style="width: 100%; vertical-align: top; color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em" border="0">

            <tr style="color: #3B333D; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5em">

                <td class="addresses-td" width="50%" valign="top" style="color: #3B333D; font-family: 'jr', sans-serif !important; font-size: 16px; line-height: 1.5em; font-weight: normal !important; padding: 0 !important; text-align: center;">

                    <h3 style="font-family: 'jr', sans-serif !important; letter-spacing: -.5px; font-weight: normal !important; color: #3B333D; text-align: center; margin: 54px 0 12px; padding: 0; font-size: 20px; line-height: 20px">Billing Address</h3>

                    <div class="edit" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; float: left; text-align: center; width: 100%; height: 35px;">

                        <div class="line" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; background: #FFFF00; display: inline-block; height: 2px; position: relative; text-align: center; width: 20px"></div>

                    </div>

                    <p style="margin: 10px 0; padding: 0; font-family: 'jr', sans-serif !important; font-weight: normal !important; text-align: center; color: #3B333D">

                        <?php _e( $attempted_order->name . '<br>' . ucwords( $customer->billing->address_1 ) . '<br>' . ucwords( $customer->billing->address_2 ) . '<br>' . ucwords( $customer->billing->city ) . '<br>' . strtoupper( $customer->billing->postcode ) );?>

                    </p>

                </td>

                <td class="addresses-td" width="50%" valign="top" style="color: #3B333D; font-family: 'jr', sans-serif !important; font-size: 16px; line-height: 1.5em; font-weight: normal !important; padding: 0 !important; text-align: center;">

                    <h3 style="font-family: 'jr', sans-serif !important; letter-spacing: -.5px; font-weight: normal !important; color: #3B333D; text-align: center; margin: 54px 0 12px; padding: 0; font-size: 20px; line-height: 20px">Delivery Address</h3>

                    <div class="edit" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; float: left; text-align: center; width: 100%; height: 35px;">

                        <div class="line" style="font-family: 'jr', sans-serif !important; font-weight: normal !important; background: #FFFF00; display: inline-block; height: 2px; position: relative; text-align: center; width: 20px"></div>

                    </div>

                    <p style="margin: 10px 0; padding: 0; font-family: 'jr', sans-serif !important; font-weight: normal !important; text-align: center; color: #3B333D">

                        <?php _e( $attempted_order->name . '<br>' . ucwords( $customer->shipping->address_1 ) . '<br>' . ucwords( $customer->shipping->address_2 ) . '<br>' . ucwords( $customer->shipping->city ) . '<br>' . strtoupper( $customer->shipping->postcode ) );?></p>

                </td>

            </tr>

        </table>

    </td>

</tr>

<!-- Nav --><!-- / Nav --><!-- Footer -->

<tr style="color:
#3B333D; font-family: Arial, sans-serif; font-size: 16px;
line-height: 1.5em">
<td class="divider-line" align="center" valign="top" style="color: =
#3B333D; font-family: 'jr', sans-serif
!important; font-size: 0; line-height: 1px; font-weight:
normal !important; background: #EEEEEE; height: 1px">
<!-- Divider -->  </td>  </tr>
<tr style="color: #3B333D; font-family: Arial, sans-serif;
font-size: 16px; line-height: 1.5em">
<td width="100%" align="center" style="color: #3B333D;
font-family: 'jr', sans-serif !important; font-size: 16px;
line-height: 1.5em; font-weight: normal !important">
<!-- Footer Text -->  <table class="footer-text-block" align="center" c=
ellpadding="0" cellspacing="0" border="0" width="100%" style="color: #3B333D; font-family:
Arial,sans-serif; font-size: 12px; line-height: 1.5em;
text-align: center"><tr style="color: #3B333D; font-family:
Arial, sans-serif; font-size: 16px; line-height: 1.5em">
<td class="footer-text-block-td" style="color: #3B333D;
font-family: 'jr', sans-serif !important; font-size: 12px;
line-height: 1.5em; font-weight: normal !important; padding: 15px 0 20px;">  <p style="margin: 10px 0; padding: 0;
font-family: 'jr', sans-serif !important; font-weight: normal
!important; text-align: center;">Project Timber &copy; <?php _e(date('Y'));?></p>  </td>
</tr></table>
<!-- / Footer Text --><!-- Footer Image --><!-- / Footer
Image -->
</td>  </tr>
<!-- / Footer -->
</table>
</td>

</tr>

</table>
</body>
</html>
