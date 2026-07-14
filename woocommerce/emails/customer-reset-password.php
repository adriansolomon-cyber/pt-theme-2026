<?php
/**
 * Customer Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<div style="border-radius: 20px; background: rgba(59, 51, 61, 0.05); padding: 24px;">
<div style="display: block; margin: 0 auto; text-align:center; margin-bottom: 8px;">
	<span style="border-radius: 99px;background: rgba(255, 255, 0, 0.90); padding: 4px 24px; font-weight: 600; m ">Reset Your Password</span>	
</div>
<?php /* translators: %s: Customer username */ ?>
<p><?php printf( __( 'Hi <span style="color: #FF0302;">%s</span>,', 'woocommerce' ), esc_html( $user_login ) ); ?></p>
<?php /* translators: %s: Store name */ ?>
<p><?php printf( __( 'Someone has requested a new password for the following account on %s:', 'woocommerce' ), esc_html( $blogname ) ); ?></p>
<?php if ( $email_improvements_enabled ) : ?>
	<div class="hr hr-top"></div>
	<?php /* translators: %s: Username */ ?>
	<p style="font-size: 1.25rem; text-align: center;"><?php echo wp_kses( sprintf( __( 'Username: <b style="color: #FF0302;">%s</b>', 'woocommerce' ), esc_html( $user_login ) ), array( 'b' => array() ) ); ?></p>
	<div class="hr hr-bottom"></div>
	<p><?php esc_html_e( 'If you didn’t make this request, just ignore this email. If you’d like to proceed, reset your password via the link below:', 'woocommerce' ); ?></p>
<?php else : ?>
	<?php /* translators: %s: Customer username */ ?>
	<p style="font-size: 1.25rem; text-align: center;"><?php printf( __( 'Username: <span style="color: #FF0302;">%s</span>', 'woocommerce' ), esc_html( $user_login ) ); ?></p>
	<p><?php esc_html_e( 'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'woocommerce' ); ?></p>
<?php endif; ?>
<p>
	
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>



<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );?>
</div>
	<a href="<?php echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id, 'login' => rawurlencode( $user_login ) ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); ?>" style="display: block; margin-top: 24px;" >
		<img src="https://www.projecttimber.com/wp-content/uploads/2026/06/email-section-reset-password.png" alt="reset-password" width="100%">
	</a>
<?php 
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

do_action( 'woocommerce_email_footer', $email );
?>


