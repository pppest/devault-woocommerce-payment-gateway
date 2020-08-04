<?php
/**
 * Plugin Name: Devault Payments Gateway
 * Plugin URI: https://github.com/pppest/DVTPay-woocommerce
 * Author: Pest
 * Author URI: https://desmadrecity.com
 * Description: DeVault crypto Payments Gateway. Based on the tutorial by techieporess on youtube.
 * Version: 0.1.0
 * License: GPL2 
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: devault-payments-woo
 * 
 * Class WC_Gateway_devault file.
 *
 * @package WooCommerce\devault
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'devault_payment_init', 11 );
add_filter( 'woocommerce_currencies', 'pest_add_dvt_currencies' );
add_filter( 'woocommerce_currency_symbol', 'pest_add_dvt_currencies_symbol', 10, 2 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_devault_payment_gateway');

function devault_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-devault.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/devault-bitdb-utils.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/devault-checkout-description-fields.php';
	}
} 

function add_to_woo_devault_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_devault';
    return $gateways;
}

function pest_add_dvt_currencies( $currencies ) {
	$currencies['DVT'] = __( 'DeVault', 'devault-payments-woo' );
	return $currencies;
}

function pest_add_dvt_currencies_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		case 'DVT': 
			$currency_symbol = 'DVT'; 
		break;
	}
	return $currency_symbol;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );
function add_action_links ( $links ) {
 $links['support'] = '<a href="https://devaultchat.cc/">' . __( 'Support', 'devault' ) . '</a>';
	 $links['settings'] ='<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=devault' ) . '">Settings</a>';
 return $links;
}

// dvt shortcodes
add_shortcode( 'dvt-logo', 'embed_dvt_logo_shortcode' );
function embed_dvt_logo_shortcode(){
	echo '<img src="'.plugin_dir_url(__FILE__).'assets/DVT-Logo-SVG-Horizontal-Dark.svg" >';
}
// dvt shortcode
add_shortcode( 'dvt-icon-light', 'embed_dvt_icon_light_shortcode' );
function embed_dvt_icon_light_shortcode(){
	echo '<img src="'.plugin_dir_url(__FILE__).'assets/DVT-Logo-D-50px-Light.png">';
}

// dvt shortcode
add_shortcode( 'dvt-icon-dark', 'embed_dvt_icon_dark_shortcode' );
function embed_dvt_icon_dark_shortcode(){
	echo '<img src="'.plugin_dir_url(__FILE__).'assets/DVT-Logo-D-50px-Dark.png">';
}
// dvt shortcode
add_shortcode( 'dvt-price', 'embed_dvt_price_shortcode' );
function embed_dvt_price_shortcode(){
	echo 'DVT/'. get_woocommerce_currency() .' '. WC_Gateway_devault::get_dvt_price();
}