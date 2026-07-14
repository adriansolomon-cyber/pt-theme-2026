<?php

remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_excerpt', 'wpautop' );
remove_filter( 'woocommerce_short_description', 'wpautop' );

function introduction_func( $atts, $content = null ) {
	return '<div class="introduction margin">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'introduction', 'introduction_func' );

function introduction_title_func( $atts, $content = null ) {
	return '<h3>'. $content .'</h3>';
}
add_shortcode( 'introduction-title', 'introduction_title_func' );

function introduction_description_func( $atts, $content = null ) {
	return '<h4>'. $content .'</h4>';
}
add_shortcode( 'introduction-description', 'introduction_description_func' );

function image_full_func( $atts, $content = null ) {
	return '<div class="image full" style="background-image: url(\''. $content .'\')"></div>';
}
add_shortcode( 'image-full', 'image_full_func' );

function image_full_url_func( $atts, $content = null ) {
    $a = shortcode_atts( array(
        'url' => ''
    ), $atts );

	return '<div class="image full" style="background-image: url(\'' . $a['url'] . '\')">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'image-full-url', 'image_full_url_func' );

function free_delivery_func( $atts, $content = null ) {
	$delivery_cost = isset( $_COOKIE['delivery-cost'] ) ? (float)trim( $_COOKIE['delivery-cost'] ) : 0;

	return !$delivery_cost ? '
		<div class="free-delivery">

        	<i class="icons8-van-filled"></i>

        	<div class="text"><span>Free delivery</span> on selected postcodes</div>

   		</div>' : '';
}
add_shortcode( 'free-delivery', 'free_delivery_func' );

function anti_rot_warranty_func( $atts, $content = null ) {
	return '
		<div class="anti-rot-warranty">

	        <div class="text"><span class="one">10 Year</span> <span class="two">Anti-Rot Warranty</span></div>

	    </div>';
}
add_shortcode( 'anti-rot-warranty', 'anti_rot_warranty_func' );

function features_func( $atts, $content = null ) {
	return '<div class="features four margin">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'features', 'features_func' );

function column_fourth_func( $atts, $content = null ) {
	return '<div class="column one-fourth">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'column-fourth', 'column_fourth_func' );

function column_fourth_second_func( $atts, $content = null ) {
	return '<div class="column one-fourth second">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'column-fourth-second', 'column_fourth_second_func' );

function column_fourth_last_func( $atts, $content = null ) {
	return '<div class="column one-fourth last">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'column-fourth-last', 'column_fourth_last_func' );

function feature_func( $atts, $content = null ) {
	return '<h5>'. $content .'</h5>';
}
add_shortcode( 'feature', 'feature_func' );

function divider_func( $atts ) {
	return '<div class="divider one"></div><div class="clear-space"></div>';
}
add_shortcode( 'divider', 'divider_func' );

function text_func( $atts, $content = null ) {
	return '<p>'. $content .'</p>';
}
add_shortcode( 'text', 'text_func' );

function title_container_func( $atts, $content = null ) {
	return '<div class="title">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'title-container', 'title_container_func' );

function title_edit_container_func( $atts, $content = null ) {
	return '<div class="title edit">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'title-edit-container', 'title_edit_container_func' );

function title_func( $atts, $content = null ) {
	return '<h2>'. $content .'</h2>';
}
add_shortcode( 'title', 'title_func' );

function description_func( $atts, $content = null ) {
	return '<h4>'. $content .'</h4>';
}
add_shortcode( 'description', 'description_func' );

function image_half_func( $atts, $content = null ) {
	return '<div class="image half" style="background-image: url(\''. $content .'\')"></div>';
}
add_shortcode( 'image-half', 'image_half_func' );

function image_half_last_func( $atts, $content = null ) {
	return '<div class="image half last" style="background-image: url(\''. $content .'\')"></div>';
}
add_shortcode( 'image-half-last', 'image_half_last_func' );

function image_half_url_func( $atts, $content = null ) {
    $a = shortcode_atts( array(
        'url' => ''
    ), $atts );

	return '<div class="image half" style="background-image: url(\'' . $a['url'] . '\')">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'image-half-url', 'image_half_url_func' );

function image_half_last_url_func( $atts, $content = null ) {
    $a = shortcode_atts( array(
        'url' => ''
    ), $atts );

	return '<div class="image half last" style="background-image: url(\'' . $a['url'] . '\')">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'image-half-last-url', 'image_half_last_url_func' );

function flag_func( $atts, $content = null ) {
	return '<div class="flag">

            	<img src="https://www.weareinnotest.com/wp-content/themes/projecttimber/assets/images/flags/united-kingdom.jpg" width="100%">

        	</div>

        	<div class="text">Made in <span>Great Britain</span></div>';
}
add_shortcode( 'flag', 'flag_func' );

function thickness_func( $atts, $content = null ) {
	return '<div class="thickness">' . do_shortcode( $content ) . '<div class="clear-space"></div></div>';
}
add_shortcode( 'thickness', 'thickness_func' );

function framing_func( $atts, $content = null ) {
	return '<div class="framing">' . do_shortcode( $content ) . '<div class="clear-space"></div></div>';
}
add_shortcode( 'framing', 'framing_func' );

function column_half_func( $atts, $content = null ) {
	return '<div class="column one-half">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'column-half', 'column_half_func' );

function column_half_last_func( $atts, $content = null ) {
	return '<div class="column one-half last">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'column-half-last', 'column_half_last_func' );

function thickness_title_func( $atts, $content = null ) {
	return '<h4>' . $content . '</h4>';
}
add_shortcode( 'thickness-title', 'thickness_title_func' );

function framing_title_func( $atts, $content = null ) {
	return '<h4>' . $content . '</h4>';
}
add_shortcode( 'framing-title', 'framing_title_func' );

function thickness_description_func( $atts, $content = null ) {
	return '<h4 class="last">' . $content . '</h4>';
}
add_shortcode( 'thickness-description', 'thickness_description_func' );

function framing_description_func( $atts, $content = null ) {
	return '<h4 class="last">' . $content . '</h4>';
}
add_shortcode( 'framing-description', 'framing_description_func' );

function thickness_img_func( $atts, $content = null ) {
	return '<img class="image" src="' . $content . '" width="100%">';
}
add_shortcode( 'thickness-img', 'thickness_img_func' );

function framing_img_func( $atts, $content = null ) {
	return '<img class="image" src="' . $content . '" width="100%">';
}
add_shortcode( 'framing-img', 'framing_img_func' );

function dimensions_func( $atts, $content = null ) {
	return '<div class="dimensions">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'dimensions', 'dimensions_func' );

function dimensions_premium_label_func( $atts, $content = null ) {
	return '<div class="dimensions premium">'. do_shortcode( $content ) .'<h5>Premium framing</h5></div>';
}
add_shortcode( 'dimensions-premium-label', 'dimensions_premium_label_func' );

function dimensions_premium_func( $atts, $content = null ) {
	return '<div class="dimensions premium">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'dimensions-premium', 'dimensions_premium_func' );

function dimensions_standard_func( $atts, $content = null ) {
	return '<div class="dimensions standard">'. do_shortcode( $content ) .'<h5>Standard framing</h5></div>';
}
add_shortcode( 'dimensions-standard', 'dimensions_standard_func' );

function one_func( $atts, $content = null ) {
	return '<div class="one">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'one', 'one_func' );

function two_func( $atts, $content = null ) {
	return '<div class="two">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'two', 'two_func' );

function width_func( $atts, $content = null ) {
	return '<div class="width">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'width', 'width_func' );

function height_func( $atts, $content = null ) {
	return '<div class="height">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'height', 'height_func' );

function image_full_thin_func( $atts, $content = null ) {
	return '<div class="image full thin" style="background-image: url(\''. $content .'\')"></div>';
}
add_shortcode( 'image-full-thin', 'image_full_thin_func' );

function image_full_last_func( $atts, $content = null ) {
	return '<div class="image full last" style="background-image: url(\''. $content .'\')"></div>';
}
add_shortcode( 'image-full-last', 'image_full_last_func' );

function floor_func( $atts, $content = null ) {
	return '<div class="floor">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'floor', 'floor_func' );

function column_third_func( $atts, $content = null ) {
	return '<div class="column one-third">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'column-third', 'column_third_func' );

function column_third_last_func( $atts, $content = null ) {
	return '<div class="column one-third last">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'column-third-last', 'column_third_last_func' );

function option_img_func( $atts, $content = null ) {
	return '
		<div class="option">

            <img class="image" src="' . $content . '" width="100%">

        </div>

        <div class="divider one"></div>';
}
add_shortcode( 'option-img', 'option_img_func' );

function option_title_func( $atts, $content = null ) {
	return '<h3>' . $content . '</h3>';
}
add_shortcode( 'option-title', 'option_title_func' );

function option_description_func( $atts, $content = null ) {
	return '<h4>' . $content . '</h4>';
}
add_shortcode( 'option-description', 'option_description_func' );

function summary_func( $atts, $content = null ) {
	return '<div class="summary">'. do_shortcode( $content ) .'</div>';
}
add_shortcode( 'summary', 'summary_func' );

function item_func( $atts, $content = null ) {
	return '

		<div class="tick">
			<p>
            	<i class="icons8-checkmark-filled"></i>
			</p>
        </div>

        <div class="item">' . $content . '</div>';
}
add_shortcode( 'item', 'item_func' );

function dimension_item_func( $atts, $content = null ) {
	return '<div class="line"></div>
            <div class="dimension">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'dimension-item', 'dimension_item_func' );

function windows_func( $atts, $content = null ) {
	return '<div class="windows-doors">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'windows', 'windows_func' );

function list_func( $atts, $content = null ) {
	return '<div class="list">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'list', 'list_func' );

function icon_checkmark_func( $atts, $content = null ) {
	return '<div>
				<p>

                	<i class="icons8-checkmark-filled"></i>

				</p>

                <h5>' . $content . '</h5>

            </div>';
}
add_shortcode( 'icon-checkmark', 'icon_checkmark_func' );

function weather_func( $atts, $content = null ) {
	return '<div class="weather-misc">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'weather', 'weather_func' );

function manufacture_left_func( $atts, $content = null ) {
	return '<div class="manufacture-location left">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'manufacture-left', 'manufacture_left_func' );

function manufacture_right_func( $atts, $content = null ) {
	return '<div class="manufacture-location right">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'manufacture-right', 'manufacture_right_func' );

function dynamic_delivery_date_func( $atts, $content = null ) {	
    if($atts){
	    $dates = explode( '-', display_expected_delivery_date_html($atts['product']) );
	    return '<span class="dynamic-delivery-date">' . trim( $dates[0] ) . '</span>';
    }
}
add_shortcode( 'dynamic-delivery-date', 'dynamic_delivery_date_func' );

function delivery_func( $atts, $content = null ) {
	$dates = explode( '-', display_expected_delivery_date_html() );
	return wpautop( '<div class="feature">
		<i class="icons8-van-filled"></i>
		<span>Delivery ' . $dates[0] . '</span>
	</div>', false );
}
add_shortcode( 'delivery', 'delivery_func' );

function sizes_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-expand-filled"></i>
		<span>Multiple sizes available</span>
	</div>', false );
}
add_shortcode( 'sizes', 'sizes_func' );
function valRange_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-best-seller-filled"></i>
		<span>Value Range</span>
	</div>', false );
}
add_shortcode( 'valuerange', 'valRange_func' );

function Fixturesincluded_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-maintenance-filled"></i>
		<span>Fixtures and Fittings included</span>
	</div>', false );
}
add_shortcode( 'FixturesFittings', 'Fixturesincluded_func' );


function extra_tall_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-long-arrow-up-filled"></i>
		<span>Extra tall inside</span>
	</div>', false );
}
add_shortcode( 'extra-tall', 'extra_tall_func' );

function double_doors_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
	    <i class="icons8-columns-filled"></i>
	    <span>Stronger and taller double doors</span>
	</div>', false );
}
add_shortcode( 'double-doors', 'double_doors_func' );

function t_g_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-hammer-filled"></i>
		<span>Easy and strong T&G construction</span>
	</div>', false );
}
add_shortcode( 't-g', 't_g_func' );

function composite_cladding_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-hammer-filled"></i>
		<span>Easy composite cladding construction</span>
	</div>', false );
}
add_shortcode( 'composite-cladding', 'composite_cladding_func' );

function anti_rot_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-diploma-1-filled"></i>
		<span>10 year anti rot guarantee</span>
	</div>', false );
}
add_shortcode( 'anti-rot', 'anti_rot_func' );

function pt_anti_rot_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-diploma-1-filled"></i>
		<span>15 year anti rot guarantee</span>
	</div>', false );
}
add_shortcode( 'pt-anti-rot', 'pt_anti_rot_func' );

function locks_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-key-2-filled"></i>
		<span>Mortice lock and lockable windows</span>
	</div>', false );
}
add_shortcode( 'locks', 'locks_func' );

function cladding_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-split-horizontal-filled"></i>
		<span>16mm cladding upgrade</span>
	</div>', false );
}
add_shortcode( 'cladding', 'cladding_func' );

function opening_windows_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-open-window-filled"></i>
		<span>Fully opening windows</span>
	</div>', false );
}
add_shortcode( 'opening-windows', 'opening_windows_func' );

function shiplap_cladding_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-split-horizontal-filled"></i>
		<span>Our signature shiplap cladding</span>
	</div>', false );
}
add_shortcode( 'shiplap-cladding', 'shiplap_cladding_func' );

function loglap_cladding_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-split-horizontal-filled"></i>
		<span>Our signature loglap cladding</span>
	</div>', false );
}
add_shortcode( 'loglap-cladding', 'loglap_cladding_func' );

function standard_double_doors_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-columns-filled"></i>
		<span>Double doors</span>
	</div>', false );
}
add_shortcode( 'standard-double-doors', 'standard_double_doors_func' );

function french_doors_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-map-filled"></i>
		<span>French doors</span>
	</div>', false );
}
add_shortcode( 'french-doors', 'french_doors_func' );

function stable_door_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-rectangle-filled"></i>
		<span>Boarded or glazed stable door</span>
	</div>', false );
}
add_shortcode( 'stable-door', 'stable_door_func' );

function multi_purpose_interior_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-champagne-filled"></i>
		<span>Multi purpose interior</span>
	</div>', false );
}
add_shortcode( 'multi-purpose-interior', 'multi_purpose_interior_func' );

function roof_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-private-pv2-filled"></i>
		<span>11mm T&G roof upgrade</span>
	</div>', false );
}
add_shortcode( 'roof', 'roof_func' );

function insulation_func( $atts, $content = null ) {
	return wpautop( '<div class="feature">
		<i class="icons8-sun-filled"></i>
		<span>Fully insulated</span>
	</div>', false );
}
add_shortcode( 'insulation', 'insulation_func' );

function shortcode_postcode_checker() {
	return wpautop( '<div class="postcode-checker-form postcode-checker-wrapper">
		<div class="text">Simply enter your postcode to find out when we can deliver to you.</div>
        <form>
            <div class="full">
                <input class="postcode" required>
                <label>Enter Postcode</label>
                <div class="line"></div>
            </div>
            <button class="btn-postcode-checker">
                <i class="icons8-long-arrow-right"></i>
            </button>
        </form>
    </div>', false );
}
add_shortcode( 'postcode-checker', 'shortcode_postcode_checker' );

function shortcode_slider() {

	ob_start();
	?>
	<div class="step one">
		<div class="circle">
			<div class="number">1</div>
			<h4>Size</h4>
		</div>
	</div>

	<div class="sizes-section">

	</div>

	<div class="clear-space"></div>
	<?php
	$output_html = ob_get_contents();
	ob_end_clean();

	return wpautop( $output_html, false );
}
add_shortcode( 'slider', 'shortcode_slider' );
