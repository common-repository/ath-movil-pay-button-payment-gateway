<?php

if ( ! defined( 'ABSPATH' ) ) exit; 

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class ATHMPBG_ATHMovil_Pay_Button_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'athmovil-pay-button-getaway';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'woocommerce_athmovil-pay-button-getaway_settings', [] );
        $this->gateway = ATHMPBG_ATHMovil_Pay_Button_Gateway::get_instance();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'athmovil-pay-button-getaway-blocks-integration',
            ATHMPBG_PLUGIN_URL . '/dist/block_checkout_athm_button_gateway.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            '1.0.0',
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'athmovil-pay-button-getaway-blocks-integration');
            
        }
        return [ 'athmovil-pay-button-getaway-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'icon' => $this->gateway->icon,
            'payment_icon_url' => $this->gateway->payment_icon_url,
            'place_order_label' => $this->gateway->place_order_label,
        ];
    }

}
?>