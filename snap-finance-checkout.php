<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              snapfinance.com
 * @since             1.0.12
 * @package           snap_finance_checkout
 *
 * @wordpress-plugin
 * Plugin Name:       Snap Finance
 * Plugin URI:        https://developer.snapfinance.com/woocommerce/
 * Description:       Available to all credit types.  Financing between $250 to $3,000. Get Fast, Flexible Financing now!
 * Version:           1.0.12
 * Author:            Snap Finance
 * Author URI:        https://snapfinance.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       snap-finance-checkout
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOCOMMERCE_GATEWAY_SNAP_FINANCE_VERSION', '1.0.4' );

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'snap_finance_add_gateway_class' );

function snap_finance_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_snap_finance_Gateway'; // your class name is here

	return $gateways;
}

$activated = true;
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$activated = false;
	}
} else {
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$activated = false;
	}
}
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
if ( $activated ) { 
	add_action( 'plugins_loaded', 'snap_finance_init_gateway_class' );
} else {	
	if ( !function_exists( 'deactivate_plugins' ) ) { 
		require_once ABSPATH . '/wp-admin/includes/plugin.php'; 
	} 
	deactivate_plugins( plugin_basename( __FILE__ ) );
	add_action( 'admin_notices', 'snap_finance_checkout_error_notice' );
}

function deactivate_snap_finance_checkout() {

	$to = 'devsupport@snapfinance.com';
	$subject = 'Disabled Wordpress plugin : '.site_url();
	$body = '<p>Following Merchant has disabled plugin</p>
	<p>Merchant URL: '.site_url().'</p>';
	$headers = array('Content-Type: text/html; charset=UTF-8');

	wp_mail( $to, $subject, $body, $headers );
	
}

register_deactivation_hook( __FILE__, 'deactivate_snap_finance_checkout' );

function snap_finance_checkout_error_notice() {
	?>
	<div class="error notice is-dismissible">
		<p><?php _e( 'Woocommerce is not activated, Please activate Woocommerce first to install Snap Finance Checkout.', 'snap-finance-checkout' ); ?></p>
	</div>
	<style>
		#message{display:none;}
	</style>
	<?php
}


function snap_finance_init_gateway_class() {

	include 'snap-finance-functions.php';

	include 'snap-finance-wc-order.php';
	
	include 'snap-finance-payment-class.php';
}