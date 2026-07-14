<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

?>
<div class="timber-cat-container">
	<div class="cat_filters_wpr">
		<div class="cat_filters_inner">
			<div class="cat_filters">
				<div class="cat_top_filter">
					<p>Refine your selection.</p>
					<button class="reset_filter">Reset All Filters </button>
				</div>
				<p class="showing_number">Showing 1-10 of 100</p>
				<div class="filters_content">
					<form action="">
						<div class="form-wpr">
							<label for="short">Sort</label>
							<select name="short" id="short">
								<option value="">Please Select</option>
								<option value="">Select-1</option>
								<option value="">Select-2</option>
								<option value="">Select-3</option>
							</select>
							<img src="<?php echo get_template_directory_uri(); ?>/assets/images/angle_dropdown.png" alt="" class="img-angle">
						</div>
						<div class="form-wpr">
							<label for="size_select">Size</label>
							<select name="size_select" id="size_select">
								<option value="">Please Select</option>
								<option value="">Select-1</option>
								<option value="">Select-2</option>
								<option value="">Select-3</option>
							</select>
							<img src="<?php echo get_template_directory_uri(); ?>/assets/images/angle_dropdown.png" alt="" class="img-angle">
						</div>
						<div class="accor_filter">
							<div id="accordion" class="accordion-container">
								<h4 class="accordion-title js-accordion-title">Usage</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="check" type="checkbox" class="myproject--checkbox" checked />
											<label  for="check">Storage <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1" type="checkbox" class="myproject--checkbox"  />
											<label for="check1">Greenhouse <span>(2)</span></label>
										</div>
										<div class="form-control">
											<input id="check2" type="checkbox" class="myproject--checkbox"  />
											<label for="check2">Workshop <span>(3)</span></label>
										</div>
										<div class="form-control">
											<input id="check3" type="checkbox" class="myproject--checkbox"  />
											<label for="check3">Potting Shed <span>(2)</span></label>
										</div>
										<div class="form-control">
											<input id="check4" type="checkbox" class="myproject--checkbox"  />
											<label for="check4">Entertainment <span>(5)</span></label>
										</div>
										<div class="form-control">
											<input id="check5" type="checkbox" class="myproject--checkbox"  />
											<label for="check5">Home Gym <span>(10)</span></label>
										</div>
										<div class="form-control">
											<input id="check6" type="checkbox" class="myproject--checkbox"  />
											<label for="check6">Hobby Room <span>(5)</span></label>
										</div>
										<div class="form-control">
											<input id="check7" type="checkbox" class="myproject--checkbox"  />
											<label for="check7">Garden Office <span>(20)</span></label>
										</div>
									</div>
								</div>
								<h4 class="accordion-title js-accordion-title">Categories</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="checkg" type="checkbox" class="myproject--checkbox" checked />
											<label  for="checkg">Garden Sheds <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1g" type="checkbox" class="myproject--checkbox"  />
											<label for="check1g">Workshops <span>(2)</span></label>
										</div>
										<div class="form-control">
											<input id="check2g" type="checkbox" class="myproject--checkbox"  />
											<label for="check2g">Fast Delivery <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check3g" type="checkbox" class="myproject--checkbox"  />
											<label for="check3g">Storage Units <span>(5)</span></label>
										</div>
									</div>
								</div>
								<h4 class="accordion-title js-accordion-title">Windows</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="checkw" type="checkbox" class="myproject--checkbox" checked />
											<label  for="checkw">Windowed <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1w" type="checkbox" class="myproject--checkbox"  />
											<label for="check1w">Windowless <span>(2)</span></label>
										</div>
									</div>
								</div>
								<h4 class="accordion-title js-accordion-title">Building Style</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="checkb" type="checkbox" class="myproject--checkbox" checked />
											<label  for="checkb">Apex <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1b" type="checkbox" class="myproject--checkbox"  />
											<label for="check1b">Pent <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check2b" type="checkbox" class="myproject--checkbox"  />
											<label for="check2b">Corner <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check3b" type="checkbox" class="myproject--checkbox"  />
											<label for="check3b">Reverse Apex <span>(5)</span></label>
										</div>
										<div class="form-control">
											<input id="check4b" type="checkbox" class="myproject--checkbox"  />
											<label for="check4b">Reverse Pent <span>(5)</span></label>
										</div>
									</div>
								</div>
								<h4 class="accordion-title js-accordion-title">Cladding</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="checks" type="checkbox" class="myproject--checkbox" checked />
											<label  for="checks">Shiplap <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1s" type="checkbox" class="myproject--checkbox"  />
											<label for="check1s">Composite <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check2s" type="checkbox" class="myproject--checkbox"  />
											<label for="check2s">Tongue & Groove <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check3s" type="checkbox" class="myproject--checkbox"  />
											<label for="check3s">Cedar <span>(5)</span></label>
										</div>
									</div>
								</div>
								<h4 class="accordion-title js-accordion-title">Door Style</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="checkd" type="checkbox" class="myproject--checkbox" checked />
											<label  for="checkd">Double Door <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1d" type="checkbox" class="myproject--checkbox"  />
											<label for="check1d">Single Door <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check2d" type="checkbox" class="myproject--checkbox"  />
											<label for="check2d">2 Double Doors <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check3d" type="checkbox" class="myproject--checkbox"  />
											<label for="check3d">Stable Door <span>(5)</span></label>
										</div>
									</div>
								</div>
								<h4 class="accordion-title js-accordion-title">Door Position</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="checkc" type="checkbox" class="myproject--checkbox" checked />
											<label  for="checkc">Central Door <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1c" type="checkbox" class="myproject--checkbox"  />
											<label for="check1c">Offset Door <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check2c" type="checkbox" class="myproject--checkbox"  />
											<label for="check2c">Door in Gable <span>(30)</span></label>
										</div>
									</div>
								</div>
								<h4 class="accordion-title js-accordion-title">Glazing</h4>
								<div class="accordion-content">
									<div class="acc-part">
										<div class="form-control">
											<input id="checkgg" type="checkbox" class="myproject--checkbox" checked />
											<label  for="checkgg">Styrene <span>(25)</span></label>
										</div>
										<div class="form-control">
											<input id="check1gg" type="checkbox" class="myproject--checkbox"  />
											<label for="check1g">Double Glazed <span>(30)</span></label>
										</div>
										<div class="form-control">
											<input id="check2gg" type="checkbox" class="myproject--checkbox"  />
											<label for="check2gg">Toughened Glass <span>(2)</span></label>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="cat_products_wpr">
		<header class="woocommerce-products-header">
			<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
				<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
			<?php endif; ?>

			<?php
			/**
			 * Hook: woocommerce_archive_description.
			 *
			 * @hooked woocommerce_taxonomy_archive_description - 10
			 * @hooked woocommerce_product_archive_description - 10
			 */
			do_action( 'woocommerce_archive_description' );
			?>
		</header>
		   <div class="cat-top-wpr">
			  <div class="shop_by_tittle"><h2>Shop by Range.</h2></div>
			  <div class="shop_by_range_wpr">
					<div class="grand_master">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/images/grand_bg.png" alt="" class="shop_by_bg">
						<div class="grand_master_shot_tittle"><p>Heavy-Duty Workspace</p></div>
						<div class="grand_master_tittle">
							<h3>Grandmaster</h3>
							<p>An extra tall building with 2m walls all round for a fully useable space, all year round.</p>
						</div>
						<div class="grand-img grand_first">
							<img src="<?php echo get_template_directory_uri(); ?>/assets/images/grandmaster.png" alt="">
						</div>
						<div class="price_wpr">
						    <button class="selected_cat">Select</button>
						</div>
					</div>
					<div class="grand_master">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/images/apex-bg.png" alt="" class="shop_by_bg">
						<div class="grand_master_shot_tittle"><p>Heavy-Duty Workspace</p></div>
						<div class="grand_master_tittle">
							<h3>Grandmaster</h3>
							<p>An extra tall building with 2m walls all round for a fully useable space, all year round.</p>
						</div>
						<div class="grand-img">
							<img src="<?php echo get_template_directory_uri(); ?>/assets/images/hobbyist_Tongue.png" alt="">
						</div>
						<div class="price_wpr">
						    <button class="selected_cat selected">Selected</button>
						</div>
					</div>
					<div class="grand_master">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/images/pent_bg.png" alt="" class="shop_by_bg">
						<div class="grand_master_shot_tittle"><p>Heavy-Duty Workspace</p></div>
						<div class="grand_master_tittle">
							<h3>Grandmaster</h3>
							<p>An extra tall building with 2m walls all round for a fully useable space, all year round.</p>
						</div>
						<div class="grand-img">
							<img src="<?php echo get_template_directory_uri(); ?>/assets/images/pent_Tongue.png" alt="">
						</div>
						<div class="price_wpr">
							<!-- <div class="price-strike">
								<div class="from_price">
									<span>From</span>
									<span class="strike-price">£675</span>
								</div>
								<div class="actual_price">
									<span>£540</span>
								</div>
							</div>
							<div class="finance_option">
								<button class="selected_option">Cash</button>
								<button class="">Finance</button>
							</div> -->
						   <button class="selected_cat">Select</button>
						</div>
					</div>
		       </div>
			</div>
			<div class="win-winless"> 
				<div class="windows-filter">
					<h4>Sheds for all your needs.</h4>
				<div class="btn-group">
					<button class="selected-opt">Windowed</button>
				   <button>Windowless</button>
				</div>
			</div>
			</div>
		<?php
		if ( woocommerce_product_loop() ) {

			/**
			 * Hook: woocommerce_before_shop_loop.
			 *
			 * @hooked woocommerce_output_all_notices - 10
			 * @hooked woocommerce_result_count - 20
			 * @hooked woocommerce_catalog_ordering - 30
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();

					/**
					 * Hook: woocommerce_shop_loop.
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();

			/**
			 * Hook: woocommerce_after_shop_loop.
			 *
			 * @hooked woocommerce_pagination - 10
			 */
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			do_action( 'woocommerce_no_products_found' );
		}?>
	</div>
</div>

<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );

/**
* Hook: woocommerce_sidebar.
*
* @hooked woocommerce_get_sidebar - 10
*/
do_action( 'woocommerce_sidebar' );