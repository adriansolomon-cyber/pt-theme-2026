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

$pt_fields = $checkout->get_checkout_fields( 'billing' );

// Contact block = email + every phone field. A second "Phone 2" is added by a
// plugin/mu-plugin under an unknown key (e.g. billing_phone_2), so detect phone
// fields by key prefix rather than hardcoding — billing_phone stays first, any
// additional phone follows and renders inline beside it.
$pt_phone_keys = array();
foreach ( array_keys( $pt_fields ) as $pt_k ) {
	if ( 0 === strpos( (string) $pt_k, 'billing_phone' ) ) {
		$pt_phone_keys[] = $pt_k;
	}
}
$pt_contact_keys = array_merge( array( 'billing_email' ), $pt_phone_keys );
// Klaviyo marketing opt-ins (added to the billing group by the Klaviyo plugin at priority
// 11). We render them in the Contact block to match the design instead of letting them fall
// into "Billing details". Present only when the plugin + its checkout checkboxes are enabled.
$pt_kl_keys = array( 'kl_newsletter_checkbox', 'kl_sms_consent_checkbox' );

$pt_has_contact = isset( $pt_fields['billing_email'] ) || ! empty( $pt_phone_keys );
?>

<div class="woocommerce-billing-fields">

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">

		<?php if ( $pt_has_contact ) : ?>
			<h3 class="pt-contact-title"><?php esc_html_e( 'Contact', 'woocommerce' ); ?></h3>
			<p class="pt-contact-hint"><?php esc_html_e( "We'll use this to send your order confirmation and delivery updates.", 'woocommerce' ); ?></p>
			<?php
			// 1) Email — full width, first.
			if ( isset( $pt_fields['billing_email'] ) ) {
				woocommerce_form_field( 'billing_email', $pt_fields['billing_email'], $checkout->get_value( 'billing_email' ) );
			}
			// 2) Phone + Phone 2 side by side (form-row-first / form-row-last → 48% columns
			//    via checkout.css). A lone phone stays full width.
			$pt_pn = count( $pt_phone_keys );
			foreach ( $pt_phone_keys as $pt_pi => $pt_k ) {
				$pt_field = $pt_fields[ $pt_k ];
				if ( $pt_pn >= 2 ) {
					$pt_field['class'] = array( 0 === $pt_pi ? 'form-row-first' : 'form-row-last' );
					$pt_field['clear'] = ( $pt_pi === $pt_pn - 1 );
				}
				woocommerce_form_field( $pt_k, $pt_field, $checkout->get_value( $pt_k ) );
			}
			// 3) Klaviyo email/SMS opt-in checkboxes, then the SMS consent disclosure below.
			foreach ( $pt_kl_keys as $pt_k ) {
				if ( isset( $pt_fields[ $pt_k ] ) ) {
					woocommerce_form_field( $pt_k, $pt_fields[ $pt_k ], $checkout->get_value( $pt_k ) );
				}
			}
			// The consent disclosure, rendered as the design's .co-consent line: the
			// first sentence stays visible, the rest collapses behind an inline
			// "Read more" (CSS-only checkbox toggle, styled in checkout.css). The plugin's
			// default bottom-of-form placement is removed in functions.php, and its render
			// function only echoes plain text, so we build the markup ourselves. Text comes
			// from the shared Klaviyo disclosure setting (same wording as Klaviyo).
			if ( isset( $pt_fields['kl_sms_consent_checkbox'] ) ) {
				$pt_kl_settings   = function_exists( 'get_option' ) ? get_option( 'klaviyo_settings' ) : array();
				$pt_kl_disclosure = is_array( $pt_kl_settings ) && ! empty( $pt_kl_settings['klaviyo_sms_consent_disclosure_text'] )
					? trim( (string) $pt_kl_settings['klaviyo_sms_consent_disclosure_text'] )
					: '';
				if ( '' !== $pt_kl_disclosure ) :
					$pt_kl_parts = preg_split( '/(?<=\.)\s+/', $pt_kl_disclosure, 2 );
					$pt_kl_lead  = $pt_kl_parts[0];
					$pt_kl_rest  = isset( $pt_kl_parts[1] ) ? $pt_kl_parts[1] : '';
					?>
					<p class="co-consent">
						<?php if ( '' !== $pt_kl_rest ) : ?>
							<input type="checkbox" id="pt-sms-consent-more" class="pt-consent-toggle">
							<?php echo esc_html( $pt_kl_lead ); ?><span class="rest"> <?php echo esc_html( $pt_kl_rest ); ?></span>
							<label class="more" for="pt-sms-consent-more"><span class="m1"><?php esc_html_e( 'Read more', 'woocommerce' ); ?></span><span class="m2"><?php esc_html_e( 'Read less', 'woocommerce' ); ?></span></label>
						<?php else : ?>
							<?php echo esc_html( $pt_kl_lead ); ?>
						<?php endif; ?>
					</p>
					<?php
				endif;
			}
			?>
		<?php endif; ?>

		<h3 class="pt-billing-title"><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h3>
		<?php
		// Render these first, in this exact order, so the "company" checkbox +
		// Company name always sit right after the name fields and ABOVE Country —
		// regardless of field priorities set by WooCommerce or plugins.
		$pt_lead_keys = array( 'billing_first_name', 'billing_last_name', 'billing_is_business', 'billing_company' );
		foreach ( $pt_lead_keys as $pt_k ) {
			if ( isset( $pt_fields[ $pt_k ] ) ) {
				woocommerce_form_field( $pt_k, $pt_fields[ $pt_k ], $checkout->get_value( $pt_k ) );
			}
		}
		foreach ( $pt_fields as $pt_key => $pt_field ) {
			if ( in_array( $pt_key, $pt_contact_keys, true ) || in_array( $pt_key, $pt_kl_keys, true ) || in_array( $pt_key, $pt_lead_keys, true ) ) {
				continue; // rendered in the Contact block above, or in the lead block just above.
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
