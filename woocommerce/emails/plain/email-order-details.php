<?php
/**
 * Order details table shown in emails.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Prior to saving the correct order totals via "save_post" hook, email notification is being sent.
// To override this, we need to force compute the correct order total so that
// email notification will contain correct order totals.
update_order_total_price_update_button_click_event( $order->get_id() );
if ( !metadata_exists( 'post', $order->get_id(), 'is_totals_computed_prior_to_email' ) ) {
    add_post_meta( $order->get_id(), 'is_totals_computed_prior_to_email', 'yes' );
}

$order_total_not_formatted = 0;

$_cart_discount = (float)trim( get_post_meta( $order->get_id(), '_cart_discount', true ) );
$_order_tax = wc_round_tax_total( (string)trim( get_post_meta( $order->get_id(), '_order_tax', true ) ) );

$_order_shipping = (float)trim( get_post_meta( $order->get_id(), '_order_shipping', true ) );

$_order_total = trim( get_post_meta( $order->get_id(), '_order_total', true ) );
$order_total_not_formatted = $_order_total;
$_order_total = wc_price( $_order_total );

$formatted_order_subtotal = ( $order_total_not_formatted + $_cart_discount ) - ( $_order_shipping + (float)$_order_tax );
//$formatted_order_subtotal = ( $order_total_not_formatted / 1.2 ) - $_order_shipping - $_cart_discount;
$formatted_order_subtotal = wc_price( $formatted_order_subtotal );

$currency_html = '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol() . '</span>';

$totals = $order->get_order_item_totals();

$trade_entered_order_number = get_post_meta( $order->get_id(), '_trade_order_reference_num', true );
$order_number = get_post_meta( $order->get_id(), '_order_number_formatted', true );
?>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<p>&nbsp;</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" class="check-item">
	<tr>
		<td class="order_items_table_holder">

			<table cellspacing="0" cellpadding="0" border="0" width="100%">
				<tr>
					<td class="order-table-heading">

						<p>
							<span class="highlight"><?php _e( 'Reference No.', 'email-control' ) ?></span>
							<?php if ( ! $sent_to_admin ) : ?>
								<?php echo $order_number; ?>
							<?php else : ?>
								<a class="link" href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ); ?>"><?php printf( __( '%s', 'woocommerce'), $order_number ); ?></a>
							<?php endif; ?>
						</p>
						<?php
						if( $trade_entered_order_number ) {
							?>
							<p>
								<span class="highlight"><?php _e( 'Customer Order No.', 'email-control' ) ?></span>
								<?php _e( $trade_entered_order_number );?>
							</p>
							<?php
						}
						?>

						<p>
							<span class="highlight"><?php _e( 'Order Date', 'email-control' ) ?></span>
							<time><?php echo date( 'g:iA / jS F Y', strtotime($order->get_date_created()) ); ?></time>
						</p>

						<?php

						$admin_emails = ['new_order', 'cancelled_order', 'failed_order'];
						if( in_array( $email->id, $admin_emails) ) {
							echo'<p><span class="highlight">Order Type</span> ' . trim( get_post_meta( $order->get_id(), '_order_type', true ) ) . '</p>';
						}

						?>

						<p>
						<span class="highlight"><?php _e( 'Delivery Date', 'email-control' ) ?></span>
						<time><?php echo date( 'jS F Y', strtotime(get_post_meta( $order->get_id(), '_from_delivery_date', true )) ); ?></time>
						</p>

					</td>
				</tr>
			</table>

			<table cellspacing="0" cellpadding="0" class="order_items_table" border="0" width="100%" >
				<?php if ( FALSE ) { ?>
					<thead>
						<tr>
							<th scope="col" class="order_items_table_th_style order_items_table_td order_items_table_td_top" width="80%"><?php _e( 'Product', 'email-control' ); ?></th>
							<th scope="col" class="order_items_table_th_style order_items_table_td order_items_table_td_top"><?php _e( 'Quantity', 'email-control' ); ?></th>
							<th scope="col" class="order_items_table_th_style order_items_table_td order_items_table_td_top" style="text-align:right"><?php _e( 'Price', 'email-control' ); ?></th>
						</tr>
					</thead>
				<?php } ?>
				<tbody>
					<?php echo wc_get_email_order_items( $order, array(
						'show_sku'      => $sent_to_admin,
						'show_image'    => FALSE,
						'image_size'    => array( 70, 70 ),
						'plain_text'    => $plain_text,
						'sent_to_admin' => $sent_to_admin
					) ); ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3">

							<table cellspacing="0" cellpadding="0" class="order_items_table" border="0" width="100%">
								<thead>
									<tr>

										<?php

											if ( $totals = $order->get_order_item_totals() ) {
												$totals['order_total']['value'] =  $_order_total;
												$totals['cart_subtotal']['value'] = $formatted_order_subtotal;

												if( $_cart_discount ) {

													$discount_arr = [
														'label' => 'Discount:',
														'value' => wc_price( 0 - $_cart_discount )
													];

													$totals = array_slice( $totals, 0, 1, true) +
												    array( "discount" => $discount_arr ) +
												    array_slice( $totals, 1, count( $totals ) - 1, true) ;
												}

												if( isset( $totals['shipping'] ) ) {
													$totals['shipping']['value'] = wc_price( $_order_shipping );
												}

												$i = 0;
												foreach ( $totals as $key => $total ) {


													if( $key == 'order_total' ) {
														if( ( !isset( $totals['vat'] ) or empty( $totals['vat'] ) )
															|| !isset( $totals['gb-vat-1'] ) or empty( $totals['gb-vat-1'] ) ) {
															?>
															<tr class="order_items_table_total_row order_items_table_total_row_order_tax">
																<th scope="row" class="order_items_table_totals_style order_items_table_td">
																	VAT:
																</th>
																<td class="order_items_table_totals_style order_items_table_td">
																	<?php echo wc_price( $_order_tax ); ?>
																</td>
															</tr>
															<?php
														}
													}

													if( $key == 'payment_method' ) {
														?>
															<tr class="order_items_table_total_row order_items_table_total_row_order_tax">
																<th scope="row" class="order_items_table_totals_style order_items_table_td">
																	Payment method:
																</th>
																<td class="order_items_table_totals_style order_items_table_td">
																	<?php

																	    if( $total['value'] == 'PayPal') {

                															echo 'Via PayPal';

                														} else if($total['value'] == 'Omni Retail Finance'){

                															echo 'Omni Retail Finance';

                														} else if($total['value'] == 'Worldpay'){

                															echo 'Via Worldpay';

                														} else if($total['value'] == 'Bank Transfer'){

                															echo 'Via Bank Transfer';

                														} else if($total['value'] == 'Credit Cards'){

                															echo 'Via Credit Cards';

                														} else if($total['value'] == 'Pay360 (Card Payment or Apple Pay)'){

                															echo 'Via Pay360';
																		
																		} else if($total['value'] == 'Apply for Finance with Deko') {

																			echo 'Apply for Finance with Deko';     

                														} else {

                															echo 'Other';
                														}

																	?>
																</td>
															</tr>
													    <?php
													}

													if( 'Delivery' == trim( $total['value'] ) )
														continue;

													$i++;


													if( $key != 'payment_method' ) {

													?>

    													<tr class="order_items_table_total_row order_items_table_total_row_<?php echo esc_attr( sanitize_title( $total['label'] ) ) ?>" id="<?php echo $key; ?>">
    														<th scope="row" class="order_items_table_totals_style order_items_table_td">
    															<?php echo $total['label']; ?>
    														</th>
    														<td class="order_items_table_totals_style order_items_table_td">
    															<?php echo $total['value']; ?>
    														</td>
    													</tr>

													<?php } ?>

													<?php

													if( $key === 'cart_subtotal' ) {
														if( !isset( $totals['shipping'] ) or empty( $totals['shipping'] ) ) {
															if( $_order_shipping > 0 ) {
																?>
																<tr class="order_items_table_total_row order_items_table_total_row_shipping">
																	<th scope="row" class="order_items_table_totals_style order_items_table_td">
																		Delivery:
																	</th>
																	<td class="order_items_table_totals_style order_items_table_td">
																		<?php echo wc_price( $_order_shipping ); ?>
																	</td>
																</tr>
																<?php
															}
														}

													}

													if( $key == 'order_total' )
														break;
												}
											}
										?>

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

<p>&nbsp;</p>

<?php
ob_start();
do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );
$check_content = ob_get_clean();
if ( '' !== $check_content ) { ?>

	<?php echo $check_content; ?>

	<p>&nbsp;</p>

	<table cellpadding="0" cellspacing="0" border="0" width="100%">
		<tr>
			<td class="divider-line" align="center" valign="top">&nbsp;
				<!-- Divider -->
			</td>
		</tr>
	</table>

	<p>&nbsp;</p>

<?php } ?>