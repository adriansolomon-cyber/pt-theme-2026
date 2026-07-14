<?php
/**
 * Email Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-addresses.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 8.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';
$address    = $order->get_formatted_billing_address();
$shipping   = $order->get_formatted_shipping_address();

$order_status 	= $order->get_status(); 


if ( ! in_array( $order_status, [ 'pending', 'failed', 'declined-loan', 'cancelled', 'refunded' ] ) ) :

?>
<div style="padding: 0 24px;">
<table id="addresses" cellspacing="0" cellpadding="0" 
    style="width: 100%; vertical-align: top; margin-bottom: 24px; padding:0 2; border: 1px solid #818286; border-radius: 25px;text-align: center; background: rgba(59, 51, 61, 0.05);">
    <tr>
        <td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Work Sans', sans-serif;; border:0; padding:0;"
            valign="top" width="50%">
            <h2 style="
	padding: 10px 10px 8px 40px;
    font-size: 1.3rem;
	margin: 0 0 5px;
    border-bottom: 1px solid #3b333d1a;
"><?php esc_html_e( 'BILLING ADDRESS', 'woocommerce' ); ?></h2>

            <address class="address" style="border: none; padding: 0 40px 12px;">
                <?php echo wp_kses_post( $address ? $address : esc_html__( 'N/A', 'woocommerce' ) ); ?>
                <?php if ( $order->get_billing_phone() ) : ?>
                <br /><?php echo wc_make_phone_clickable( $order->get_billing_phone() ); ?>
                <?php endif; ?>
                <?php if ( $order->get_billing_email() ) : ?>
                <br /><?php echo esc_html( $order->get_billing_email() ); ?>
                <?php endif; ?>
                <?php
				/**
				 * Fires after the core address fields in emails.
				 *
				 * @since 8.6.0
				 *
				 * @param string $type Address type. Either 'billing' or 'shipping'.
				 * @param WC_Order $order Order instance.
				 * @param bool $sent_to_admin If this email is being sent to the admin or not.
				 * @param bool $plain_text If this email is plain text or not.
				 */
				do_action( 'woocommerce_email_customer_address_section', 'billing', $order, $sent_to_admin, false );
				?>
            </address>
        </td>
        <?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && $shipping ) : ?>
        <td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Work Sans', sans-serif; padding:0;"
            valign="top" width="50%">
      <h2 style="
	padding: 10px 10px 8px;
    font-size: 1.3rem;
	margin: 0 0 5px;
    border-bottom: 1px solid #3b333d1a;
"><?php esc_html_e( 'SHIPPING ADDRESS', 'woocommerce' ); ?></h2>
            <address class="address" style="border: none; " >
                <?php echo wp_kses_post( $shipping ); ?>
                <?php if ( $order->get_shipping_phone() ) : ?>
                <br /><?php echo wc_make_phone_clickable( $order->get_shipping_phone() ); ?>
                <?php endif; ?>
                <?php
					/**
					 * Fires after the core address fields in emails.
					 *
					 * @since 8.6.0
					 *
					 * @param string $type Address type. Either 'billing' or 'shipping'.
					 * @param WC_Order $order Order instance.
					 * @param bool $sent_to_admin If this email is being sent to the admin or not.
					 * @param bool $plain_text If this email is plain text or not.
					 */
					do_action( 'woocommerce_email_customer_address_section', 'shipping', $order, $sent_to_admin, false );
					?>
            </address>
        </td>
        <?php endif; ?>
    </tr>
</table>
</div>

<?php
  	global $wpdb; 

	$isMyDen 				= false;
    $isCannes 				= false;
    $isAlpine				= false;  

    $query_items = 'SELECT * FROM wp_woocommerce_order_items WHERE order_id = "'.$order->get_id().'"';
    $meta_items = $wpdb->get_results($query_items, OBJECT);

    $items = $order->get_items();
    $item_name = '';
    foreach ( $items as $item ) {

        $productid = $item['product_id'];

		if(empty($item_name)) {
			$item_name = $item['name'];
		}

        if( strpos( strtolower( $item['name'] ), 'my den' ) !== false ) {  
            $isMyDen = true;
        }

        if( strpos( strtolower( $item['name'] ), 'canne' ) !== false ) {
            $isCannes = true;
        }

        if( strpos( strtolower( $item['name'] ), 'alpine' ) !== false ) {
            $isAlpine = true;
        }

        break;
    }

    $product_id = 0;

    foreach ($meta_items as $indxitem => $item) {

        if($indxitem == 1) {
            $product_size = $item->order_item_name; 
            $order_item_id = $item->order_item_id;

            $order_query_items = "SELECT meta_value AS product_id FROM wp_woocommerce_order_itemmeta WHERE meta_key =  '_product_id' AND order_item_id = " . $order_item_id;
            $order_meta_items = $wpdb->get_results($order_query_items, OBJECT);

            $product_id = $order_meta_items[0]->product_id;

            $pdf = get_field('evolution_pdf_instruction', $product_id);
            //$evo_pdfLink = $pdf[0]['pdf'];
            $evo_pdfLink = $pdf[0]['flipbook'];
		}

        if( strpos( strtolower( $item->order_item_name ), 'upvc' ) !== false ) {
            $upvc_pdf = get_field('evolution_upvc_pdf_instruction', $product_id);
            //$evo_upvc_pdfLink = $upvc_pdf[0]['pdf'];
            $evo_upvc_pdfLink = $upvc_pdf[0]['flipbook'];
			break;
        }
    }

    $size = explode(' ', $product_size);

    $pdfs = get_field('pdf_instruction_gables', $productid);

    $youtube_link_ins = get_field('youtube_link_instruction', $productid);


    foreach($pdfs as $instruction) {
        $idx = $isAlpine ? 0 : 2;
        if($size[$idx] == $instruction['size_gable']) {
        //$pdfLink = $instruction['pdf'];
		$pdfLink = $instruction['flipbook'];
            break;
        }
    }

	$link = '';
	$youtube_link = '';

	if( ( $evo_pdfLink || $pdfLink ) && !$isAlpine ) {
		if($evo_pdfLink) {
			$link =  $evo_pdfLink;
		} else {
			$link = $pdfLink;
		}
	}

	if( $evo_upvc_pdfLink ) {
		$link = $evo_upvc_pdfLink;
	}
	
	if( $isAlpine ) {
		$link = $pdfLink;
	}

	if( $isMyDen ) {
		$youtube_link = 'https://www.youtube.com/watch?v=hZZXTVQy9II';
	}
		
	if( $isCannes ) {
		$youtube_link = 'https://www.youtube.com/watch?v=6ID5Tq0_u30';
	}

	if($youtube_link_ins) {
		$youtube_link = $youtube_link_ins;
	}

?>
<?php $link = false; ?>
<?php if($link): ?>
<!-- ===== ASSEMBLY INSTRUCTIONS CARD ===== -->
<table cellpadding="0" cellspacing="0" border="0" width="100%"
    style="width: 100%; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #3B333D; border-radius: 20px; margin: 24px 0;">
    <tr>
        <td align="center" style="padding: 24px;">

            <p style="margin: 0 0 16px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 700; color: #ffffff; line-height: 24px; letter-spacing: 0.05em; text-align: center;">
                PREFERRED DELIVERY DATE
            </p>

            <!--
                Content-width button: the outer table has no width attribute,
                so it shrinks to fit its content. align="center" on the parent
                <td> centres it horizontally. The inner <td> carries the
                background and border-radius, the <a> provides the padding.
            -->
          <table cellpadding="0" cellspacing="0" border="0"
            style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto; display: inline-table;">
            <tr>
                <td align="center"
                    style="background-color: #ffff00; border-radius: 99px; padding: 0; mso-padding-alt: 0;">
                    <a href="<?php echo esc_url( $link ); ?>"
                        style="display: block; white-space: nowrap; padding: 14px 32px; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 700; color: #3B333D; text-decoration: none; text-align: center; border-radius: 99px; mso-padding-alt: 14px 32px;">
                        Get Assembly Instructions
                    </a>
                </td>
            </tr>
        </table>

        </td>
    </tr>
</table>
<!-- ===== END ASSEMBLY INSTRUCTIONS CARD ===== -->
<?php 
endif;
endif;