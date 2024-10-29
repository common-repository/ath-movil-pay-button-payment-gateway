<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/athmpbg-athm-pay-button-gateway.php';
require_once __DIR__ . '/athmpbg-athm-pay-button-checkout.php';


class ATHMPBG_ATHMovil_Pay_Button_Hook_Helper
{
    public function __construct()
    {
        add_action('wp_ajax_athmpbg_checkout_form_fields', array($this,'athmpbg_checkout_form_fields'));
        add_action('wp_ajax_nopriv_athmpbg_checkout_form_fields', array($this,'athmpbg_checkout_form_fields'));
        add_action('wp_ajax_athmpbg_get_cart_data_items', array($this,'athmpbg_get_cart_data_items'));
        add_action('wp_ajax_nopriv_athmpbg_get_cart_data_items', array($this,'athmpbg_get_cart_data_items'));
    }

    function athmpbg_checkout_form_fields() {
        check_ajax_referer('athmpbg_consume_services_action', 'nonce_token_service');

        if (!isset($_POST['data'])) {          
            wp_send_json_error(['message' => 'Error when receive data form.']);
        }
        // Use wp_unslash and sanitize data
         $data = sanitize_url(wp_unslash($_POST['data']));

        if (empty($data)) {
            wp_send_json_error(['message' => 'Data is empty after sanitization.']);
        }

        parse_str( $data, $form_data );
        foreach ($form_data as $key => $value) {

            if (stripos($key, 'email') !== false) { //check if field is email
                $form_data[$key] = sanitize_email($value);
            } else {
                $form_data[$key] = sanitize_text_field($value);
            }
        }

        $checkout = new ATHMPBG_ATHMovil_Pay_Button_Checkout();
        $errors_validation = $checkout->validateCheckoutFields($form_data);

        if (!$errors_validation['success']) {
            $error_messages = $this->validate_error_fields($errors_validation);
            wp_send_json_error(['messages' => $error_messages]);
        } else {
            wp_send_json_success(['message' => 'field validation success']);
        }
    }
    

    function athmpbg_get_cart_data_items()
    {
        check_ajax_referer('athmpbg_consume_services_action', 'nonce_token_service');

        // Get cart information
        $cart = WC()->cart;

        $subtotal_cart = floatval($cart->cart_contents_total ?? 0);
        $shipping_price = floatval($cart->shipping_total ?? 0);
        $subtotal_with_shipping = $subtotal_cart + $shipping_price;
        
        $cart_products_taxes = floatval($cart->tax_total ?? 0);
        $total_shipping_taxes = number_format(floatval(array_sum($cart->get_shipping_taxes() ?? [])), 2, '.', '');
        
        $total_taxes_with_shipping_taxes = number_format($cart_products_taxes + floatval($total_shipping_taxes), 2, '.', '');
        
        // Cart Data
        $data = ['total' => floatval($cart->total) , 'subtotal' => floatval($subtotal_with_shipping) , 'tax' => floatval($total_taxes_with_shipping_taxes) , ];

        // Check cart items
        foreach ($cart->get_cart() as $cart_item_key => $cart_item)
        {
            $product = $cart_item['data'];
            $data['items'][] = ['name' => $product->get_name() , 'description' => $product->get_Description() , 'quantity' => $cart_item['quantity'], 'price' => $cart_item['line_total'], 'tax' => $cart_item['line_tax'], 'metadata' => ''];
        }

        // Return data in JSON
        wp_send_json_success($data);
    }

    private function validate_error_fields($errors_validation) {
        $error_messages = [];
        $allowed_html = [
            'strong' => [],
        ];
    
        foreach ($errors_validation['messages'] as $message) {
            $message = wp_kses($message, $allowed_html);
            $error_messages[] = sprintf('%s', $message);
        }
    
        return $error_messages;
    }
    
}

// Initialize the class
$athmpbg_athm_pay_button_hook_helper = new ATHMPBG_ATHMovil_Pay_Button_Hook_Helper();

