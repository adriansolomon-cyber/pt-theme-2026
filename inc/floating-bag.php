<?php

global $woocommerce;

?>

    <div class="bag<?php echo ( isset($_SESSION['new_item_added_to_cart']) ? ' active' : '' );?>">

        <div class="container">

            <div class="close">

                <i class="icons8-delete"></i>

            </div>

            <h2>Your Basket</h2>

            <div class="divider one"></div>

            <div class="clear-space"></div>

            <div class="product">

                <?php

                $composite_container_id = 0;

                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ):

                $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

                if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ):

                ?>

                <?php

                if( wc_cp_is_composite_container_cart_item($cart_item) ):

                $composite_container_id = $_product->id;

                ?>

                <div class="composite-container composite-container-<?php _e($composite_container_id); ?>" data-id="<?php _e($_product->id); ?>">

                    <?php
                        $featured_img = '';
                        if( has_term( 'misc', 'product_cat', $_product->id ) ) {
                            $featured_img = wp_get_attachment_image_src( get_post_thumbnail_id( $_product->get_id() ), 'single-post-thumbnail' );
                            ?>
                                <div class="image" style="background: url(<?php _e( esc_attr( $featured_img[0] ) );?>);""></div>
                            <?php
                        } else {
                            ?>
                            <div class="image" style=""></div>
                            <?php
                        }
                    ?>

                    <div class="actions">

                        <div class="edit" style="display: none"> <a href="<?php _e( esc_attr( get_permalink( $_product->id ) ) );?>?edit_cart_item=true&cart_item_key=<?php _e( esc_attr( $cart_item_key ) );?>"> Edit </a> </div>

                        <a href="<?php _e( esc_attr( WC()->cart->get_remove_url( $cart_item_key ) ) ); ?>">

                            <div class="delete">Remove</div>

                        </a>

                    </div>

                    <div class="title">

                        <?php echo ($cart_item['quantity'] > 1 ? $cart_item['quantity'] : '')  . ' ' . apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ); ?>

                    </div>

                    <div class="price">

                        <?php //echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>

                    </div>

                </div>

                <?php else: ?>

                <div class="child-items child-items-<?php _e($composite_container_id); ?>" data-parent="<?php _e( $composite_container_id ); ?>">

                    <?php

					if( has_term( 'bundles', 'product_cat', $_product->id ) ) {
						$productData 	= wc_get_product( $_product->id );

						foreach ( $productData->get_gallery_image_ids() as $attachment_id ) {

							$image_link = wp_get_attachment_url( $attachment_id );
							if ( ! $image_link )
								continue;

							echo '<img src="'.substr($image_link, 0, -4)."-403x227.jpg".'" data-id="'.$_product->get_id().'" class="hidden">';
							break;
						}

					} else {

						$image = wp_get_attachment_image_src( get_post_thumbnail_id( $_product->get_id() ), 'single-post-thumbnail' );

						if( $image[0] <> "" ) {
							?> <img src="<?php  echo $image[0]; ?>" data-id="<?php echo $_product->get_id(); ?>" class="hidden"> <?php
						}
					}

                    ?>

                    <div class="title">

                        <?php

                        ob_start();
                        echo apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key );

                        $component_title = ob_get_contents();
                        ob_end_clean();

                        if( $component_title ) {

                        ?>

                        <dl class="component">
                            <?php echo $component_title; ?>
                            <dd class="component-value"><?php echo $_product->get_title(); ?></dd>
                        </dl>

                        <?php

                        } else {
                            echo $_product->get_title();
                        }

                        ?>

                    </div>

                    <div class="price">

                        <?php //echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>

                    </div>

                </div>

                <?php endif; ?>

                <?php

                endif;

                endforeach;

                ?>

                <div class="clear-space"></div>

            </div>

            <div class="totals">

                <?php

                $total_tax = 0;                
                $total_discount = WC()->cart->get_total_discount();
                $cart_total = WC()->cart->total;

                foreach ( WC()->cart->get_taxes() as $tax ) {
                    $total_tax += $tax;
                }

                if( current_user_is_collector() ) {
                    $trade_discount = 0;
                    $total_weight   = cart_get_total_weight();
                    $trade_discount = $total_weight * 0.2;
                }
                $NDPrice = $woocommerce->cart->cart_contents_total+$woocommerce->cart->tax_total;
                ?>

               <!--<div class="NVtotal-voucher">For 10% discount use code <strong>SPRING10</strong></div>-->

               

                <div class="subtotal">

                    <div class="subtotal-title"><?php _e( 'Subtotal', 'woocommerce' ); ?></div>

                    <div class="subtotal-amount">
                        <?php  //_e( wc_price( $cart_total - $total_tax + $total_discount ) ); ?>
                    </div>

                </div>
               

                <?php if( !empty( WC()->cart->get_coupons() ) ) : ?>

                <div class="cart-discount">

                   <!-- <div class="label">Voucher Discount</div>
                    <div class="amount">-<?php //_e( WC()->cart->get_total_discount() ); ?></div> -->

                </div>

                <?php elseif( current_user_is_collector() ) : ?>

                <?php

                if( $trade_discount <> 0 ) {

                ?>

                <div class="cart-discount">

                    <div class="label">Discount</div>
                    <div class="amount"><?php _e( wc_price( 0 - $trade_discount ) ); ?></div>

                </div>

                <?php

                }

                ?>

                <?php endif; ?>

                <?php

                /*if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

                    <?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

                    <?php wc_cart_totals_shipping_html(); ?></span>

                    <div class="shipping shipping-expected-delivery-date">
                        <div class="label">Shipping</div>
                        <div class="amount"> <?php _e( str_replace( 'from', '', display_expected_delivery_date_html() ) ); ?> </div>
                    </div>

                <?php endif;*/

                ?>

                <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>

                <div class="fee">

                    <div class="label"><?php echo esc_html( $fee->name ); ?></div>
                    <div class="amount"><?php wc_cart_totals_fee_html( $fee ); ?></div>

                </div>

                <?php endforeach; ?>

                <?php if ( wc_tax_enabled() && 'excl' === WC()->cart->tax_display_cart ) : ?>

                    <?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>

                        <?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>

                            <div class="tax-rate tax-rate-<?php echo sanitize_title( $code ); ?>">

                                <div class="label"><?php echo esc_html( $tax->label ); ?></div>
                                <div class="amount"><?php echo wp_kses_post( $tax->formatted_amount ); ?></div>

                            </div>

                        <?php endforeach; ?>
                        <?php else : ?>

                        <div class="tax-total">

                            <div class="label"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></div>
                            <div class="amount"><?php wc_cart_totals_taxes_total_html(); ?></div>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>

                <div class="tax-total">

                    <div class="label"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></div>
                    <div class="amount"><?php $woocommerce->cart->tax_total; ?></div>

                </div>

                <div class="order-total">

                    <div class="label"><?php _e( 'Total', 'woocommerce' ); ?></div>

                    <div class="amount"> <?=  $woocommerce->cart->get_cart_total(); ?> </div>

                </div>
                <div style="clear:both;"></div>

                <div class="order-total">
                    <?php //if( empty( WC()->cart->get_coupons() ) ) { ?>
                       <!-- <div class="label">Voucher Price</div>
                        <div class="amount"><span style="color:red;">£<?php //echo number_format(($NDPrice - ($NDPrice * 0.10)),2); ?></span> </div> -->
                    <?php //} ?>
                </div>

                <a href="<?php echo esc_attr( $woocommerce->cart->get_checkout_url() ); ?>">

                    <div class="button">Proceed To Checkout <i class="icons8-long-arrow-right"></i></div>

                </a>

                <div class="clear-space"></div>

            </div>

            <div class="clear-space"></div>

        </div>

        <div class="overlay"></div>

    </div>

    <?php

    if( isset($_SESSION['new_item_added_to_cart']) ) {
        $_SESSION['new_item_added_to_cart'] = false;
        unset($_SESSION['new_item_added_to_cart']);
    }

    ?>

    <script type="text/javascript">
        jQuery(function($){
            $( '.composite-container' ).each(function(){
                var parent_id = $( this ).data( 'id' );
                var component_size_el = $( '.child-items-' + parent_id );
                if( component_size_el.find( '.component-Size' ).length > 0 ) {
                    var image_url = component_size_el.find( 'img' ).attr( 'src' );
                    $( document ).find( '.composite-container-' + parent_id + ' .image' ).css("background", "url(" + image_url + ")");
                }
            });
        });
    </script>

      <style>
        .NVtotal-voucher .amount {
            float: none;
            font-size: 14px;
            color: red;
        }
        .NVtotal-voucher {
            font-size: 14px;
            font-weight: bold;
        }
    </style>
