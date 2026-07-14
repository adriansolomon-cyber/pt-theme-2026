<?php
/**
 * Email Addresses
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	<div class="address">
				<?php if ( $order->get_billing_phone() ) : ?>
					<br/>Phone Number : <?php echo wc_make_phone_clickable( $order->get_billing_phone() ); ?>
				<?php endif; ?>
				<?php if ( $order->get_billing_email() ) : ?>
					<br/>Billing Email : <?php echo esc_html( $order->get_billing_email() ); ?>
				<?php endif; ?>
	</div>
<table id="addresses" cellspacing="0" cellpadding="0" align="center" style="width: 100%; vertical-align: top;" border="0">

	<tr>

		<td class="addresses-td" width="50%" valign="top" class="addresses-td">

			<h3><?php _e( "Billing Address", 'email-control' ); ?></h3>

			<div class="edit">

				<div class="line"></div>

			</div>
		

			<p><?php echo $order->get_formatted_billing_address(); ?></p>

		</td>

		<?php 

			$customer_user = get_userdata( $order->get_user_id() );

			if( !empty( array_intersect( $customer_user->roles, TRADE_ACCOUNTS ) ) ) : ?>

				<?php if ( $shipping = $order->get_formatted_shipping_address() ) : ?>

					<td class="addresses-td" width="50%" valign="top" class="addresses-td">

						<h3><?php _e( "Shipping Address", 'email-control' ); ?></h3>

						<div class="edit">

							<div class="line"></div>

						</div>
						
						<p><?php echo $shipping; ?></p>

					</td>

				<?php endif; ?>

		<?php else : ?>

			<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ( $shipping = $order->get_formatted_shipping_address() ) ) : ?>

				<td class="addresses-td" width="50%" valign="top" class="addresses-td">

					<h3><?php _e( "Shipping Address", 'email-control' ); ?></h3>

					<div class="edit">

						<div class="line"></div>

					</div>
					
					<p><?php echo $shipping; ?></p>

				</td>

			<?php endif; ?>

		<?php endif; ?>

	</tr>

</table>