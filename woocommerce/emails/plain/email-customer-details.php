<?php
/**
 * Additional Customer Details
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h3><?php _e( "Personal Details", 'email-control' ); ?></h3>

<div class="edit">

	<div class="line"></div>

</div>

<?php foreach ( $fields as $field ) : ?>

	<span class="text"><?php echo wp_kses_post( $field['value'] ); ?></span>

<?php endforeach; ?>
