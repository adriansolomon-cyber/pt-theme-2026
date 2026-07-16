<?php
/**
 * Checkout billing form — Project Timber 2026 redesign.
 *
 * Overrides woocommerce/checkout/form-billing.php. Splits the billing group into the
 * mockup's two sections (projecttimber-checkout.html): a "Contact" block (email +
 * phone) FIRST, then "Billing details" (name + address). WooCommerce keeps email /
 * phone as billing_* fields, so they remain in the billing group functionally — this
 * only changes WHERE they render. All hooks, the field loop, and the guest-registration
 * account fields are preserved.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

$pt_fields       = $checkout->get_checkout_fields( 'billing' );
$pt_contact_keys = array( 'billing_email', 'billing_phone' );

$pt_has_contact = false;
foreach ( $pt_contact_keys as $pt_k ) {
	if ( isset( $pt_fields[ $pt_k ] ) ) {
		$pt_has_contact = true;
		break;
	}
}
?>

<div class="woocommerce-billing-fields">

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">

		<?php if ( $pt_has_contact ) : ?>
			<h3 class="pt-contact-title"><?php esc_html_e( 'Contact', 'woocommerce' ); ?></h3>
			<p class="pt-contact-hint"><?php esc_html_e( "We'll use this to send your order confirmation and delivery updates.", 'woocommerce' ); ?></p>
			<?php
			foreach ( $pt_contact_keys as $pt_k ) {
				if ( isset( $pt_fields[ $pt_k ] ) ) {
					woocommerce_form_field( $pt_k, $pt_fields[ $pt_k ], $checkout->get_value( $pt_k ) );
				}
			}
			?>
		<?php endif; ?>

		<h3 class="pt-billing-title"><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h3>
		<?php
		foreach ( $pt_fields as $pt_key => $pt_field ) {
			if ( in_array( $pt_key, $pt_contact_keys, true ) ) {
				continue; // rendered in the Contact block above.
			}
			woocommerce_form_field( $pt_key, $pt_field, $checkout->get_value( $pt_key ) );
		}
		?>

	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>
			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>
		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $pt_akey => $pt_afield ) : ?>
					<?php woocommerce_form_field( $pt_akey, $pt_afield, $checkout->get_value( $pt_akey ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>
