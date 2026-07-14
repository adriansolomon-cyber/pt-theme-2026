<?php
/**
 * Custom WooCommerce order statuses — migrated VERBATIM from theTimber/functions.php.
 * Registers the RDM (returns), shipment (Palletways / planned / unplanned) and finance
 * order statuses used across the store's back-office. No frontend/design impact.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register new status
function register_planned_shipment_order_status()
{
    register_post_status('wc-planned', array(
        'label'                     => 'Planned',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Planned (%s)', 'Planned (%s)')
    ));
}
add_action('init', 'register_planned_shipment_order_status');

function add_awaiting_planned_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-planned'] = 'Planned';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_awaiting_planned_to_order_statuses');

// Register new status
function register_palletways_shipment_order_status()
{
    register_post_status('wc-palletways', array(
        'label'                     => 'SD - Palletways',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('SD - Palletways (%s)', 'SD - Palletways (%s)')
    ));
}
add_action('init', 'register_palletways_shipment_order_status');

function add_awaiting_palletways_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-palletways'] = 'SD - Palletways';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_awaiting_palletways_to_order_statuses');

/*// Register new status
function register_unplanned_shipment_order_status() {
    register_post_status( 'wc-unplanned', array(
        'label'                     => 'Unplanned',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Unplanned (%s)', 'Unplanned (%s)' )
    ) );
}
add_action( 'init', 'register_unplanned_shipment_order_status' );

function add_awaiting_unplanned_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-unplanned'] = 'Unplanned';
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_awaiting_unplanned_to_order_statuses' );
*/

// Register new status RDM - Case Closed 
function register_rdm_caseclosed_shipment_order_status()
{
    register_post_status('wc-rdmcaseclosed', array(
        'label'                     => 'RDM Case Closed',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('RDM Case Closed (%s)', 'RDM Case Closed (%s)')
    ));
}
add_action('init', 'register_rdm_caseclosed_shipment_order_status');

function add_rdm_caseclosed_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-rdmcaseclosed'] = 'RDM Case Closed';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_caseclosed_to_order_statuses');

// Register new status for investigation
function register_rdm_investigation_shipment_order_status()
{
    register_post_status('wc-investigation', array(
        'label'                     => 'For Investigation',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('For Investigation (%s)', 'For Investigation (%s)')
    ));
}
add_action('init', 'register_rdm_investigation_shipment_order_status');

function add_rdm_investigation_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-investigation'] = 'For Investigation';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_investigation_to_order_statuses');

// Register new status for Resolved - DP 
function register_rdm_Resolveddp_shipment_order_status()
{
    register_post_status('wc-resolveddp', array(
        'label'                     => 'Resolved - DP',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DP (%s)', 'Resolved - DP (%s)')
    ));
}
add_action('init', 'register_rdm_Resolveddp_shipment_order_status');

function add_rdm_Resolveddp_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolveddp'] = 'Resolved - DP';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolveddp_to_order_statuses');

// Register new status for Resolved - DF
function register_rdm_Resolveddf_shipment_order_status()
{
    register_post_status('wc-resolveddf', array(
        'label'                     => 'Resolved - DF',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DF (%s)', 'Resolved - DF (%s)')
    ));
}
add_action('init', 'register_rdm_Resolveddf_shipment_order_status');

function add_rdm_Resolveddf_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolveddf'] = 'Resolved - DF';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolveddf_to_order_statuses');

// Register new status for Resolved - DRR
function register_rdm_Resolvedrr_shipment_order_status()
{
    register_post_status('wc-resolvedrr', array(
        'label'                     => 'Resolved - DRR',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DDR (%s)', 'Resolved - DDR (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedrr_shipment_order_status');

function add_rdm_Resolvedrr_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedrr'] = 'Resolved - DF';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedrr_to_order_statuses');

// Register new status for Resolved - DGL
function register_rdm_Resolvedlg_shipment_order_status()
{
    register_post_status('wc-resolvedlg', array(
        'label'                     => 'Resolved - DGL',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DGL (%s)', 'Resolved - DGL (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedlg_shipment_order_status');

function add_rdm_Resolvedlg_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedlg'] = 'Resolved - DGL';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedlg_to_order_statuses');

// Register new status for Resolved - DB
function register_rdm_Resolveddb_shipment_order_status()
{
    register_post_status('wc-resolveddb', array(
        'label'                     => 'Resolved - DB',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DB (%s)', 'Resolved - DB (%s)')
    ));
}
add_action('init', 'register_rdm_Resolveddb_shipment_order_status');

function add_rdm_Resolveddb_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolveddb'] = 'Resolved - DB';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolveddb_to_order_statuses');

// Register new status for Resolved - DGT
function register_rdm_Resolvedgt_shipment_order_status()
{
    register_post_status('wc-resolvedgt', array(
        'label'                     => 'Resolved - DGT',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DGT (%s)', 'Resolved - DGT (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedgt_shipment_order_status');

function add_rdm_Resolvedgt_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedgt'] = 'Resolved - DGT';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedgt_to_order_statuses');

// Register new status for Resolved - DRT
function register_rdm_Resolvedrt_shipment_order_status()
{
    register_post_status('wc-resolvedrt', array(
        'label'                     => 'Resolved - DRT',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DRT (%s)', 'Resolved - DRT (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedrt_shipment_order_status');

function add_rdm_Resolvedrt_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedrt'] = 'Resolved - DRT';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedrt_to_order_statuses');

// Register new status for Resolved - DS
function register_rdm_Resolvedds_shipment_order_status()
{
    register_post_status('wc-resolvedds', array(
        'label'                     => 'Resolved - DS',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - DS (%s)', 'Resolved - DS (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedds_shipment_order_status');

function add_rdm_Resolvedds_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedds'] = 'Resolved - DS';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedds_to_order_statuses');

// Register new status for Resolved - MK
function register_rdm_Resolvedmk_shipment_order_status()
{
    register_post_status('wc-resolvedmk', array(
        'label'                     => 'Resolved - MK',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MK (%s)', 'Resolved - MK (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmk_shipment_order_status');

function add_rdm_Resolvedmk_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmk'] = 'Resolved - MK';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmk_to_order_statuses');

// Register new status for Resolved - FD
function register_rdm_Resolvedfd_shipment_order_status()
{
    register_post_status('wc-resolveddfd', array(
        'label'                     => 'Resolved - FD',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - FD (%s)', 'Resolved - FD (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedfd_shipment_order_status');

function add_rdm_Resolvedfd_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedfd'] = 'Resolved - FD';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedfd_to_order_statuses');

// Register new status for Resolved - IP
function register_rdm_Resolvedip_shipment_order_status()
{
    register_post_status('wc-resolvedip', array(
        'label'                     => 'Resolved - IP',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - IP (%s)', 'Resolved - IP (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedip_shipment_order_status');

function add_rdm_Resolvedip_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedip'] = 'Resolved - IP';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedip_to_order_statuses');

// Register new status for Resolved - QU
function register_rdm_Resolvedqu_shipment_order_status()
{
    register_post_status('wc-resolvedqu', array(
        'label'                     => 'Resolved - QU',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - QU (%s)', 'Resolved - QU (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedqu_shipment_order_status');

function add_rdm_Resolvedqu_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedqu'] = 'Resolved - QU';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedqu_to_order_statuses');

// Register new status for Resolved - CRF
function register_rdm_Resolvedcrf_shipment_order_status()
{
    register_post_status('wc-resolvedcrf', array(
        'label'                     => 'Resolved - CRF',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - CRF (%s)', 'Resolved - CRF (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedcrf_shipment_order_status');

function add_rdm_Resolvedcrf_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedcrf'] = 'Resolved - CRF';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedcrf_to_order_statuses');

// Register new status for Resolved - CRP
function register_rdm_Resolvedcrp_shipment_order_status()
{
    register_post_status('wc-resolvedcrp', array(
        'label'                     => 'Resolved - CRP',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - CRP (%s)', 'Resolved - CRP (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedcrp_shipment_order_status');

function add_rdm_Resolvedcrp_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedcrp'] = 'Resolved - CRP';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedcrp_to_order_statuses');

// Register new status for Resolved - MP
function register_rdm_Resolvedmp_shipment_order_status()
{
    register_post_status('wc-resolvedmp', array(
        'label'                     => 'Resolved - MP',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MP (%s)', 'Resolved - MP (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmp_shipment_order_status');

function add_rdm_Resolvedmp_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmp'] = 'Resolved - MP';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmp_to_order_statuses');

// Register new status for Resolved - MF
function register_rdm_Resolvedmf_shipment_order_status()
{
    register_post_status('wc-resolvedmf', array(
        'label'                     => 'Resolved - MF',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MF (%s)', 'Resolved - MF (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmf_shipment_order_status');

function add_rdm_Resolvedmf_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmf'] = 'Resolved - MF';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmf_to_order_statuses');

// Register new status for Resolved - MRR  
function register_rdm_Resolvedmrr_shipment_order_status()
{
    register_post_status('wc-resolvedmrr', array(
        'label'                     => 'Resolved - MRR',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MRR (%s)', 'Resolved - MRR (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmrr_shipment_order_status');

function add_rdm_Resolvedmrr_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmrr'] = 'Resolved - MRR';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmrr_to_order_statuses');

// Register new status for Resolved - MGL  
function register_rdm_Resolvedmgl_shipment_order_status()
{
    register_post_status('wc-resolvedmgl', array(
        'label'                     => 'Resolved - MGL',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MGL (%s)', 'Resolved - MGL (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmgl_shipment_order_status');

function add_rdm_Resolvedmgl_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmgl'] = 'Resolved - MGL';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmgl_to_order_statuses');

// Register new status for Resolved - MB
function register_rdm_Resolvedmb_shipment_order_status()
{
    register_post_status('wc-resolvedmb', array(
        'label'                     => 'Resolved - MB',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MB (%s)', 'Resolved - MB (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmb_shipment_order_status');

function add_rdm_Resolvedmb_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmb'] = 'Resolved - MB';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmb_to_order_statuses');

// Register new status for Resolved - MGT
function register_rdm_Resolvedmgt_shipment_order_status()
{
    register_post_status('wc-resolvedmgt', array(
        'label'                     => 'Resolved - MGT',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MGT (%s)', 'Resolved - MGT (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmgt_shipment_order_status');

function add_rdm_Resolvedmgt_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmgt'] = 'Resolved - MGT';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmgt_to_order_statuses');

// Register new status for Resolved - MRT
function register_rdm_Resolvedmrt_shipment_order_status()
{
    register_post_status('wc-resolvedmrt', array(
        'label'                     => 'Resolved - MRT',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MRT (%s)', 'Resolved - MRT (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmrt_shipment_order_status');

function add_rdm_Resolvedmrt_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmrt'] = 'Resolved - MRT';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmrt_to_order_statuses');

// Register new status for Resolved - MS
function register_rdm_Resolvedmms_shipment_order_status()
{
    register_post_status('wc-resolvedmms', array(
        'label'                     => 'Resolved - MS',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MS (%s)', 'Resolved - MS (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmms_shipment_order_status');

function add_rdm_Resolvedmms_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing 
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmss'] = 'Resolved - MS';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmms_to_order_statuses');

// Register new status for Resolved - MFK
function register_rdm_Resolvedmmfk_shipment_order_status()
{
    register_post_status('wc-resolvedmmfk', array(
        'label'                     => 'Resolved - MFK',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MFK (%s)', 'Resolved - MFK (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmmfk_shipment_order_status');

function add_rdm_Resolvedmmfk_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing 
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmmfk'] = 'Resolved - MFK';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmmfk_to_order_statuses');

// Register new status for Resolved - MPK
function register_rdm_Resolvedmmpk_shipment_order_status()
{
    register_post_status('wc-resolvedmmpk', array(
        'label'                     => 'Resolved - MPK',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Resolved - MPK (%s)', 'Resolved - MPK (%s)')
    ));
}
add_action('init', 'register_rdm_Resolvedmmpk_shipment_order_status');

function add_rdm_Resolvedmmpk_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing 
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-resolvedmmpk'] = 'Resolved - MPK';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_Resolvedmmpk_to_order_statuses');

// Register new status RDM - Not Needed  
function register_rdm_notneeded_shipment_order_status()
{
    register_post_status('wc-rdmnotneeded', array(
        'label'                     => 'RDM Not Needed',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('RDM Not Needed (%s)', 'RDM Not Needed (%s)')
    ));
}
add_action('init', 'register_rdm_notneeded_shipment_order_status');

function add_rdm_notneeded_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-rdmnotneeded'] = 'RDM Not Needed';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_notneeded_to_order_statuses');

// Register Case OPEN 
function register_case_open_shipment_order_status()
{
    register_post_status('wc-caseopen', array(
        'label'                     => 'Case Open',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Case Open (%s)', 'Case Open (%s)')
    ));
}
add_action('init', 'register_case_open_shipment_order_status');

function add_case_open_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-caseopen'] = 'Case Open';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_case_open_to_order_statuses');

// Register new status RDM - OPEN 
function register_rdm_open_shipment_order_status()
{
    register_post_status('wc-rdmopen', array(
        'label'                     => 'RDM Open',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('RDM Open (%s)', 'RDM Open (%s)')
    ));
}
add_action('init', 'register_rdm_open_shipment_order_status');

function add_rdm_open_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-rdmopen'] = 'RDM Open';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_open_to_order_statuses');

// Register new status RDM - Enquiry 
function register_rdm_enquiry_shipment_order_status()
{
    register_post_status('wc-rdmenquiry', array(
        'label'                     => 'RDM - Enquiry',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('RDM - Enquiry (%s)', 'RDM - Enquiry (%s)')
    ));
}
add_action('init', 'register_rdm_enquiry_shipment_order_status');

function add_rdm_enquiry_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-rdmenquiry'] = 'RDM - Enquiry';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_rdm_enquiry_to_order_statuses');

// Register new status
function register_fully_scanned_order_status()
{
    register_post_status('wc-fully-scanned', array(
        'label'                     => 'Fully Scanned',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Fully Scanned (%s)', 'Fully Scanned (%s)')
    ));
}
add_action('init', 'register_fully_scanned_order_status');

function add_awaiting_fully_scanned_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-fully-scanned'] = 'Fully Scanned';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_awaiting_fully_scanned_to_order_statuses');

/*// Register new status
function register_ebaysp_shipment_order_status() {
    register_post_status( 'wc-ebay-s-p', array(
        'label'                     => 'EBay surcharge pending',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'EBay surcharge pending (%s)', 'EBay surcharge pending (%s)' )
    ) );
}
add_action( 'init', 'register_ebaysp_shipment_order_status' );

function add_awaiting_ebaysp_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-ebay-s-p'] = 'EBay surcharge pending';
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_awaiting_ebaysp_to_order_statuses' );
*/

// Register new status
function register_cancel_refund_shipment_order_status()
{
    register_post_status('wc-cancel-refund', array(
        'label'                     => 'Cancelled and Refunded',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Cancelled and Refunded (%s)', 'Cancelled and Refunded (%s)')
    ));
}
add_action('init', 'register_cancel_refund_shipment_order_status');

function add_awaiting_cancel_refund_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-cancel-refund'] = 'Cancelled and Refunded';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_awaiting_cancel_refund_to_order_statuses');

// Register new status
function register_cancel_upgrade_shipment_order_status()
{
    register_post_status('wc-cancel-upgrade', array(
        'label'                     => 'Cancelled and Upgraded',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Cancelled and Upgraded (%s)', 'Cancelled and Upgraded (%s)')
    ));
}
add_action('init', 'register_cancel_upgrade_shipment_order_status');

function add_awaiting_cancel_upgrade_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-cancel-refund' === $key) {
            $new_order_statuses['wc-cancel-upgrade'] = 'Cancelled and Upgraded';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_awaiting_cancel_upgrade_to_order_statuses');

// Register new status
function register_collect_refund_shipment_order_status()
{
    register_post_status('wc-collect-refund', array(
        'label'                     => 'Collected and Refunded',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Collected and Refunded (%s)', 'Collected and Refunded (%s)')
    ));
}
add_action('init', 'register_collect_refund_shipment_order_status');

function add_awaiting_collect_refund_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-cancel-upgrade' === $key) {
            $new_order_statuses['wc-collect-refund'] = 'Collected and Refunded';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_awaiting_collect_refund_to_order_statuses');

// Register new status
function register_declined_loan_order_status()
{
    register_post_status('wc-declined-loan', array(
        'label'                     => 'Declined Finance',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Declined Finance (%s)', 'Declined Finance (%s)')
    ));
}
add_action('init', 'register_declined_loan_order_status');

function add_declined_loan_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    // add new order status after processing
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-collect-refund' === $key) {
            $new_order_statuses['wc-declined-loan'] = 'Declined Finance';
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_declined_loan_to_order_statuses');

// Register new status
function register_pending_finance_order_status()
{
    register_post_status('wc-pending-finance', array(
        'label'                     => 'Pending Finance',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Pending Finance (%s)', 'Pending Finance (%s)')
    ));
}
add_action('init', 'register_pending_finance_order_status');

function add_pending_finance_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    // add new order status after processing
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-collect-refund' === $key) {
            $new_order_statuses['wc-pending-finance'] = 'Pending Finance';
        }
    }

    return $new_order_statuses;
}

add_filter('wc_order_statuses', 'add_pending_finance_to_order_statuses');

// Register new status
function register_awaiting_amendment_order_status()
{
    register_post_status('wc-awaiting-amendment', array(
        'label'                     => 'Awaiting Amendment',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Awaiting Amendment (%s)', 'Awaiting Amendment (%s)')
    ));
}
add_action('init', 'register_awaiting_amendment_order_status');

function add_awaiting_amendment_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    // add new order status after processing
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-collect-refund' === $key) {
            $new_order_statuses['wc-awaiting-amendment'] = 'Awaiting Amendment';
        }
    }

    return $new_order_statuses;
}

add_filter('wc_order_statuses', 'add_awaiting_amendment_to_order_statuses');


