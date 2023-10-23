<?php
if ( ! defined( 'ABSPATH' ) ) {exit;} /* Exit if accessed directly */

class WC_Ilaa_Windcave_Pxpay2 extends WC_Payment_Gateway {

	/**
	 * Constructor for Windcave PxPay2 class
	 *
	 * access public
	 * return void
	 */
	public function __construct() {

		$this->id				 = 'wc_ilaa_windcave_pxpay2';
		$this->icon			   = '';
		$this->method_title	   = __( 'Windcave PxPay 2.0 Method', 'woocommerce-windcave-pxpay' );
		$this->method_description = __( 'Windcave PxPay 2.0 method uses Windcave API to make payments.', 'woocommerce-windcave-pxpay' ); 
		$this->has_fields = false;
		$this->enabled = true;

		$this->init();
		$this->title = $this->get_option('title');
		$this->pxpay2url = $this->get_option('pxpay2url');
		$this->pxpay2userid = $this->get_option('pxpay2userid');
		$this->pxpay2apikey = $this->get_option('pxpay2apikey');

		/* Hook IPN callback logic*/
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'ilaa_check_windcave_callback' ) );
		add_action( 'valid-windcave-callback', array($this, 'ilaa_successful_request') );

		/* initiation of logging instance */
		//$this->log = new WC_Logger();
		$this->log = wc_get_logger();
	}


	/**
	 * Init settings
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		// Load the settings API
		$this->init_form_fields();
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Save settings in admin.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	}

	/**
	 * Init admin form fields
	 * 
	 * access public
	 * return void
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable', 'woocommerce-windcave-pxpay'),
				'type' => 'checkbox',
				'label' => __('Enable Windcave PxPay 2.0', 'woocommerce-windcave-pxpay'),
				'default' => 'yes'
			),'title' => array(
				'title' => __('Title', 'woocommerce-windcave-pxpay'),
				'type' => 'text',
				'default' => __('Windcave PxPay 2.0', 'woocommerce-windcave-pxpay'),
				'description' => __('This is the title customer see during checkout.', 'woocommerce-windcave-pxpay'),
				'desc_tip' => true,
			),
			'pxpay2url' => array(
				'title' => __('PxPay 2.0 API URL', 'woocommerce-windcave-pxpay'),
				'type' => 'text',
				'default' => 'https://sec.windcave.com/pxaccess/pxpay.aspx',
				'description' => __('This is the API URL for Windcave.', 'woocommerce-windcave-pxpay'),
				'desc_tip' => true,
			),
			'pxpay2userid' => array(
				'title' => __('PxPay 2.0 User ID', 'woocommerce-windcave-pxpay'),
				'type' => 'text',
				'default' => '',
				'description' => __('This is the user id for Windcave API', 'woocommerce-windcave-pxpay'),
				'desc_tip' => true,
			),
			'pxpay2apikey' => array(
				'title' => __('PxPay 2.0 API Key', 'woocommerce-windcave-pxpay'),
				'type' => 'text',
				'default' => '',
				'description' => __('This is the API key for Windcave API.', 'woocommerce-windcave-pxpay'),
				'desc_tip' => true,
			),
		);
	}

	/**
	 * Function to redirect customer to the payment gateway, and
	 * puts the order on hold.
	 */
	public function process_payment( $order_id ){

		global $woocommerce;
		$order = new WC_Order($order_id);

		$url = $this->pxpay2url;
		$PxPayUserId = $this->pxpay2userid;
		$PxPayKey = $this->pxpay2apikey;

		$currency = '';
		if ( function_exists ( 'get_woocommerce_currency' ) ) {
			$currency = get_woocommerce_currency(); 
		} else {
			$currency = $this->get_option('woocommerce_currency');
		}

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('GenerateRequest');

		$xml->writeElement('PxPayUserId', $PxPayUserId);
		$xml->writeElement('PxPayKey', $PxPayKey);
		$xml->writeElement('TxnType', 'Purchase');
		$xml->writeElement('TxnId', $order_id);
		$xml->writeElement('AmountInput', number_format($order->get_total(), 2, '.', ''));
		$xml->writeElement('CurrencyInput', $currency);
		$xml->writeElement('UrlSuccess', $this->get_return_url($order));
		$xml->writeElement('UrlFail', $this->get_return_url($order));
		$xml->writeElement('UrlCallback', get_site_url() . '/wc-api/WC_Ilaa_Windcave_Pxpay2/');

		$xml->endElement();		// GenerateRequest
		$data = $xml->outputMemory();

		/**
		 * Getting paxpay 2.0 payment uri from server
		 */
		$pxpay2_URI = $this->get_windcave_pxpay2_uri($url, $data);

		if ($pxpay2_URI != 'ERROR') {
			// Mark the order on-hold untill order is paid.
			$order->update_status('on-hold', __('Awaiting payment through PxPay 2.0', 'woocommerce-windcave-pxpay'));
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

	/**
	 * Following functions connect to Windcave API to retrieve URI for customer and
	 * translate the response after the customer completes payment process. 
	 */
	public function get_windcave_pxpay2_uri($url, $xmlData) {

		$response = wp_remote_post($url, array(
			'body' => $xmlData,
		));

		if (!is_wp_error($response) && $response['response']['code'] == 200) {

			$simpleXML = simplexml_load_string($response['body']);

			// Convert the SimpleXMLElement object to an array.
			$bodyArray = json_decode(json_encode($simpleXML), true);
			return $bodyArray['URI'];
		} else {
			return 'ERROR';
		}
	}

	public function get_windcave_pxpay2_response($url, $xmlData){

		$response = wp_remote_post($url, array(
			'body' => $xmlData,
		));

		if (!is_wp_error($response) && $response['response']['code'] === 200) {
			return $response['body'];
		} else {
			return 'ERROR';
		}
	}

	/**
	 * Following two functions handle the callback from the windcave servers after the transaction 
	 * process (success/failure).
	 */

	/** Receives the response back from Windcave servers after payment is submitted. */
	public function ilaa_check_windcave_callback() {
		
		if ( isset($_REQUEST['userid']) ) :
			$uri  = explode('result=', $_SERVER['REQUEST_URI']);
			$uri1 = $uri[1];
			$uri2  = explode('&', $uri1);
			$enc_hex = $uri2[0];
			
			do_action("valid-windcave-callback", $enc_hex);
		endif;
	}

	/** 
	 * Based on the results received from Windcave servers, changes the order to complete/processing if the
	 * payment is approved.
	 */
	public function ilaa_successful_request ($enc_hex) {

		$url = $this->pxpay2url;
		$PxPayUserId = $this->pxpay2userid;
		$PxPayKey = $this->pxpay2apikey;
		//$PxPayUserId = 'WebNextLtd_REST_Dev';
		//$PxPayKey = '34fe19e53d68d38f7d43f8959004a32b4d9d93eadd803ca8babd081c63140623';

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('ProcessResponse');

		$xml->writeElement('PxPayUserId', $PxPayUserId);
		$xml->writeElement('PxPayKey', $PxPayKey);
		$xml->writeElement('Response', $enc_hex);

		$xml->endElement();		// ProcessResponse
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
			$this->log->add( 'pxpay2 IPN callback 2', "Payment was approved!" );
		} else {
			$this->log->add( 'pxpay2 IPN callback 2', "Payment was not approved, something wrong happened!" );
		}

	}


}
