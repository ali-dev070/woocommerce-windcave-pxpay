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
            $this->id                 = 'ilaa_windcave_pxpay2';
            $this->icon               = '';
            $this->method_title       = __( 'Windcave PxPay 2.0 Method', 'ilaa-windcave-pxpay2' );
            $this->method_description = __( 'Windcave PxPay 2.0 method uses Windcave API to make payments.', 'ilaa-windcave-pxpay2' ); 

            $this->has_fields = false;
            $this->title = $this->get_option('title');

            $this->enabled = true;
            $this->init();

            /* Hook IPN callback logic*/
    		add_action( 'woocommerce_api_wc_ilaa_windcave_pxpay2', array( $this, 'ilaa_check_windcave_callback' ) );
            add_action( 'valid-windcave-callback', array($this, 'ilaa_successful_request') );

        }

        function ilaa_check_windcave_callback() {
            if ( isset($_REQUEST["userid"]) ) :
                $uri  = explode('result=', $_SERVER['REQUEST_URI']);
                $uri1 = $uri[1];
                $uri2  = explode('&', $uri1);
                $enc_hex = $uri2[0];
    
                do_action("valid-windcave-callback", $enc_hex);
            endif;
        }
    
        function ilaa_successful_request ($enc_hex) {

            $PxPayUserId = 'WebNextLtd_REST_Dev';
            $PxPayKey = '34fe19e53d68d38f7d43f8959004a32b4d9d93eadd803ca8babd081c63140623';

            $xml = new XMLWriter();
			$xml->openMemory();
			$xml->startDocument('1.0', 'UTF-8');
			$xml->startElement('ProcessResponse');

			$xml->writeElement('PxPayUserId', $PxPayUserId);
			$xml->writeElement('PxPayKey', $PxPayKey);
			$xml->writeElement('Response', $enc_hex);

			$xml->endElement();		// ProcessResponse

            $url = 'https://sec.windcave.com/pxaccess/pxpay.aspx';
            $data = $xml->outputMemory();

            /**
             * Getting paxpay 2.0 payment uri from server
             */
            $pxpay2_result = $this->get_windcave_pxpay2_response($url, $data);
            $simpleXML = simplexml_load_string($pxpay2_result);

            // Convert the SimpleXMLElement object to an array.
            $bodyArray = json_decode(json_encode($simpleXML), true);
            if( $bodyArray['ResponseText'] == 'APPROVED' ){
                $order_id = $bodyArray['TxnId'];
                $order = wc_get_order( $order_id );

                $order->payment_complete();
                wc_reduce_stock_levels($order_id);

                // header( 'HTTP/1.1 200 OK' );
                // echo $bodyArray['TxnId'];
                // die;
            }

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
                ),
                'test_button' => array(
                    'title' => 'test button',
                    'type' => 'button',
                    'label' => 'Test'
                )
            );
        }

        /**
         * Function to redirect customer to the payment gateway.
         */
        function process_payment( $order_id ){
            global $woocommerce;
            $order = new WC_Order($order_id);

            $PxPayUserId = 'WebNextLtd_REST_Dev';
            $PxPayKey = '34fe19e53d68d38f7d43f8959004a32b4d9d93eadd803ca8babd081c63140623';

            $xml = new XMLWriter();
			$xml->openMemory();
			$xml->startDocument('1.0', 'UTF-8');
			$xml->startElement('GenerateRequest');

			$xml->writeElement('PxPayUserId', $PxPayUserId);
			$xml->writeElement('PxPayKey', $PxPayKey);
			$xml->writeElement('TxnType', 'Purchase');
			$xml->writeElement('TxnId', $order_id);
			$xml->writeElement('AmountInput', number_format(12.15, 2, '.', ''));
			$xml->writeElement('CurrencyInput', 'NZD');
			$xml->writeElement('UrlSuccess', $this->get_return_url($order));
			$xml->writeElement('UrlFail', $this->get_return_url($order));
            $xml->writeElement('UrlCallback', get_site_url() . '/wc-api/WC_ilaa_windcave_pxpay2/');

			$xml->endElement();		// GenerateRequest

            $url = 'https://sec.windcave.com/pxaccess/pxpay.aspx';
            $data = $xml->outputMemory();

            /**
             * Getting paxpay 2.0 payment uri from server
             */
            $pxpay2_URI = $this->get_windcave_pxpay2_uri($url, $data);

            if($pxpay2_URI != 'ERROR'){
                // Mark the order on-hold untill order is paid.
                $order->update_status('on-hold', __('Awaiting payment through PxPay 2.0', 'ilaa-windcave-pxpay2'));
                $woocommerce->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $pxpay2_URI
                );
            } else {
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
        }

        function get_windcave_pxpay2_uri($url, $xmlData){

            $response = wp_remote_post($url, array(
                'body' => $xmlData,
            ));

            if (!is_wp_error($response) && $response['response']['code'] === 200) {

                $simpleXML = simplexml_load_string($response['body']);

				// Convert the SimpleXMLElement object to an array.
				$bodyArray = json_decode(json_encode($simpleXML), true);
				return $bodyArray['URI'];
            } else {
				return 'ERROR';
			}
        }

        function get_windcave_pxpay2_response($url, $xmlData){

            $response = wp_remote_post($url, array(
                'body' => $xmlData,
            ));

            if (!is_wp_error($response) && $response['response']['code'] === 200) {
				return $response['body'];
            } else {
				return 'ERROR';
			}
        }
 
    }
}
