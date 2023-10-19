<?php
if ( ! defined( 'ABSPATH' ) ) {exit;} /* Exit if accessed directly */

if ( ! class_exists( 'WC_ilaa_windcave_pxpay2' ) ) {
    class WC_ilaa_windcave_pxpay2 extends WC_Payment_Gateway {
        /**
         * Constructor for Windcave PxPay2 class
         *
         * @access public
         * @return void
         */
        public function __construct() {
            $this->id                 = 'ilaa_windcave_pxpay2_method';
            $this->icon               = '';
            $this->method_title       = __( 'Windcave PxPay 2.0 Method', 'ilaa-windcave-pxpay2' );
            $this->method_description = __( 'Windcave PxPay 2.0 method uses Windcave API to make payments.', 'ilaa-windcave-pxpay2' ); 

            $this->has_fields = false;
            $this->title = $this->get_option('title');

            $this->enabled = true;
            $this->init();
        }

        /**
         * Init settings
         *
         * @access public
         * @return void
         */
        function init() {
            // Load the settings API
            $this->init_form_fields();
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

            // Save settings in admin.
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        }

        /**
         * Init admin form fields
         * 
         * @access public
         * @return void
         */
        function init_form_fields() {
            $this->form_fields = array(
                'title' => array(
                    'title' => __('Title', 'ilaa-windcave-pxpay2'),
                    'type' => 'text',
                    'default' => __('Windcave PxPay 2.0', 'ilaa-windcave-pxpay2'),
                    'description' => __('This is the title customer see during checkout.', 'ilaa-windcave-pxpay2'),
                    'desc_tip' => true,
                ),
                'enabled' => array(
                    'title' => __('Enable', 'ilaa-windcave-pxpay2'),
                    'type' => 'checkbox',
                    'label' => __('Enable Windcave PxPay 2.0', 'ilaa-windcave-pxpay2'),
                    'default' => 'yes'
                )
            );
        }

        /**
         * Function to redirect customer to the payment gateway.
         */
        function process_payment( $order_id ){
            global $woocommerce;
            $order = new WC_Order($order_id);

            // Mark the order on-hold untill order is paid.
            $order->update_status('on-hold', __('Awaiting payment through PxPay 2.0', 'ilaa-windcave-pxpay2'));

            $woocommerce->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
 
    }
}
