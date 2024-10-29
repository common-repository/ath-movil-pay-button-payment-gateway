<?php
// Only will execute when is unistall proccess
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// Delete plugin settings options
function athmpbg_delete_woocommerce_gateway_settings() {
    if ( class_exists( 'WC_Payment_Gateway' ) ) {
        $gateway_id = 'athmovil-pay-button-getaway';
        delete_option( 'woocommerce_' . $gateway_id . '_settings' );
    }
}

athmpbg_delete_woocommerce_gateway_settings();
