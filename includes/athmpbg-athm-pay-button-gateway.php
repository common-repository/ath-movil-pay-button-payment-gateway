<?php

    if ( ! defined( 'ABSPATH' ) ) exit; 

    require_once __DIR__ . '/athmpbg-athm-pay-button-hook-helper.php';

    error_reporting(0);
    @ini_set( 'display_errors', 0 );

	class ATHMPBG_ATHMovil_Pay_Button_Gateway extends WC_Payment_Gateway
	{

        private static $instance = null;
        private $athm_api_base_endpoint;
        private $refund_endpoint;
        private $athm_env;
		private $public_key;
		private $private_key;
		private $payment_icon;
        public $payment_icon_url;
        public $place_order_label;
        public $place_order_label_en;
        public $place_order_label_es;


        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

		public function __construct() {
			$this->id                       = 'athmovil-pay-button-getaway';
			$this->icon                     = ATHMPBG_PLUGIN_URL . '/includes/images/ATHM-Logo.png';
			$this->has_fields               = false;
			$this->method_title             = 'ATH Movil Payment';
			$this->method_description       = 'A Payment ATH Movil Button Gateway';
            $this->athm_api_base_endpoint = ATHMPBG_PLUGIN_CONFIG_SETTING['athm_api_base_endpoint'];
            $this->refund_endpoint = ATHMPBG_PLUGIN_CONFIG_SETTING['refund_endpoint'];
			$this->athm_env = ATHMPBG_PLUGIN_CONFIG_SETTING['athm_env'];
            $this->title = 'ATH Móvil';

            new ATHMPBG_ATHMovil_Pay_Button_Hook_Helper();

			// gateways can support subscriptions, refunds, saved payment methods,
			// but in this plugin will only support simple payments
			$this->supports = [
				'products',
				'refunds'
			];

			// Method with all the options fields
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();
			$this->enabled = $this->get_option( 'enabled' );
			$this->public_key = $this->get_option( 'public_key' );
			$this->private_key = $this->get_option( 'private_key' );
			$this->payment_icon = $this->get_option( 'payment_icon' );
            $this->payment_icon_url =  ATHMPBG_PLUGIN_URL . '/includes/images/' . $this->payment_icon;
            $this->place_order_label_en = $this->get_option( 'place_order_label_en' );
            $this->place_order_label_es = $this->get_option( 'place_order_label_es' );
            $this->icon  = $this->payment_icon_url;

            if (strpos(get_locale(), 'es') === 0) {
                $this->place_order_label =  $this->place_order_label_es;
             }
             else{
                $this->place_order_label =  $this->place_order_label_en;
             }

			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );	
        }

		    /**
             * Initialize Gateway Settings Form Fields
             */
            public function init_form_fields() {
                
                $this->form_fields = apply_filters( 'wc_iframe_form_fields', [
                    
                    'enabled' => [
                        'title'   => esc_html__( 'Enable/Disable.', 'ath-movil-pay-button-payment-gateway' ),
                        'type'    => 'checkbox',
                        'label'   => esc_html__( 'Enable ATH Movil Payments.', 'ath-movil-pay-button-payment-gateway' ),
                        'default' => 'no'
                    ],
                    'public_key' => [
                        'title'       => esc_html__( 'Public token.', 'ath-movil-pay-button-payment-gateway' ),
                        'type'        => 'text',
                        'default'     => '',
                        'description' => __( 'To find your public and private tokens open the ATH Business app and go to <strong>Settings -> API Keys</strong>.', 'ath-movil-pay-button-payment-gateway' ),
                    ],
                    'private_key' => [
                        'title'       => esc_html__( 'Private token.', 'ath-movil-pay-button-payment-gateway' ),
                        'type'        => 'text',
                        'default'     => '',
                        'description' => __( 'To find your public and private tokens open the ATH Business app and go to <strong>Settings -> API Keys</strong>.', 'ath-movil-pay-button-payment-gateway' ),
                    ],
                    'payment_icon'      => [
                        'title'       => esc_html__( 'Payment Icon Style.', 'ath-movil-pay-button-payment-gateway' ),
                        'type'        => 'select',
                        'options'     => [
                            'ATHM-logo-horizontal.svg'       => 'ATHM Logo Horizontal.',
                            'ATHM-logo-simple.svg' => 'ATHM Logo Simple.',
                        ],
                        'default'     => 'ATHM-logo-horizontal.svg',
                        'description' => __( 'Payment Icon Style to show in checkout.', 'ath-movil-pay-button-payment-gateway' ) . ' <ul><li><b>ATHM Logo Horizontal:</b> <br/><img src="' . esc_url( ATHMPBG_PLUGIN_URL . '/includes/images/ATHM-logo-horizontal.svg' ) . '" style="width: 150px; margin-top: 10px; margin-bottom: 10px;" /></li><li><b>ATHM Logo Simple:</b> <br/><img src="' . esc_url( ATHMPBG_PLUGIN_URL . '/includes/images/ATHM-logo-simple.svg' ) . '" style="width: 45px; margin-top: 10px; margin-bottom: 10px;" /></li></ul>',
                    ],
                    'place_order_label_en' => [
                        'title'       => esc_html__( 'Place Order Button Text in English.', 'ath-movil-pay-button-payment-gateway' ),
                        'type'        => 'textarea',
                        'description' => esc_html__( 'Text that will have the place order button in English.', 'ath-movil-pay-button-payment-gateway' ),
                        'default'     => 'Pay with ATH Móvil',
                    ],
                    'place_order_label_es' => [
                        'title'       => esc_html__( 'Label Place Order Button in Spanish.', 'ath-movil-pay-button-payment-gateway' ),
                        'type'        => 'textarea',
                        'description' => esc_html__( 'Text that will have the place order button in Spanish.', 'ath-movil-pay-button-payment-gateway' ),
                        'default'     =>  'Paga con ATH Móvil',
                    ],
                 ] );

                 $this->form_fields['nonce_admin_form'] = array(
                    'type'  => 'hidden',
                    'default' => wp_create_nonce('submit_admin_form_action'), // Crea un nonce
                );
            }

			public function process_admin_options() {
                $fieldPrefix = 'woocommerce_' . $this->id . '_';
                $this->form_fields['nonce_admin_form']['value'] = wp_create_nonce('submit_admin_form_action');
                $nonce_token = $this->form_fields['nonce_admin_form']['value'];

                if (!isset($nonce_token) || !wp_verify_nonce($nonce_token, 'submit_admin_form_action')) {
                    WC_Admin_Settings::add_error( 'Error: ' . esc_html__( 'Form Validation Error.', 'ath-movil-pay-button-payment-gateway' ) );
                    return false;
                }

                if ( empty( $_POST[ $fieldPrefix . 'public_key'] ) ) {
                    WC_Admin_Settings::add_error( 'Error: ' . esc_html__( 'You must provide a Public token.', 'ath-movil-pay-button-payment-gateway' ) );
                    return false;
                }

                if ( empty( $_POST[ $fieldPrefix . 'private_key'] ) ) {
                    WC_Admin_Settings::add_error( 'Error: ' . esc_html__( 'You must provide a Private token.', 'ath-movil-pay-button-payment-gateway' ) );
                    return false;
                }

                if ( empty( $_POST[ $fieldPrefix . 'place_order_label_en'] ) ) {
                    WC_Admin_Settings::add_error( 'Error: ' . esc_html__( 'You must provide a Place Order Button Text in English.', 'ath-movil-pay-button-payment-gateway' ) );
                    return false;
                }

                if ( empty( $_POST[ $fieldPrefix . 'place_order_label_es'] ) ) {
                    WC_Admin_Settings::add_error( 'Error: ' . esc_html__( 'You must provide a Label Place Order Button in Spanish.', 'ath-movil-pay-button-payment-gateway' ) );
                    return false;
                }

                parent::process_admin_options();
            }

        function payment_scripts(){

            wp_register_script('athmpbg-ath-movil', ATHMPBG_PLUGIN_URL . '/includes/js/athmovil-button.js', array(), '1.0.0', true); 

            wp_register_script('athmpbg-ath-movil-base-api', $this->athm_api_base_endpoint . '/modal/js/athmovil_base.js', array(), '1.0.0', true); 

            wp_enqueue_script( 'athmpbg-ath-movil-base-api' );

            wp_localize_script('athmpbg-ath-movil', 'ATHM_Checkout', [
                'env'                   => $this->athm_env,
                'publicToken'           =>  $this->public_key,
                'timeout'               => 300,
                'lang'                  => substr( get_locale(), 0, 2 ) != 'en' && substr( get_locale(), 0, 2 ) != 'es' ? 'en' : substr( get_locale(), 0, 2 ), 
                'total'                 => 0,
                'tax'                   => 0 ,
                'subtotal'              => 0,
                'items'                 => null,
                'metadata1'             => '',
                'metadata2'             => '',
                'noncetokenrequest'            => wp_create_nonce('athmpbg_gateway_payment_action'),
                'ajax_url' => admin_url('admin-ajax.php'),
            ]);

            wp_enqueue_style( 'athmpbg-athmovil-style', ATHMPBG_PLUGIN_URL . '/includes/css/athmovil.css', array(), '1.0.0');
            wp_enqueue_script( 'athmpbg-ath-movil' );

            // if is in legacy checkout page load custom javascript for legacy
            if (is_checkout() && !is_wc_endpoint_url()) {
                wp_register_script(
                    'athmovil-pay-button-getaway-classic-integration',
                    ATHMPBG_PLUGIN_URL . '/dist/classic_checkout_athm_button_gateway.js',
                    [
                        'wc-blocks-registry',
                        'wc-settings',
                        'wp-element',
                        'wp-html-entities',
                        'wp-i18n',
                        'wp-blocks',
                        'wp-components'
                    ],
                    '1.0.0',
                    true
                );

                wp_localize_script('athmovil-pay-button-getaway-classic-integration', 'ATHM_PAY_BUTTON_SETTINGS', [
                    'buttonText'                   => $this->place_order_label,
                    'nonce_token_service'            => wp_create_nonce('athmpbg_consume_services_action'),
                ]);
            
                wp_enqueue_script( 'athmovil-pay-button-getaway-classic-integration' );
            }
            
        }    

        
        // Process the payment
        public function process_payment($order_id) {

            if (!isset($_POST['noncetokenrequest']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['noncetokenrequest'])), 'athmpbg_gateway_payment_action')) {          
                return array(
                    'result' => 'fail',
                    'message' => __('Error While Confirmed Transaction', 'ath-movil-pay-button-payment-gateway'),
                );
            }

            if (!isset($_POST['trasanctionresult']) || $_POST['trasanctionresult'] !== 'COMPLETED') {          
                return array(
                    'result' => 'fail',
                    'message' => __('Error While Confirmed Transaction', 'ath-movil-pay-button-payment-gateway'),
                );
            }

            //After validate all the payment process
            $order = wc_get_order($order_id);
            $referenceNumber = isset($_POST['referencenumber']) ? sanitize_text_field(wp_unslash($_POST['referencenumber'])) : null;

            if($referenceNumber != null && !empty($referenceNumber))
                $order->set_transaction_id($referenceNumber);

            $order->save();
            $order->payment_complete();

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }

        public function process_refund( $order_id, $amount = null, $reason = '' ) {

            $order = wc_get_order( $order_id );     
            
            if( empty( $order ) ) 
                return new WP_Error( 'refund_error_order_not_found', __('Order not found.','ath-movil-pay-button-payment-gateway'));

            $minimun_amount = floatval( '0.01' );

            if( empty( $amount ) || (floatval( $amount ) < $minimun_amount ))
                return new WP_Error( 'refund_amount_error', __("Refund amount can't be empty or less than 0.01.",'ath-movil-pay-button-payment-gateway' ));

            if( empty( $order->get_transaction_id() ) )
                 return new WP_Error( 'refund_transaction_reference_error', __("Order can't be refunded there is not an available ATHM reference number.",'ath-movil-pay-button-payment-gateway'));

            $data = [ 
                'publicToken'       => $this->public_key,
                'privateToken'      => $this->private_key,
                'referenceNumber'   => $order->get_transaction_id(),
                'amount'            => $amount,
                'message'           => $reason,
            ];

            $args = [
                'method'        => 'POST',
                'body'          => wp_json_encode( $data ),
                'timeout'       => 30,
                'redirection'   => 10,
                'httpversion'   => '1.0',
                'blocking'      => true,
                'headers'       => [
                    'Content-Type'  => 'application/json',
                ],
                'sslverify'     => false
            ];

            try {
                $response = wp_remote_post( $this->athm_api_base_endpoint . $this->refund_endpoint, $args );

                if ( is_wp_error( $response ) ) 
                    return $response;

                $response = json_decode( $response['body'], true );

                if ( sanitize_text_field($response['status']) != 'success' ){
                   $response_message_error = sanitize_text_field($response['message']);
                   return new WP_Error( 'refund_transaction_api_error', __('Something went wrong creating refund, Detail Message: ','ath-movil-pay-button-payment-gateway'). $response_message_error . '.');
                }

                if( sanitize_text_field($response['data']['refund']['status']) != 'COMPLETED' )
                    return new WP_Error( 'refund_transaction_api_status_error', __("Refund didn't complete successfully.",'ath-movil-pay-button-payment-gateway') );

                $order->add_order_note( 'Refunded $' . sanitize_text_field($response['data']['refund']['refundedAmount']) . ' through ' . $this->title . '. Reference #' . sanitize_text_field($response['data']['refund']['referenceNumber']) . '.' . ( empty( $reason ) ? '' : ' Reason: ' . $reason . '.') );

                // Change order status if it is a successfully refund the complete amount
                if (floatval( sanitize_text_field($response['data']['originalTransaction']['totalRefundedAmount']) ) == floatval(  sanitize_text_field($response['data']['originalTransaction']['total']) ) )
                    $order->update_status('refunded');

                return true;
            }
            catch(Exception $ex) {
                return new WP_Error( 'refund_transaction_generic_error', __("Something went wrong please try again later.",'ath-movil-pay-button-payment-gateway') );
            }
        }
        
	}

?>