<?php
function get_composite_images_for_slider( $product ) {

	if( !is_object( $product ) )
		return array();

	$components = $product->get_components();

    $images = array();

    foreach ( $components as $component_id => $component ) {
	    if ($component->get_title( true ) === 'Size') {
	    	$component_options = $component->get_options();
	    	break;
	    }
	}

	if( isset( $component_options ) and !empty( $component_options ) ) :

		foreach( $component_options as $option ) {

			$_product = wc_get_product( $option );
			$_attachment_ids = $_product->get_gallery_image_ids();
			$first_image_link = '';
			$last_image_link = '';
			$first_image_id = 0;
			$last_image_id = 0;

			// Main image
			for( $i = 0; $i < count( $_attachment_ids ); $i++ ) {
				$first_image_link = wp_get_attachment_url( $_attachment_ids[$i] );

				if( $first_image_link ) {
					$first_image_id = $_attachment_ids[$i];
					break;
				}
			}

			// Inner image
			for( $i = ( count( $_attachment_ids ) - 1 ); $i > 0; $i-- ) {
				$last_image_link = wp_get_attachment_url( $_attachment_ids[$i] );

				if( $last_image_link ) {
					$last_image_id = $_attachment_ids[$i];
					break;
				}
			}

			$images[$option] =  [
					'main_image' => array(
							'id' 	=> $first_image_id,
							'link'	=> $first_image_link
						),
					'inner_image' => array(
							'id' 	=> $last_image_id,
							'link'	=> $last_image_link
						),
					'caption' => $_product->get_title()
				];
		}

	endif;

	return $images;
}

function display_composite_slider( $product ) {

    $images = get_composite_images_for_slider( $product );

	// Populate images ready for slider
	if( !empty( $images ) ) :
		?>
		<div class="flexslider sizes">
			<ul class="slides">
				<?php
				$ctr = 1;
				foreach ( $images as $component_product_id => $image ) :
					?>
					<li class="slide-item-<?php esc_attr_e( $ctr ); ?>">
	                    <h5><?php _e( $image['caption'] ); ?></h5>
	                    <div class="image exterior" style="background-image: url( <?php esc_attr_e( $image['main_image']['link'] ); ?> );"></div>
	                    <div class="image interior" style="background-image: url( <?php esc_attr_e( $image['inner_image']['link'] ); ?> );"></div>
	                </li>
					<?php

					$ctr++;
				endforeach;
				?>
			</ul>
		</div>

		<div class="sizes-slider-nav">	
			<ul class="flex-control-nav flex-control-paging">
				<?php
				$ctr = 1;
				foreach ( $images as $component_product_id => $image ) :
					?>
					<li class="nav-item-<?php esc_attr_e( $ctr ); ?>" data-ctr="<?php esc_attr_e( $ctr ); ?>">
	                    <a href="#"><?php _e( $image['caption'] ); ?></a>
	                </li>
					<?php

					$ctr++;
				endforeach;
				?>
			</ul>
		</div>

		<?php

	endif;
}
