<?php
/**
 * Plugin Name:             ATH Movil Pay Button (payment gateway)
 * Description:             ATH Movil Payment Button. Official Plugin.
 * Version:                 1.1.1
 * Author:                  Evertec Inc
 * Author URI:              https://www.evertecinc.com/
 * Requires Plugins:  woocommerce
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


 //Plugin prefix athmpbg, ATHMPBG

if ( ! defined( 'ABSPATH' ) ) exit; 

// Include file functions to read config
require_once( ABSPATH . 'wp-admin/includes/file.php' );

// Initialize file system
WP_Filesystem();
 
add_action('plugins_loaded', 'athmpbg_init', 0);
function athmpbg_init() {

    //load config
    athmpbg_load_plugin_config();

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'athmpbg_woocommerce_missing_wc_notice' );
		return;
    }

    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class doesn't exists

	define( 'ATHMPBG_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

	include(plugin_dir_path(__FILE__) . 'includes/athmpbg-athm-pay-button-gateway.php');

    add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'athmpbg_plugin_page_settings_link'  );
    
    // Initialize text domain
    athmpbg_load_plugin_text_ubication();    
}

add_filter('woocommerce_payment_gateways', 'athmpbg_athmovil_add_to_gateways');

function athmpbg_athmovil_add_to_gateways($gateways) {
  $gateways[] = ATHMPBG_ATHMovil_Pay_Button_Gateway::get_instance();
  return $gateways;
}

function athmpbg_load_plugin_text_ubication() {

    $domain = 'ath-movil-pay-button-payment-gateway';

    load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages');
    $locale = substr(get_locale(), 0, 2);

    //english and spanish support
    if($locale != 'es' && $locale != 'en')
        $locale = 'en';

    $mo_file = dirname(__FILE__) . '/languages/' . $domain . '-' . $locale . '.mo';

    
    if (file_exists($mo_file)) {
         load_textdomain($domain, $mo_file);
    }    
}

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function athmpbg_declare_cart_checkout_blocks_compatibilitys() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'athmpbg_declare_cart_checkout_blocks_compatibilitys');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'athmpbg_register_order_approval_payment_method_types' );


/**
 * Custom function to register a payment method type

 */
function athmpbg_register_order_approval_payment_method_types() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

	// Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'includes/athmpbg-athm-pay-button-gateway-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of ATHMPBG_ATHMovil_Pay_Button_Blocks
            $payment_method_registry->register( new ATHMPBG_ATHMovil_Pay_Button_Gateway_Blocks );
        }
    );
}


function athmpbg_woocommerce_missing_wc_notice() {
    echo '<div class="error"><p><strong>' . sprintf(
        /* Translators: %s is the link to WooCommerce. */
        esc_html__( 'ATH Movil Pay Button requires WooCommerce to be installed and active. You can download %s here.', 'ath-movil-pay-button-payment-gateway' ),
        '<a href="' . esc_url( 'https://woocommerce.com/' ) . '" target="_blank">' . esc_html__( 'WooCommerce', 'ath-movil-pay-button-payment-gateway' ) . '</a>'
    ) . '</strong></p></div>';
}


function athmpbg_plugin_page_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=athmovil-pay-button-getaway">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function athmpbg_load_plugin_config() {
    global $wp_filesystem; 
    // Define env config by defect 'production'
    $env = getenv('ATHM_PLUGIN_ENV') ?: 'production';
    
    // Load file location
    $config_file = plugin_dir_path(__FILE__) . "config/config-{$env}.json";
    
    // Verify if file exist
    if (!file_exists($config_file)) {
        // If file doesnt exist load deafult file
        $config_file = plugin_dir_path(__FILE__) . "config/config-production.json";
    }
    
    // Read File
    $content_file = $wp_filesystem->get_contents($config_file);
    $config_plugin = json_decode($content_file, true);
    
    define('ATHMPBG_PLUGIN_CONFIG_SETTING', $config_plugin);
}

add_filter('woocommerce_order_actions', 'athmpbg_hide_refund_manually_button', 10, 2);

function athmpbg_hide_refund_manually_button($actions, $order) {
    // Check if order was created with custom gateway
    if ( 'athmovil-pay-button-getaway' === $order->get_payment_method() ) {
        // remove  "Refund Manually" button with css
        wp_enqueue_style( 'athmovil-admin-style', ATHMPBG_PLUGIN_URL . '/includes/css/athmovil-admin.css', array(), '1.0.0' );
    }
    return $actions;
}

?>