<?php
if (!defined('ABSPATH')) exit;

class ATHMPBG_ATHMovil_Pay_Button_Checkout extends WC_Checkout
{
    public function validateCheckoutFields($form_data) {
        wc_maybe_define_constant('WOOCOMMERCE_CHECKOUT', true);
        wc_set_time_limit(0);

        do_action('woocommerce_before_checkout_process');

        if (WC()->cart->is_empty()) {
            /* translators: %s: shop cart url */
            throw new Exception(sprintf(
                /* translators: This message is displayed when the user's session has expired. */
                esc_html__('Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'athmovil-pay-button-getaway'),
                esc_url(wc_get_page_permalink('shop'))
            ));
        }

        do_action('woocommerce_checkout_process');

        $data = $form_data;

        $errors = new WP_Error();

        parent::validate_checkout($data, $errors);  //native check fields woocomerce

        $error_messages = [];

        if ($errors->errors && count($errors->errors) > 0) {
            foreach ($errors->errors as $code => $messages) {
                foreach ($messages as $message) {
                    $error_messages[] = $message; 
                }
            }
        }

        if (!empty($error_messages)) {
            return [
                'success' => false,
                'messages' => $error_messages,
            ];
        }

        return [
            'success' => true,
            'messages' => [],
        ];
    }
}

// Initialize the  class
$athmpbg_athm_pay_button_checkout = new ATHMPBG_ATHMovil_Pay_Button_Checkout();
