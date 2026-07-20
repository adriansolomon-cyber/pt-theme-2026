<?php
/**
 * Legacy back-office logic — migrated VERBATIM from theTimber/functions.php.
 *
 * Kept at the theme ROOT so __DIR__-relative includes (e.g. __DIR__ . "/inc/…-email.php")
 * resolve exactly as they did in the old theme. Contains everything from the old
 * functions.php EXCEPT: (a) the frontend-setup block — old asset enqueues, duplicate
 * theme-supports, the nav-menu markup filter and widget sidebars (superseded by our
 * theme); and (b) the custom order statuses (already in inc/order-statuses.php).
 *
 * TODO (stage-2, once verified on staging): refactor into concern files under inc/.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Load custom WooCommerce email classes early
function load_custom_wc_emails( $email_classes ) {
    require_once get_stylesheet_directory() . '/includes/classes/class-wc-customer-cancelled-order-email.php';
    $email_classes['WC_Customer_Cancelled_Order_Email'] = new WC_Customer_Cancelled_Order_Email();
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'load_custom_wc_emails', 5 );

// Send customer cancellation email on order status change
add_action( 'woocommerce_order_status_changed', 'send_customer_cancellation_email', 10, 3 );
function send_customer_cancellation_email( $order_id, $old_status, $new_status ) {
    if ( $new_status !== 'cancelled' ) return;

    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $recipient = $order->get_billing_email();
    if ( ! $recipient ) return;

    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();

    if ( isset( $emails['WC_Customer_Cancelled_Order_Email'] ) ) {
        $emails['WC_Customer_Cancelled_Order_Email']->trigger( $order_id, $old_status, $new_status );
    }
}
function mailchimp($subscriber, $listId)
{
    $apikey = defined( 'PT_MAILCHIMP_API_KEY' ) ? PT_MAILCHIMP_API_KEY : '';
    $auth = base64_encode('user:' . $apikey);

    $data = array(
        'apikey'        => $apikey,
        'email_address' => $subscriber['email'],
        'status'        => 'subscribed',
        'merge_fields'  => array(
            'FNAME' => $subscriber['FNAME'],
            'LNAME' => $subscriber['LNAME']
        )
    );

    $json_data = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://us16.api.mailchimp.com/3.0/lists/' . $listId . '/members/');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic ' . $auth));
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

    $result = curl_exec($ch);
}

function sw_cp_display_total_string($frontend_params)
{
    $frontend_params['i18n_price_format'] = sprintf(_x('%1$s%2$s%3$s', '"Total" string followed by price followed by price suffix', 'woocommerce-composite-products'), '%t', '%p', '%s');
    return $frontend_params;
}

add_action('woocommerce_checkout_after_customer_details', 'order_summary_on_checkout');
function order_summary_on_checkout()
{
    // echo 'Content you want to place';
    //include get_template_directory() . '/woocommerce-multistep-checkout/order-summary.php';
}

remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);

add_action("wp_ajax_get_slider_images", "get_slider_images");
add_action("wp_ajax_nopriv_get_slider_images", "get_slider_images");
function get_slider_images()
{

    global $product;
    $product = new WC_product($_POST['size_id']);
    $attachment_ids = $product->get_gallery_image_ids($_POST['size_id']);
    $sliderimages = '';

    foreach ($attachment_ids as $attachment_id) {

        $sliderimages .= "<div>" . wp_get_attachment_image($attachment_id, 'full') . "</div>";
    }
    $jsondata['images'] = $sliderimages;
    $json = json_encode($jsondata);
    echo ($json);
    die();
}
add_action('acf/init', 'my_acf_op_init');
function my_acf_op_init()
{

    // Check function exists.
    if (function_exists('acf_add_options_page')) {

        // Add parent.
        $parent = acf_add_options_page(array(
            'page_title'  => __('Theme General Settings'),
            'menu_title'  => __('Theme Settings'),
            'redirect'    => false,
        ));

        // // Add sub page.
        // $child = acf_add_options_page(array(
        //     'page_title'  => __('Social Settings'),
        //     'menu_title'  => __('Social'),
        //     'parent_slug' => $parent['menu_slug'],
        // ));
    }
}

function custom_payment_divs()
{
    include get_template_directory() . '/woocommerce-multistep-checkout/custom_payment_divs.php';
}

//add_action('woocommerce_checkout_before_terms_and_conditions', 'custom_payment_divs');


function pagination_overide_composite_products($query)
{

    // Not a query for an admin page.
    // It's the main query for a front end page of your site.

    if (is_product()) {
        // It's the main query for a category archive.

        // Let's change the query for category archives.
        $query->set('posts_per_page', 100);
    }
}
add_action('pre_get_posts', 'pagination_overide_composite_products');

remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);

add_action('woocommerce_archive_description', 'wc_category_description');
function wc_category_description()
{
    if (is_product_category()) {
        global $wp_query;
        $cat_id = $wp_query->get_queried_object_id();
        $cat_desc = term_description($cat_id, 'product_cat');
        echo "<div class='short_description'>";
        echo wp_trim_words($cat_desc, 10, '...');
        echo "<p class='show_more'>Show More</p></div>";
        echo "<div class='long_description' style='display:none;'>";
        echo $cat_desc;
        echo "<p class='show_less'>Show Less</p></div>";
    }
}

// function my_custom_archive_description( $description ) {
//     if ( is_product_category() ) {
//         $description = 'This is my custom category description.';
//     } elseif ( is_shop() ) {
//         $description = 'This is my custom shop description.';
//     }
//     return $description;
// }
// add_filter( 'woocommerce_archive_description', 'my_custom_archive_description' ); 

function woocommerce_single_tabs()
{

    global $product, $components, $composite;
    include get_template_directory() . '/includes/woocommerce-single-tabs.php';
}

add_action('woocommerce_singlepage_tabs', 'woocommerce_single_tabs');


function get_pip_product_name($order_ids, $show_size_component = true)
{
    $pip_product_names = array();
    $ctr = 0;

    foreach ($order_ids as $order_id) :
        $order = wc_get_order($order_id);
        $items = $order->get_items();

        foreach ($items as $item) {

            // Check if the item is parent composite
            $item_is_parent            = false;
            $item_is_composite_parent = false;
            $item_is_parts                = false;
            $item_is_component            = false;
            $component_title            = "";

            foreach ($item->get_meta_data() as $meta_data) {

                // composite parent
                if ($meta_data->key == '_composite_children' and !empty($meta_data->value)) {
                    $item_is_parent = true;
                    $item_is_composite_parent = true;
                    break;

                    // child parts
                } elseif (
                    $meta_data->key == '_parent_composite_order_item_id'
                    and trim($meta_data->value) <> ""
                ) {
                    $item_is_parts = true;
                    break;

                    // child component
                } elseif (
                    $meta_data->key == '_composite_parent'
                    and trim($meta_data->value) <> ""
                ) {
                    $item_is_component = true;
                    break;

                    // parts under child component of parent composite
                } elseif (
                    $meta_data->key == '_bundled_item_hidden'
                    and trim($meta_data->value) == "yes"
                ) {
                    $item_is_parts = true;
                    break;
                } else {
                    $item_is_parent = true;
                }
            }

            // Get the product
            $product = $item->get_product($item->get_id());

            // Do not include composite parent
            if (!empty($product)) {
                if ($product->get_type() == 'composite') {
                    $pip_product_names[$ctr] = '<a href="' . esc_attr(get_post_permalink($product->get_id())) . '"><span></span>' . $product->get_name() . '</a>';
                }
            } elseif ($item_is_component) {
                $component_title = trim(get_component_title_by_order_item($item->get_id(), $item));
                if ($component_title == 'Size' and $show_size_component) {
                    $pip_product_names[$ctr] = str_replace('<span></span>', '<span>' . $product->get_name() . '</span>&nbsp;', $pip_product_names[$ctr]);
                    $ctr++;
                }
            } else {
                break;
            }
        }
    endforeach;

    $str_pip_product_names = "";
    foreach ($pip_product_names as $pip_product_name) {
        $str_pip_product_names .= $pip_product_name . '<br>';
    }

    return $str_pip_product_names;
}

add_filter('wc_pip_show_print_dialog', function () {
    return false;
});

function get_component_title_by_order_item($item_id, $item)
{

    $title = '';
    $product = $item->get_product();

    if (wc_cp_maybe_is_composited_cart_item($item)) {
        foreach ($item->get_meta_data() as $key => $value) {
            if ($value->key == '_composite_data') {
                foreach ($value->value as $k) {
                    if ($k['product_id'] == $product->get_id()) {
                        $title = $k['title'];
                        break;
                    }
                }
                break;
            }
        }
    }

    if ($title == "") {

        $composite = wc_cp_get_composited_order_item_container($item, $item->get_order());


        if (!$composite) return;

        $composite = wc_get_product($composite->get_product_id());

        if ($composite->get_type() == 'composite') {

            $components = $composite->get_components();
            foreach ($components as $component_id => $component) {
                if (in_array($product->get_id(), $component->get_options())) {
                    $title = $component->get_title(true);
                    break;
                }
            }
        }
    }

    return $title;
}

function convertCmtoFt($cm)
{
    $cm  = (float) $cm;
    $var = 0.0328084;
    $ft  = $cm * $var;
    if (strpos($ft, '.') !== false) {
        $number = explode('.', $ft);
        $display = $number[0] . '\' ';

        if ($number[1][0] > 1) {
            $display .= $number[1][0] . '"';
        } else {
            $display .= $number[1][0] . '"';
        }

        return $display;
    }

    return $ft . ' ft';
}

function get_overall_dimensions()
{

    $overallWidth               = get_post_meta($_POST['id'], '_specs_overall_width', true);
    $overallDepth               = get_post_meta($_POST['id'], '_specs_overall_depth', true);
    $overalTotalWallThickness   = get_post_meta($_POST['id'], '_specs_total_wall_thickness', true);
    $widthInternal              = get_post_meta($_POST['id'], '_specs_width_internal', true);
    $depthInternal              = get_post_meta($_POST['id'], '_specs_depth_internal', true);

    if ($_POST['measurement'] === 'metric') {
        $measurement = '<span class="measurements cm">cm</span>';
        $overallWidth .= $measurement;
        $overallDepth .= $measurement;
        $overalTotalWallThickness .= $measurement;
        $widthInternal .= $measurement;
        $depthInternal .= $measurement;
    } else {
        $overallWidth = convertCmtoFt($overallWidth);
        $overallDepth = convertCmtoFt($overallDepth);
        $overalTotalWallThickness = convertCmtoFt($overalTotalWallThickness);
        $widthInternal = convertCmtoFt($widthInternal);
        $depthInternal = convertCmtoFt($depthInternal);
    }
?>
<div class="title">Overall Dimensions</div>
<div class="divider one"></div>
<div class="clear-space"></div>

<!-- <div class="left">
            <div class="text">Overall Width</div>
            <div class="value"><?= $overallWidth ?></div>

            <div class="text">Overall Depth</div>
            <div class="value"><?= $overallDepth ?></div>

            <?php if (! empty($overalTotalWallThickness)) { ?>
            <div class="clear-space"></div>

            <div class="text">Total Wall Thickness (including Insulation)</div>
            <div class="value"><?= $overalTotalWallThickness ?></div>
            <?php } ?>
        </div>

        <div class="right">
            <div class="text">Width <span>Internal</span></div>
            <div class="value"><?= $widthInternal ?></div>

            <div class="text">Depth <span>Internal</span></div>
            <div class="value"><?= $depthInternal ?></div>
        </div> -->
<table class="specs-table">
    <tr>
        <th>Measurement</th>
        <th>Size</th>
    </tr>
    <tr>
        <td>
            <div class="text">Overall Width</div>
        </td>
        <td>
            <div class="value"><?= $overallWidth ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Overall Depth</div>
        </td>
        <td>
            <div class="value"><?= $overallDepth ?></div>
        </td>
    </tr>
    <?php if (! empty($overalTotalWallThickness)) { ?>
    <tr>
        <td>
            <div class="text">Total Wall Thickness <span>(including Insulation)</span></div>
        </td>
        <td>
            <div class="value"><?= $overalTotalWallThickness ?></div>
        </td>
    </tr>
    <?php } ?>
    <tr>
        <td>
            <div class="text">Width <span>(Internal)</span></div>
        </td>
        <td>
            <div class="value"><?= $widthInternal ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Depth <span>(Internal)</span></div>
        </td>
        <td>
            <div class="value"><?= $depthInternal ?></div>
        </td>
    </tr>
</table>
<?php
    wp_die();
}
add_action('wp_ajax_get_overall_dimensions', 'get_overall_dimensions');
add_action('wp_ajax_nopriv_get_overall_dimensions', 'get_overall_dimensions');

function get_eaves_ridge()
{

    $eavesHeightInc = get_post_meta($_POST['id'], '_specs_eaves_height_inc_floor', true);
    $ridgeHeightInc = get_post_meta($_POST['id'], '_specs_ridge_height_inc_floor', true);
    $eavesHeightInt = get_post_meta($_POST['id'], '_specs_eaves_height_internal', true);
    $eavesHeightExc = get_post_meta($_POST['id'], '_specs_eaves_height_excl_floor', true);
    $ridgeHeightExc = get_post_meta($_POST['id'], '_specs_ridge_height_excl_floor', true);
    $ridgeHeightInt = get_post_meta($_POST['id'], '_specs_ridge_height_internal', true);

    if ($_POST['measurement'] === 'metric') {
        $measurement = '<span class="measurements cm">cm</span>';
        if (is_numeric($eavesHeightInc)) {
            $eavesHeightInc .= $measurement;
        }

        if (is_numeric($ridgeHeightInc)) {
            $ridgeHeightInc .= $measurement;
        }

        if (is_numeric($eavesHeightInt)) {
            $eavesHeightInt .= $measurement;
        }

        if (is_numeric($eavesHeightExc)) {
            $eavesHeightExc .= $measurement;
        }

        if (is_numeric($ridgeHeightExc)) {
            $ridgeHeightExc .= $measurement;
        }

        if (is_numeric($ridgeHeightInt)) {
            $ridgeHeightInt .= $measurement;
        }
    } else {
        $eavesHeightInc = convertCmtoFt($eavesHeightInc);
        $ridgeHeightInc = convertCmtoFt($ridgeHeightInc);
        $eavesHeightInt = convertCmtoFt($eavesHeightInt);
        $eavesHeightExc = convertCmtoFt($eavesHeightExc);
        $ridgeHeightExc = convertCmtoFt($ridgeHeightExc);
        $ridgeHeightInt = convertCmtoFt($ridgeHeightInt);
    }
?>
<div class="title">Eaves & Ridge</div>
<div class="divider one"></div>
<div class="clear-space"></div>

<!-- <div class="left">
            <div class="text">Eaves Height <span>Inc. Floor</span></div>
            <div class="value"><?= $eavesHeightInc ?></div>

            <div class="clear-space"></div>

            <div class="text">Eaves Height <span>Excl. Floor</span></div>
            <div class="value"><?= $eavesHeightExc ?></div>
        </div>

        <div class="center">
            <div class="text">Ridge Height <span>Inc. Floor</span></div>
            <div class="value"><?= $ridgeHeightInc ?></div>

            <div class="clear-space"></div>

            <div class="text">Ridge Height <span>Excl. Floor</span></div>
            <div class="value"><?= $ridgeHeightExc ?></div>
        </div>

        <div class="right">
            <div class="text">Eaves Height <span>Internal</span></div>
            <div class="value"><?= $eavesHeightInt ?></div>

            <div class="clear-space"></div>

            <div class="text">Ridge Height <span>Internal</span></div>
            <div class="value"><?= $ridgeHeightInt ?></div>
        </div> -->
<table class="specs-table">
    <tr>
        <th>Measurement</th>
        <th>Size</th>
    </tr>
    <tr>
        <td>
            <div class="text">Eaves Height <span>(Inc. Floor)</span></div>
        </td>
        <td>
            <div class="value"><?= $eavesHeightInc ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Eaves Height <span>(Excl. Floor)</span></div>
        </td>
        <td>
            <div class="value"><?= $eavesHeightExc ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Ridge Height <span>(Inc. Floor)</span></div>
        </td>
        <td>
            <div class="value"><?= $ridgeHeightInc ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Ridge Height <span>(Excl. Floor)</span></div>
        </td>
        <td>
            <div class="value"><?= $ridgeHeightExc ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Eaves Height <span>(Internal)</span></div>
        </td>
        <td>
            <div class="value"><?= $eavesHeightInt ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Ridge Height <span>(Internal)</span></div>
        </td>
        <td>
            <div class="value"><?= $ridgeHeightInt ?></div>
        </td>
    </tr>
</table>
<?php
    wp_die();
}
add_action('wp_ajax_get_eaves_ridge', 'get_eaves_ridge');
add_action('wp_ajax_nopriv_get_eaves_ridge', 'get_eaves_ridge');

function get_doors()
{

    $doorHeight = get_post_meta($_POST['id'], '_specs_door_height', true);
    $doorOpeningSize = get_post_meta($_POST['id'], '_specs_door_opening_size_w_x_h', true);
    $doorWidth = get_post_meta($_POST['id'], '_specs_door_width', true);

    if ($_POST['measurement'] === 'metric') {
        $measurement = '<span class="measurements cm">cm</span>';
        if (is_numeric($doorHeight)) {
            $doorHeight .= $measurement;
        }

        if (is_numeric($doorOpeningSize)) {
            $doorOpeningSize .= $measurement;
        } else {
            if (strpos($doorOpeningSize, ' x ') !== false) {
                $doorOpeningSize .= $measurement;
            }
        }

        if (is_numeric($doorWidth)) {
            $doorWidth .= $measurement;
        } else {
            if (strpos($doorWidth, ' x ') !== false) {
                $doorWidth .= $measurement;
            }
        }
    } else {
        $doorHeight = convertCmtoFt($doorHeight);
        $doorWidth = convertCmtoFt($doorWidth);

        if (strpos($doorOpeningSize, ' x ') !== false) {
            $doorOpeningSizes = explode(' x ', $doorOpeningSize);

            $height = convertCmtoFt(str_replace('H', '', $doorOpeningSizes[0]));
            $width = convertCmtoFt(str_replace('W', '', $doorOpeningSizes[1]));

            $doorOpeningSize = $height . ' x ' . $width;
        }
    }
?>
<div class="title">Doors</div>
<div class="divider one"></div>
<div class="clear-space"></div>

<!-- <div class="left">
            <div class="text">Door Height</div>
            <div class="value"><?= $doorHeight ?></div>

            <div class="text">Door Width</div>
            <div class="value"><?= $doorWidth ?></div>
        </div>

        <div class="right">
            <div class="text">Door Opening Size <span>H x W</span></div>
            <div class="value"><?= $doorOpeningSize ?></div>
        </div> -->
<table class="specs-table">
    <tr>
        <th>Measurement</th>
        <th>Size</th>
    </tr>
    <tr>
        <td>
            <div class="text">Door Height</div>
        </td>
        <td>
            <div class="value"><?= $doorHeight ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Door Width</div>
        </td>
        <td>
            <div class="value"><?= $doorWidth ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Door Opening Size <span>(H x W)</span></div>
        </td>
        <td>
            <div class="value"><?= $doorOpeningSize ?></div>
        </td>
    </tr>
</table>
<?php
    wp_die();
}
add_action('wp_ajax_show_video_product', 'get_video_product');
add_action('wp_ajax_nopriv_show_video_product', 'get_video_product');

function get_video_product()
{

    echo $_POST['video'];

    wp_die();
}

add_action('wp_ajax_get_doors', 'get_doors');
add_action('wp_ajax_nopriv_get_doors', 'get_doors');

function get_windows()
{

    $windowDimensions = get_post_meta($_POST['id'], '_specs_window_dimensions_w_x_h', true);
    $glazingThickness = get_post_meta($_POST['id'], '_specs_glazing_thickness', true);
    $frameThickness = get_post_meta($_POST['id'], '_specs_frame_thickness_h_x_w', true);

    if ($_POST['measurement'] === 'metric') {
        $measurement = '<span class="measurements cm">cm</span>';
        if (is_numeric($windowDimensions)) {
            $windowDimensions .= $measurement;
        } else {
            if (strpos($windowDimensions, ' x ') !== false) {
                $windowDimensions .= $measurement;
            }
        }

        if (is_numeric($glazingThickness)) {
            $glazingThickness .= $measurement;
        }

        if (is_numeric($frameThickness)) {
            $frameThickness .= $measurement;
        } else {
            if (strpos($frameThickness, ' x ') !== false) {
                $frameThickness .= $measurement;
            }
        }
    } else {

        if (strpos($windowDimensions, ' x ') !== false) {
            $windowDimensionss = explode(' x ', $windowDimensions);
            $height = convertCmtoFt($windowDimensionss[0]);
            $width = convertCmtoFt($windowDimensionss[1]);

            $windowDimensions = $height . ' x ' . $width;
        }

        if (is_numeric($glazingThickness)) {
            $glazingThickness = convertCmtoFt($glazingThickness);
        }

        if (strpos($frameThickness, ' x ') !== false) {
            $thickness = explode(' x ', $frameThickness);
            $height = convertCmtoFt(str_replace('H', '', $thickness[0]));
            $width = convertCmtoFt(str_replace('H', '', $thickness[1]));

            $frameThickness = $height . ' x ' . $width;
        }
    }
?>
<div class="title">Windows</div>
<div class="divider one"></div>
<div class="clear-space"></div>

<!-- <div class="left">
            <div class="text">Window Dimensions <span>W x H</span></div>
            <div class="value"><?= $windowDimensions ?></div>
        </div>

        <div class="right">
            <div class="text">Glazing Thickness</div>
            <div class="value"><?= $glazingThickness ?></div>

            <div class="text">Frame Thickness <span>H x W</span></div>
            <div class="value"><?= $frameThickness ?></div>
        </div> -->
<table class="specs-table">
    <tr>
        <th>Measurement</th>
        <th>Size</th>
    </tr>
    <tr>
        <td>
            <div class="text">Window Dimensions <span>(W x H)</span></div>
        </td>
        <td>
            <div class="value"><?= $windowDimensions ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Glazing Thickness</div>
        </td>
        <td>
            <div class="value"><?= $glazingThickness ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Frame Thickness <span>(H x W)</span></div>
        </td>
        <td>
            <div class="value"><?= $frameThickness ?></div>
        </td>
    </tr>
</table>
<?php
    wp_die();
}
add_action('wp_ajax_get_windows', 'get_windows');
add_action('wp_ajax_nopriv_get_windows', 'get_windows');

function get_floor_base()
{

    $floorSize = get_post_meta($_POST['id'], '_specs_overall_floor_size_w_x_d', true);
    $baseSize = get_post_meta($_POST['id'], '_specs_base_size_w_x_d', true);

    if ($_POST['measurement'] === 'metric') {
        $measurement = '<span class="measurements cm">cm</span>';
        if (is_numeric($floorSize)) {
            $floorSize .= $measurement;
        } else {
            if (strpos($floorSize, ' x ') !== false) {
                $floorSize .= $measurement;
            }
        }

        if (is_numeric($baseSize)) {
            $baseSize .= $measurement;
        } else {
            if (strpos($baseSize, ' x ') !== false) {
                $baseSize .= $measurement;
            }
        }
    } else {
        //$floorSize = convertCmtoFt($floorSize);

        if (strpos($floorSize, ' x ') !== false) {
            $floorSizes = explode(' x ', $floorSize);
            $height = convertCmtoFt(str_replace('H', '', $floorSizes[0]));
            $width = convertCmtoFt(str_replace('H', '', $floorSizes[1]));

            $floorSize = $height . ' x ' . $width;
        }

        if (strpos($baseSize, ' x ') !== false) {
            $baseSizes = explode(' x ', $baseSize);
            $height = convertCmtoFt(str_replace('H', '', $baseSizes[0]));
            $width = convertCmtoFt(str_replace('H', '', $baseSizes[1]));

            $baseSize = $height . ' x ' . $width;
        }
    }
?>
<div class="title">Floor & Base</div>
<div class="divider one"></div>
<div class="clear-space"></div>

<!-- <div class="left">
            <div class="text">Overall Floor Size <span>W x D</span></div>
            <div class="value"><?= $floorSize ?></div>
        </div>

        <div class="right">
            <div class="text">Base Size</div>
            <div class="value"><?= $baseSize ?></div>
        </div> -->
<table class="specs-table">
    <tr>
        <th>Measurement</th>
        <th>Size</th>
    </tr>
    <tr>
        <td>
            <div class="text">Overall Floor Size <span>(W x D)</span></div>
        </td>
        <td>
            <div class="value"><?= $floorSize ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Base Size</div>
        </td>
        <td>
            <div class="value"><?= $baseSize ?></div>
        </td>
    </tr>
</table>
<?php
    wp_die();
}
add_action('wp_ajax_get_floor_base', 'get_floor_base');
add_action('wp_ajax_nopriv_get_floor_base', 'get_floor_base');

function get_materials()
{

    $floorMaterial        = get_post_meta($_POST['id'], '_specs_floor_material', true);
    $material             = get_post_meta($_POST['id'], '_specs_material', true);
    $roofMaterial         = get_post_meta($_POST['id'], '_specs_roof_material', true);
    $roofCoveringMaterial = get_post_meta($_POST['id'], '_specs_roof_covering_material', true);
    $glazingMaterial      = get_post_meta($_POST['id'], '_specs_glazing_material', true);

?>
<div class="title">Materials</div>
<div class="divider one"></div>
<div class="clear-space"></div>

<!-- <div class="full">
            <div class="text">Floor Material</div>
            <div class="value"><?= $floorMaterial ?></div>

            <div class="clear-space"></div>

            <div class="text">Material</div>
            <div class="value"><?= $material ?></div>

            <div class="clear-space"></div>

            <div class="text">Roof Material</div>
            <div class="value"><?= $roofMaterial ?></div>

            <div class="clear-space"></div>

            <div class="text">Roof Covering Material</div>
            <div class="value"><?= $roofCoveringMaterial ?></div>

            <div class="clear-space"></div>

            <div class="text">Glazing Material</div>
            <div class="value"><?= $glazingMaterial ?></div>
        </div> -->
<table class="specs-table">
    <tr>
        <td>
        </td>
        <td>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Floor Material</div>
        </td>
        <td>
            <div class="value"><?= $floorMaterial ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Material</div>
        </td>
        <td>
            <div class="value"><?= $material ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Roof Material</div>
        </td>
        <td>
            <div class="value"><?= $roofMaterial ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Roof Covering Material</div>
        </td>
        <td>
            <div class="value"><?= $roofCoveringMaterial ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Glazing Material</div>
        </td>
        <td>
            <div class="value"><?= $glazingMaterial ?></div>
        </td>
    </tr>
</table>
<?php
    wp_die();
}
add_action('wp_ajax_get_materials', 'get_materials');
add_action('wp_ajax_nopriv_get_materials', 'get_materials');

function get_features()
{

    $windows            = get_post_meta($_POST['id'], '_specs_windows', true);
    $shedType           = get_post_meta($_POST['id'], '_specs_shed_type', true);
    $billyOhRange       = get_post_meta($_POST['id'], '_specs_billyOh_range', true);
    $roofStyle          = get_post_meta($_POST['id'], '_specs_roof_style', true);
    $fixtures           = get_post_meta($_POST['id'], '_specs_supplied_with_fixtures_and_fittings', true);
    $claddingThickness  = get_post_meta($_POST['id'], '_specs_cladding_thickness', true);
    $claddingStyle      = get_post_meta($_POST['id'], '_specs_cladding_style', true);
    $lockingSystem      = get_post_meta($_POST['id'], '_specs_locking_system', true);
    $interWindows       = get_post_meta($_POST['id'], '_specs_interchangeable_windows', true);
    $basecoatTreatment  = get_post_meta($_POST['id'], '_specs_factory_basecoat_treatment', true);
    $sidePanels         = get_post_meta($_POST['id'], '_specs_pre_assembled_side_panels', true);
    $metalRoof          = get_post_meta($_POST['id'], '_u_values_of_metal_roof', true);
    $wallsandfloor      = get_post_meta($_POST['id'], '_u_values_of_walls_and_floor', true);
?>
<div class="title">Features</div>
<div class="divider one"></div>
<div class="clear-space"></div>

<!-- <div class="left">
            <div class="text">Windows</div>
            <div class="value"><?= $windows ?></div>

            <div class="clear-space"></div>

            <div class="text">Shed Type</div>
            <div class="value"><?= $shedType ?></div>

            <div class="clear-space"></div>

            <div class="text">Roof Style</div>
            <div class="value"><?= $roofStyle ?></div>

            <div class="clear-space"></div>

            <div class="text">Fixtures & Fittings</div>
            <div class="value"><?= $fixtures ?></div>

            <div class="clear-space"></div>

            <div class="text">Cladding Thickness</div>
            <div class="value"><?= $claddingThickness ?></div>

            <?php if (! empty($metalRoof)) { ?>
            <div class="clear-space"></div>

            <div class="text">U-Values of Metal Roof</div>
            <div class="value"><?= $metalRoof ?></div>
            <?php } ?>
        </div>

        <div class="right">
            <div class="text">Cladding Style</div>
            <div class="value"><?= $claddingStyle ?></div>

            <div class="clear-space"></div>

            <div class="text">Locking System</div>
            <div class="value"><?= $lockingSystem ?></div>

            <div class="clear-space"></div>

            <div class="text">Interchangeable Windows</div>
            <div class="value"><?= $interWindows ?></div>

            <div class="clear-space"></div>

            <div class="text">Basecoat Treatment</div>
            <div class="value"><?= $basecoatTreatment ?></div>

            <div class="clear-space"></div>

            <div class="text">Pre-Assembled Side Panels</div>
            <div class="value"><?= $sidePanels ?></div>

            <?php if (! empty($wallsandfloor)) { ?>
            <div class="clear-space"></div>

            <div class="text">U-Values of Walls and Floor</div>
            <div class="value"><?= $wallsandfloor ?></div>
            <?php } ?>
        </div> -->
<table class="specs-table">
    <tr>
        <td>
        </td>
        <td>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Windows</div>
        </td>
        <td>
            <div class="value"><?= $windows ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Shed Type</div>
        </td>
        <td>
            <div class="value"><?= $shedType ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Roof Style</div>
        </td>
        <td>
            <div class="value"><?= $roofStyle ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Fixtures & Fittings</div>
        </td>
        <td>
            <div class="value"><?= $fixtures ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Cladding Thickness</div>
        </td>
        <td>
            <div class="value"><?= $claddingThickness ?></div>
        </td>
    </tr>
    <?php if (! empty($metalRoof)) { ?>
    <tr>
        <td>
            <div class="text">U-Values of Metal Roof</div>
        </td>
        <td>
            <div class="value"><?= $metalRoof ?></div>
        </td>
    </tr>
    <?php } ?>

    <tr>
        <td>
            <div class="text">Cladding Style</div>
        </td>
        <td>
            <div class="value"><?= $claddingStyle ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Locking System</div>
        </td>
        <td>
            <div class="value"><?= $lockingSystem ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Interchangeable Windows</div>
        </td>
        <td>
            <div class="value"><?= $interWindows ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Basecoat Treatment</div>
        </td>
        <td>
            <div class="value"><?= $basecoatTreatment ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="text">Pre-Assembled Side Panels</div>
        </td>
        <td>
            <div class="value"><?= $sidePanels ?></div>
        </td>
    </tr>
    <?php if (! empty($wallsandfloor)) { ?>
    <tr>
        <td>
            <div class="text">U-Values of Walls and Floor</div>
        </td>
        <td>
            <div class="value"><?= $wallsandfloor ?></div>
        </td>
    </tr>
    <?php } ?>
</table>
<?php
    wp_die();
}
add_action('wp_ajax_get_features', 'get_features');
add_action('wp_ajax_nopriv_get_features', 'get_features');

// my admin custom css start with projtb-admin
add_action('admin_head', 'projecttimber_admin_style');
function projecttimber_admin_style()
{
    wp_enqueue_style('timber-resolution', get_template_directory_uri() . '/assets/css/admin.css', array(), time(), 'resolution');
}

add_action('woocommerce_admin_order_data_after_order_details', 'fn_add_order_type_after_order_details');
function fn_add_order_type_after_order_details()
{
    $order_id = $_GET['post'];
    $order_type = get_post_meta($order_id, '_order_type', true);
?>

<p class="form-field form-field-wide wc-order-type">
    <label for="_order_type">Order Type:</label>
    <select class="wc-order-type" id="order_type" name="_order_type"
        data-placeholder="<?php esc_attr_e('Order Type', 'woocommerce'); ?>" data-allow_clear="true"
        data-val="<?php esc_attr_e($order_type, 'woocommerce'); ?>">
        <option></option>
        <option <?php if ($order_type == 'Website') {
                        echo "selected";
                    } ?> value="Website">Website</option>
        <option <?php if ($order_type == 'Phone') {
                        echo "selected";
                    } ?> value="Phone">Phone</option>
        <option <?php if ($order_type == 'eBay [Consumer]') {
                        echo "selected";
                    } ?> value="eBay [Consumer]">eBay [Consumer]</option>
        <option <?php if ($order_type == 'eBay [Phone]') {
                        echo "selected";
                    } ?> value="eBay [Phone]">eBay [Phone]</option>
        <option <?php if ($order_type == 'Amazon') {
                        echo "selected";
                    } ?> value="Amazon">Amazon</option>
        <!-- <option value="Amazon [Phone]">Amazon [Phone]</option>
            <option value="Google [Phone]">Google [Phone]</option> -->
        <option <?php if ($order_type == 'Trade [Delivery]') {
                        echo "selected";
                    } ?> value="Trade [Delivery]">Trade [Delivery]</option>
        <option <?php if ($order_type == 'Trade [Collection]') {
                        echo "selected";
                    } ?> value="Trade [Collection]">Trade [Collection]</option>
    </select>
</p>

<?php
}

// extra fields for the per order
add_action('woocommerce_admin_order_data_after_shipping_address', 'fn_add_delivery_date_shipping_address');
function fn_add_delivery_date_shipping_address($order)
{
    $order_id = trim($_GET['post']);
    $order_type = trim(get_post_meta($order_id, '_order_type', true));
    $allowed_order_type = array('eBay [Consumer]', 'eBay [Trade]');

    $order = wc_get_order($order_id);


    if ($_GET['action'] == 'edit') :

        $order_status  = $order->get_status();

    ?>

<p class="form-field _final_delivery_date_field">

    <?php if (isset($_GET['ex']) && $_GET['ex'] == 1) : ?>

<ul id="list-product">
    <?php
                foreach ($order->get_items() as $item_id => $item) {
                    $composite_id = $item->get_product_id();
                    $product      = $item->get_product();
                    $weightKG = (get_post_meta($composite_id, '_weight', true) ? get_post_meta($composite_id, '_weight', true) : 0);

                    if ($product->product_type == 'composite') {
                        echo "<li>Weight - <b> " . ($weightKG *  $item->get_quantity()) . "</b>; <a href='" . get_site_url() . "/wp-admin/post.php?post=" . $composite_id . "&action=edit'>" . $item->get_name() . "</a> ==|||== " . $product->product_type . "</li>";
                    } else if ($product->product_type == 'bundle') {
                        echo "<li>Weight - <b>" . ($weightKG *  $item->get_quantity()) . "</b>; <a href='" . get_site_url() . "/wp-admin/post.php?post=" . $composite_id . "&action=edit'>" . $item->get_name() . "</a> " . $product->product_type . "</li>";
                    } else {
                        echo "<li> Weight " . ($weightKG *  $item->get_quantity()) . "; <a href='" . get_site_url() . "/wp-admin/post.php?post=" . $composite_id . "&action=edit'>" . $item->get_name() . "</a> </li>";
                    }
                }
            ?>

</ul>

<?php endif;

            $extra_option = '';
            $delivery_option = '';

            foreach ($order->get_items() as $item_id_ => $item_) {
                $composite_id = $item_->get_product_id();
                $product      = $item_->get_product();
                $weightKG = (get_post_meta($composite_id, '_weight', true) ? get_post_meta($composite_id, '_weight', true) : 0);

                $kg[] =  $weightKG *  $item_->get_quantity();

                if ($product->product_type == 'composite') {
                    $extra_option .= $item_->get_name() . " ||| ";
                } else if ($product->product_type == 'bundle') {
                    $extra_option .= $item_->get_name() . ", ";
                }

                if ($product->product_type == 'bundle') {

                    $productMatch_size = preg_match('/[0-9]{1,2} x [0-9]{1,2}/', $item_->get_name(), $productURL_size);

                    if ($productMatch_size) {

                        $delivery_type = get_field('delivery', $item_->get_product_id());

                        if ($delivery_type) :
                            $delivery_option .= $productURL_size[0] . " = " . $delivery_type . "<br/>";
                        endif;
                    }
                }
            }

    ?>

<?php

        similar_text(get_post_meta($order_id, '_pip_extras_or_option', true), $extra_option, $percent);

        if ($percent > 50) {
            $extra_options_text = get_post_meta($order_id, '_pip_extras_or_option', true);
        } else {
            $extra_options_text = get_post_meta($order_id, '_pip_extras_or_option', true) . " - " . $extra_option;
        }

    ?>

</p>

<?php endif; ?>


<p class="form-field _expected_delivery_date_field">
    <label for="_expected_date"><strong>Expected Delivery Date:</strong></label>
    <input type="text" class="short date-picker _delivery_date-field" placeholder="From" name="_from_delivery_date"
        id="_from_delivery_date" value="<?php echo get_post_meta($order_id, '_from_delivery_date', true); ?>">
</p>

<p class="_to_delivery_date-wrapper" style="display: none;">
    <span class="to-hyphen"> - </span>
    <input type="text" class="short date-picker _delivery_date-field" placeholder="To" name="_to_delivery_date"
        id="_to_delivery_date" value="<?php echo get_post_meta($order_id, '_to_delivery_date', true); ?>">
</p>

<p class="form-field _final_delivery_date_field">
    <label for="_delivery_date"><strong>Final Delivery Date:</strong></label>
    <input type="text" class="short date-picker _delivery_date-field" style="" name="_final_delivery_date"
        id="_final_delivery_date" placeholder=""
        value="<?php echo get_post_meta($order_id, '_final_delivery_date', true); ?>">
</p>
<p class="form-field _dispatch_date_field">
    <label for="_dispatch_date"><strong>Dispatch Date:</strong></label>
    <input type="text" class="short date-picker _delivery_date-field" placeholder="Dispatch Date" name="_dispatch_date"
        id="_dispatch_date" value="<?php echo get_post_meta($order_id, '_dispatch_date', true); ?>">
</p>
<p class="form-field full-width _pip_driver_name_field">
    <label for="_previous_delivery_date"><strong>Previous delivery date:</strong></label>
    <input type="text" class="short" style="" name="_previous_delivery_date" id="_previous_delivery_date" placeholder=""
        value="<?php echo get_post_meta($order_id, '_previous_delivery_date', true); ?>" readonly>
</p>

<p class="form-field full-width _pip_driver_name_field">
    <label for="_delivery_update_counter"><strong>Delivery update counter:</strong></label>
    <input type="text" class="short" style="" name="_delivery_update_counter" id="_delivery_update_counter" placeholder=""
        value="<?php echo get_post_meta($order_id, '_delivery_update_counter', true); ?>" readonly>
</p>
<p class="form-field full-width _pip_driver_name_field">
    <label for="_delivery_update_notes"><strong>Delivery notes:</strong></label>
    <textarea class="short" name="_delivery_update_notes" id="_delivery_update_notes" placeholder="" readonly><?php echo esc_textarea(get_post_meta($order_id, '_delivery_update_notes', true)); ?></textarea>
</p>
<p class="form-field full-width _pip_driver_name_field">
    <label for="_pip_driver_name"><strong>Delivery method:</strong></label>
    <input type="text" class="short" style="" name="_delivery_method" id="_delivery_method" placeholder=""
        value="<?php echo get_post_meta($order_id, '_delivery_method', true); ?>" readonly>
</p>
<p class="form-field full-width _pip_driver_name_field">
    <label for="_pip_driver_name"><strong>Driver Name ( Only for Optimo):</strong></label>
    <input type="text" class="short" style="" name="_pip_driver_name" id="_pip_driver_name" placeholder=""
        value="<?php echo get_post_meta($order_id, '_pip_driver_name', true); ?>" readonly>
</p>
<p class="form-field full-width _pip_load_code_field">
    <label for="_pip_load_code"><strong> Load Code:</strong></label>
    <input type="text" class="short" style="" name="_pip_load_code" id="_pip_load_code" placeholder=""
        value="<?php echo get_post_meta($order_id, '_pip_load_code', true); ?>">
</p>

<p class="form-field full-width _pip_drop_field">
    <label for="_pip_drop"><strong>Drop:</strong></label>
    <input type="text" class="short" style="" name="_pip_drop" id="_pip_drop" placeholder=""
        value="<?php echo get_post_meta($order_id, '_pip_drop', true); ?>">
</p>

<p class="form-field full-width _pip_extras_or_option_field">
    <label for="_pip_extras_or_option"><strong>Extras/Options:</strong></label>
    <textarea class="short" style="height: 120px;" name="_pip_extras_or_option" id="_pip_extras_or_option"
        placeholder="" cols="40" rows="5" form="post"><?php echo $extra_options_text; ?></textarea>
</p>

<?php if (in_array($order_type, $allowed_order_type)) : ?>
<?php
        $_delivery_status = trim(get_post_meta($order_id, '_delivery_status', true));

        if (!$_delivery_status or $_delivery_status == 'Unpaid') {
            $_order_shipping = (float)trim(get_post_meta($order_id, '_order_shipping', true));
            if ($_order_shipping > 0)
                $_delivery_status = 'Unpaid';
            else
                $_delivery_status = '';
        }
    ?>
<p class="form-field form-field-wide wc-delivery-status">
    <label for="_delivery_status"><strong>Delivery Status:</strong></label>
    <select id="_delivery_status" name="_delivery_status"
        data-placeholder="<?php esc_attr_e('Delivery status', 'woocommerce'); ?>" data-allow_clear="true"
        data-val="<?php esc_attr_e($_delivery_status, 'woocommerce'); ?>">
        <option value="" <?php esc_attr_e($_delivery_status == '' ? ' selected' : ''); ?>></option>
        <option value="Unpaid" <?php esc_attr_e($_delivery_status == 'Unpaid' ? ' selected' : ''); ?>>Unpaid</option>
        <option value="Paid" <?php esc_attr_e($_delivery_status == 'Paid' ? ' selected' : ''); ?>>Paid</option>
    </select>
</p>
<?php endif; ?>

<p class="form-field _dispatched_delivery_date_field" style="display: none;">
<div class="_delivery_date" style="display: none;"><strong>Dispatched Date:</strong></div>
&nbsp; <input type="text" style="display: none;" class="short date-picker _dispatched_delivery_date_field-field"
    placeholder="From" name="_dispatched_delivery_date_field" id="_dispatched_delivery_date_field"
    value="<?php echo get_post_meta($order_id, '_dispatched_delivery_date_field', true); ?>">
</p>

<p class="form-field full-width _dispatched_consignment_field">
    <label for="_dispatched_consignment_field"><strong>Dispatched Consignment:</strong></label>
    <input type="text" class="short" style="" name="_dispatched_consignment_field" id="_dispatched_consignment_field"
        placeholder="" value="<?php echo get_post_meta($order_id, '_dispatched_consignment_field', true); ?>">
</p>

<p class="form-field delivery_option">
    <label for="_delivery_date"><strong>Delivery Type:</strong></label>
    <?php echo $delivery_option; ?>
</p>

<?php
    if ($_GET['action'] == 'edit') :
        if (
            $order_status != 'pending'
            && $order_status != 'declined-loan'
            && $order_status != 'on-hold'
            && $order_status != 'refunded'
            && $order_status != 'cancelled'
            && $order_status != 'failed'
        ) :
?>
<p class="form-field _prices_excluding_delivery"> <a
        href="https://projecttimbercom.kinsta.cloud/order-dear.php?orderid=<?php echo $order_id; ?>&userid=<?php echo get_current_user_id(); ?>">Push
        and Delete Order on Dear System </a> </p>
<p class="form-field _prices_excluding_delivery"> <b>Total Weight:</b> <?php echo order_get_total_weight($order_id); ?>
</p>
<?php endif;
    endif; ?>

<input type="hidden" name="orderMetaChanges" class="orderMetaChanges" value="" />

<?php
}
function search_order_id($orderid)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.optimoroute.com/v1/get_orders?key=' . ( defined( 'PT_OPTIMO_API_KEY' ) ? PT_OPTIMO_API_KEY : '' ) . '&orderNo=' . $orderid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response);
}

add_action('woocommerce_process_shop_order_meta', 'my_custom_checkout_field_update_order_meta', 99);
function my_custom_checkout_field_update_order_meta($order_id)
{

    global $current_user;
    wp_get_current_user();
    $order = new WC_Order($order_id);
    
    // Track changes to the final delivery date.
    // Compare the incoming POST value against the currently stored value
    // BEFORE the generic loop below overwrites it.
    $old_final_delivery_date = get_post_meta($order_id, '_final_delivery_date', true);
    $new_final_delivery_date = isset($_POST['_final_delivery_date']) ? sanitize_text_field($_POST['_final_delivery_date']) : '';

    if ($new_final_delivery_date !== '' && $new_final_delivery_date !== $old_final_delivery_date) {

        // Store the value it changed from.
        update_post_meta($order_id, '_previous_delivery_date', $old_final_delivery_date);

        // Increment the change counter.
        $counter = (int) get_post_meta($order_id, '_delivery_update_counter', true);
        update_post_meta($order_id, '_delivery_update_counter', $counter + 1);

        // Prepend a note describing the change so the newest is on top,
        // separated from previous entries.
        $existing_notes = get_post_meta($order_id, '_delivery_update_notes', true);
        $new_note       = 'Final delivery date changed to: ' . $new_final_delivery_date;
        $updated_notes  = $existing_notes ? $new_note . "\n----------\n" . $existing_notes : $new_note;
        update_post_meta($order_id, '_delivery_update_notes', $updated_notes);
    }
    $meta_keys = array(
        '_order_type',
        '_ebay_username',
        '_ebay_email_address',
        '_from_delivery_date',
        '_to_delivery_date',
        '_final_delivery_date',
        '_dispatch_date',
        '_delivery_method',
        '_pip_load_code',
        '_pip_drop',
        '_pip_driver_name',
        '_pip_extras_or_option',
        '_dispatched_delivery_date_field',
        '_dispatched_consignment_field',
        '_delivery_status'
    );

    foreach ($meta_keys as $meta_key) {
        if (isset($_POST[$meta_key])) {
            update_post_meta($order_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$meta_key])));
        }
    }
    $meta_keys_checkboxes = array(
        '_dispatched_on_ebay',
        '_messaged_on_ebay',
        'parent-can-be-zero',
        '_prices_excluding_delivery',
        '_order_source'
    );

    foreach ($meta_keys_checkboxes as $meta_key) {
        update_post_meta($order_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
    }

    // if (
    //     $_POST['order_status'] == 'wc-processing' && get_field('send_to_palletforce', $order_id) == 'Yes' ||
    //     $_POST['order_status'] == 'wc-planned' && get_field('send_to_palletforce', $order_id) == 'Yes' ||
    //     $_POST['order_status'] == 'wc-unplanned' && get_field('send_to_palletforce', $order_id) == 'Yes' ||
    //     $_POST['order_status'] == 'wc-transfer-to-nexus' && get_field('send_to_palletforce', $order_id) == 'Yes'
    // ) {

    //     //         $orderOptimoStatus = search_order_id($order->get_order_number());

    //     //         if ($orderOptimoStatus->success == 1) {
    //     //             $order->add_order_note('Order not added to Nexus because it already exists in OptimoRoute.', 0, false);
    //     //             return;
    //     //         }

    //     $response = pt_nexus_pallet_api_process($order_id, $order);

    //     if (!empty($response->consignmentID)) {

    //         $order->update_status('wc-palletways');
    //     }

    //     $to         = "szegedi.szilard@projecttimber.com,laurence@projecttimber.co.uk,jamie.croft@projecttimber.co.uk,jordan.reed-poulson@projecttimber.co.uk";
    //     $headers    = array('Content-Type: text/html; charset=UTF-8');
    //     $headers[]  = 'From: Project Timber <sales@projecttimber.com>';
    //     $headers[]  = 'Reply-To: <sales@projecttimber.com>';

    //     if ($response->consignmentID) {

    //         update_post_meta($order_id, 'consignment_tracking_code', $response->successfulTrackingCodes[0]);

    //         $order->add_order_note('Nexus pallex successfully push (Order ID:' . $order->get_order_number() . ' Consignment ID:' . $response->consignmentID . ')', 0, false);
    //     } else {
    //         $body = '';
    //         foreach ($response->errors as $error) {
    //             $error_detail = $error;
    //             $body .= '<br/>' . $error;
    //         }

    //         $order->add_order_note('Nexus pallex error for #' . $order->get_order_number() . ' - ' . $error_detail[0], 0, false);
    //         // 			$to         = "szegedi.szilard@projecttimber.co.uk";
    //         //         	$subject    = "Nexus error:";

    //         //         	$headers    = array('Content-Type: text/html; charset=UTF-8');
    //         //         	$headers[]  = 'From: Project Timber <sales@projecttimber.com>';
    //         //         	$headers[]  = 'Reply-To: <sales@projecttimber.com>';

    //         //         	wp_mail($to, $subject, $body, $headers);
    //     }
    // }
    // End Pick A Date

    if (is_user_logged_in() && empty($_POST['order_email_template']) && $current_user->ID !== 112 && (strpos($order->get_order_number(), 'PT') !== false || strpos($order->get_order_number(), 'RDM'))) {
        $order->add_order_note(sprintf(__('<a href="' . add_query_arg('user_id', $current_user->ID, self_admin_url('user-edit.php')) . '" target="_blank">%s</a> updated the order.', 'woocommerce'), ucwords($current_user->display_name)), false, true);

        $to         = "szegedi.szilard@projecttimber.co.uk";
        $subject    = ucwords($current_user->display_name) . " updated the order for " . $order->get_order_number();
        $body       = "Username: " . $current_user->user_login . "<br/>Shop Order Link: " . admin_url("post.php?post=" . absint($order_id)) . "&action=edit" . (!empty($_POST['orderMetaChanges']) ? "<br/>Fields Changed: " . $_POST['orderMetaChanges'] : "") . "<br/>IP Address: " . get_client_ip();
        $headers    = array('Content-Type: text/html; charset=UTF-8');
        $headers[]  = 'From: Project Timber <sales@projecttimber.com>';
        $headers[]  = 'Reply-To: <sales@projecttimber.com>';

        wp_mail($to, $subject, $body, $headers);
    }
}

// function pt_nexus_pallet_api_process($order_id, $order)
// {
//     global $wpdb;
//     $consignment_number = 1;

//     if (FALSE === get_option('consignment_number') && FALSE === update_option('consignment_number', FALSE)) {
//         add_option('consignment_number', $consignment_number);
//     } else {
//         $consignment_number = get_option('consignment_number');
//         update_option('consignment_number', $consignment_number++);
//     }

//     $collection_date = get_field('collection_date', $order_id);
//     $delivery_due_date = get_field('delivery_due_date', $order_id);
//     $pallet_type = get_field('pallet', $order_id);
//     $pservices = get_field('pservices', $order_id);

//     $consignment_date = get_post_meta($order_id, '_final_delivery_date', true);

//     $service = get_field('pallet_code', $order_id);
//     $service_code = explode(",", $service);

//     $pallet_numbers  = get_field('billing_units', $order_id);
//     $pallet_number = explode(",", $pallet_numbers);

//     $status = get_field('pallet_status', $order_id);


//     foreach ($order->get_items() as $item_id_ => $item_) {
//         $product_name = $item_->get_name();
//         $composite_id = $item_->get_product_id();

//         $kgWeight = (get_post_meta($composite_id, '_weight', true) ? get_post_meta($composite_id, '_weight', true) : 0);

//         $kg[] = $kgWeight *  $item_->get_quantity();
//         $qty = $item_->get_quantity();
//     }

//     $data = $wpdb->get_results("
//         SELECT * FROM wp_nexus_token
//     ")[0];

//     if ($pservices == 3) {
//         $dduedate = ',"dueDate": "' . $delivery_due_date . '"';
//     } else {
//         $dduedate = '';
//     }

//     // Constructing the "pallets" array
//     $pallets = [];
//     foreach ($pallet_type as $index => $item) {

//         if ($item["pallet_type"] == 7) {
//             $Plength = '120';
//             $Pwidth = '120';
//             $Pheight = '220';
//         } else if ($item["pallet_type"] == 6) {
//             $Plength = '150';
//             $Pwidth = '120';
//             $Pheight = '220';
//         }

//         $pallets[] = [
//             "palletBaseID" => 1,
//             "palletTypeID" => (int) $item["pallet_type"],
//             "length" => (int) $Plength,
//             "width" => (int) $Pwidth,
//             "height" => (int) $Pheight,
//             "weight" => round(array_sum($kg)),
//             "limitedQuantityWeight" => round(array_sum($kg)),
//             "doNotStack" => true
//         ];
//     }

//     $palletsdata = json_encode($pallets, JSON_UNESCAPED_SLASHES);

//     $body = '{
//                 "general": {
//                     "consignmentTypeID": 2,
//                     "customerID": "64086",
//                     "customerReference": "' . $order->get_order_number() . '",   
//                     "consignmentNumber": "' . $order->get_order_number() . '",             
//                     "descriptionOfGoods": "' . $product_name . '",
//                     "limitedQuantities": true 
//                 },
//                 "collectionInfo": {
//                     "serviceID": 1,
//                     "contact": "01777593079",
//                     "email": "karen.kirby@projecttimber.co.uk",
//                     "dueDate": "' . $collection_date . '"         
//                 },
//                 "deliveryInfo": {
//                     "serviceID": ' . $pservices . ',
//                     "contact": "' . $order->get_billing_phone() . '",
//                     "email": "' . $order->get_billing_email() . '"         
// 					' . $dduedate . '					                                   
//                 },  
//                 "deliveryAddress": {
//                     "countryID": 233,
//                     "name": "' . $order->get_shipping_first_name() . '",
//                     "line1": "' . $order->get_shipping_address_1() . ' ' . $order->get_shipping_city() . '",    
//                     "postcode": "' . $order->get_shipping_postcode() . '",
//                     "telephone": "' . $order->get_billing_phone() . '",
//                     "town" : "' . $order->get_shipping_city() . '"
//                 },
//                 "pallets": ' . $palletsdata . '

//             }';

//     $curl = curl_init();

//     curl_setopt_array($curl, array(
//         CURLOPT_URL => 'https://rest-api-nexus.pallex.com/v1/Consignments',
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_ENCODING => '',
//         CURLOPT_MAXREDIRS => 10,
//         CURLOPT_TIMEOUT => 0,
//         CURLOPT_FOLLOWLOCATION => true,
//         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//         CURLOPT_CUSTOMREQUEST => 'POST',
//         CURLOPT_POSTFIELDS => $body,
//         CURLOPT_HTTPHEADER => array(
//             'Content-Type: application/json',
//             'Accept: text/plain',
//             'Authorization: Bearer ' . $data->bearerToken
//         ),
//     ));

//     $response = curl_exec($curl);

//     curl_close($curl);

//     return json_decode($response);
// }

// function pt_pickadate_api_process($order_id, $order)
// {

//     $consignment_number = 1;

//     if (FALSE === get_option('consignment_number') && FALSE === update_option('consignment_number', FALSE)) {
//         add_option('consignment_number', $consignment_number);
//     } else {
//         $consignment_number = get_option('consignment_number');
//         update_option('consignment_number', $consignment_number++);
//     }

//     $collection_date = get_field('collection_date', $order_id);
//     $consignment_date = get_post_meta($order_id, '_final_delivery_date', true);

//     $service = get_field('pallet_code', $order_id);
//     $service_code = explode(",", $service);

//     $pallet_numbers  = get_field('billing_units', $order_id);
//     $pallet_number = explode(",", $pallet_numbers);

//     $status = get_field('pallet_status', $order_id);

//     foreach ($order->get_items() as $item_id_ => $item_) {
//         $composite_id = $item_->get_product_id();

//         $kgWeight = (get_post_meta($composite_id, '_weight', true) ? get_post_meta($composite_id, '_weight', true) : 0);

//         $kg[] = $kgWeight *  $item_->get_quantity();
//         $qty = $item_->get_quantity();
//     }

//     $body = '{
//             "Manifest": {
//             "Date": "' . date("Y-m-d") . '",
//             "Time": "' . date("G:i:s") . '",
//             "Confirm": "' . $status . '",
//             "Depot": {
//                 "Account": {
//                 "UniqueId": "",
//                 "Consignment": {
//                     "Type": "D",
//                     "ImportID": ' . $order_id . ',
//                     "Number": "' . $order->get_order_number() . '",
//                     "Reference": ' . $order_id . ',
//                     "Lifts": "' . $qty . '",
//                     "Weight": "' . round(array_sum($kg)) . '",
//                     "Handball": false,
//                     "TailLift": true, 
//                     "BookInRequest": false,
//                     "BookInInstructions": "",
//                     "BookInReference": "",
//                     "BookInNotes": "",
//                     "BookInContactName": "",
//                     "BookInContactPhone": "",
//                     "ManifestNote": "",
//                     "CollectionDate": "' . $collection_date . '",
//                     "DeliveryDate": "' . $consignment_date . '",
//                     "DeliveryTime": "08:00:00",
//                     "Service": [
//                     {
//                         "Type": "Delivery",
//                         "Code": "' . $service_code[0] . '",
//                         "Surcharge": "' . $service_code[1] . '" 
//                     }                    
//                     ],
//                     "Address": [                    
//                     {
//                         "Type": "Delivery",
//                         "ContactName": "' . $order->get_shipping_first_name() . '",
//                         "Telephone": "' . $order->get_billing_phone() . '",
//                         "Fax": "' . $order->get_shipping_first_name() . '", 
//                         "CompanyName": "' . $order->get_shipping_first_name() . '", 
//                         "Line": [
//                         "' . $order->get_shipping_address_1() . '",
//                         "' . $order->get_shipping_city() . '",
//                         "' . $order->get_shipping_state() . '"
//                         ],
//                         "Town": "' . $order->get_shipping_city() . '", 
//                         "County": "' . $order->get_shipping_state() . '",
//                         "PostCode": "' . $order->get_shipping_postcode() . '",
//                         "Country": "GB"
//                     }
//                     ],
//                     "BillUnit": {
//                     "Type": "' . $pallet_number[0] . '",
//                     "Amount": "' . $pallet_number[1] . '" 
//                     }
//                 }
//                 }
//             }
//             }
//         }';

//     $json_data  = json_encode($body);


//     $to         = "carlos.tandal@projecttimber.co.uk,jamie.croft@projecttimber.co.uk,william.walton@projecttimber.co.uk";
//     $headers    = array('Content-Type: text/html; charset=UTF-8');
//     $headers[]  = 'From: Project Timber <sales@projecttimber.com>';
//     $headers[]  = 'Reply-To: <sales@projecttimber.com>';

//     wp_mail($to, 'Data pallet', print_r($json_data, true), $headers);

//     $curl = curl_init();

//     curl_setopt_array($curl, array(
//         CURLOPT_URL => 'https://api.palletways.com/createconsignment?apikey=OGpHLBxOXd7j9PAScUbno%2FBYbZefIbSE3UXwY4Hcufc%3D&inputformat=JSON&outputformat=JSON&data=&commit=true',
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_ENCODING => '',
//         CURLOPT_MAXREDIRS => 10,
//         CURLOPT_TIMEOUT => 0,
//         CURLOPT_FOLLOWLOCATION => true,
//         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//         CURLOPT_CUSTOMREQUEST => 'POST',
//         CURLOPT_POSTFIELDS => array('data' => $body),
//         CURLOPT_HTTPHEADER => array(
//             'Cookie: PORTAL=8svql9cp7g2jmhjbceu4ttbmf0'
//         ),
//     ));

//     $response = curl_exec($curl);

//     curl_close($curl);

//     return json_decode($response);
// }

# Begin :: Custom sortable column on admin's order page
add_filter('manage_edit-shop_order_columns', 'custom_shop_order_column', 10);
function custom_shop_order_column($columns)
{

    $new_columns = (is_array($columns)) ? $columns : array();

    $new_columns['expected-delivery-date'] = __('Expected Delivery Date', 'theme_slug');
    $new_columns['final-delivery-date'] = __('Final Delivery Date', 'theme_slug');
    $new_columns['order-type']          = __('Order Type', 'theme_slug');
    $new_columns['weight']          = __('Weight', 'theme_slug');
    $new_columns['post-code']           = __('Postcode', 'theme_slug');
    $new_columns['load-code']           = __('Load Code', 'theme_slug');
    $new_columns['drop']                = __('Drop', 'theme_slug');
    //$new_columns['parent-products']     = __( 'Product(s)','theme_slug');
    $new_columns['delivery-status']     = __('Delivery Status', 'theme_slug');

    return $new_columns;
}

add_action('manage_shop_order_posts_custom_column', 'custom_orders_list_column_content', 10);
function custom_orders_list_column_content($column)
{
    global $post, $woocommerce, $the_order;
    $order_id = $the_order->id;

    switch ($column) {
        case 'expected-delivery-date':
            $_expected_delivery_date = get_post_meta($order_id, '_from_delivery_date', true);
            _e($_expected_delivery_date ? $_expected_delivery_date : '<strong>–</strong>');
            break;

        case 'final-delivery-date':
            $_final_delivery_date = get_post_meta($order_id, '_final_delivery_date', true);
            _e($_final_delivery_date ? $_final_delivery_date : '<strong>–</strong>');
            break;

        case 'order-type':
            $_order_type = get_post_meta($order_id, '_order_type', true);
            _e($_order_type ? $_order_type : '<strong>–</strong>');
            break;

        case 'weight':
            $_order_type = order_get_total_weight($order_id);
            _e($_order_type ? $_order_type . 'kg' : '<strong>–</strong>');
            break;

        case 'post-code':
            $_shipping_postcode = get_post_meta($order_id, '_shipping_postcode', true);
            $_shipping_postcode = trim($_shipping_postcode) <> '' ? $_shipping_postcode : get_post_meta($order_id, '_billing_postcode', true);
            _e($_shipping_postcode ? $_shipping_postcode : '<strong>–</strong>');
            break;

        case 'load-code':
            $_pip_load_code = trim(get_post_meta($order_id, '_pip_load_code', true));
            _e($_pip_load_code ? $_pip_load_code : '<strong>–</strong>');
            break;

        case 'drop':
            $_pip_drop = trim(get_post_meta($order_id, '_pip_drop', true));
            _e($_pip_drop ? $_pip_drop : '<strong>–</strong>');
            break;

        /*case 'parent-products':
            $parent_products = trim( get_pip_product_name( array( $order_id ), true ) );
            _e( $parent_products ? $parent_products : '<strong>–</strong>' );
            break;*/

        case 'delivery-status':

            $order_type = get_post_meta($order_id, '_order_type', true);
            $allowed_order_type = array('eBay [Consumer]', 'eBay [Trade]');
            $_delivery_status = trim(get_post_meta($order_id, '_delivery_status', true));

            if (!in_array($order_type, $allowed_order_type)) {
                _e('<strong>–</strong>');
            } else {
                if (!$_delivery_status or $_delivery_status == 'Unpaid') {
                    $_order_shipping = (float)trim(get_post_meta($order_id, '_order_shipping', true));
                    if ($_order_shipping > 0) {
                        _e('Unpaid');
                    } else {
                        _e('<strong>–</strong>');
                    }
                } else {
                    _e($_delivery_status);
                }
            }

            break;

        case 'order_title':
            $ebay_username = trim(get_post_meta($order_id, '_ebay_username', true));
            _e($ebay_username ? '<br>' . $ebay_username : '');
            break;
    }
}

add_filter('manage_edit-shop_order_sortable_columns', 'final_delivery_date_orderby');
function final_delivery_date_orderby($columns)
{
    $custom = array(
        'expected-delivery-date'    => '_from_delivery_date',
        'final-delivery-date'       => '_final_delivery_date',
        'order-type'                => '_order_type',
        'delivery-status'           => '_delivery_status'
    );
    return wp_parse_args($custom, $columns);
}

//add_filter( 'request', 'final_delivery_date_orderby_value' );
function final_delivery_date_orderby_value($vars)
{
    if (isset($vars['orderby']) && '_final_delivery_date' == $vars['orderby']) {
        $vars = array_merge($vars, array(
            'meta_key' => '_final_delivery_date',
            'orderby' => 'meta_value'
        ));
    }

    if (isset($vars['orderby']) && '_order_type' == $vars['orderby']) {
        $vars = array_merge($vars, array(
            'meta_key' => '_order_type',
            'orderby' => 'meta_value'
        ));
    }

    if (isset($vars['orderby']) && '_delivery_status' == $vars['orderby']) {
        $vars = array_merge($vars, array(
            'meta_key' => '_delivery_status',
            'orderby' => 'meta_value'
        ));
    }

    // Sort Orders by ID by default on admin's order page
    if (!isset($vars['orderby']) or $vars['orderby'] === "") {
        $vars['orderby'] = "ID";
    }

    return $vars;
}
# End :: Custom sortable column on admin's order page
// extra fields for the per order ========================================== end!

add_filter('woocommerce_checkout_fields', 'pttimber_override_checkout_fields');
function pttimber_override_checkout_fields($fields)
{

    $fields['billing']['billing_address_1']['placeholder'] = 'House number and street name';
    $fields['billing']['billing_address_2']['placeholder'] = 'Apartment, suite, unit, etc. (optional)';
    $fields['billing']['billing_city']['placeholder'] = 'Town / City';
    $fields['billing']['billing_state']['placeholder'] = 'County';
    $fields['billing']['billing_postcode']['placeholder'] = 'Postcode';

    // $fields['billing']['billing_company']['placeholder'] = 'Business Name';    
    $fields['billing']['billing_first_name']['placeholder'] = 'First Name';
    $fields['shipping']['shipping_first_name']['placeholder'] = 'First Name';
    $fields['shipping']['shipping_last_name']['placeholder'] = 'Last Name';
    // $fields['shipping']['shipping_company']['placeholder'] = 'Company Name';
    $fields['billing']['billing_last_name']['placeholder'] = 'Last Name';
    $fields['billing']['billing_email']['placeholder'] = 'Email';
    $fields['billing']['billing_phone']['placeholder'] = 'Contact Number';

    return $fields;
}

// Rename billing address fields
add_filter('woocommerce_default_address_fields', 'custom_rename_address_fields');
function custom_rename_address_fields($fields) {
    // Rename specific fields
    $fields['address_1']['label'] = 'Address Line 1';
    
    return $fields;
}

function get_parent_product_category_by_product_id($product_id)
{
    // Get product categories
    $terms = wp_get_post_terms($product_id, 'product_cat');

    // Store parent categories
    $parent_categories = array();

    foreach ($terms as $term) {
        // Check if the category has a parent
        if ($term->parent != 0) {
            // Get parent category
            $parent = get_term($term->parent, 'product_cat');
            $parent_categories[] = $parent;
        }
    }

    return $parent_categories;
}

add_action('wp_enqueue_scripts', 'enabling_date_picker');
function enabling_date_picker()
{

    if (is_admin() || ! is_checkout()) return;

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}

function after_new_order_action($order_id)
{

    $consignmentDate = DateTime::createFromFormat('d/m/Y', $_POST['order_pickup_date']);
    update_post_meta($order_id, '_from_delivery_date', $consignmentDate->format('Y-m-d'));

    if (current_user_can('administrator')) {
        update_post_meta($order_id, '_order_type', 'Phone');
    } else {
        // Order made from website
        update_post_meta($order_id, '_order_type', 'Website');
    }
}
add_action('woocommerce_checkout_order_processed', 'after_new_order_action');


function push_dearsys_after_complete_pay($order_id)
{

    $orderid = $order_id;
    $userid = '7';
    $productName = [];

    $invoice_num = get_post_meta($orderid, '_order_number_formatted', true);
    $totalCompositePrice = get_post_meta($orderid, '_order_total', true);

    global $wpdb;
    $query_items = 'SELECT * FROM wp_woocommerce_order_items WHERE order_id = "' . $orderid . '"';
    $meta_items = $wpdb->get_results($query_items, OBJECT);
    $numItems = 0;

    foreach ($meta_items as $index_items => $item) {

        $itemmetas = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "' . $item->order_item_id . '" ');

        foreach ($itemmetas as $index_itemmeta => $itemmeta) {

            if ($itemmeta->meta_key == '_product_id') {

                $_product = wc_get_product($itemmeta->meta_value);

                if ($_product->is_type('composite')) {
                    $productName[] = $_product->get_name();
                }

                if ($_product->is_type('bundle')) {

                    if (preg_match('/[0-9]{0,5} x [0-9]{0,5}/', $_product->get_name())) {
                        $productSize[] = $_product->get_name();
                        $productSizeItem = $_product->get_name();
                    }
                }

                if (!$_product->is_type('bundle') && !$_product->is_type('composite')) {

                    $qty = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "' . $item->order_item_id . '" AND meta_key = "_qty"', OBJECT)[0];
                    $productid = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "' . $item->order_item_id . '" AND meta_key = "_product_id"', OBJECT)[0];

                    $partProduct = wc_get_product($itemmeta->meta_value);

                    if (
                        $partProduct->get_sku() != '2571' && $partProduct->get_sku() != '2571-S' && $partProduct->get_sku() != '2571-A' &&
                        $partProduct->get_sku() != '2571-G' && $partProduct->get_sku() != '2571-WC' && $partProduct->get_sku() != '2571-TC' &&
                        $partProduct->get_sku() != '2571-RC' && $partProduct->get_sku() != '2571-F' && $partProduct->get_sku() != 'option-misc'
                    ) {

                        $product_items[$productSizeItem][$partProduct->get_sku()]['qty'] += $qty->meta_value;
                        $product_items[$productSizeItem][$partProduct->get_sku()]['product_name'] = $partProduct->get_name();
                        $product_items[$productSizeItem][$partProduct->get_sku()]['price'] = $partProduct->get_price();
                        $product_items[$productSizeItem][$partProduct->get_sku()]['total'] = $qty->meta_value;

                        $numItems++;
                    }
                }
            }
        }
    }

    if (empty($productName)) {

        return false;
    }

    $reference_num = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "' . $orderid . '" AND meta_key = "_trade_order_reference_num"',  OBJECT)[0];
    $load_code = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "' . $orderid . '" AND meta_key = "_pip_load_code"', OBJECT)[0];
    $driver_name = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "' . $orderid . '" AND meta_key = "_pip_driver_name"', OBJECT)[0];
    $drop = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "' . $orderid . '" AND meta_key = "_pip_drop"', OBJECT)[0];
    $fromdev = get_post_meta($orderid, '_final_delivery_date', true);
    $cin7_dispatch_date = get_post_meta($orderid, '_dispatch_date', true);

    $expectedDate = get_post_meta($orderid, '_from_delivery_date', true);
    $extra_option = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "' . $orderid . '" AND meta_key = "_pip_extras_or_option"', OBJECT)[0];
    $sales_rep = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "' . $orderid . '" AND meta_key = "sales_agent_name"', OBJECT)[0];

    $expectedDate_ = date("Y-m-d", strtotime($expectedDate));

    $order = wc_get_order($orderid);
    $order_data = $order->get_data(); // The Order data

    $order_shipping_name = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];
    $order_shipping_company = $order_data['billing']['company'];
    $order_shipping_address_1 = $order_data['billing']['address_1'];
    $order_shipping_address_2 = $order_data['billing']['address_2'];
    $order_shipping_city = $order_data['billing']['city'];
    $order_shipping_state = $order_data['billing']['state'];
    $order_shipping_postcode = $order_data['billing']['postcode'];
    $order_shipping_country = 'GB';

    $order_billing_phone = $order_data['billing']['phone'];
    $order_shipping_email = $order_data['billing']['email'];
    $order_company = $order_data['billing']['first_name'] . " " . $order_data['billing']['last_name'];

    $order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');

    $skuid_ = $invoice_num;
    // add total qty order
    add_post_meta($orderid, '_total_qty_order', count($productName), true);

    $checkUser = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/customer?name=' . rawurlencode($order_shipping_name), 'GET');
    $checkSale = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/SaleList?search=' . $skuid_ . '&OrderStatus=AUTHORISED', 'GET');

    $newCustomer = '
    {
    "Addresses": [
        {
        "Line1": "' . $order_shipping_address_1 . '",
        "Line2": "' . $order_shipping_address_2 . '",
        "City": "' . $order_shipping_city . '",
        "State": "' . $order_shipping_city . '",
        "Postcode": "' . $order_shipping_postcode . '",
        "Country": "' . $order_shipping_country . '",
        "Type": "Shipping",
        "DefaultForType": true
        },
    ],
    "Contacts": [
        {
        "Name": "' . $order_shipping_name . '",
        "Phone": "' . $order_billing_phone . '",
        "Email": "' . $order_shipping_email . '",
        "Default": true
        }
    ],
    "Name": "' . $order_shipping_name . '",
    "Currency": "GBP",
    "PaymentTerm": "30 days",
    "Discount": 0,
    "TaxRule": "20% (VAT on Income)",
    "Carrier": "8 Working Days",
    "Location": "Sutton Warehouse",
    "Comments": "Customer",
    "AccountReceivable": "610",
    "RevenueAccount": "200",
    "PriceTier": "Tier 1",
    "Status": "Active",
    "CreditLimit": 0
    }';

    $newSales = '
    {
    "Customer": "' . $order_shipping_name . '",
    "Contact": "' . $order_shipping_name . '",
    "Phone": "' . $order_billing_phone . '",
    "OrderDate":"' . $order_date_created . '",
    "SaleOrderDate" : "' . $order_date_created . '",
    "SaleAccount":"711",
    "BillingAddress":{
    "Line1": "' . $order_shipping_address_1 . '",
    "Line2": "' . $order_shipping_address_2 . '",
    "City": "' . $order_shipping_city . '",
    "State": "' . $order_shipping_city . '",
    "Postcode": "' . $order_shipping_postcode . '",
    "Country": "' . $order_shipping_country . '"
    },
    "ShippingAddress":{
        "Line1": "' . $order_shipping_address_1 . '",
        "Line2": "' . $order_shipping_address_2 . '",
        "City": "' . $order_shipping_city . '",
        "State": "' . $order_shipping_city . '",
        "Postcode": "' . $order_shipping_postcode . '",
        "Country": "' . $order_shipping_country . '"
    },
    "ShippingNotes": "Expected Delivery ' . $expectedDate_ . '",
    "TaxRule":"20% (VAT on Income)",
    "TaxInclusive": "false",
    "Terms":"30 Days",
    "PriceTier":"Tier 1",
    "ShipBy" : "' . $expectedDate_ . '",
    "Location":"Sutton Warehouse",
    "Note":"Extra Option ' . addslashes($extra_option->meta_value) . ' ",
    "CustomerReference" : "' . $skuid_ . '",
    "AutoPickPackShipMode":"NOPICK",
    "SalesRepresentative": "None",
    "Carrier": "8 Working Days",
    "CurrencyRate": "1",
    "SaleType" : "Advanced"
    }';

    if ($checkSale->Total == 1) {

        $voidSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale?ID=' . $checkSale->SaleList[0]->SaleID . '&Void=True', 'DELETE');

        $createNewSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale', 'POST', $newSales);

        // Sales Items
        $ProductNameItem = '';

        foreach ($productName as $ProdIndex => $Prodname) {
            $ProductNameItem .= $Prodname . '-' . $ProdIndex[$ProdIndex] . "; ";
        }

        $cntItem = 0;
        $lines_item = '';

        foreach ($product_items as $index_size => $size) {
            foreach ($size as $index_item => $item) {
                $lines_item .= '{"SKU":"' . $index_item . '",';
                $lines_item .= '"Name": "' . addslashes($item['product_name']) . '",';
                $lines_item .= '"Quantity": "' . $item['qty'] . '",';
                $lines_item .= '"Price": "0",';
                $lines_item .= '"Tax": "0",';
                $lines_item .= '"TaxRule": "20% (VAT on Income)",';
                $lines_item .= '"Total" : "0" }';

                if ($cntItem++ === $numItems) {
                    $lines_item .= '';
                } else {
                    $lines_item .= ',';
                }
            }
        }

        $newOrderItems = '
          {
            "SaleID": "' . $createNewSales->ID . '",
              "Memo": "' . addslashes($extra_option->meta_value) . '",
              "Status": "AUTHORISED",
            "Lines": [' . $lines_item . '],
              "AdditionalCharges": [
                  {
                        "Description": "' . addslashes($ProductNameItem) . ' Total Price: ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
                        "Price": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . ',
                        "Quantity": 1,
                        "Discount": 0,
                        "Tax": ' . calculateTwentyPercent($totalCompositePrice) . ',
                        "Total": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . ',
                        "TaxRule": "20% (VAT on Income)",
                        "Comment": ""
                    }
              ],
              "TotalBeforeTax": 0,
              "Tax": ' . calculateTwentyPercent($totalCompositePrice) . ',
              "Total": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '
          }';


        $createNewOrderItems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/order', 'POST', $newOrderItems);

        $cntItemv = 0;
        $lines_itemv = '';

        foreach ($product_items as $index_size => $size) {
            foreach ($size as $index_item => $item) {
                $lines_itemv .= '{"SKU":"' . $index_item . '",';
                $lines_itemv .= '"Name": "' . addslashes($item['product_name']) . '",';
                $lines_itemv .= '"Quantity": "' . $item['qty'] . '",';
                $lines_itemv .= '"Price": "0",';
                $lines_itemv .= '"Tax": "0",';
                $lines_itemv .= '"TaxRule": "20% (VAT on Income)",';
                $lines_itemv .= '"Account": "200",';
                $lines_itemv .= '"Total" : "0" }';

                if ($cntItemv++ === $numItems) {
                    $lines_itemv .= '';
                } else {
                    $lines_itemv .= ',';
                }
            }
        }

        $newInvoice = '
        {
           "SaleID": "' . $createNewSales->ID . '",
           "TaskID" : "00000000-0000-0000-0000-000000000000",
           "CombineAdditionalCharges": false,
           "Memo":"",
           "Status":"AUTHORISED",
           "InvoiceDate" : "' . $order_date_created . '",
           "InvoiceDueDate" : "' . $order_date_created . '",
           "CurrencyConversionRate": 1,
           "BillingAddressLine1": "' . $order_shipping_address_1 . '",
           "BillingAddressLine2": "' . $order_shipping_address_2 . '",
           "Lines":[' . $lines_itemv . '],
           "AdditionalCharges":[
              {
                 "Description":"' . addslashes($ProductNameItem) . ' Total Price: ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
                 "Price":' . $totalCompositePrice . ',
                 "Quantity":1,
                 "Discount":0,
                 "Tax":' . calculateTwentyPercent($totalCompositePrice) . ',
                 "Total": ' . $totalCompositePrice . ',
                 "TaxRule":"20% (VAT on Income)",
                 "Account": "200",
                 "Comment":"" 
              }
           ],
           "TotalBeforeTax":"' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
           "Tax":' . calculateTwentyPercent($totalCompositePrice) . ',
           "Total":"' . $totalCompositePrice . '"
        }
      ';

        $createNewInvoicetems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/invoice', 'POST', $newInvoice);

        $paymentInvoice = '
            {
            "TaskID": "' . $createNewInvoicetems->Invoices[0]->TaskID . '",
            "Type": "Payment",
            "Reference": "' . $skuid_ . '",
            "Amount": "' . $totalCompositePrice . '",
            "DatePaid": "' . $order_date_created . '",
            "Account": "701",
            "CurrencyRate": 1
            }
        ';

        $createPaymentInvoice = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/payment', 'POST', $paymentInvoice);

        $order = new WC_Order($orderid);

        if ($createNewOrderItems != 'error') {
            if ($userid !== 112) {
                $byname = get_user_by('id', $userid);
                $order->add_order_note("Push again to dear system - Time " . date("Y/m/d h:i:sa") . " By:" . $byname->first_name, false, true);
            }
        } else {
            if ($userid !== 112) {
                $order->add_order_note("Push error to dear system - Time " . date("Y/m/d h:i:sa") . " By:" . $byname->first_name, false, true);
            }
        }

        return true;
    }

    if ($checkSale->Total == 0) {

        /* if ($checkUser->Total == 1) {

            $createNewSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale', 'POST', $newSales);

            // Sales Items
            $ProductNameItem = '';

            foreach ($productName as $ProdIndex => $Prodname) {
                $ProductNameItem .= $Prodname . '-' . $ProdIndex[$ProdIndex] . "; ";
            }

            $cntItem = 0;
            $lines_item = '';

            foreach ($product_items as $index_size => $size) {
                foreach ($size as $index_item => $item) {
                    $lines_item .= '{"SKU":"' . $index_item . '",';
                    $lines_item .= '"Name": "' . addslashes($item['product_name']) . '",';
                    $lines_item .= '"Quantity": "' . $item['qty'] . '",';
                    $lines_item .= '"Price": "0",';
                    $lines_item .= '"Tax": "0",';
                    $lines_item .= '"TaxRule": "20% (VAT on Income)",';
                    $lines_item .= '"Total" : "0" }';

                    if (++$cntItem === $numItems) {
                        $lines_item .= '';
                    } else {
                        $lines_item .= ',';
                    }
                }
            }

            $newOrderItems = '
          {
            "SaleID": "' . $createNewSales->ID . '",
              "Memo": "' . addslashes($extra_option->meta_value) . '",
              "Status": "AUTHORISED",
            "Lines": [' . $lines_item . '],
              "AdditionalCharges": [
                  {
                        "Description": "' . addslashes($newPnew) . ' Total Price: ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
                        "Price": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . ',
                        "Quantity": 1,
                        "Discount": 0,
                        "Tax": ' . calculateTwentyPercent($totalCompositePrice) . ',
                        "Total": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . ',
                        "TaxRule": "20% (VAT on Income)",
                        "Comment": ""
                    }
              ],
              "TotalBeforeTax": 0,
              "Tax": ' . calculateTwentyPercent($totalCompositePrice) . ',
              "Total": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '
          }';


            $createNewOrderItems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/order', 'POST', $newOrderItems);

            $cntItemv = 0;
            $lines_itemv = '';

            foreach ($product_items as $index_size => $size) {
                foreach ($size as $index_item => $item) {
                    $lines_itemv .= '{"SKU":"' . $index_item . '",';
                    $lines_itemv .= '"Name": "' . addslashes($item['product_name']) . '",';
                    $lines_itemv .= '"Quantity": "' . $item['qty'] . '",';
                    $lines_itemv .= '"Price": "0",';
                    $lines_itemv .= '"Tax": "0",';
                    $lines_itemv .= '"TaxRule": "20% (VAT on Income)",';
                    $lines_itemv .= '"Account": "200",';
                    $lines_itemv .= '"Total" : "0" }';

                    if ($cntItemv++ === $numItems) {
                        $lines_itemv .= '';
                    } else {
                        $lines_itemv .= ',';
                    }
                }
            }

            $newInvoice = '
            {
            "SaleID": "' . $createNewSales->ID . '",
            "TaskID" : "00000000-0000-0000-0000-000000000000",
            "CombineAdditionalCharges": false,
            "Memo":"",
            "Status":"AUTHORISED",
            "InvoiceDate" : "' . $order_date_created . '",
            "InvoiceDueDate" : "' . $order_date_created . '",
            "CurrencyConversionRate": 1,
            "BillingAddressLine1": "' . $order_shipping_address_1 . '",
            "BillingAddressLine2": "' . $order_shipping_address_2 . '",
            "Lines":[' . $lines_itemv . '],
            "AdditionalCharges":[
                {
                    "Description":"' . addslashes($newPnew) . ' Total Price: ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
                    "Price":' . $totalCompositePrice . ',
                    "Quantity":1,
                    "Discount":0,
                    "Tax":' . calculateTwentyPercent($totalCompositePrice) . ',
                    "Total": ' . $totalCompositePrice . ',
                    "TaxRule":"20% (VAT on Income)",
                    "Account": "200",
                    "Comment":""
                }
            ],
            "TotalBeforeTax":"' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
            "Tax":' . calculateTwentyPercent($totalCompositePrice) . ',
            "Total":"' . $totalCompositePrice . '"
            }
            ';

                $createNewInvoicetems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/invoice', 'POST', $newInvoice);

                $paymentInvoice = '
                {
                "TaskID": "' . $createNewInvoicetems->Invoices[0]->TaskID . '",
                "Type": "Payment",
                "Reference": "' . $skuid_ . '",
                "Amount": "' . $totalCompositePrice . '",
                "DatePaid": "' . $order_date_created . '",
                "Account": "701",
                "CurrencyRate": 1
                }
            ';

                $createPaymentInvoice = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/payment', 'POST', $paymentInvoice);

                $order = new WC_Order($orderid);

                $byname = get_user_by('id', $userid);

                if ($createNewOrderItems != 'error') {
                    $byname = get_user_by('id', $userid);
                    if ($userid != '112') {
                        $order->add_order_note("Push to dear system - Time " . date("Y/m/d h:i:sa") . " By:" . $byname->first_name, false, true);
                    }
                } else {
                    if ($userid != '112') {
                        $order->add_order_note("Push error to dear system - Time " . date("Y/m/d h:i:sa") . " By:" . $byname->first_name, false, true);
                    }
                }

                return true;
        } else {*/

        $createNewUser = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/customer', 'POST', $newCustomer);
        $createNewSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale', 'POST', $newSales);

        // Sales Items
        $ProductNameItem = '';

        foreach ($productName as $ProdIndex => $Prodname) {
            $ProductNameItem .= $Prodname . '-' . $ProdIndex[$ProdIndex] . "; ";
        }

        $cntItem = 0;
        $lines_item = '';

        foreach ($product_items as $index_size => $size) {
            foreach ($size as $index_item => $item) {
                $lines_item .= '{"SKU":"' . $index_item . '",';
                $lines_item .= '"Name": "' . addslashes($item['product_name']) . '",';
                $lines_item .= '"Quantity": "' . $item['qty'] . '",';
                $lines_item .= '"Price": "0",';
                $lines_item .= '"Tax": "0",';
                $lines_item .= '"TaxRule": "20% (VAT on Income)",';
                $lines_item .= '"Total" : "0" }';

                if ($cntItem++ === $numItems) {
                    $lines_item .= '';
                } else {
                    $lines_item .= ',';
                }
            }
        }

        $newOrderItems = '
              {
                "SaleID": "' . $createNewSales->ID . '",
                  "Memo": "' . addslashes($extra_option->meta_value) . '",
                  "Status": "AUTHORISED",
                "Lines": [' . $lines_item . '],
                  "AdditionalCharges": [
                      {
                            "Description": "' . addslashes($item['product_name']) . ' Total Price: ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
                            "Price": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . ',
                            "Quantity": 1,
                            "Discount": 0,
                            "Tax": ' . calculateTwentyPercent($totalCompositePrice) . ',
                            "Total": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . ',
                            "TaxRule": "20% (VAT on Income)",
                            "Comment": ""
                        }
                  ],
                  "TotalBeforeTax": 0,
                  "Tax": ' . calculateTwentyPercent($totalCompositePrice) . ',
                  "Total": ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '
              }';


        $createNewOrderItems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/order', 'POST', $newOrderItems);

        $cntItemv = 0;
        $lines_itemv = '';

        foreach ($product_items as $index_size => $size) {
            foreach ($size as $index_item => $item) {
                $lines_itemv .= '{"SKU":"' . $index_item . '",';
                $lines_itemv .= '"Name": "' . addslashes($item['product_name']) . '",';
                $lines_itemv .= '"Quantity": "' . $item['qty'] . '",';
                $lines_itemv .= '"Price": "0",';
                $lines_itemv .= '"Tax": "0",';
                $lines_itemv .= '"TaxRule": "20% (VAT on Income)",';
                $lines_itemv .= '"Account": "200",';
                $lines_itemv .= '"Total" : "0" }';

                if ($cntItemv++ === $numItems) {
                    $lines_itemv .= '';
                } else {
                    $lines_itemv .= ',';
                }
            }
        }


        $newInvoice = '
            {
               "SaleID": "' . $createNewSales->ID . '",
               "TaskID" : "00000000-0000-0000-0000-000000000000",
               "CombineAdditionalCharges": false,
               "Memo":"",
               "Status":"AUTHORISED",
               "InvoiceDate" : "' . $order_date_created . '",
               "InvoiceDueDate" : "' . $order_date_created . '",
               "CurrencyConversionRate": 1,
               "BillingAddressLine1": "' . $order_shipping_address_1 . '",
               "BillingAddressLine2": "' . $order_shipping_address_2 . '",
               "Lines":[' . $lines_itemv . '],
               "AdditionalCharges":[
                  {
                     "Description":"' . addslashes($item['product_name']) . ' Total Price: ' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
                     "Price":' . $totalCompositePrice . ',
                     "Quantity":1,
                     "Discount":0,
                     "Tax":' . calculateTwentyPercent($totalCompositePrice) . ',
                     "Total": ' . $totalCompositePrice . ',
                     "TaxRule":"20% (VAT on Income)",
                     "Account": "200",
                     "Comment":""
                  }
               ],
                "TotalBeforeTax":"' . round(($totalCompositePrice - calculateTwentyPercent($totalCompositePrice)), 2) . '",
                "Tax":' . calculateTwentyPercent($totalCompositePrice) . ',
                "Total":"' . $totalCompositePrice . '"
            }
          ';

        $createNewInvoicetems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/invoice', 'POST', $newInvoice);

        $paymentInvoice = '
                {
                "TaskID": "' . $createNewInvoicetems->Invoices[0]->TaskID . '",
                "Type": "Payment",
                "Reference": "' . $skuid_ . '",
                "Amount": "' . $totalCompositePrice . '",
                "DatePaid": "' . $order_date_created . '",
                "Account": "701",
                "CurrencyRate": 1
                }
            ';

        $createPaymentInvoice = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/payment', 'POST', $paymentInvoice);
        // }

        $order = new WC_Order($orderid);

        $byname = get_user_by('id', $userid);

        if ($createNewOrderItems != 'error') {
            if ($userid != '112') {
                $byname = get_user_by('id', $userid);
                $order->add_order_note("Push to dear system - Time " . date("Y/m/d h:i:sa") . " By:" . $byname->first_name, false, true);
            }
        } else {
            if ($userid != '112') {
                $order->add_order_note("Push error to dear system - Time " . date("Y/m/d h:i:sa") . " By:" . $byname->first_name, false, true);
            }
        }

        return true;
    } else {

        return true;
    }
}

add_action('woocommerce_order_status_processing', 'push_dearsys_after_complete_pay');

function my_custom_mime_types($mimes)
{
    // New allowed mime types.
    $mimes['WebP'] = 'image/webp';

    // Optional. Remove a mime type.
    unset($mimes['exe']);

    return $mimes;
}
add_filter('upload_mimes', 'my_custom_mime_types');

// add coupon after billing 
function cw_scripts()
{
    wp_enqueue_script('jquery-ui-dialog');
}
add_action('wp_enqueue_scripts', 'cw_scripts');

function cw_show_coupon_js()
{
    wc_enqueue_js('$("a.showcoupon").parent().hide();');
    wc_enqueue_js('dialog = $("form.checkout_coupon").dialog({
                       autoOpen: false,
                       width: 320, 
                       minHeight: 280,
                       modal: false,
                       appendTo: "#coupon-anchor",
                       position: { my: "left top+50", at: "left top+50", of: "#coupon-anchor"}, 
                       draggable: false,
                       resizable: false,
                       dialogClass: "coupon-special",
                       closeText: "Close",
                       buttons: {}});');
    wc_enqueue_js('$("#show-coupon-form").click( function() {
                       if (dialog.dialog("isOpen")) {
                           $(".checkout_coupon").hide();
                           dialog.dialog( "close" );
                       } else {
                           $(".checkout_coupon").show();
                           dialog.dialog( "open" );
                       }
                       return false;});');
}
add_action('woocommerce_before_checkout_form', 'cw_show_coupon_js');

function cw_show_coupon()
{
    global $woocommerce;
    $promo_enable = get_field('show_coupon', 'option');

    if ($woocommerce->cart->needs_payment()) {
        echo '<p class="woo-promo-checkout"> Have a coupon? <a href="#" id="show-coupon-form">Click here to enter your coupon code</a>.';
        echo '<p>Click here to opt out of voucher <span><a href="#to-footer">*</span/>';
        if ($promo_enable == 1) {


            /*foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        
                $product_id = $cart_item['product_id'];
                $terms = get_the_terms($product_id, 'product_cat');
         
                foreach ($terms as $term) {
                    
                    if($term->slug == 'garden-offices' || $term->slug == 'garden-workshops') {                
                        $coupon_code = 'snowdrops30';                
                        break;
        
                    } else if($term->slug == 'garden-sheds' || $term->slug == 'summerhouses' || $term->slug == 'insulated-garden-buildings' 
                             || $term->slug == 'greenhouses') {                                 
                        $coupon_code = 'snowdrops25';
                    }            
                }
            }*/

            //echo '<span class="code-name">' . get_field('global_coupon_text', 'option') . '&nbsp; "' . get_field("coupon_code", "option") . '"</span>';

            //echo '<span class="code-name">'.get_field('global_coupon_text', 'option') .'&nbsp; "'.$coupon_code. '"</span>';
        }
        echo '</p><div id="coupon-anchor"></div>';
    }
}

// add_action('woocommerce_after_order_notes', 'cw_show_coupon');

function time_schedule()
{
    // Always use UK time
    $tz = new DateTimeZone('Europe/London');
    $now = new DateTime('now', $tz);

    $day_of_week = $now->format('l');
    $current_time = $now->format('H:i:s');
    $current_date = $now->format('Y-m-d');

    // Email fallback
    $closed_msg = 'PHONE LINES CLOSED - SEND US AN <a href="mailto:sales@projecttimber.co.uk">EMAIL</a>';

    /* =========================
     * FULLY CLOSED DATE RANGES
     * ========================= */
    $closed_ranges = [
        ['2025-12-24', '2025-12-26'],
        ['2025-12-31', '2026-01-04'],
    ];

    foreach ($closed_ranges as [$start, $end]) {
        if ($current_date >= $start && $current_date <= $end) {
            return $closed_msg;
        }
    }

    /* =========================
     * SPECIAL REDUCED HOURS
     * ========================= */
    $special_days = [
        '2025-12-22',
        '2025-12-23',
        '2025-12-29',
        '2025-12-30',
    ];

    if (in_array($current_date, $special_days, true)) {
        if ($current_time >= '10:00:00' && $current_time <= '18:00:00') {
            return 'PHONE LINES OPEN UNTIL 6:00PM CALL NOW';
        }
        return $closed_msg;
    }

    /* =========================
     * NORMAL WEEKDAYS
     * ========================= */
    if (in_array($day_of_week, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], true)) {
        if ($current_time >= '08:30:00' && $current_time <= '18:00:00') {
            return 'PHONE LINES OPEN UNTIL 7:00PM CALL NOW';
        }
        return $closed_msg;
    }

    /* =========================
     * WEEKENDS
     * ========================= */
    return $closed_msg;
}


function array_replacing(&$item, $key)
{

    if ($key == 'option_title') {

        $productidsss[] = $item;
    }

    if ($key == 'option_thumbnail_html') {
        $item = 'my new image - ' . $productidsss[0];
    }
}

// Add a custom column before "actions" last column
add_filter('manage_edit-shop_order_columns', 'custom_shop_order_column1', 100);
function custom_shop_order_column1($columns)
{
    $ordered_columns = array();

    foreach ($columns as $key => $column) {
        $ordered_columns[$key] = $column;
        if ('order_date' == $key) {
            $ordered_columns['transaction_id'] = __('Transaction id', 'woocommerce');
        }
    }

    return $ordered_columns;
}

add_action('manage_shop_order_posts_custom_column', 'custom_shop_order_list_column_content', 10, 1);
function custom_shop_order_list_column_content($column)
{
    global $post, $the_order;

    if ('transaction_id' === $column) {
        echo $the_order->get_transaction_id();
    }
}

//add_filter( 'woocommerce_get_price_html', 'wc_cp_custom_price_html', 10, 2 );
function wc_cp_custom_price_html($price_html, $product)
{

    $price_html = $price_html . "=== test !!";

    return $price_html;
}

// Adding Meta container admin shop_order pages
add_action('add_meta_boxes', 'pt_add_email_meta_boxes');
if (! function_exists('pt_add_email_meta_boxes')) {
    function pt_add_email_meta_boxes()
    {
        add_meta_box('woocommerce-custom-email', __('Email', 'woocommerce'), 'pt_add_other_fields_for_email', 'shop_order', 'side', 'high');
    }
}

// Adding Meta field in the meta container admin shop_order pages
if (! function_exists('pt_add_other_fields_for_email')) {
    function pt_add_other_fields_for_email()
    {
        global $post;

        echo '<p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
            <select name="order_email_template" id="order_email_template">
                <option value="">Select Templates</option>
                <optgroup label="Send Email">
                    <option value="delivery">Delivery</option>
                    <option value="assembly">Assembly</option>
                    <!--option value="covid-19">Covid-19</option-->
                    <option value="rdm">RDM</option>
                    <option value="trustpilot">Trustpilot</option>
                    <option value="delivery-apologizes">Delivery Apologizes</option>
                </optgroup>
            </select>
            <button type="submit" class="rdm button-primary">Send Email</button></p>';
    }
}

add_action('woocommerce_process_shop_order_meta', 'pt_send_email_template');
function pt_send_email_template()
{

    $post_request = !empty($_POST['order_email_template']) && !empty($_POST['_final_delivery_date']);

    $order_id   = (int) $_POST['post_ID'];
    $order      = new WC_Order($order_id);

    if ($post_request) {

        if (! is_user_logged_in()) {
            return;
        }

        //$to       = "carlos.tandal@projecttimber.co.uk laurence@projecttimber.co.uk";
        $to         = $order->get_billing_email();
        $subject    = "Project Timber's Delivery Update and Assembly Instructions";

        if ('assembly' == $_POST['order_email_template']) {
            $subject = "Your Building Assembly Instructions";
            //$to   = "davecanilao@projecttimber.co.uk, laurence@projecttimber.co.uk";
        }

        //if ('rdm' == $_POST['order_email_template']) {
        //  $subject = "Project Timber's Spares Delivery Update";
        //}

        if ('trustpilot' == $_POST['order_email_template']) {
            $subject = "Trustpilot";
        }
        if ('delivery-apologizes' == $_POST['order_email_template']) {
            $subject = "Project Timber's Delivery Update";
        }
        ob_start();
        require_once __DIR__ . "/inc/custom-emails/" . $_POST['order_email_template'] . "-email.php";

        $body = ob_get_clean();

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = 'From: Project Timber <deliveries@projecttimber.co.uk>';
        $headers[] = 'Reply-To: <deliveries@projecttimber.co.uk>';
        $headers[] = 'Bcc: deliveries@projecttimber.co.uk, samuel.weeks@projecttimber.co.uk, LS@projecttimber.co.uk, deliveries@projecttimber.co.uk, william.walton@projecttimber.co.uk, szegedi.szilard@projecttimber.co.uk';

        if (wp_mail($to, $subject, $body, $headers)) {
            $order->add_order_note(sprintf(__('%s email notification sent.', 'woocommerce'), $_POST['order_email_template']), false, true);
            return 1;
        }
    }
}

function mtp_disable_mobile_messaging($mailer)
{
    remove_action('woocommerce_email_footer', array($mailer->emails['WC_Email_New_Order'], 'mobile_messaging'), 9);
}
add_action('woocommerce_email', 'mtp_disable_mobile_messaging');


function order_get_total_weight($order_id)
{

    global $wpdb;

    $order          = wc_get_order($order_id);
    $total_weight   = 0;
    $items          = $order->get_items();
    $order_type     = trim(get_post_meta($order->get_id(), '_order_type', true));
    $finaltotal[]     = 0;
    $order_total = floatval($order->get_total());


    foreach ($order->get_items() as $item_ids => $items) {

        $composite_id = $items->get_product_id();
        $product      = $items->get_product();
        $weightKG = (get_post_meta($composite_id, '_weight', true) ? get_post_meta($composite_id, '_weight', true) : 0);

        if ($weightKG !== 0) {

            if ($product->product_type == 'composite') {
                $total_weight  = ($weightKG *  $items->get_quantity());
            } else if ($product->product_type == 'bundle') {
                $total_weight = ($weightKG *  $items->get_quantity());
            } else {
                $total_weight = ($weightKG *  $items->get_quantity());
            }

            $finaltotal[] = $total_weight;
        }
    }

    return array_sum($finaltotal);
}

/*
function order_get_total_weight($order_id)
{

    global $wpdb;

    $order          = wc_get_order($order_id);
    $total_weight   = 0;
    $items          = $order->get_items();
    $order_type     = trim(get_post_meta($order->get_id(), '_order_type', true));

    foreach ($items as $item) {

        // Check if the item is parent composite
        $item_is_parent           = false;
        $item_is_composite_parent = false;
        $item_is_parts            = false;
        $item_is_component        = false;
        $component_title          = "";

        foreach ($item->get_meta_data() as $meta_data) {

            // composite parent
            if ($meta_data->key == '_composite_children' and !empty($meta_data->value)) {
                $item_is_parent = true;
                $item_is_composite_parent = true;
                break;

                // child parts
            } elseif (
                $meta_data->key == '_parent_composite_order_item_id'
                and trim($meta_data->value) <> ""
            ) {
                $item_is_parts = true;
                break;

                // child component
            } elseif (
                $meta_data->key == '_composite_parent'
                and trim($meta_data->value) <> ""
            ) {
                $item_is_component = true;
                break;

                // parts under child component of parent composite 
            } elseif (
                $meta_data->key == '_bundled_item_hidden'
                and trim($meta_data->value) == "yes"
            ) {
                $item_is_parts = true;
                break;
            } else {
                $item_is_parent = true;
            }
        }

        // Get the product
        $product = $order->get_product_from_item($item);



        // Do not include composite parent
        if (!empty($product)) {
            if ($product->get_type() == 'composite') {
                continue;
            }
        }

        if (!empty($product)) {

            $parent_qty = $item->get_quantity(); // Get the parent product's quantity

            $bundled_qty = (int)$item['qty'];
            $sql = "SELECT a.product_id, b.meta_value
                    FROM {$wpdb->prefix}woocommerce_bundled_items a
                    INNER JOIN {$wpdb->prefix}woocommerce_bundled_itemmeta b ON b.bundled_item_id = a.bundled_item_id
                    WHERE a.bundle_id = {$product->get_id()}
                    and b.meta_key = 'quantity_max'";
            $bundled_items = $wpdb->get_results($sql);

            foreach ($bundled_items as $bundled_item) {

                //$bundled_qty = (int)$item['qty'] * (int)$bundled_item->meta_value;

                $bundled_qty = isset($bundled_item->meta_value) && is_numeric($bundled_item->meta_value)
                    ? (int)$bundled_item->meta_value
                    : 0; // Default to 1 if not valid 

                $bundled_item_product = wc_get_product($bundled_item->product_id);

                if ($bundled_item_product) :

                    //$total_weight += $bundled_item_product->get_weight() * $bundled_qty;

                    $product_weight = $bundled_item_product->get_weight();
                    $product_weight = is_numeric($product_weight) ? $product_weight : 0; // Ensure weight is numeric
                    $total_weight += $product_weight * $bundled_qty;

                endif;
            }
        }
    }

    return $total_weight * $parent_qty;
}*/

add_filter('woocommerce_email_recipient_new_order', 'disable_new_order_for_on_hold_order_status', 10, 2);
function disable_new_order_for_on_hold_order_status($recipient, $order = false)
{
    if (! $order || ! is_a($order, 'WC_Order'))
        return $recipient;

    if ($order->get_status() === 'on-hold') {
        return '';
    } else {
        return $recipient;
    }
}

function add_voucher_once()
{
    // Define the coupon code
    $coupon_code = 'BF40';

    // Access the WooCommerce session
    if (WC()->session) {
        // Check if the voucher was already applied or removed
        if (WC()->session->get('voucher_applied_once') === 'yes') {
            return; // Do not reapply the coupon
        }
    }

    // Check if the voucher is already in the cart 
    if (!WC()->cart->has_discount($coupon_code)) {
        // Apply the voucher
        WC()->cart->apply_coupon($coupon_code);

        // Mark it as applied in the session
        if (WC()->session) {
            WC()->session->set('voucher_applied_once', 'yes');
        }
    }
}
//add_action('woocommerce_cart_updated', 'add_voucher_once');

function prevent_voucher_readdition($coupon_code)
{
    // Define the coupon code to track
    $tracked_coupon = 'BF40';

    // If the user removes the voucher
    if ($coupon_code === $tracked_coupon) {
        // Set a session to prevent re-adding
        if (WC()->session) {
            WC()->session->set('voucher_applied_once', 'yes');
        }
    }
}
//add_action('woocommerce_removed_coupon', 'prevent_voucher_readdition');


//add_action('woocommerce_before_checkout_form', 'ptcoupon_apply_coupon');
function ptcoupon_apply_coupon()
{

    /*foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        
        $product_id = $cart_item['product_id'];
        $terms = get_the_terms($product_id, 'product_cat');

        foreach ($terms as $term) {
            
            if($term->slug == 'garden-offices' || $term->slug == 'garden-workshops') {                
                $coupon_code = 'snowdrops30';                
                break;

            } else if($term->slug == 'garden-sheds' || $term->slug == 'summerhouses' || $term->slug == 'insulated-garden-buildings' 
                     || $term->slug == 'greenhouses') {                                 
                $coupon_code = 'snowdrops25';
            }            
        }
    }*/

    $coupon_code = 'BF40';

    if (WC()->cart->has_discount($coupon_code)) {
        return;
    }
    WC()->cart->apply_coupon($coupon_code);
}

add_filter('loop_shop_per_page', 'new_loop_shop_per_page', 20);

function new_loop_shop_per_page($cols)
{
    $cols = 18;
    return $cols;
}

//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
add_action('pre_get_posts', 'exclude_simple_and_bundle_from_search');

function exclude_simple_and_bundle_from_search($query)
{
    if (!is_admin() && $query->is_main_query() && $query->is_search()) { // Only modify search queries on frontend

        $tax_query = array(
            'relation' => 'AND'
        );

        // Include your existing tax_query rules
        if (isset($query->query_vars['tax_query'])) {
            $tax_query[] = $query->query_vars['tax_query'];
        }

        // Exclude Simple and Bundle products
        $tax_query[] = array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => array('simple', 'bundle'),
                'operator' => 'NOT IN',
            ),
        );

        $query->set('tax_query', $tax_query);
    }
}

#BEGIN :: Technical Specifications

function tech_specs_tabs($tabs)
{
    $tabs['tech_specs'] = array(
        'label'     => __('Specifications', 'woocommerce'),
        'target'    => 'tech_specs_tab',
        'class'     => array('show_if_composite', 'show_if_bundle')
    );
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'tech_specs_tabs', 98);

function tech_specs_tabs_content()
{

    global $thepostid, $post, $wpdb;

    $thepostid = empty($thepostid) ? $post->ID : $thepostid;
    $postmeta = $wpdb->get_results(" SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = {$thepostid} ", ARRAY_A);

    ?>
<div id='tech_specs_tab' class='panel woocommerce_options_panel'>
    <div class='options_group block-dimension'>

        <h4>Dimensions [cm]</h4>

        <?php

            $arr = array(
                '_specs_overall_width'              => 'Overall Width',
                '_specs_overall_depth'              => 'Overall Depth',
                '_specs_total_wall_thickness'       => 'Total Wall Thickness (including Insulation)',
                '_specs_eaves_height_inc_floor'     => 'Eaves Height [Inc. Floor]',
                '_specs_eaves_height_excl_floor'    => 'Eaves Height [Excl Floor]',
                '_specs_ridge_height_inc_floor'     => 'Ridge Height [Inc. Floor]',
                '_specs_ridge_height_excl_floor'    => 'Ridge Height [Excl Floor]',
                '_specs_eaves_height_internal'      => 'Eaves Height [Internal]',
                '_specs_door_opening_size_w_x_h'    => 'Door Opening Size [H x W]',
                '_specs_ridge_height_internal'      => 'Ridge Height [Internal]',
                '_specs_width_internal'             => 'Width [Internal]',
                '_specs_depth_internal'             => 'Depth [Internal]',
                '_specs_door_height'                => 'Door Height',
                '_specs_door_width'                 => 'Door Width',
                '_specs_cladding_thickness'         => 'Cladding Thickness',
                '_specs_window_dimensions_w_x_h'    => 'Window Dimensions [W x H]',
                '_specs_glazing_thickness'          => 'Glazing Thickness',
                '_specs_frame_thickness_h_x_w'      => 'Frame Thickness [H x W]',
                '_specs_overall_floor_size_w_x_d'   => 'Overall Floor Size [W x D]',
                '_specs_base_size_w_x_d'            => 'Base Size [W x D]'
            );

            foreach ($arr as $key => $value) {
                woocommerce_wp_text_input(array(
                    'id'        => $key,
                    'label'     => __($value, 'woocommerce'),
                    'value'     => get_post_meta($thepostid, $key, true)
                ));
            }

            ?>
    </div>

    <div class='options_group block-dimension'>

        <h4>Materials</h4>

        <?php

            $arr = array(
                '_specs_floor_material'         => 'Floor Material',
                '_specs_material'               => 'Material',
                '_specs_roof_material'          => 'Roof Material',
                '_specs_roof_covering_material' => 'Roof Covering Material',
                '_specs_glazing_material'       => 'Glazing Material'
            );

            foreach ($arr as $key => $value) {
                woocommerce_wp_text_input(array(
                    'id'        => $key,
                    'label'     => __($value, 'woocommerce'),
                    'value'     => get_post_meta($thepostid, $key, true)
                ));
            }
            ?>
    </div>

    <div class='options_group block-dimension'>

        <h4>Features</h4>

        <?php

            $arr = array(
                '_specs_windows'                                => 'Windows',
                '_specs_shed_type'                              => 'Shed Type',
                '_specs_billyOh_range'                          => 'Project Timber Range',
                '_specs_roof_style'                             => 'Roof Style',
                '_specs_supplied_with_fixtures_and_fittings'    => 'Supplied with Fixtures and Fittings',
                '_specs_cladding_style'                         => 'Cladding Style',
                '_specs_locking_system'                         => 'Locking System',
                '_specs_interchangeable_windows'                => 'Interchangeable Windows?',
                '_specs_factory_basecoat_treatment'             => 'Factory Basecoat Treatment',
                '_specs_pre_assembled_side_panels'              => 'Pre-Assembled Side Panels',
                '_u_values_of_metal_roof'                       => 'U-Values of Metal Roof',
                '_u_values_of_walls_and_floor'                  => 'U-Values of Walls and Floor',
                '_u_values_of_walls_and_floor1'                  => 'Custom fields floor',

            );

            foreach ($arr as $key => $value) {
                woocommerce_wp_text_input(array(
                    'id'        => $key,
                    'label'     => __($value, 'woocommerce'),
                    'value'     => get_post_meta($thepostid, $key, true)
                ));
            }

            ?>

    </div>
</div> <?php
        }
        add_action('woocommerce_product_data_panels', 'tech_specs_tabs_content');


        function tech_specs_save_variable_fields($post_id)
        {

            $arr = array(
                '_specs_overall_width',
                '_specs_overall_depth',
                '_specs_total_wall_thickness',
                '_specs_eaves_height_excl_floor',
                '_specs_width',
                '_specs_depth',
                '_specs_ridge_height_excl_floor',
                '_specs_eaves_height_inc_floor',
                '_specs_ridge_height_inc_floor',
                '_specs_eaves_height_internal',
                '_specs_ridge_height_internal',
                '_specs_door_opening_size_w_x_h',
                '_specs_door_width',
                '_specs_cladding_thickness',
                '_specs_window_dimensions_w_x_h',
                '_specs_glazing_thickness',
                '_specs_frame_thickness_h_x_w',
                '_specs_overall_floor_size_w_x_d',
                '_specs_base_size_w_x_d',
                '_specs_width_internal',
                '_specs_depth_internal',
                '_specs_door_height',
                '_specs_roof_material',
                '_specs_floor_material',
                '_specs_material',
                '_specs_roof_covering_material',
                '_specs_glazing_material',
                '_specs_cladding_style',
                '_specs_windows',
                '_specs_shed_type',
                '_specs_locking_system',
                '_specs_factory_basecoat_treatment',
                '_specs_pre_assembled_side_panels',
                '_specs_roof_style',
                '_specs_supplied_with_fixtures_and_fittings',
                '_specs_interchangeable_windows',
                '_specs_billyOh_range',
                '_u_values_of_metal_roof',
                '_u_values_of_walls_and_floor'
            );

            foreach ($arr as $key) {
                if (isset($_POST[$key])) {
                    update_post_meta($post_id, $key, stripslashes($_POST[$key]));
                }
            }
        }
        add_action('save_post', 'tech_specs_save_variable_fields', 10, 1);

        #END :: Technical Specifications

        # automation for pallet labels
        function print_label($orderid)
        {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.palletways.com/GetLabelsByConNo/' . $orderid . '?apikey=OGpHLBxOXd7j9PAScUbno%2FBYbZefIbSE3UXwY4Hcufc%3D',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: PORTAL=pc264eajob4hur0inq72ulak8o'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return file_put_contents('./pdflabel/' . $orderid . '.pdf', $response); // save the string to a file
        }

        function send_email($orderid)
        {

            // (A) EMAIL SETTINGS  
            $mailTo = 'carlos.tandal@projecttimber.co.uk';
            $mailSubject = "Palletways PDF Label " . $orderid;
            $mailMessage = "<strong>Palletways PDF Label</strong>";
            $mailAttach = "./pdflabel/$orderid.pdf";

            // (B) GENERATE RANDOM BOUNDARY TO SEPARATE MESSAGE & ATTACHMENTS
            $mailBoundary = md5(time());
            $mailHead = implode("\r\n", [
                "MIME-Version: 1.0",
                "Content-Type: multipart/mixed; boundary=\"$mailBoundary\""
            ]);

            // (C) DEFINE THE EMAIL MESSAGE
            $mailBody = implode("\r\n", [
                "--$mailBoundary",
                "Content-type: text/html; charset=utf-8",
                "",
                $mailMessage
            ]);

            // (D) MANUALLY ENCODE & ATTACH THE FILE
            $mailBody .= implode("\r\n", [
                "",
                "--$mailBoundary",
                "Content-Type: application/octet-stream; name=\"" . basename($mailAttach) . "\"",
                "Content-Transfer-Encoding: base64",
                "Content-Disposition: attachment",
                "",
                chunk_split(base64_encode(file_get_contents($mailAttach))),
                "--$mailBoundary--"
            ]);

            // (E) SEND
            return mail($mailTo, $mailSubject, $mailBody, $mailHead) ? "OK" : "ERROR";
        }


        if (!wp_next_scheduled('send_pallet_label_toemail')) {
            $time = strtotime('08:00:00');
            wp_schedule_event($time, 'daily', 'send_pallet_label_toemail');
        }
        add_action('send_pallet_label_toemail', 'send_cron_palletlabel');

        function send_cron_palletlabel()
        {

            $toAdmind = array('carlos.tandal@projecttimber.co.uk');
            $subjectAdmind = 'Pallet Cron Run!!!';
            $bodyAdmind = "==== send email ====";
            $headersAdmind = "MIME-Version: 1.0\n";
            $headersAdmind .= "Content-type: text/html; charset=utf-8\n";
            $headersAdmind .= "X-Mailer: PHP/" . phpversion();

            wp_mail($toAdmind, $subjectAdmind, $bodyAdmind, $headersAdmind);

            global $wpdb, $mini_totals, $trade_orders;

            $labels = $wpdb->get_results("SELECT posts.ID AS orderid, meta__order_ids.meta_value AS ordernum, posts.post_date AS post_date FROM wp_posts AS posts INNER JOIN wp_postmeta AS meta__order_ids ON (posts.ID = meta__order_ids.post_id AND meta__order_ids.meta_key = '_pip_invoice_number') INNER JOIN wp_postmeta AS meta__order_type ON posts.ID = meta__order_type.post_id LEFT JOIN wp_posts AS parent ON posts.post_parent = parent.ID WHERE posts.post_type IN ('shop_order_refund' , 'shop_order') AND posts.post_status IN ( 'wc-completed', 'wc-processing', 'wc-planned' ) AND posts.post_date >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00') AND posts.post_date < DATE_FORMAT( DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 23:59:59') AND ( ( meta__order_type.meta_key = '_order_type' AND meta__order_type.meta_value = 'Website' )) GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date) ORDER BY post_date ASC");

            foreach ($labels as $key => $label) {

                $check_label = get_post_meta($label->orderid, "label_sent_" . $label->ordernum, $label->orderid);
                $final_delivery = get_post_meta($label->orderid, "_final_delivery_date");

                if ($check_label && $check_label != 'sent' && $final_delivery[0]) {

                    $today = new DateTime(date("Y-m-d"));

                    $date_delivery = new DateTime($final_delivery[0]);
                    $interval = $today->diff($date_delivery);

                    if (file_exists("./pdflabel/.$label->orderid.pdf") && $interval->format('%a') == 2) {
                        send_email($label->ordernum);
                        update_post_meta($label->orderid, "label_sent_" . $label->ordernum, 'sent');

                        unlink("./pdflabel/" . $label->ordernum . ".pdf");
                    }
                }
            }
        }



        // email reports
        if (!wp_next_scheduled('send_profit_report18')) {
            $time = strtotime('18:00:00');
            wp_schedule_event($time, 'daily', 'send_profit_report18');
        }
        add_action('send_profit_report18', 'send_profit_report_action');

        if (!wp_next_scheduled('send_profit_report12')) {
            $time = strtotime('00:00:00');
            wp_schedule_event($time, 'daily', 'send_profit_report12');
        }
        add_action('send_profit_report12', 'send_profit_report_action');

        function query($type, $user_id = 0)
        {

            global $wpdb, $mini_totals, $trade_orders;

            $date_ranges = ['today', 'yesterday', 'month'];
            $result      = [];
            $total_order  = 0;

            foreach ($date_ranges as $date_range) {

                $sql = filter_by_date([
                    'type'          => $type,
                    'user_id'      => $user_id,
                    'date_range' => $date_range
                ]);

                $rows = $wpdb->get_results($sql);

                $final_total_sales = 0;
                $final_profit = 0;
                $final_total_cog = 0;

                foreach ($rows as $key => $row) {
                    $total_sales         = wc_format_decimal($row->total_sales, wc_get_price_decimals());
                    $total_shipping     = wc_format_decimal($row->total_shipping, wc_get_price_decimals());
                    $total_tax             = wc_format_decimal($row->total_tax, wc_get_price_decimals());
                    $total_shipping_tax = wc_format_decimal($row->total_shipping_tax, wc_get_price_decimals());
                    $total_cog             = wc_format_decimal($row->total_cog, wc_get_price_decimals());
                    $profit             = $total_sales - $total_shipping - $total_tax - $total_shipping_tax - $total_cog;

                    $final_total_sales += $total_sales;
                    $final_profit += $profit;

                    $total_order += $row->number_of_sales;
                }

                if ($user_id) {
                    if ($final_total_sales == 0 and 27 != $user_id) {
                        return false;
                    }
                }

                $final_total_sales = $final_total_sales;
                $final_profit        = $final_total_sales / 1.2;

                $result['Gross Sales'][$date_range][$final_total_sales] = $total_order;
                $result['Net Sales'][$date_range][$final_profit] = $total_order;
            }

            return $result;
        }

        function filter_by_date($data)
        {

            global $wpdb;

            $user_id = $data['user_id'];
            $type      = $data['type'];

            $order_type_join_query     = "";
            $order_type_where_query = "";

            if ($user_id) {
                $order_type_join_query  = " INNER JOIN {$wpdb->prefix}postmeta AS meta__customer_user ON posts.ID = meta__customer_user.post_id";
                $order_type_where_query = " AND ( ( meta__customer_user.meta_key   = '_customer_user' AND meta__customer_user.meta_value = {$user_id} ))";
            } elseif ($type <> 'Total') {
                $order_type_join_query  = " INNER JOIN {$wpdb->prefix}postmeta AS meta__order_type ON posts.ID = meta__order_type.post_id";
                $order_type_where_query = " AND ( ( meta__order_type.meta_key   = '_order_type' AND meta__order_type.meta_value = '{$type}' ))";
            }

            switch ($data['date_range']) {
                case 'week':
                    $start_date = 'DATE_FORMAT( DATE_ADD(NOW(), INTERVAL -7 DAY), \'%Y-%m-%d 00:00:00\')';
                    $end_date     = 'DATE_FORMAT( DATE_ADD(NOW(), INTERVAL -1 DAY), \'%Y-%m-%d 23:59:59\')';
                    break;

                case 'month':
                    $start_date = "'" . date('Y-m-01 00:00:00') . "'";
                    $end_date = "'" . date('Y-m-d 23:59:59') . "'";
                    break;

                case 'today':
                    $start_date = "'" . date('Y-m-d 00:00:00') . "'";
                    $end_date = "'" . date('Y-m-d 23:59:59') . "'";
                    break;

                case 'yesterday':
                    $start_date = 'DATE_FORMAT( DATE_ADD(NOW(), INTERVAL -1 DAY), \'%Y-%m-%d 00:00:00\')';
                    $end_date     = 'DATE_FORMAT( DATE_ADD(NOW(), INTERVAL -1 DAY), \'%Y-%m-%d 23:59:59\')';
                    break;

                default:
                    $start_date = "'" . date('Y-m-01 00:00:00') . "'";
                    $end_date = "'" . date('Y-m-d 23:59:59') . "'";
                    break;
            }

            $sql = "SELECT
            COUNT(meta__order_total.meta_value) AS number_of_sales,
            SUM(meta__order_total.meta_value) AS total_sales,
            SUM(meta__order_shipping.meta_value) AS total_shipping,
            SUM(meta__order_tax.meta_value) AS total_tax,
            SUM(meta__order_shipping_tax.meta_value) AS total_shipping_tax,
            posts.post_date AS post_date,
            (
                SELECT SUM(meta_value)
                FROM {$wpdb->prefix}postmeta
                WHERE meta_key = 'wc_cog_order_total_cost'
                AND post_id = posts.ID
            ) AS total_cog
        FROM
            {$wpdb->prefix}posts AS posts
                INNER JOIN
            {$wpdb->prefix}postmeta AS meta__order_total ON (posts.ID = meta__order_total.post_id
                AND meta__order_total.meta_key = '_order_total')
                INNER JOIN
            {$wpdb->prefix}postmeta AS meta__order_shipping ON (posts.ID = meta__order_shipping.post_id
                AND meta__order_shipping.meta_key = '_order_shipping')
                INNER JOIN
            {$wpdb->prefix}postmeta AS meta__order_tax ON (posts.ID = meta__order_tax.post_id
                AND meta__order_tax.meta_key = '_order_tax')
                INNER JOIN
            {$wpdb->prefix}postmeta AS meta__order_shipping_tax ON (posts.ID = meta__order_shipping_tax.post_id
                AND meta__order_shipping_tax.meta_key = '_order_shipping_tax')
            {$order_type_join_query}
                LEFT JOIN
            {$wpdb->prefix}posts AS parent ON posts.post_parent = parent.ID
        WHERE
            posts.post_type IN ('shop_order_refund' , 'shop_order')
            AND posts.post_status IN (
                'wc-completed',
                'wc-processing',
                'wc-planned',
                'wc-unplanned',
                'wc-palletways',
                'wc-rdm',
                'wc-delivery-date',
                'wc-account-collected',
                'wc-account-payment',
				'wc-transfer-to-nexus',
				'wc-amended-order'
            ) AND posts.post_date >= {$start_date} AND posts.post_date < {$end_date} {$order_type_where_query}
        GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)
        ORDER BY post_date ASC";

            return $sql;
        }

        function send_profit_report_action()
        {

            ob_start();

            $totalSales = [];
            $types           = ['Website', 'Phone', 'eBay [Consumer]', 'eBay [Phone]', 'Amazon'];
            $date_ranges = ['today', 'yesterday', 'month'];
            $trade_orders = ['Trade [Delivery]', 'Trade [Collection]'];
            $mini_totals = [];

            echo '<div class="top_heading" style="font-family: jr, Open Sans, sans-serif !important; font-weight:
    normal !important; color: #3b333d; font-size: 30px;
    letter-spacing: -1px; line-height: 30px; margin: 0; padding:
    0 0 10px !important;">
    <p style="margin: 10px 0; padding:
        0; font-family: jr, Open Sans, sans-serif !important; font-weight:
        normal !important; text-align: left; color: #3b333d;">Daily Sales Reporttt 
    </p>		
</div>';

            echo '<table style="width: 100%;"> <tbody>';

            foreach ($types as $type) {

                echo '<tr style="color:
        #3b333d; font-family: jr, Open Sans, sans-serif; font-size: 16px;
        line-height: 1.5em;">
        <td class="body_content" align="center" valign="top" style="color: #3b333d; font-family: jr, Open Sans, sans-serif
            !important; font-size: 16px; line-height: 1.5em;
            font-weight: normal !important; text-align: center; margin:
            0; padding: 30px 0 1em !important;">';

                echo '<td><h2 style="padding: 0; margin: 0;">' . $type . '</h2>';

                echo '<table style="padding-bottom: 2em; width: 60%;"> <tbody>';
                foreach (query($type) as $index => $sales_types) {
                    echo '<tr>';
                    echo "<td>";

                    echo "<strong>" . $index . "<strong>";
                    echo '<table style="padding-bottom: 1em; width: 520px;"> <tbody>';

                    foreach ($sales_types as $days => $sales_type) {

                        echo '<tr>';
                        echo "<td style='width: 130px;'>";
                        echo ucwords($days);
                        echo ":</td>";
                        echo "<td style='width: 130px;'><strong>£";
                        foreach ($sales_type as $price => $qty) {
                            echo number_format($price);
                            $totalSales[$index][$days]['price'] += $price;
                        }
                        echo "</strong></td>";
                        echo "<td style='width: 130px;'>";
                        echo '# of Orders:';
                        echo "</td>";
                        echo "<td style='width: 130px;'><strong>";
                        foreach ($sales_type as $price => $qty) {
                            echo $qty;
                            $totalSales[$index][$days]['qty'] += $qty;
                        }
                        echo "</strong></td>";
                        echo '</tr>';
                    }

                    echo '</tbody></table>';

                    echo "</td>";
                    echo '</tr>';
                }
                echo '</tbody></table>';


                echo '</td>';

                echo '</tr>';
            }

            echo '</tbody></table>';

            ?>

<?php
            echo '<table style="width: 100%;"> <tbody>';

            echo '<tr style="color:
#3b333d; font-family: jr, Open Sans, sans-serif; font-size: 16px;
line-height: 1.5em;">
<td class="body_content" align="center" valign="top" style="color: #3b333d; font-family: jr, Open Sans, sans-serif
!important; font-size: 16px; line-height: 1.5em;
font-weight: normal !important; text-align: center; margin:
0; padding: 0 !important;">';
    ?>

<h2 style="padding: 0; margin: 0; text-align: left;"> All Sales </h2>

<table style="padding-bottom: 2em; width: 60%; text-align: left;">
    <tbody>
        <tr>
            <td><strong>Gross Sales<strong>
                        <table style="padding-bottom: 1em; width: 520px;">
                            <tbody>
                                <tr>
                                    <td style="width: 130px;">Today:</td>
                                    <td style="width: 130px;">
                                        <strong>£<?php echo $totalSales['Gross Sales']['today']['price']; ?></strong>
                                    </td>
                                    <td style="width: 130px;"># of Orders:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Gross Sales']['today']['qty']; ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 130px;">Yesterday:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Gross Sales']['yesterday']['price']; ?></strong>
                                    </td>
                                    <td style="width: 130px;"># of Orders:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Gross Sales']['yesterday']['qty']; ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 130px;">Month:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Gross Sales']['month']['price']; ?></strong>
                                    </td>
                                    <td style="width: 130px;"># of Orders:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Gross Sales']['month']['qty']; ?></strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Net Sales<strong>
                        <table style="padding-bottom: 1em; width: 520px;">
                            <tbody>
                                <tr>
                                    <td style="width: 130px;">Today:</td>
                                    <td style="width: 130px;">
                                        <strong>£<?php echo $totalSales['Net Sales']['today']['price']; ?></strong>
                                    </td>
                                    <td style="width: 130px;"># of Orders:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Net Sales']['today']['qty']; ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 130px;">Yesterday:</td>
                                    <td style="width: 130px;">
                                        <strong>£<?php echo $totalSales['Net Sales']['yesterday']['price']; ?></strong>
                                    </td>
                                    <td style="width: 130px;"># of Orders:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Net Sales']['yesterday']['qty']; ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 130px;">Month:</td>
                                    <td style="width: 130px;">
                                        <strong>£<?php echo $totalSales['Net Sales']['month']['price']; ?></strong>
                                    </td>
                                    <td style="w
                            idth: 130px;"># of Orders:</td>
                                    <td style="width: 130px;">
                                        <strong><?php echo $totalSales['Net Sales']['month']['qty']; ?></strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
            </td>
        </tr>
    </tbody>
</table>

<?php

            echo '</td>';

            echo '</tr>';

            echo '</tbody></table>';

            $body = ob_get_clean();

            $to      = 'william.walton@projecttimber.co.uk, nigel.walton@projecttimber.co.uk, andrew.knowles@projecttimber.co.uk, sam.todd@projecttimber.co.uk, szegedi.szilard@projecttimber.co.uk, laurence.sembrano@projecttimber.co.uk, adrian.solomon@projecttimber.co.uk';
            //$to      = 'carlos.tandal@projecttimber.co.uk';
            $subject = "Daily sales report";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $headers[] = 'From: Project Timber <sales@projecttimber.com>';
            $headers[] = 'Reply-To: <sales@projecttimber.com>';

            wp_mail($to, $subject, $body, $headers);
        }

        if (!wp_next_scheduled('send_pending_report')) {
            $time = strtotime('18:00:00');
            wp_schedule_event($time, 'daily', 'send_pending_report');
        }
        add_action('send_pending_report', 'send_pending_report_action');

        function send_pending_report_action()
        {

            $initial_date = date("Y-m-d", strtotime("today"));
            $final_date = date("Y-m-d", strtotime("+1 day"));

            $list_of_orders = wc_get_orders(array(
                'limit'    => -1,
                'status'   => 'wc-pending',
                'date_created' => $initial_date . '...' . $final_date
            ));

            ob_start();

            echo '<table style="width: 100%; color: #3b333d; font-family: jr, Open Sans, sans-serif; font-size: 16px; line-height: 1.5em; margin: 0; padding: 0;"> <tbody>';

            echo '<tr>';
            echo '<td colspan="2">';
            echo "<h2>Pending Payment (" . $initial_date . ")</h2>";
            echo '</td>';
            echo '</tr>';

            foreach ($list_of_orders as $orders) {

                $order_data = $orders->get_data();
                $order = wc_get_order($order_data['id']);

                echo '<tr>';
                echo '<td>';
                echo "First Name";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['first_name'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Last Name";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['last_name'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Email";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['email'];;
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Phone";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['phone'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Postcode";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['postcode'];
                echo '</td>';
                echo '</tr>';

                foreach ($order->get_items() as $item_id => $item):

                    $item_data = $item->get_data();

                    echo '<tr>';
                    echo '<td>';
                    echo "Product Name";
                    echo '</td>';
                    echo '<td>';
                    echo $item_data['name'];
                    echo '</td>';
                    echo '</tr>';

                    break;

                endforeach;

                echo '<tr>';
                echo '<td style="border-bottom: 1px solid #ccc; padding-bottom: 1em;">';
                echo "Payment Type";
                echo '</td>';
                echo '<td style="border-bottom: 1px solid #ccc; padding-bottom: 1em;">';
                echo $order_data['payment_method_title'];
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            $body = ob_get_clean();

            $to      = 'bonnie.cable@projecttimber.co.uk, angela.ndlovu@projecttimber.co.uk, rory.mcadam@projecttimber.co.uk';
            $subject = "Pending Payment report";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $headers[] = 'From: Project Timber <sales@projecttimber.com>';
            $headers[] = 'Cc: william.walton@projecttimber.co.uk, andrew.knowles@projecttimber.co.uk, laurence@projecttimber.co.uk'; 
            $headers[] = 'Reply-To: <sales@projecttimber.com>';

            wp_mail($to, $subject, $body, $headers);
        }

        if (!wp_next_scheduled('send_failed_report')) {
            $time = strtotime('18:00:00');
            wp_schedule_event($time, 'daily', 'send_failed_report');
        }
        add_action('send_failed_report', 'send_failed_report_action');

        function send_failed_report_action()
        {

            $initial_date = date("Y-m-d", strtotime("today"));
            $final_date = date("Y-m-d", strtotime("+1 day"));

            $list_of_orders = wc_get_orders(array(
                'limit'    => -1,
                'status'   => 'wc-failed',
                'date_created' => $initial_date . '...' . $final_date
            ));

            ob_start();

            echo '<table style="width: 100%; color: #3b333d; font-family: jr, Open Sans, sans-serif; font-size: 16px; line-height: 1.5em; margin: 0; padding: 0;"> <tbody>';

            echo '<tr>';
            echo '<td colspan="2">';
            echo "<h2>Failed Payment (" . $initial_date . ")</h2>";
            echo '</td>';
            echo '</tr>';

            foreach ($list_of_orders as $orders) {

                $order_data = $orders->get_data();
                $order = wc_get_order($order_data['id']);

                echo '<tr>';
                echo '<td>';
                echo "First Name";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['first_name'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Last Name";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['last_name'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Email";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['email'];;
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Phone";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['phone'];
                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td>';
                echo "Postcode";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['postcode'];
                echo '</td>';
                echo '</tr>';

                foreach ($order->get_items() as $item_id => $item):

                    $item_data = $item->get_data();

                    echo '<tr>';
                    echo '<td>';
                    echo "Product Name";
                    echo '</td>';
                    echo '<td>';
                    echo $item_data['name'];
                    echo '</td>';
                    echo '</tr>';

                    break;

                endforeach;

                echo '<tr>';
                echo '<td style="border-bottom: 1px solid #ccc; padding-bottom: 1em;">';
                echo "Payment Type";
                echo '</td>';
                echo '<td style="border-bottom: 1px solid #ccc; padding-bottom: 1em;">';
                echo $order_data['payment_method_title'];
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            $body = ob_get_clean();

            $to      = 'noreply@projecttimber.co.uk, karolina.wozniak@projecttimber.co.uk, rob.sweeney@projecttimber.co.uk, laurence@projecttimber.co.uk, william.walton@projecttimber.co.uk, jasmin.felicitas@projecttimber.co.uk, josh.banyard@projecttimber.co.uk, karen.kirby@projecttimber.co.uk, nichole@projecttimber.co.uk,gary.stones@projecttimber.co.uk, craig.capps@projecttimber.co.uk,anne.smith@projecttimber.co.uk';
            //$to      = 'carlos.tandal@projecttimber.co.uk';
            $subject = "Failed Payment report";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $headers[] = 'From: Project Timber <sales@projecttimber.com>';
            $headers[] = 'Reply-To: <sales@projecttimber.com>';

            wp_mail($to, $subject, $body, $headers);
        }

        // RDM Report

        if (!wp_next_scheduled('send_rdm_report')) {
            $time = strtotime('18:00:00');
            wp_schedule_event($time, 'daily', 'send_rdm_report');
        }
        add_action('send_rdm_report', 'send_rdm_report_action');

        function send_rdm_report_action()
        {

            $initial_date = date("Y-m-d", strtotime("today"));
            $final_date = date("Y-m-d", strtotime("+1 day"));

            $list_of_orders = wc_get_orders(array(
                'limit'    => -1,
                'status'   => array('wc-rdmopen', 'wc-rdmenquiry', 'wc-rdmnotneeded', 'wc-rdmcaseclosed'),
                'date_created' => $initial_date . '...' . $final_date
            ));

            ob_start();

            echo '<table style="width: 100%; color: #3b333d; font-family: jr, Open Sans, sans-serif; font-size: 16px; line-height: 1.5em; margin: 0; padding: 0;"> <tbody>';

            echo '<tr>';
            echo '<td colspan="2">';
            echo "<h2>RDM Orders (" . $initial_date . ")</h2>";
            echo '</td>';
            echo '</tr>';

            foreach ($list_of_orders as $orders) {

                $order_data = $orders->get_data();
                $order = wc_get_order($order_data['id']);
                $order_original_ref_field = get_field('order_ref_original', $order_data['id']  );
                $order_original_ref=  $order_original_ref_field ? $order_original_ref_field : 'N/A';
                echo '<tr>';
                echo '<td>';
                echo "First Name";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['first_name'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Last Name";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['last_name'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Email";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['email'];;
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Phone";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['phone'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "Postcode";
                echo '</td>';
                echo '<td>';
                echo $order_data['billing']['postcode'];
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                echo "RDM TYPE";
                echo '</td>';
                echo '<td>';

                if ($order_data['status'] == 'rdmenquiry') {
                    $rdm_status = 'RDM - Enquiry';
                } else if ($order_data['status'] == 'rdmopen') {
                    $rdm_status = 'RDM Open';
                } else if ($order_data['status'] == 'caseopen') {
                    $rdm_status = 'Case Open';
                } else if ($order_data['status'] == 'resolvedmmfk') {
                    $rdm_status = 'Resolved - MFK';
                } else if ($order_data['status'] == 'rdmnotneeded') {
                    $rdm_status = 'RDM Not Needed';
                } else if ($order_data['status'] == 'resolvedmmpk') {
                    $rdm_status = 'Resolved - MPK';
                } else if ($order_data['status'] == 'resolvedmss') {
                    $rdm_status = 'Resolved - MS';
                } else if ($order_data['status'] == 'resolvedmrt') {
                    $rdm_status = 'Resolved - MRT';
                } else if ($order_data['status'] == 'resolvedmgt') {
                    $rdm_status = 'Resolved - MGT';
                } else if ($order_data['status'] == 'resolvedmb') {
                    $rdm_status = 'Resolved - MB';
                } else if ($order_data['status'] == 'resolvedmgl') {
                    $rdm_status = 'Resolved - MGL';
                } else if ($order_data['status'] == 'resolvedmrr') {
                    $rdm_status = 'Resolved - MRR';
                } else if ($order_data['status'] == 'resolvedmf') {
                    $rdm_status = 'Resolved - MF';
                } else if ($order_data['status'] == 'resolvedmp') {
                    $rdm_status = 'Resolved - MP';
                } else if ($order_data['status'] == 'resolvedcrf') {
                    $rdm_status = 'Resolved - CRF';
                } else if ($order_data['status'] == 'resolvedqu') {
                    $rdm_status = 'Resolved - QU';
                } else if ($order_data['status'] == 'resolvedip') {
                    $rdm_status = 'Resolved - IP';
                } else if ($order_data['status'] == 'resolvedfd') {
                    $rdm_status = 'Resolved - FD';
                } else if ($order_data['status'] == 'resolvedmk') {
                    $rdm_status = 'Resolved - MK';
                } else if ($order_data['status'] == 'resolvedds') {
                    $rdm_status = 'Resolved - DS';
                } else if ($order_data['status'] == 'resolvedrt') {
                    $rdm_status = 'Resolved - DRT';
                } else if ($order_data['status'] == 'resolvedgt') {
                    $rdm_status = 'Resolved - DGT';
                } else if ($order_data['status'] == 'resolveddb') {
                    $rdm_status = 'Resolved - DB';
                } else if ($order_data['status'] == 'resolvedlg') {
                    $rdm_status = 'Resolved - DLG';
                } else if ($order_data['status'] == 'resolvedrr') {
                    $rdm_status = 'Resolved - DF';
                } else if ($order_data['status'] == 'resolveddf') {
                    $rdm_status = 'Resolved - DF';
                } else if ($order_data['status'] == 'resolveddp') {
                    $rdm_status = 'RResolved - DP';
                } else if ($order_data['status'] == 'investigation') {
                    $rdm_status = 'Investigation';
                }

                echo $rdm_status;

                echo '</td>';
                echo '</tr>';

                foreach ($order->get_items() as $item_id => $item):

                    $item_data = $item->get_data();

                    echo '<tr>';
                    echo '<td>';
                    echo "Product Name";
                    echo '</td>';
                    echo '<td>';
                    echo $item_data['name'];
                    echo '</td>';
                    echo '</tr>';

                    break;

                endforeach;

                echo '<tr>';
                echo '<td style="border-bottom: 1px solid #ccc; padding-bottom: 1em;">';
                echo "Original order number";
                echo '</td>';
                echo '<td style="border-bottom: 1px solid #ccc; padding-bottom: 1em;">';
                echo $order_original_ref;
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            $body = ob_get_clean();

            $to      = 'william.walton@projecttimber.co.uk, nigel.walton@projecttimber.co.uk, sam.todd@projecttimber.co.uk, andrew.knowles@projecttimber.co.uk, paul.payne@projecttimber.co.uk, care@projecttimber.co.uk, matt.weir@projecttimber.co.uk, kitshop@projecttimber.co.uk, assembly@projecttimber.co.uk, adrian.solomon@projecttimber.co.uk';
            $subject = "RDM Order report";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $headers[] = 'From: Project Timber <sales@projecttimber.com>';
            $headers[] = 'Reply-To: <sales@projecttimber.com>';

            wp_mail($to, $subject, $body, $headers);
        }

        // Adjust 65% of price in bundle only
        function adjust_composite_product_price_display($price, $product)
        {

            // Get the product title
            $product_title = $product->get_title();
            $discount_coeficient = get_field('add_parts_discount_coeficient', 'option') ?? 1;
            // Define the pattern to match titles like '8 x 8', '12 x 8', '12 x 10'
            $pattern = '/\b(\d+)\s*x\s*(\d+)\b/';

            if (empty($price)) {
                $price = 0;
            }

            if ($product->is_type('bundle') && !preg_match($pattern, $product_title)) {
                // Increase sub option price by discount in percentage
                // $adjusted_price = $price *  $discount_coeficient;
                $adjusted_price = $price;

                // Round to the nearest whole number
                $rounded_price = round($adjusted_price);

                // Return the rounded price
                return $rounded_price;
            }

            // Return the original price if not a bundle product
            return $price;
        }

        // If your composite products can be on sale, you might also want to apply the filter to the sale price
        //add_filter( 'woocommerce_product_get_sale_price', 'adjust_composite_product_price_display', 10, 2 );
        //add_filter( 'woocommerce_composite_base_price', 'adjust_composite_product_price_display', 10, 2 );
        //add_filter( 'woocommerce_composite_total_price', 'adjust_composite_product_price_display', 10, 2 );
        add_filter('woocommerce_product_get_price', 'adjust_composite_product_price_display', 10, 2);
        add_filter('woocommerce_product_get_regular_price', 'adjust_composite_product_price_display', 10, 2);

        // function adjust_completed_order_bundle_prices($order_id)
        // {
        //     // Getting the order object
        //     $order = wc_get_order($order_id);

        //     // Check if the order is made today or later
        //     $order_date = date('Y-m-d', strtotime($order->get_date_created()));
        //     $today_date = '2023-11-01';

        //     if ($order_date >= $today_date) :

        //         // Loop through each product in the order
        //         foreach ($order->get_items() as $item_id => $item) {
        //             // Load the product
        //             $product = $item->get_product();

        //             // Define the pattern to match titles like '8 x 8', '12 x 8', '12 x 10'
        //             $pattern = '/\b(\d+)\s*x\s*(\d+)\b/';

        //             // Check if the product is a bundle and the title doesn't match the pattern
        //             if ($product->is_type('bundle') && !preg_match($pattern, $product->get_name())) {
        //                 // Calculate the new price
        //                 $new_price = $item->get_subtotal() * 1.33;

        //                 // Set the new price
        //                 $item->set_subtotal($new_price);
        //                 $item->set_total($new_price);

        //                 // Save the item
        //                 $item->save();
        //             }
        //         }

        //         // Save the order
        //         $order->calculate_totals();

        //     endif;
        // }
        //add_action('woocommerce_order_status_completed', 'adjust_completed_order_bundle_prices', 10, 1);

        function adjust_planned_order_send_pdf($order_id)
        {

            global $wpdb;
            $order = wc_get_order($order_id);

            $isMyDen                 = false;
            $isCannes                 = false;
            $isAlpine                = false;

            $final_delivery_date_raw = $order->get_meta('_final_delivery_date', true);
            if (empty($final_delivery_date_raw)) {
                $order->add_order_note('Delivery email failed, final delivery date is not set.', false, true);
                return; // Exit early if no delivery date set
            }

            $final_delivery_date = date_create($final_delivery_date_raw);
            $final_delivery_date = $final_delivery_date->format('l j F Y');

            $billing_first_name        = $order->get_billing_first_name();
            $billing_last_name        = $order->get_billing_last_name();

            $query_items = 'SELECT * FROM wp_woocommerce_order_items WHERE order_id = "' . $order->get_id() . '"';
            $meta_items = $wpdb->get_results($query_items, OBJECT);

            $items = $order->get_items();

            foreach ($items as $item) {

                $productid = $item['product_id'];

                if (preg_match("/\bmy den\b/i", $item['name'], $matches)) {
                    $isMyDen = true;
                }

                if (preg_match("/\bcanne\b/i", $item['name'], $matches)) {
                    $isCannes = true;
                }

                if (preg_match("/\balpine\b/i", $item['name'], $matches)) {
                    $isAlpine = true;
                }

                break;
            }

            $product_id = 0;

            foreach ($meta_items as $indxitem => $item) {

                if ($indxitem == 1) {
                    $product_size = $item->order_item_name;
                    $order_item_id = $item->order_item_id;

                    $order_query_items = "SELECT meta_value AS product_id FROM wp_woocommerce_order_itemmeta WHERE meta_key =  '_product_id' AND order_item_id = " . $order_item_id;
                    $order_meta_items = $wpdb->get_results($order_query_items, OBJECT);

                    $product_id = $order_meta_items[0]->product_id;

                    $pdf = get_field('evolution_pdf_instruction', $product_id);
                    //$evo_pdfLink = $pdf[0]['pdf'];
                    $evo_pdfLink = $pdf[0]['flipbook'];
                }

                if (strpos(strtolower($item->order_item_name), 'upvc') !== false) {
                    $upvc_pdf = get_field('evolution_upvc_pdf_instruction', $product_id);
                    //$evo_upvc_pdfLink = $upvc_pdf[0]['pdf'];
                    $evo_upvc_pdfLink = $upvc_pdf[0]['flipbook'];
                    break;
                }
            }

            $size = explode(' ', $product_size);

            $pdfs = get_field('pdf_instruction_gables', $productid);


            foreach ($pdfs as $instruction) {
                $idx = $isAlpine ? 0 : 2;
                if ($size[$idx] == $instruction['size_gable']) {
                    //$pdfLink = $instruction['pdf'];
                    $pdfLink = $instruction['flipbook'];
                    break;
                }
            }

            $to         = $order->get_billing_email();
            $subject    = "Project Timber's Delivery Update and Assembly Instructions";

            ob_start();
?>

<!DOCTYPE html>
<html dir="ltr">

	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Project Timber<img src="https://s.w.org/images/core/emoji/17.0.2/72x72/2122.png" alt="™" class="wp-smiley" style="height: 1em; max-height: 1em;" /></title>

		<!--[if mso]>
		<style type="text/css">
		body, table, td { font-family: Arial, sans-serif !important; }
		</style>
		<![endif]-->

		

		<!--
			NOTE: Google Fonts via <link> are stripped by most email clients including
			Gmail web. Work Sans is used here as a best-effort for clients that support
			it (Apple Mail, Outlook macOS). All other clients will fall back to Arial.
			Do NOT rely on web fonts for layout or sizing.
		-->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,100..900;1,100..900&amp;display=swap" rel="stylesheet">

	<style type="text/css">@media screen and (max-width: 600px){#body_content table > tbody > tr > td{padding: 10px !important;}#body_content_inner{font-size: 10px !important;}}.component_table_item_subtotal:after{display: inline-block; width: 1em; height: 1em; background: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzODQgNTEyIj48cGF0aCBkPSJNMzM2LjEgMzc2LjFsLTEyOCAxMjhDMjA0LjMgNTA5LjcgMTk4LjIgNTEyIDE5MS4xIDUxMnMtMTIuMjgtMi4zNDQtMTYuOTctNy4wMzFsLTEyOC0xMjhjLTkuMzc1LTkuMzc1LTkuMzc1LTI0LjU2IDAtMzMuOTRzMjQuNTYtOS4zNzUgMzMuOTQgMEwxNjggNDMwLjFWNDhoLTE0NEMxMC43NSA0OCAwIDM3LjI1IDAgMjRTMTAuNzUgMCAyNCAwSDE5MmMxMy4yNSAwIDI0IDEwLjc1IDI0IDI0djQwNi4xbDg3LjAzLTg3LjAzYzkuMzc1LTkuMzc1IDI0LjU2LTkuMzc1IDMzLjk0IDBTMzQ2LjMgMzY3LjYgMzM2LjEgMzc2LjF6Ii8+PC9zdmc+"); background-repeat: no-repeat; background-position: right; background-size: contain; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; transform: rotate(90deg); content: ""; margin: 0 0 0 8px; opacity: .25;}@media only screen and (max-width: 620px){#template_container,#template_header_table{width: 100% !important;}.header-logo-cell{padding: 16px 16px 0 16px !important;}.header-icons-cell{padding: 16px 16px 0 16px !important; text-align: left !important;}.social-icon{margin-right: 8px !important;}}.component_table_item_subtotal:after{display: none !important;}</style></head>

	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style='text-align: center; font-family: "Work Sans",Arial,sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; width: 100%;' bgcolor="#f5f5f5" width="100%">

		<!-- Wrapper table — full width background -->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" id="bodyTable" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f5f5f5; display: table; margin: 0; padding: 0; width: 100%;" bgcolor="#f5f5f5">
			<tr>
				<td align="center" valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 0;'>

					<!-- Main container: 600px wide -->
					<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; border: none; max-width: 600px; box-shadow: 0 1px 4px rgba(0,0,0,.1); border-radius: 3px;" bgcolor="#ffffff">
					<tbody style="">
						<!-- ===== HEADER ROW ===== -->
						<tr>
							<td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 24px 0 24px;'>

								<!--
									Header bar: logo left, social icons right.
									We use a nested table instead of flexbox/div layout
									because flexbox is not supported in email clients.
									background-color is a solid yellow fallback — gradients
									and border-radius are ignored by Outlook.
								-->
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header_table" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffff00; border-radius: 12px;" bgcolor="#ffff00">
									<tr>

										<!-- Logo cell -->
										<td valign="middle" class="header-logo-cell" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 16px 0 16px 20px; width: auto;'>
                                            <a href="https://projecttimber.com/">
											    <img src="https://www.projecttimber.com/wp-content/uploads/2026/04/ProjectTimber-Logo-2-1.png" alt="Project Timber&#x2122;" width="150" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; text-decoration: none; display: block; max-width: 150px; height: auto; border: 0; outline: none;" border="0">										
                                            </a>
                                        </td>

										<!-- Social icons cell — right-aligned -->
										<td valign="middle" align="right" class="header-icons-cell" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 16px 20px 16px 0;'>

											<!--
												Icons in a table row so spacing is consistent
												across all clients (gap/flex not supported).
											-->
											<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; display: inline-table;">
												<tr>
													<td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 10px 0 0;'>
														<a href="https://www.facebook.com/projecttimber" style="color: #454530; font-weight: normal; text-decoration: none;">
                       										 <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/fb-v2.svg" width="24" height="24" class="social-icon" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; text-decoration: none; display: block; border: 0; outline: none; width: 24px; height: 24px;" border="0">
														</a>
													</td>
													<td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 10px 0 0;'>
														<a href="https://www.youtube.com/@projecttimber" style="color: #454530; font-weight: normal; text-decoration: none;">
															 <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/youtube-v2.svg" width="24" height="24" class="social-icon" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; text-decoration: none; display: block; border: 0; outline: none; width: 24px; height: 24px;" border="0">
														</a>
													</td>
													<td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
														<a href="https://www.instagram.com/projecttimber/" style="color: #454530; font-weight: normal; text-decoration: none;">
															 <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/insta-v2.svg" width="24" height="24" class="social-icon" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; text-decoration: none; display: block; border: 0; outline: none; width: 24px; height: 24px;" border="0">
														</a>
													</td>
												</tr>
											</table>

										</td>
									</tr>
								</table>

							</td>
						</tr>
						<!-- ===== END HEADER ROW ===== -->

						<!-- ===== BODY ROW ===== -->
						<tr>
							<td align="center" valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px;'>
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; max-width: 600px;">
									<tr>
										<td valign="top" id="body_content" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; background-color: #fff;' bgcolor="#fff">
											<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
												<tr>
													<td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px;'>
														<div id="body_content_inner" style="text-align: left; font-family: 'Work Sans', Arial, sans-serif; color: #3B333D; font-size: 16px; line-height: 1.6;" align="left">
<!--
    EMAIL COMPATIBILITY FIXES:
    1. <style> block removed — Gmail strips it entirely. All styles are inline.
    2. linear-gradient removed — not supported in Outlook. Replaced with solid
       hex fallback #efffcc (closest neutral to the original green-yellow blend).
    3. rgba() backgrounds replaced with solid hex equivalents.
    4. <div> layout replaced with <table> structure.
    5. Order label pill: <span> inside a centered <td> — reliable cross-client
       pill shape using padding + border-radius on the <td>.
    6. font-style: normal added on address-like content to prevent iOS Mail
       from auto-italicising detected address strings.
-->



<!-- <p>&nbsp;</p> -->


<!-- ===== INTRO CARD ===== -->
<table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: linear-gradient(73deg, rgba(178, 255, 157, 0.30) 0.12%, rgba(255, 255, 157, 0.30) 54.74%, rgba(255, 255, 0, 0.30) 109.01%); border-radius: 20px;" bgcolor="linear-gradient(73deg,">
                <tr>
                    <td align="center" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 24px 0 24px;'>
                        <!-- Order number pill -->
                        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto; display: inline-table;" align="center">
                            <tr>
                                <td align="center" style="background-color: #ffff00; border-radius: 99px; padding: 4px 24px; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #3B333D; line-height: 24px; white-space: nowrap;" bgcolor="#ffff00">
                                    ORDER <?php echo esc_html( $order->get_order_number() ); ?>  DATE CONFIRMATION
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 16px 24px 0 24px;'>
                        <h1 style="text-align: left; text-shadow: 0 1px 0 #6a6a59; margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 28px; font-weight: 700; color: #3B333D; line-height: 1.3;">
                            Your Delivery is Confirmed.
                        </h1>
                    </td>
                </tr>
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 24px 0 24px; min-height: 100px;'>
                        <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; color: #3B333D; line-height: 1.6; font-style: normal;">                 
                            <?php printf( __( 'Get ready, <span>%s</span>. The delivery is scheduled.', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?>
                        </p>                                
                    </td>
                </tr>
                
            </table>
        </td>
    </tr>
</table>
<!-- ===== END INTRO CARD ===== -->
<div style="width: 100%; text-align: center; padding: 24px 0; margin-top: 24px; background-color: #3B333D; border-radius: 20px; ">
    <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; line-height: 24px; letter-spacing: 0.05em;">
        CONFIRMED DELIVERY DATE
    </p>
    <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 2.3rem; font-weight: 600; color: #ffff00; line-height: 1.2;">
        <?php echo $final_delivery_date ?? ''; ?>                        
    </p>
    <p style="margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; color: #ffffff; line-height: 1.6;">
        Window: 8:00 AM – 6:00 PM
    </p>
</div>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr style="padding: 0;">
        <td class="order_items_table_holder" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0px;'>
            <table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 auto; max-width: 600px; text-align: center; font-family: 'Work Sans', sans-serif; border-spacing: 0; border-collapse: collapse; padding:0;" align="center">
              
                <!-- Delivery Roadmap Card Row -->
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 0;'>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f4f3f4; border-radius: 20px;" bgcolor="#f4f3f4">
                            <tr>
                                <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px;'>
                                    <!-- Heading -->
                                    <p style="margin: 0 0 5px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 20px; font-weight: 600; color: #3B333D; line-height: 28px; text-align: left;" align="left">
                                        Delivery Rules
                                    </p>
                                    <!-- Item 1 — active -->
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-bottom: 1px solid #e0dde0; text-align: left;">
                                        <tr>
                                            <td valign="top" width="36" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 12px 12px 0; text-align: center;' align="center">
                                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24" style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0;" bgcolor="#ffff99">
                                                            1
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 0 12px 0; text-align: left;' align="left">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Kerbside Only:</p>
                                                <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">The delivery vans may be large, so please let us know of any access requirements.</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- Item 2 -->
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-bottom: 1px solid #e0dde0;">
                                        <tr>
                                            <td valign="top" width="36" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 12px 12px 0; text-align: left;' align="left">
                                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24" style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0;" bgcolor="#ffff99">
                                                            2
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 0 12px 0; text-align: left;' align="left">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Pallet Delivery:</p>
                                                <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">Your building is constructed from manageable-sized pieces for easy transportation from the pallet to your property; however, you may wish to seek assistance with this.</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- Item 3 — no border -->
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                        <tr>
                                            <td valign="top" width="36" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 12px 0 0; text-align: left;' align="left">
                                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24" style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0;" bgcolor="#ffff99">
                                                            3
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 0 0 0; text-align: left;' align="left">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Signature Required:</p>
                                                <p style="margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">If you are unable to receive your delivery, please inform us as soon as possible. It is important that the delivery is signed for.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding-bottom: 24px;'>
                        <a href="https://www.projecttimber.com/" >
                            <img src="https://www.projecttimber.com/wp-content/uploads/2026/05/email-section-check-info.png" alt="Project Timber" width="100%" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; text-decoration: none; display: block; width: 100%; max-width: 600px; height: auto; border: 0; outline: none;" border="0">
                        </a>
                    </td>
                </tr>
                   <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding-bottom: 24px;'>
                        <a href="https://www.projecttimber.com/" >
                            <img src="https://www.projecttimber.com/wp-content/uploads/2026/05/email-section-info.png" alt="Project Timber" oncontextmenu="return false;" width="100%" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; text-decoration: none; display: block; width: 100%; max-width: 600px; height: auto; border: 0; outline: none;" border="0">
                        </a>
                        </td>
                </tr>
                   <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
                        <a href="https://www.projecttimber.com/building-base-garden-building/" >
                            <img src="https://www.projecttimber.com/wp-content/uploads/2026/05/email-section-building-base.png" alt="Project Timber" width="100%" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; text-decoration: none; display: block; width: 100%; max-width: 600px; height: auto; border: 0; outline: none;" border="0">
                        </a>
                    </td>
                </tr>
                <!-- Customer Order Number Row (Conditional) -->
                            </table>

                            </td>
                        </tr>
                    </table>
   
</div></td>
</tr>
<!-- Footer -->
<tr>
    <td width="100%" align="center" bgcolor="#ffffff" style="font-size: 15px; padding: 20px 20px 30px; background-color: #ffffff; font-family: 'Work Sans', Arial, sans-serif;">

        <table align="center" cellpadding="0" cellspacing="0" border="0" width="560" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
            <tr>
                <td align="center" bgcolor="#ffff00" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; background-color: #ffff00; border-radius: 16px; padding: 30px 30px 24px;'>

                    <!-- Logo -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 0 16px 0;'>
                                <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/logo-email-footer.svg" alt="Project Timber" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; text-decoration: none; display: block; height: auto; max-width: 100%; border: 0; outline: none;">
                            </td>
                        </tr>
                    </table>

                    <!-- Social icons -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 0 20px 0;'>
                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                    <tr>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.facebook.com/projecttimber" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/fb-v2.svg" alt="Facebook" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://twitter.com/project_timber" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/x-v2-icon.svg" alt="X / Twitter" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.pinterest.co.uk/projecttimberltd/" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/pinterest-v2.svg" alt="Pinterest" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.instagram.com/projecttimber/" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/insta-v2.svg" alt="Instagram" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.youtube.com/@projecttimber" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/youtube-v2.svg" alt="YouTube" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Support message -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style="padding: 0 10px 20px; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #333333; line-height: 1.6; text-align: center;">
                                If you have any questions, feedback, or concerns regarding your purchase or anything else, please don't hesitate to reach out to us. We're here to assist you every step of the way.
                            </td>
                        </tr>
                    </table>

                    <!--
                        Contact info row.
                        FIXED: removed display:flex from <tr> and display:inline-flex + gap
                        from <td> — both are ignored by Outlook and Gmail web, causing the
                        icon and text to stack vertically.
                        Each contact item is now a nested two-cell table (icon | text) so
                        they sit side by side reliably in every client.
                    -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>

                            <!-- Email contact -->
                            <td align="center" valign="middle" width="50%" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto;" align="center">
                                    <tr>
                                        <td valign="middle" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding:0;'>
                                            <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/envelope.svg" alt="" width="18" oncontextmenu="return false;" height="18" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 12px; height: 12px;">
                                        </td>
                                        <td valign="middle" style="padding-right: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; white-space: nowrap;">
                                            <a href="mailto:care@projecttimber.co.uk" style="color: #333333; text-decoration: none; font-weight: bold;">
                                                care@projecttimber.co.uk
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>

                            <!-- Phone contact -->
                            <td align="center" valign="middle" width="50%" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto;" align="center">
                                    <tr>
                                        <td valign="middle" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
                                        <a href="https://www.projecttimber.com" target="_blank" style="color: #333333; text-decoration: underline; font-weight: normal;">
                                            <img src="https://projecttimber.com/wp-content/themes/theTimber/assets/images/social-logos/v2/phone.svg" alt="" width="12" height="12" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 12px; height: 12px;">
                                        </a>
    
                                    </td>
                                        <td valign="middle" style=" font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; font-weight: bold; white-space: nowrap;">
                                            01777 801215
                                        </td>
                                    </tr>
                                </table>
                            </td>

                        </tr>
                    </table>

                    <!-- Terms & copyright -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style="padding: 12px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; line-height: 1.6; text-align: center;">
                                <p style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; margin: 0 0 6px;'>
                                    Please review our
                                    <a href="https://www.projecttimber.com/terms/" target="_blank" style="color: #333333; text-decoration: underline; font-weight: normal;">
                                        Terms and Conditions
                                    </a>
                                    for more details about your purchase.
                                </p>
                                <p style='font-family: "Work Sans",Arial,sans-serif; margin: 0; font-size: 13px; color: #333333;'>
                                    Project Timber © 2026                                
                                </p>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

    </td>
</tr>
<!-- / Footer --></table></td></tr></table></td></tr></tbody></table></td></tr></table></body></html>

<?php

            $body = ob_get_clean();

            $headers = array('Content-Type: text/html; charset=UTF-8');
            $headers[] = 'From: Project Timber <deliveries@projecttimber.co.uk>';
            $headers[] = 'Reply-To: <deliveries@projecttimber.co.uk>';
            $headers[] = 'Bcc: deliveries@projecttimber.co.uk, samuel.weeks@projecttimber.co.uk, Richard.Charnock@projecttimber.co.uk, LS@projecttimber.co.uk, william.walton@projecttimber.co.uk';

            if ($final_delivery_date) :
                if (wp_mail($to, $subject, $body, $headers)) {
                    $order->add_order_note('Delivery update email notification sent.', false, true);
                    return 1;
                    } else {
                        $order->add_order_note('🚫 Delivery email failed to send.');
                    }
            else :
                $order->add_order_note('🚫 Delivery email not sent: _final_delivery_date is not set for this order.');
            endif;
        }

        add_action('woocommerce_order_status_planned', 'adjust_planned_order_send_pdf', 10, 1);
        add_action('woocommerce_order_status_palletways', 'adjust_planned_order_send_pdf', 10, 1);
   


        add_filter('wpseo_sitemap_entry', function ($url, $type, $object) {
            if ('post' === $type && 'product' === get_post_type($object)) {
                $product = wc_get_product($object);
                if ($product && ('bundle' === $product->get_type() || 'simple' === $product->get_type())) {
                    return false;
                }
            }
            return $url;
        }, 10, 3);


        function hide_delete_note_from_edit_order()
        {
            $screen = get_current_screen();
            if ($screen->post_type === "shop_order" && $screen->base === "post") {
                echo '<style>a.delete_note { display:none; }</style>';
            }
        }

        add_action('admin_head', 'hide_delete_note_from_edit_order');

        add_filter('woocommerce_composite_component_default_orderby', function ($orderby) {
            return 'price';
        });


        add_action('wp_ajax_product_gallery_tab', 'get_product_gallery_tab');
        add_action('wp_ajax_nopriv_product_gallery_tab', 'get_product_gallery_tab');

        function get_product_gallery_tab()
        {
            $ids = preg_split('/,/', preg_replace('/gallery/', '', $_POST['id']));
            $dynamic_sliders = get_field('dynamic_sliders', $ids[1]);

            //echo '<div class="single_product_slider">';
            echo '<div class="slider slider-for">';

            if ($ids[0] == 99) {
                $product = new WC_product($ids[1]);
                $attachment_ids = $product->get_gallery_image_ids();

                foreach ($attachment_ids as $attachment_id) {
                    echo wp_get_attachment_image($attachment_id, 'full');
                }
            } else {
                foreach ($dynamic_sliders[$ids[0]]['images'] as $image) {
                    echo '<div><img src="' . $image['image'] . '" class="attachment-full size-full"></div>';
                }
            }

            echo '</div>';
            echo '<div class="slider slider-nav">';


            if ($ids[0] == 99) {
                foreach ($attachment_ids as $attachment_id) {
                    echo wp_get_attachment_image($attachment_id, 'full');
                }
            } else {
                foreach ($dynamic_sliders[$ids[0]]['images'] as $image) {
                    echo '<div><img src="' . $image['image'] . '" class="attachment-full size-full"></div>';
                }
            }

            echo '</div>';
            // echo '</div>';

            wp_die();
        }

        function search_consigment_order($orderid, $tokenid)
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://rest-api-nexus.pallex.com/v1/consignments?statusID=18&searchterm=' . $orderid,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $tokenid
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return json_decode($response);
        }

        // Product: format  weight, 0.2->0.200  and 0.12->0.120
        add_filter('woe_get_order_product_value_download_url', function ($value, $order, $item, $product, $item_meta) {

            // Get order items
            $pdf_link = '';

            $kits = get_field('kits', $item_meta['_product_id'][0]);

            if ($kits) {

                foreach ($kits as $kit) {
                    $url[$item->get_product_id()][] = $kit['kit'];
                }

                foreach ($url as $prodid => $kit_pdf) {

                    $product = wc_get_product($prodid);
                    $cntIndex = 0;

                    foreach ($kit_pdf as $index => $pdf) {
                        $cntIndex++;
                        $pdf_link .= $pdf . ($cntIndex == count($kit_pdf) ? "" : "\r\n");
                    }
                }

                return $value . "\r\n" . $pdf_link;
            } else {

                return $value;
            }
        }, 10, 5);

        /** kit downloadable **/
        add_action('woocommerce_admin_order_data_after_order_details', 'misha_editable_order_meta_general');

        function misha_editable_order_meta_general($order)
        {

            $order = wc_get_order($order);

            // Get order items
            $items = $order->get_items();

            foreach ($items as $item) {

                $kits = get_field('kits', $item->get_product_id());

                if ($kits) {
                    foreach ($kits as $kit) {
                        $url[$item->get_product_id()][] = $kit['kit'];
                    }
                }
            }
    ?>
<br class="clear" />
<h3>Kit Downloable</h3>
<?php
            foreach ($url as $prodid => $kit_pdf) {

                $product = wc_get_product($prodid);
                echo $product->get_title() . "<br/>";

                foreach ($kit_pdf as $pdf) {
                    echo "<a href='" . $pdf . "' target='_blank'>" . $pdf . "</a><br/>";
                }

                echo "<br/>";
            }
    ?>
<?php
        }

        add_filter('action_scheduler_retention_period', 'wpb_action_scheduler_purge');
        /**
         * Change Action Scheduler default purge to 1 week
         */
        function wpb_action_scheduler_purge()
        {
            return WEEK_IN_SECONDS;
        }


        // rmd form submission

        add_action("wp_ajax_submit_rdm_info", "submit_rdm_info_process");
        add_action("wp_ajax_nopriv_submit_rdm_info", "submit_rdm_info_process");

        function submit_rdm_info_process()
        {
            global $wpdb;

            $orderid = $_POST['id'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];

            $postid = $wpdb->get_results("SELECT m.post_id, p.post_status, m.meta_value FROM wp_postmeta as m JOIN wp_posts as p ON m.post_id = p.id WHERE m.meta_value = '" . $orderid . "' AND m.meta_key = '_order_number_formatted'")[0];
            $order = new WC_Order($postid->post_id);

            $order_data = $order->get_data();

            if ($postid->post_id && $postid->post_id != 0 && $order_data['billing']['email'] == $email) {

                // Order exists 
                $order->update_status('wc-investigation');
            } else {

                // Order does not exist
                $to = 'carlos.tandal@projecttimber.co.uk,laurence@projecttimber.co.uk';

                $subject    = "Order (#" . $orderid . ") does not exist " . $order->data->date;
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $headers[] = 'From: Project Timber <sales@projecttimber.com>';
                $headers[] = 'Reply-To: <sales@projecttimber.com>';

                $body  .= "Please note that this order does indeed exist. Could you please contact the customer.</b><br><br>";
                $body  .= "Email: <b>" . $email . "</b><br>";
                $body  .= "Phone: <b>" . $phone . "</b><br>";
                $body  .= "<br><br><b>Thank You!</b><br/>RDM FROM Submission";

                wp_mail($to, $subject, $body, $headers);
            }
        }

        //export stripe id, add meta key "_pip_extras_or_option"
        add_filter('woe_get_order_value_pip_extras_or_option_order', function ($value, $order, $fieldname) {

            $extra_option = '';

            foreach ($order->get_items() as $item_id_ => $item_) {
                $composite_id = $item_->get_product_id();
                $product      = $item_->get_product();
                $weightKG = (get_post_meta($composite_id, '_weight', true) ? get_post_meta($composite_id, '_weight', true) : 0);

                $kg[] =  $weightKG *  $item_->get_quantity();

                if ($product->product_type == 'composite') {
                    $extra_option .= $item_->get_name() . " ||| ";
                } else if ($product->product_type == 'bundle') {
                    $extra_option .= $item_->get_name() . ", ";
                }

                if ($product->product_type == 'bundle') {

                    $productMatch_size = preg_match('/[0-9]{1,2} x [0-9]{1,2}/', $item_->get_name(), $productURL_size);

                    if ($productMatch_size) {

                        $delivery_type = get_field('delivery', $item_->get_product_id());

                        if ($delivery_type) :
                            $delivery_option .= $productURL_size[0] . " = " . $delivery_type . "<br/>";
                        endif;
                    }
                }
            }

            similar_text(get_post_meta($order_id, '_pip_extras_or_option', true), $extra_option, $percent);

            if ($percent > 50) {
                $extra_options_text = get_post_meta($order_id, '_pip_extras_or_option', true);
            } else {
                $extra_options_text = get_post_meta($order_id, '_pip_extras_or_option', true) . " - " . $extra_option;
            }

            return $extra_options_text;
        }, 10, 3);


        /*
 * trigger email during adding notes
 */
        add_action('woocommerce_new_order_note', 'trigger_after_order_note_added', 10, 2);
        function trigger_after_order_note_added($note_id, $note_data)
        {
            //if($note_data['note_type'] == 'internal') {

            $order = wc_get_order($note_data['order_id']);

            $email_subject = 'New Order Note Added by - ' . $note_data['author'];
            $email_body = 'A new note has been added to order #' . $order->get_order_number() . ': ' . $note_data['content'];

            $headers1 = array('Content-Type: text/html; charset=UTF-8');
            $headers1[] = 'From: Project Timber <sales@projecttimber.com>';
            $headers1[] = 'Reply-To: <sales@projecttimber.com>';

            wp_mail('carlos.tandal@projecttimber.co.uk', $email_subject, $email_body, $headers1);

            //} 
        }

        // disable stock reduce in woo 
        add_filter('woocommerce_can_reduce_order_stock', '__return_false');

        function my_unserialize_export_field_RDM_Category($value, $order, $field)
        {
            if ($field == 'RDM-Category') {
                // Check if the value is serialized
                if (is_serialized($value)) {

                    // Unserialize the value
                    $unserialized_value = maybe_unserialize($value);

                    // If it's an array, convert it to a readable format (e.g., comma-separated) 
                    if ($unserialized_value[0]) {
                        $value = implode(', ', $unserialized_value);
                    } else {
                        // For non-array data, just return the unserialized value
                        $value = $unserialized_value;
                    }
                }
            }

            return $value;
        }

        // Apply the filter to modify the field
        add_filter('woe_get_order_value_RDM-Category', 'my_unserialize_export_field_RDM_Category', 10, 3);

        function my_unserialize_export_field_RDM_Sub_Category($value, $order, $field)
        {
            if ($field == 'RDM-Sub-Category') {
                // Check if the value is serialized 
                if (is_serialized($value)) {

                    // Unserialize the value
                    $unserialized_value = maybe_unserialize($value);

                    // If it's an array, convert it to a readable format (e.g., comma-separated) 
                    if ($unserialized_value[0]) {
                        $value = implode(', ', $unserialized_value);
                    } else {
                        // For non-array data, just return the unserialized value
                        $value = $unserialized_value;
                    }
                }
            }

            return $value;
        }

        // Apply the filter to modify the field
        add_filter('woe_get_order_value_RDM-Sub-Category', 'my_unserialize_export_field_RDM_Sub_Category', 10, 3);


        function check_and_void_dearsystem_order($order_id)
        {

            $order = wc_get_order($order_id);
            $ordern_id = $order->get_meta('_order_number_formatted');

            $checkSale = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/SaleList?search=' . $ordern_id . '&OrderStatus=AUTHORISED', 'GET');

            if ($checkSale->SaleList[0]->Status !== 'COMPLETED') {
                dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale?ID=' . $checkSale->SaleList[0]->SaleID . '&Void=True', 'DELETE');
            }
        }

        add_action('woocommerce_order_status_cancelled', 'check_and_void_dearsystem_order', 10, 1);


        function calculateTwentyPercent($amount)
        {

            $vatRate = 0.20;

            $net = $amount / (1 + $vatRate);
            $vat = $amount - $net;

            return $vat;
        }

        add_action('woocommerce_before_order_itemmeta', 'add_custom_text_to_admin_order_line_items', 10, 3);

        function add_custom_text_to_admin_order_line_items($item_id, $item, $product)
        {
            // Add your custom text or retrieve saved meta

            if ($item instanceof WC_Order_Item) {
                // Get the parent order ID
                $order_id = $item->get_order_id();
            }

            $order = wc_get_order($order_id);


            foreach ($order->get_items() as $item_ids => $items) {
                if ($item->get_name() == $items->get_name()):
                    $composite_id = $items->get_product_id();
                    $product      = $items->get_product();
                    $weightKG = (get_post_meta($composite_id, '_weight', true) ? get_post_meta($composite_id, '_weight', true) : 0);

                    if ($product->product_type == 'composite') {
                        echo "<p>Weight - <b> " . ($weightKG *  $items->get_quantity()) . "</b></p>";
                    } else if ($product->product_type == 'bundle') {
                        echo "<p>Weight - <b>" . ($weightKG *  $items->get_quantity()) . "</b></p>";
                    } else {
                        echo "<p> Weight " . ($weightKG *  $items->get_quantity()) . "</p>";
                    }

                endif;
            }
        }
        add_action('woocommerce_after_checkout_form', 'persist_checkout_data_with_localstorage');

        function persist_checkout_data_with_localstorage()
        {
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Define the fields to persist
    const fieldsToPersist = [
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'order_pickup_date' // Include the datepicker field
    ];

    // Restore data from localStorage
    fieldsToPersist.forEach(function(field) {
        const inputField = document.querySelector('#' + field);
        if (inputField && localStorage.getItem(field)) {
            inputField.value = localStorage.getItem(field);

            // Trigger a change event for datepicker fields
            if (field === 'order_pickup_date') {
                const event = new Event('change');
                inputField.dispatchEvent(event);
            }
        }
    });

    // Save text fields to localStorage on input
    fieldsToPersist.forEach(function(field) {
        const inputField = document.querySelector('#' + field);
        if (inputField) {
            inputField.addEventListener('input', function() {
                localStorage.setItem(field, inputField.value);
            });
        }
    });

    // Initialize the datepicker and save the selected date
    const datepickerField = document.querySelector('#order_pickup_date');
    if (datepickerField) {
        jQuery(datepickerField).datepicker({
            onSelect: function(dateText) {
                // Save the selected date to localStorage
                localStorage.setItem('order_pickup_date', dateText);

                // Update the field value
                datepickerField.value = dateText;

                // Trigger a change event to update any dependent logic
                const event = new Event('change');
                datepickerField.dispatchEvent(event);
            }
        });

        // Save the value on manual change (fallback for certain edge cases)
        datepickerField.addEventListener('change', function() {
            localStorage.setItem('order_pickup_date', datepickerField.value);
        });
    }
});
</script>
<?php
        }



        // Schedule a cron event for every two weeks
        function custom_schedule_csv_email_event()
        {
            if (!wp_next_scheduled('send_csv_email_event')) {
                wp_schedule_event(time(), 'fortnightly', 'send_csv_email_event');
            }
        }
        add_action('wp', 'custom_schedule_csv_email_event');

        // Define the custom interval for fortnightly (every 2 weeks)
        function add_fortnightly_cron_schedule($schedules)
        {
            $schedules['fortnightly'] = [
                'interval' => 1209600, // 2 weeks in seconds
                'display' => __('Every Two Weeks')
            ];
            return $schedules;
        }
        add_filter('cron_schedules', 'add_fortnightly_cron_schedule');


        // Hook into the scheduled event
        add_action('send_csv_email_event', 'send_order_notes_csv_email');

        // Function to generate and email the CSV
        function send_order_notes_csv_email()
        {
            global $wpdb;

            // Define the SQL query
            $query = "
        SELECT
            c.comment_ID AS note_id,
            c.comment_post_ID AS order_id,
            CONCAT('HPY', pm.meta_value) AS order_number,
            c.comment_content AS note_content,
            c.comment_date AS comment_date,
            c.comment_author_email AS comment_author_email,
            c.comment_author AS comment_author
        FROM
            {$wpdb->comments} AS c
        LEFT JOIN
            {$wpdb->users} AS u
        ON
            c.user_id = u.ID
        LEFT JOIN
            {$wpdb->postmeta} AS pm
        ON
            c.comment_post_ID = pm.post_id AND pm.meta_key = '_order_number'
        WHERE
            c.comment_type = 'order_note'
            AND c.comment_author_email != 'woocommerce@projecttimber.com'
            AND c.comment_author_email != 'woocommerce@projecttimbercom.kinsta.cloud'
            AND c.comment_content NOT LIKE '%email notification sent%'
            AND c.comment_content NOT LIKE '%Added line%'
            AND c.comment_content NOT LIKE '%assembly instructions%'
            AND pm.meta_value != '-1'
            AND DATE(c.comment_date) BETWEEN DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND CURDATE();
    ";

            // Fetch results from the database
            $results = $wpdb->get_results($query, ARRAY_A);

            // Generate the CSV content
            $csv_file = fopen('php://temp', 'w');
            fputcsv($csv_file, [
                'Note ID',
                'Order ID',
                'Order Number',
                'Note Content',
                'Comment Date',
                'Author Email',
                'Author'
            ]);

            foreach ($results as $row) {
                fputcsv($csv_file, $row);
            }

            rewind($csv_file);
            $csv_content = stream_get_contents($csv_file);
            fclose($csv_file);

            // Prepare the email 
            $to = 'carlos.tandal@projecttimber.co.uk,laurence@projecttimber.co.uk,samuel.weeks@projecttimber.co.uk,andrew.knowles@projecttimber.co.uk,william.walton@projecttimber.co.uk';
            $subject = 'Order Notes Status Change Export - Every Two Weeks';
            $message = 'Please find attached the latest order notes export.';
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
            ];

            // Attach the CSV file
            $attachments = [
                wp_upload_dir()['basedir'] . '/order_notes_export.csv'
            ];

            file_put_contents($attachments[0], $csv_content);

            // Send the email
            wp_mail($to, $subject, $message, $headers, $attachments);

            // Cleanup the temporary file
            unlink($attachments[0]);
        }

        add_action('woocommerce_thankyou', 'send_order_status_to_ga4_once', 10, 1);
        function send_order_status_to_ga4_once($order_id)
        {

            if (!$order_id) {
                return;
            }

            $order = wc_get_order($order_id);

            // Check if the GA4 event has already been sent
            if ($order && !$order->get_meta('_ga4_event_sent')) {
                // Get order status
                $order_status = $order->get_status();

                $items = $order->get_items();

                foreach ($items as $item_id => $item) {

                    // Get the product object
                    $product = $item->get_product();

                    // Check if the product is a composite product
                    if ('composite' === $product->get_type()) {

                        $product_name = $product->get_name();
                    }
                }

                // Generate JavaScript to send to GA4
                ?>
<script>
gtag('event', 'purchase', {
    'order_status': '<?php echo esc_js($order_status); ?>',
    'transaction_id': '<?php echo esc_js($order->get_id()); ?>',
    'building_name': '<?php echo $product_name; ?>',
    'value': <?php echo esc_js($order->get_total()); ?>,
    'currency': '<?php echo esc_js($order->get_currency()); ?>',
    'items': <?php echo wp_json_encode(get_order_items_for_ga4($order)); ?>
});
</script>
<?php

                // Mark the event as sent
                $order->update_meta_data('_ga4_event_sent', true);
                $order->save();
            }
        }

        function get_order_items_for_ga4($order)
        {
            $items = [];

            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();

                $items[] = [
                    'item_name' => $item->get_name(),
                    'item_id' => $product ? $product->get_id() : '',
                    'price' => $item->get_total(),
                    'quantity' => $item->get_quantity()
                ];
            }

            return $items;
        }

        add_action('woocommerce_order_status_changed', 'update_order_status_in_ga4', 10, 4);
        function update_order_status_in_ga4($order_id, $old_status, $new_status, $order)
        {
            if (! $order_id || ! $order instanceof WC_Order) {
                return;
            }

            // Don't try to print <script> during REST, AJAX, or CLI requests.
            if ((defined('REST_REQUEST') && REST_REQUEST)
                || wp_doing_ajax()
                || (defined('WP_CLI') && WP_CLI)) {
                return;
            }

            $order_total    = $order->get_total();
            $currency       = $order->get_currency();
            $transaction_id = $order->get_id();
            $product_name   = '';

            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (! $product instanceof WC_Product) {
                    continue;
                }
                if ('composite' === $product->get_type()) {
                    $product_name = $product->get_name();
                    break; // first composite wins; remove if you want last-wins
                }
            }
            ?>
        <script>
        gtag('event', 'order_status_update', {
            'transaction_id': '<?php echo esc_js($transaction_id); ?>',
            'order_status':   '<?php echo esc_js($new_status); ?>',
            'building_name':  '<?php echo esc_js($product_name); ?>',
            'old_status':     '<?php echo esc_js($old_status); ?>',
            'value':          <?php echo esc_js($order_total); ?>,
            'currency':       '<?php echo esc_js($currency); ?>'
        });
        </script>
            <?php
        }

        add_action('woocommerce_email_after_order_table', 'add_payment_link_to_failed_email', 10, 4);
        function add_payment_link_to_failed_email($order, $sent_to_admin, $plain_text, $email)
        {
            if ($email->id === 'customer_failed_order' && !$sent_to_admin) {
                $order_status = $order->get_status(); // Get the current order status

                // Define the allowed statuses for showing the payment link
                $allowed_statuses = ['pending', 'failed', 'declined-loan', 'cancelled', 'refunded'];

                if (in_array($order_status, $allowed_statuses)) {
                    $order_id = $order->get_id();
                    $payment_url = $order->get_checkout_payment_url(); // WooCommerce payment retry link

                    echo '<p style="text-align: center;"><strong>Payment Failed?</strong></p>';
                    echo '<p style="text-align: center;">Your payment for order <strong>#' . $order_id . '</strong> has failed. Click the button below to retry your payment:</p>';
                    echo '<p style="text-align: center;">   <a href="' . esc_url($payment_url) . '" style="background-color:#ff0000;color:#ffffff;padding:10px 20px;text-decoration:none;border-radius:5px;">Retry Payment</a></p>';
                }
            }
        }

        add_filter('woocommerce_email_recipient_new_order', 'disable_email_if_price_below_threshold', 10, 2);
        add_filter('woocommerce_email_recipient_customer_processing_order', 'disable_email_if_price_below_threshold', 10, 2);
        add_filter('woocommerce_email_recipient_customer_completed_order', 'disable_email_if_price_below_threshold', 10, 2);

        function disable_email_if_price_below_threshold($recipient, $order)
        {
            if (!$order instanceof WC_Order) {
                return $recipient;
            }

            $order_total = floatval($order->get_total());

            // Disable email if order total is less than or equal to $0.01
            if ($order_total <= 0.01) {
                return '';
            }

            return $recipient;
        }

        add_filter('woocommerce_rest_prepare_shop_order_object', function ($response, $order, $request) {

            $from_delivery_date = $order->get_meta('_from_delivery_date');
            if ($from_delivery_date) {
                $response->data['from_delivery_date'] = $from_delivery_date;
            }

            $final_delivery_date = $order->get_meta('_final_delivery_date');
            if ($final_delivery_date) {
                $response->data['final_delivery_date'] = $final_delivery_date;
            }
             $dispatch_date = $order->get_meta('_dispatch_date');  // Add this block
                if ($dispatch_date) {
                    $response->data['dispatch_date'] = $dispatch_date;
                }

            return $response;
        }, 10, 3);

        function wr_klaviyo_default_checked()
        {
?>
<script>
jQuery(document).ready(function() {
    if (jQuery("#kl_newsletter_checkbox").length > 0) {
        jQuery("#kl_newsletter_checkbox").attr('checked', 'checked');
    }
    if (jQuery("#kl_sms_consent_checkbox").length > 0) {
        jQuery("#kl_sms_consent_checkbox").attr('checked', 'checked');
    }

})
</script>
<?php
        }
        add_action('wp_footer', 'wr_klaviyo_default_checked');

        // Add a filter to allow custom order number as a query parameter for orders API
        function custom_wc_rest_api_filter_by_seq_order_number($args, $request)
        {
            // Check if the 'seq_order_number' parameter is set in the API request
            if (isset($request['seq_order_number']) && ! empty($request['seq_order_number'])) {
                $custom_number = sanitize_text_field($request['seq_order_number']);

                // Add a meta_query to filter by the plugin's custom field
                $args['meta_query'][] = array(
                    'key'     => '_order_number_formatted', // The meta key used by Sequential Order Numbers Pro
                    'value'   => $custom_number,
                    'compare' => '=', // Use '=' for exact match

                );
            }
            return $args;
        }
        add_filter('woocommerce_rest_shop_order_object_query', 'custom_wc_rest_api_filter_by_seq_order_number', 10, 2);

        add_action('rest_api_init', 'register_orders_by_phone_endpoint');

        /**
         * Registers the custom REST API route to load orders by phone number.
         */
        function register_orders_by_phone_endpoint()
        {
            register_rest_route(
                'wc/v3', // The namespace for the endpoint. Using wc/v3 keeps it consistent with other WooCommerce endpoints.
                '/orders-by-phone/(?P<phone>[\d\s\-\+\(\)]+)', // The route. It captures the phone number.
                array(
                    'methods'             => WP_REST_Server::READABLE, // Corresponds to a GET request.
                    'callback'            => 'get_orders_by_phone_callback', // The function that handles the request.
                    'permission_callback' => 'check_api_permissions_for_orders', // The function that checks for permissions.
                    'args'                => array( // Defines the arguments the endpoint can receive.
                        'phone' => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return is_string($param) && !empty($param);
                            },
                            'sanitize_callback' => 'sanitize_text_field',
                            'required'          => true,
                            'description'       => 'The billing phone number of the customer to search for.',
                        ),
                    ),
                )
            );
        }

        /**
         * Permission callback function to ensure the user has the right to view orders.
         *
         * @param WP_REST_Request $request The current request object.
         * @return bool|WP_Error True if the user has permission, WP_Error otherwise.
         */
        function check_api_permissions_for_orders($request)
        {
            // Only allow users who can view/edit WooCommerce orders.
            // This is a crucial security measure.
            if (!current_user_can('edit_shop_orders')) {
                return new WP_Error(
                    'rest_forbidden',
                    esc_html__('You do not have permission to view this resource.', 'your-text-domain'),
                    array('status' => 403)
                );
            }
            return true;
        }

        /**
         * Callback function to process the API request and return the orders.
         *
         * @param WP_REST_Request $request The full request object.
         * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
         */
        function get_orders_by_phone_callback(WP_REST_Request $request)
        {
            // Get the phone number from the URL parameter we defined in the route.
            $phone_number = $request['phone'];

            // Use wc_get_orders with a meta_query to find matching orders.
            // This is the standard and safest way to retrieve orders.
            $orders = wc_get_orders(array(
                'limit'      => -1, // -1 gets all matching orders.
                'meta_key'   => '_billing_phone', // The meta key for the billing phone number.
                'meta_value' => $phone_number,
            ));

            // If no orders are found, return a successful response with an empty array.
            if (empty($orders)) {
                return new WP_REST_Response(array(), 200);
            }

            // Prepare the data for a clean API response.
            // Instead of returning the entire complex WC_Order object, we format a simpler array.
            $response_data = array();
            foreach ($orders as $order) {
                $response_data[] = array(
                    'id'             => $order->get_id(),
                    'order_number'   => $order->get_order_number(),
                    'status'         => $order->get_status(),
                    'date_created'   => $order->get_date_created()->date('c'), // ISO 8601 format
                    'total'          => $order->get_total(),
                    'currency'       => $order->get_currency(),
                    'customer_id'    => $order->get_customer_id(),
                    'billing_info'   => array(
                        'first_name' => $order->get_billing_first_name(),
                        'last_name'  => $order->get_billing_last_name(),
                        'email'      => $order->get_billing_email(),
                        'phone'      => $order->get_billing_phone(),
                    ),
                    'view_order_url' => $order->get_view_order_url(), // Direct link to the order for the customer
                );
            }

            // Return the formatted data in a success response.
            return new WP_REST_Response($response_data, 200);
        }



// Helper function: calculate delivery Date
function calculateDeliveryDate($days)
{
    $current_date = new DateTime();
    $delivery_date = clone $current_date;
    $days_added = 0;

    while ($days_added < $days) {
        $delivery_date->add(new DateInterval('P1D'));

        // Check if it's not a weekend (1 = Monday, 7 = Sunday)
        $day_of_week = $delivery_date->format('N');
        if ($day_of_week < 6) { // Monday (1) through Friday (5)
            $days_added++;
        }
    }

    return $delivery_date;
}

// Helper function: calculate monthly payment rate
function calculateMonthlyPayment($principal, $annual_rate = 0.1699, $years = 5)
{
    $monthly_rate = pow(1 + $annual_rate, 1 / 12) - 1;
    $number_of_payments = $years * 12;
    $currency = get_woocommerce_currency_symbol();

    $monthly_payment = $principal * ($monthly_rate * pow(1 + $monthly_rate, $number_of_payments))
        / (pow(1 + $monthly_rate, $number_of_payments) - 1);

    return $currency . number_format($monthly_payment, 2);
}


add_filter('woocommerce_structured_data_product', 'fix_composite_product_price_schema', 10, 2);
function fix_composite_product_price_schema($markup, $product)
{
    if ($product->get_type() === 'composite') {
        $current_url = $_SERVER['REQUEST_URI'];
        $psizeUrl = null;

        // Find pattern like 18-x-6 etc
        if (preg_match('/(\d+-x-\d+)/', $current_url, $matches)) {
            $psizeUrl = $matches[1];
        }

        if ($psizeUrl && $product instanceof WC_Product_Composite) {
            $product_data = $product->get_composite_data();
            $keys = array_keys($product_data);
            $selected_key = $keys[0];
            $all_sizes_products = $product_data[$selected_key]['assigned_ids'];
            if ( av_product_qualifies_simple( get_the_ID() ) ) {
                $coupon_percent = (float) get_field('special_coupon_percentage', 'option');
            }else{
                $coupon_percent = (float) get_field('coupon_percentage', 'option');
            } 

            if (!empty($all_sizes_products)) {
                foreach ($all_sizes_products as $product_id) {
                    $bundle_product = wc_get_product($product_id);
                  

                    if ($bundle_product) {
                        $regular_price = (float) $bundle_product->get_price();
                        $size_nameP = str_replace(' ', '-', $bundle_product->get_name());

                        // Match product size by name
                        if ($size_nameP === $psizeUrl || strpos($size_nameP, $psizeUrl) !== false) {

                            $formatted_price = number_format($regular_price, 0, '.', '');

                            // Always set availability + currency
                            $markup['offers'][0]['priceCurrency'] = 'GBP';
                            $markup['offers'][0]['availability'] =
                                $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

                            // IF WE HAVE A DISCOUNT
                            if ($coupon_percent > 0) {
                                $discounted_price = $regular_price - (($regular_price / 100) * $coupon_percent);
                                $formatted_sale_price = number_format($discounted_price, 0, '.', '');

                                // Display SALE as primary price
                                $markup['offers'][0]['price'] = $formatted_sale_price;

                                // Add regular + sale specifications
                                $markup['offers'][0]['priceSpecification'] = [
                                    [
                                        "@type" => "PriceSpecification",
                                        "price" => $formatted_price,
                                        "priceCurrency" => "GBP"
                                    ],
                                    [
                                        "@type" => "SalePriceSpecification",
                                        "price" => $formatted_sale_price,
                                        "priceCurrency" => "GBP",
                                        "validThrough" => "2027-12-31"
                                    ]
                                ];
                            }

                            // NO DISCOUNT — NORMAL PRICE ONLY
                            else {
                                $markup['offers'][0]['price'] = $formatted_price;

                                $markup['offers'][0]['priceSpecification'] = [
                                    [
                                        "@type" => "PriceSpecification",
                                        "price" => $formatted_price,
                                        "priceCurrency" => "GBP"
                                    ]
                                ];
                            }

                            break;
                        }
                    }
                }
            }
        }
    }

    return $markup;
}

add_filter( 'woocommerce_email_headers', function ( $headers, $email_id, $order ) {
    // Add your email to copies of emails that customers receive
    $customer_emails = [
        'customer_processing_order',
        'customer_completed_order',
        'customer_on_hold_order',
        'customer_invoice'
    ];

    if ( in_array( $email_id, $customer_emails ) ) {
        $headers .= "Bcc: laurence.sembrano@projecttimber.co.uk\r\n";
    }
    return $headers;
}, 10, 3 );

// Helper function - add this to functions.php if not already there
function calculate_business_days_delivery($business_days_needed) {
    $current_date = new DateTime();
    $business_days_counted = 0;
    
    while ($business_days_counted < $business_days_needed) {
        $current_date->add(new DateInterval('P1D'));
        
        // Check if this day is a weekday (Monday=1 to Friday=5)
        $day_of_week = (int) $current_date->format('N');
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            $business_days_counted++;
        }
    }
    
    return $current_date->format('M, jS D');
}

add_action('template_redirect', function() {
    $request_uri = strtok($_SERVER['REQUEST_URI'], '?'); // remove query string
    if (untrailingslashit($request_uri) === '/delivery-10') {
        wp_redirect('https://www.projecttimber.com/delivery', 301);
        exit;
    }
});

/**
 * Force ACF field "order_ref_original" required when order status = rdmopen
 */
add_action('acf/validate_save_post', function () {
    // Get posted status (new order will have "wc-rdmopen" here)
    $posted_status = $_POST['order_status'] ?? '';
    $status = str_replace('wc-', '', sanitize_text_field($posted_status));

    // Fallback: if updating, also check the order object
    if (empty($status) && !empty($_POST['post_ID'])) {
        $order = wc_get_order((int) $_POST['post_ID']);
        if ($order) {
            $status = $order->get_status();
        }
    }

    // Only enforce if status is rdmopen
    if ($status !== 'rdmopen') {
        return;
    }

    // Get field definition
    $field = acf_get_field('order_ref_original');
    if (!$field || empty($field['key'])) {
        return;
    }

    // Posted field value
    $posted_value = $_POST['acf'][$field['key']] ?? '';

    // If empty → attach field-level error
    if (empty(trim((string)$posted_value))) {
        acf_add_validation_error(
            "acf[{$field['key']}]",
            __('The "Order Ref Original" field is required when order status is RDM Open.', 'your-textdomain')
        );
    }
});


function av_product_matches_special_category( $product_id ) {

    $product_id = (int) $product_id;
    if ( ! $product_id ) return false;

    $cats = wc_get_product_term_ids( $product_id, 'product_cat' );
    if ( ! is_array( $cats ) ) return false;

    // SPECIAL CATEGORY IDS
    $allowed_categories = [ 2346, 15, 16 ];

    return ! empty( array_intersect( $allowed_categories, $cats ) );
}

function product_matches_insulated_category( $product_id ) {

    $product_id = (int) $product_id;
    if ( ! $product_id ) return false;

    $cats = wc_get_product_term_ids( $product_id, 'product_cat' );
    if ( ! is_array( $cats ) ) return false;

    // SPECIAL CATEGORY IDS
    $allowed_categories = [  ];

    return ! empty( array_intersect( $allowed_categories, $cats ) );
}


function sc_force_redirect( $atts ) {
    $atts = shortcode_atts([
        'to'   => '',
        'code' => 301, // default permanent
    ], $atts);

    if ( empty($atts['to']) ) {
        return '';
    }

    // Prevent redirect loops
    if ( untrailingslashit( home_url( add_query_arg([], $_SERVER['REQUEST_URI']) ) ) === untrailingslashit( $atts['to'] ) ) {
        return '';
    }

    wp_redirect( esc_url_raw( $atts['to'] ), intval( $atts['code'] ) );
    exit;
}
add_shortcode( 'redirect', 'sc_force_redirect' );


/**
 * Allow selected users full access to WooCommerce orders screen
 * BUT block saving / updating / modifying
 */
add_action('admin_init', function () {

    // 👇 READ-ONLY ORDER MANAGER USERS
    $readonly_users = [262];
    $uid = get_current_user_id();

    if (!in_array($uid, $readonly_users, true)) {
        return;
    }

    /**
     * 1️⃣ Grant access to WooCommerce orders & menus
     *    (list, filter, search, export, view, open)
     */
    add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) use ($uid) {

        if ($user->ID !== $uid) {
            return $allcaps;
        }

        // Allow order dashboard access
        $allcaps['manage_woocommerce']         = true;
        $allcaps['view_admin_dashboard']       = true;
        $allcaps['edit_shop_orders']           = true; // needed to open screens
        $allcaps['read_private_shop_orders']   = true;
        $allcaps['read_shop_orders']           = true;
        $allcaps['view_woocommerce_reports']   = true;

        // BLOCK editing abilities
        $allcaps['publish_posts']              = false;
        $allcaps['delete_posts']               = false;
        $allcaps['delete_shop_orders']         = false;
        $allcaps['delete_private_shop_orders'] = false;

        return $allcaps;
    }, 20, 4);

    /**
     * 2️⃣ HARD BLOCK saving, status change & updates
     */
    add_action('load-post.php', function () {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            wp_die('Read-only: You can view orders but not update or save them.');
        }
    });

    /**
     * 3️⃣ Hide UI that allows updates (cosmetic)
     */
    add_action('admin_head', function () {
        echo '<style>
            .page-title-action,
            .row-actions span.trash,
            .row-actions span.inline-edit,
            .bulkactions,
            #publishing-action,
            #delete-action,
            select[name="wc_order_action"],
            .edit_address,
            .wc-order-status,
            .refund-items,
            #woocommerce-order-items .actions,
            .order_notes .add_note,
            .order_notes .delete_note,
            .button-primary,
            .save_order,
            .button.refund-items { display:none!important; }
        </style>';
    });
});



/**
 * Get total order weight
 */
function get_order_total_weight( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return 0;

    $total_weight = 0;

    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) continue;

        $total_weight += (float) $product->get_weight() * (int) $item->get_quantity();
    }

    return $total_weight;
}

/**
 * Append order total weight to REST API response
 */
add_filter( 'woocommerce_rest_prepare_shop_order_object', function( $response, $object, $request ) {

    $order_id = $object->get_id();
    $weight   = get_order_total_weight( $order_id );

    $data = $response->get_data();
    $data['total_weight'] = $weight; // appears in JSON

    $response->set_data( $data );

    return $response;

}, 10, 3 );


/**
 * Register exported_to_ads_tracking order meta
 * Boolean + visible and editable in REST API
 */
add_action( 'init', function() {
    register_post_meta( 'shop_order', 'exported_to_ads_tracking', array(
        'type'          => 'boolean',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => false,
        'auth_callback' => function() {
            return current_user_can( 'manage_woocommerce' );
        }
    ));
});

/**
 * Display the checkbox in WP Admin Order screen
 */
add_action( 'woocommerce_admin_order_data_after_order_details', function( $order ) {
    $value = $order->get_meta('exported_to_ads_tracking');
    ?>
<div class="order_data_column">
    <span>
        <h4>Exported to google Ads Conversion Tracking: </h4> <?php echo  $value == true ? 'Yes' : 'No'; ?>
    </span>
</div>
<?php
});

/**
 * Save field when order is updated in admin
 */
add_action( 'woocommerce_process_shop_order_meta', function( $order_id ) {
    $is_checked = isset( $_POST['exported_to_ads_tracking'] ) ? true : false;
    update_post_meta( $order_id, 'exported_to_ads_tracking', $is_checked );
});

/**
 * Optional: initialize meta on new orders if missing
 */
add_action( 'woocommerce_checkout_update_order_meta', function( $order_id ) {
    if ( get_post_meta( $order_id, 'exported_to_ads_tracking', true ) === '' ) {
        update_post_meta( $order_id, 'exported_to_ads_tracking', false );
    }
});

add_filter( 'woocommerce_rest_prepare_shop_order_object', function( $response, $order ) {
    $value = $order->get_meta( 'exported_to_ads_tracking' );
    $response->data['exported_to_ads_tracking'] = (bool) $value;
    return $response;
}, 10, 2 );



add_action( 'admin_footer-post.php', 'pt_inline_dispatch_js' );
add_action( 'admin_footer-post-new.php', 'pt_inline_dispatch_js' );

function pt_inline_dispatch_js() {
    $screen = get_current_screen();
    if ( $screen->post_type !== 'shop_order' ) {
        return;
    }
    ?>
<script>
jQuery(function($) {

    function calculateDispatchDate(expected) {
        if (!expected) return '';

        const parts = expected.split('-');
        const expectedDate = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));

        // If Expected is Monday → dispatch previous Friday
        if (expectedDate.getUTCDay() === 1) {
            expectedDate.setUTCDate(expectedDate.getUTCDate() - 3);
        } else {
            // Otherwise → minus 1 day
            expectedDate.setUTCDate(expectedDate.getUTCDate() - 1);
        }

        const yyyy = expectedDate.getUTCFullYear();
        const mm = String(expectedDate.getUTCMonth() + 1).padStart(2, '0');
        const dd = String(expectedDate.getUTCDate()).padStart(2, '0');

        return `${yyyy}-${mm}-${dd}`;
    }

    function ensureDispatchNote() {
        if ($('#dispatch-auto-note').length) return;

        $('#_dispatch_date')
            .closest('.form-field')
            .append(
                '<p id="dispatch-auto-note" style="margin:4px 0 0;color:#fff; background: #6ea029; font-size:12px; border-radius: 8px; padding: 4px !important;">' +
                'Dispatch date is auto-calculated from Final Delivery Date.' +
                '</p>'
            );
    }

    function updateDispatchField() {
        const finalDelivery = $('#_final_delivery_date').val();
        const dispatch = calculateDispatchDate(finalDelivery);

        if (dispatch) {
            $('#_dispatch_date').val(dispatch);
            ensureDispatchNote();
        }
    }
    // Watch FINAL delivery date
    $('#_final_delivery_date').on('change blur', updateDispatchField);

    // Initial load
    updateDispatchField();

});
</script>
<?php
}


/**
 * Auto-calculate Dispatch Date from Estimated Delivery Date
 */
// function pt_set_dispatch_date_from_estimated_delivery( $order_id ) {

//     if ( get_post_type( $order_id ) !== 'shop_order' ) {
//         return;
//     }

//     $order = wc_get_order( $order_id );
//     if ( ! $order ) {
//         return;
//     }

//     // ✅ Estimated / Expected Delivery Date
//     $estimated = get_post_meta( $order_id, '_from_delivery_date', true );
//     if ( empty( $estimated ) ) {
//         return;
//     }

//     try {
//         $date = new DateTime( $estimated, wp_timezone() );

//         // ISO-8601 weekday: Monday = 1
//         if ( $date->format( 'N' ) == 1 ) {
//             // Estimated is Monday → dispatch previous Friday
//             $date->modify( '-3 days' );
//         } else {
//             // Normal case → minus 1 day
//             $date->modify( '-1 day' );
//         }

//         update_post_meta(
//             $order_id,
//             '_dispatch_date',
//             $date->format( 'Y-m-d' )
//         );

//     } catch ( Exception $e ) {
//         // Optional: error_log( $e->getMessage() );
//     }
// }

// // Order created
// add_action(
//     'woocommerce_checkout_order_processed',
//     'pt_set_dispatch_date_from_estimated_delivery',
//     20
// );




/**
 * ============================================================
 * WooCommerce Composite Products + Optional Extras Integration
 * ============================================================
 *
 * Features:
 *  - Displays optional extras on the composite product page.
 *  - Injects selected extras into the composite form data.
 *  - Adds extras in the same cart transaction as the composite.
 *  - Shows extras as "Attached to" in cart and checkout.
 *  - 100% AJAX compatible with official Composite Products plugin.
 */

// Disable Additional Information tab
add_filter( 'woocommerce_product_tabs', '__return_empty_array', 98 );



/**
 * 1️⃣ Display extra buttons below Add to Cart area
 */
add_action('woocommerce_before_add_to_cart_button', function () {
    $product_id = get_the_ID();
    $extras = [];

    if (have_rows('extra_products', $product_id)) {
        while (have_rows('extra_products', $product_id)) {
            the_row();
            $extra_id = get_sub_field('add_extra_product_id');
            if (!empty($extra_id)) {
                $extras[] = $extra_id;
            }
        }
    }



    if (empty($extras)) return;

    echo '<div class="extra-products-wrap" style="margin-top:20px">';
    foreach ($extras as $id) {
        $p = wc_get_product($id);
        if (!$p || !$p->is_purchasable()) continue;

        $image = $p->get_image('woocommerce_thumbnail', ['style' => 'width:80px;height:auto;border-radius:6px; border: 1px solid rgba(59, 51, 61, 0.05);
']);
        $name = $p->get_name();
        $regular_price = (float) $p->get_regular_price();
        $sale_price = (float) $p->get_price();

        // 🧮 Compute visual 30% discount
        $display_price = $sale_price ?: $regular_price;
        $discounted = round($display_price * 0.7); // Apply 30% discount & round nicely

        // 💅 Build formatted price HTML (same as JS)
        $price_html = '
            <p class="price" style="margin:0;">
                <span class="price-strikes-new" style="color:#FF0202; margin-right:6px;">
                    ' . wc_price($display_price) . '
                </span>
                <span class="woocommerce-Price-amount amount" style=" color:#3B333D;">
                    ' . wc_price($discounted) . '
                </span>
            </p>';

        echo '
        <div class="extra-product-item" 
            style="display:flex;align-items:center;justify-content:space-between;
                   gap:15px;border:1px solid #ddd;border-radius:10px;
                   padding:10px 15px;margin-bottom:10px;background:#fff;">
            
            <div class="extra-image" style="flex:0 0 90px;">' . $image . '</div>

            <div class="extra-info" style="flex:1;">
                <div class="extra-name">' . esc_html($name) . '</div>
                <div class="extra-price">' . $price_html . '</div>
            </div>

            <div class="extra-action" style="flex:0 0 auto;">
                <button type="button"
                    class="add-extra-product-button"
                    data-product-id="' . esc_attr($id) . '"
                    data-price="' . esc_attr($display_price) . '"
                    style="padding:6px 16px;border:1px solid #444;border-radius:20px;
                           background:#fff;color:#000;cursor:pointer;font-weight:500;
                           transition:all .2s;">
                    Add
                </button>
            </div>
        </div>';
    }

    echo '</div>';
});


/**
 * 2️⃣ Inject extras into composite form data before processing
 */
add_filter('woocommerce_composite_posted_data', function ($posted_data) {
    if (!empty($_REQUEST['extra_product_ids'])) {
        $posted_data['extra_product_ids'] = sanitize_text_field($_REQUEST['extra_product_ids']);
    }
    return $posted_data;
});

/**
 * 3️⃣ After composite successfully added — add extras immediately
 */
add_action('woocommerce_composite_added_to_cart', function ($composite_cart_key, $composite_id, $posted_data, $composite) {

    if (empty($posted_data['extra_product_ids'])) return;

    $ids = array_filter(array_map('intval', explode(',', $posted_data['extra_product_ids'])));
    if (!$ids) return;

    foreach ($ids as $extra_id) {
        $extra = wc_get_product($extra_id);
        if (!$extra || !$extra->is_purchasable()) continue;

        WC()->cart->add_to_cart($extra_id, 1, 0, [], [
            'linked_to'        => $composite_cart_key,
            'linked_parent_id' => $composite_id,
        ]);
    }
}, 10, 4);

/**
 * 4️⃣ Show attached extras in cart / checkout
 */
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['linked_parent_id'])) {
        $item_data[] = [
            'name'  => __('Attached to', 'woocommerce'),
            'value' => get_the_title($cart_item['linked_parent_id']),
        ];
    }
    return $item_data;
}, 10, 2);
/**
 * 6️⃣ Fallback: handle extras for non-AJAX composite POST flow (guarded)
 */
add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id, $qty, $variation_id, $variation, $cart_item_data) {

    // 🛑 Prevent recursion (WC triggers add_to_cart inside add_to_cart)
    if (defined('ADDING_EXTRAS')) {
        return;
    }
    define('ADDING_EXTRAS', true);

    // Skip if composite hook already handled extras
    if (did_action('woocommerce_composite_added_to_cart')) {
        return;
    }

    // No extras passed
    if (empty($_REQUEST['extra_product_ids'])) {
        return;
    }

    $ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_REQUEST['extra_product_ids']))));
    if (!$ids) return;

    foreach ($ids as $extra_id) {
        $extra = wc_get_product($extra_id);
        if (!$extra || !$extra->is_purchasable()) continue;

        WC()->cart->add_to_cart($extra_id, 1, 0, [], [
            'linked_to'        => $cart_item_key,
            'linked_parent_id' => $product_id,
        ]);
    }

    error_log('🧩 Added extras in fallback flow: ' . implode(',', $ids));

}, 10, 6);


/**
 * 5️⃣ Enqueue front-end JS for selecting extras
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_singular('product')) return; // safety check

    $single_product_template = get_field('layout_type', get_the_ID());

    if ($single_product_template === 'composite-simplified') {
        wp_enqueue_script(
            'extras-handler',
            get_stylesheet_directory_uri() . '/assets/js/simplified-product-page-scripts.js',
            ['jquery'],
            '1.53',
            true
        );
    }else{
        wp_enqueue_script(
            'extras-handler',
            get_stylesheet_directory_uri() . '/assets/js/product-page-scripts.js',
            ['jquery'],
            '1.63',
            true
        );
    }
});


/**
 * Add a "Clear Cart" button on the checkout page.
 */
// add_action('woocommerce_review_order_before_submit', function () {
//     echo '<p style="margin-top:15px;text-align:right;">
//         <a href="' . esc_url(add_query_arg('empty-cart', 'yes')) . '" 
//            class="button" 
//            style="background:#cc0000;color:#fff;border:none;padding:8px 16px;border-radius:4px;">
//            🗑️ Clear Cart
//         </a>
//     </p>';
// });
/**
 * Handle the clear cart action when the button is clicked.
 */
add_action('init', function () {
    if (isset($_GET['empty-cart']) && $_GET['empty-cart'] === 'yes') {
        WC()->cart->empty_cart();
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
});



add_action('wp_ajax_get_product_gallery', 'get_product_gallery');
add_action('wp_ajax_nopriv_get_product_gallery', 'get_product_gallery');

function get_product_gallery() {
  $product_id = intval($_GET['product_id'] ?? 0);
  if (!$product_id) wp_send_json_error('Missing product_id');

  $product = wc_get_product($product_id);
  if (!$product) wp_send_json_error('Invalid product');

  $images = [];

  // Include main image
  if ($product->get_image_id()) {
    $images[] = wp_get_attachment_image_url($product->get_image_id(), 'large');
  }

  // Include gallery images
  foreach ($product->get_gallery_image_ids() as $id) {
    $images[] = wp_get_attachment_image_url($id, 'large');
  }

  wp_send_json_success($images);
}

add_filter( 'woocommerce_default_catalog_orderby', function () {
    return 'date';
});


add_filter( 'woocommerce_coupons_enabled', 'disable_coupons_on_checkout' );
function disable_coupons_on_checkout( $enabled ) {
    if ( is_checkout() ) {
        return false;
    }
    return $enabled;
}

add_filter( 'av_cart_parent_product_id', function( $product_id, $cart_item, $cart_item_key ) {

    // If this is a composite child, resolve the parent cart item, then return its product_id
    if ( isset( $cart_item['composite_parent'] ) ) {
        $parent_key  = $cart_item['composite_parent'];
        $parent_item = WC()->cart ? WC()->cart->get_cart_item( $parent_key ) : null;

        if ( is_array( $parent_item ) && isset( $parent_item['product_id'] ) ) {
            return (int) $parent_item['product_id'];
        }
    }

    // Otherwise (top-level item), return its product_id
    return (int) $product_id;

}, 10, 3 );


// add_filter( 'woocommerce_email_headers', 'bcc_all_woo_emails', 10, 3 );

// function bcc_all_woo_emails( $headers, $email_id, $order ) {
//     $headers .= 'Bcc: adrian.solomon@projecttimber.co.uk' . "\r\n";
//     return $headers;
// }


add_action('admin_head', 'hide_woo_update_button_for_users');
function hide_woo_update_button_for_users() {
    $screen = get_current_screen();
    
    // Only run on order edit pages (works for both HPOS and legacy)
    if (!in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'])) {
        return;
    }
    
    $restricted_users = [517]; // User IDs to restrict
    
    if (in_array(get_current_user_id(), $restricted_users)) {
        ?>
        <style>
            button.save_order,
            .button.save_order,
            #publishing-action,
            li.wide.wc-order-status > button,
            .rdm.button-primary {
                display: none !important;
            }
        </style>
        <?php
    }
}

add_action('template_redirect', function () {

    if (!is_wc_endpoint_url('order-pay')) {
        return;
    }

    $order_id = absint(get_query_var('order-pay'));

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // Only restore cancelled unpaid orders
    if (
        $order->has_status('cancelled')
        && floatval($order->get_total()) > 0
        && !$order->is_paid()
    ) {
        $order->update_status(
            'pending',
            'Customer reopened payment link — restored from cancelled.'
        );
    }

});
